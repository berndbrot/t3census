<?php

class UrlFetcher {

	const HTTP_HEAD = 'HEAD';
	const HTTP_POST = 'POST';
	const HTTP_GET = 'GET';

	protected $url = NULL;

	protected $responseCookies = NULL;

	protected $numRedirects = 0;

	protected $responseHttpCode = NULL;

	protected $ipAddress = NULL;

	protected $port = NULL;

	protected $errno = 0;

	protected $body = NULL;

	public function __construct() {
		$this->reset();
	}

	public function fetchUrl($httpType, $retrieveCookies = FALSE) {
		if (is_bool($retrieveCookies) && $retrieveCookies && $httpType === self::HTTP_HEAD) {
			throw new RuntimeException('HEAD request along with cookie retrieval does not work', 1371845320);
		}

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $this->url);
		curl_setopt($curl, CURLOPT_POST, FALSE);
		curl_setopt($curl, CURLOPT_TIMEOUT, 60);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($curl, CURLOPT_USERAGENT, 'T3census-Crawler/1.0');

		if (is_bool($retrieveCookies) && $retrieveCookies) {
			curl_setopt($curl, CURLOPT_HEADER, 1);
		} else {
			curl_setopt($curl, CURLOPT_HEADER, 0);
		}

		$result = NULL;
		if ($httpType === self::HTTP_HEAD) {
			curl_setopt($curl, CURLOPT_NOBODY, TRUE);
		} else {
			curl_setopt($curl, CURLOPT_NOBODY, FALSE);
			$result = curl_exec($curl);
		}

		if (is_bool($retrieveCookies) && $retrieveCookies) {
			$map = array();
			preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $result, $map);

			if (is_array($map) && array_key_exists(1, $map)) {
				if (is_array($map[1])) {
					$cookies = array();
					foreach ($map[1] as $headerLine) {
						list($key, $value) = explode('=', $headerLine);
						$cookies[$key] = $value;
					}
					$this->responseCookies = $cookies;
				}
			}
		}

		if (is_string($result)) {

			if (is_bool($retrieveCookies) && $retrieveCookies) {
				$posHtml = strpos($result, '<');
				$this->body = (FALSE === $posHtml ? $result : substr($result, $posHtml));
			} else {
				$this->body = $result;
			}
		}

		$curlInfo = curl_getinfo($curl);

		if (array_key_exists('primary_ip', $curlInfo) && array_key_exists('primary_port', $curlInfo)) {
			$this->ipAddress = $curlInfo['primary_ip'];
			$this->port = $curlInfo['primary_port'];
		} else {
			$arrUrl = parse_url($this->url);

			$ip = gethostbyname($arrUrl['host']);

			$this->ipAddress = ($ip !== $arrUrl['host'] ? $ip : NULL);

			if (!array_key_exists('port', $arrUrl)) {
				if ($arrUrl['scheme'] === 'http') {
					$this->port = 80;
				} elseif ($arrUrl['scheme'] === 'https') {
					$this->port = 443;
				} else {
					$result['port'] = NULL;
				}
			} else {
				$this->port = $arrUrl['port'];
			}

		}

		if (is_array($curlInfo) && array_key_exists('http_code', $curlInfo)) {
			$this->responseHttpCode = $curlInfo['http_code'];
		}

		if (is_array($curlInfo) && array_key_exists('redirect_count', $curlInfo)) {
			$this->numRedirects = $curlInfo['redirect_count'];
			if (array_key_exists('url', $curlInfo) && !empty($curlInfo['url'])) {
				$this->url = $curlInfo['url'];
			}
		}

		$this->errno = curl_errno($curl);

#var_dump(get_object_vars($this));

		curl_close($curl);
		unset($curl);
	}

	/**
	 * @param null $url
	 * @return UrlFetcher class instance
	 */
	public function setUrl($url) {
		$this->url = $url;
		return $this;
	}

	/**
	 * @return null
	 */
	public function getUrl() {
		return $this->url;
	}

	/**
	 * @return null
	 */
	public function getResponseCookies() {
		return $this->responseCookies;
	}

	/**
	 * @return null
	 */
	public function getResponseHttpCode() {
		return $this->responseHttpCode;
	}

	/**
	 * @return null
	 */
	public function getPort() {
		return $this->port;
	}

	/**
	 * @return int
	 */
	public function getNumRedirects() {
		return $this->numRedirects;
	}

	/**
	 * @return null
	 */
	public function getIpAddress() {
		return $this->ipAddress;
	}

	/**
	 * @return int
	 */
	public function getErrno() {
		return $this->errno;
	}

	/**
	 * @return null
	 */
	public function getBody() {
		return $this->body;
	}

	/**
	 * @return  UrlFetcher  class instance
	 */
	public function reset() {
		$this->url = $this->responseCookies = $this->responseHttpCode = $this->ipAddress = $this->port = $this->body = NULL;
		$this->numRedirects = $this->errno = 0;
		return $this;
	}
}
?>