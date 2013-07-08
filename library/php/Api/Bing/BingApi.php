<?php

ini_set('default_socket_timeout', 10);

class BingApi {

	protected $accountKey = NULL;

	protected $endpoint = NULL;

	protected $format = 'json';

	protected $query = NULL;

	protected $isProcessed = FALSE;

	protected $offset = 0;

	protected $maxResults = NULL;

	protected $results = array();


	public function __construct($accountKey = NULL, $endpoint = NULL) {

		if (!is_null($accountKey)) {
			$this->setAccountKey($accountKey);
		}

		if (!is_null($endpoint)) {
			$this->setEndpoint($endpoint);
		}

		\Purl\Autoloader::register();
	}

	/**
	 * @param null|string $accountKey
	 */
	public function setAccountKey($accountKey) {
		if (is_string($accountKey)) {
			$this->accountKey = $accountKey;
		}

		return $this;
	}

	/**
	 * @return null|string
	 */
	public function getAccountKey() {
		return $this->accountKey;
	}

	/**
	 * @param null $endpoint
	 */
	public function setEndpoint($endpoint) {
		if (is_string($endpoint)) {
			$this->endpoint = $endpoint;
		}

		return $this;
	}

	/**
	 * @return null
	 */
	public function getEndpoint() {
		return $this->endpoint;
	}

	/**
	 * @param string $format
	 */
	public function setFormat($format) {
		if (is_string($format) && in_array($format, array('json'))) {
			$this->format = $format;
		}

		return $this;
	}

	/**
	 * @return string
	 */
	public function getFormat() {
		return $this->format;
	}

	/**
	 * @return null
	 */
	public function getMaxResults() {
		return $this->maxResults;
	}

	/**
	 * @param null $maxResults
	 */
	public function setMaxResults($maxResults) {
		if (is_null($maxResults) || is_int($maxResults)) {
			$this->maxResults = $maxResults;
		}

		return $this;
	}

	/**
	 * @return int
	 */
	public function getOffset() {
		return $this->offset;
	}

	/**
	 * @param int $offset
	 */
	public function setOffset($offset) {
		if (is_int($offset)) {
			$this->offset = $offset;
		}

		return $this;
	}

	/**
	 * @param null $query
	 */
	public function setQuery($query) {
		if (is_string($query)) {
			$this->query = $query;
			unset($this->results); $this->results = array();
			$this->isProcessed = FALSE;
		}

		return $this;
	}

	/**
	 * @return null
	 */
	public function getQuery() {
		return $this->query;
	}

	public function getResults() {
		if (!$this->isProcessed) {
			$auth = base64_encode($this->accountKey . ':' . $this->accountKey);
			$data = array(
				'http' => array(
				'request_fulluri' => TRUE,
				'ignore_errors' => FALSE,
				'header' => "Authorization: Basic $auth")
			);

			$query = urlencode('\'' . $this->query . '\'');
			$requestUri = $this->endpoint . '/Web?$format=' . $this->format . '&Query=' . $query . '&$skip=' . strval($this->offset);

			$urls = array();
			for($i=0; $i< 2500; $i++) {
				$context = stream_context_create($data);
				$response = file_get_contents($requestUri, 0, $context);

				$jsonObj = json_decode($response);
				$urls = array_merge($urls, $this->extractUrlsFrom($jsonObj));

				$this->offset += 50;
				if (!property_exists($jsonObj->d, '__next') || !is_string($jsonObj->d->__next) || empty($jsonObj->d->__next)) {
					break;
				} elseif (!is_null($this->maxResults) && is_int($this->maxResults) && $this->offset > $this->maxResults) {
					break;
				}

				$requestUri = $this->endpoint . '/Web?$format=' . $this->format . '&Query=' . $query . '&$skip=' . strval($this->offset);
			}
			natsort($urls);
			$urls = array_reverse($urls);

			$lastInsertedUrl = NULL;
			$mergedUrls = array();
			foreach ($urls as $url) {
				if (is_string($lastInsertedUrl)) {
					/* @var $currentUrl \Purl\Url */
					$currentUrl = $this->getSimplifiedUrl(\Purl\Url::parse($url));

					if (0 !== strcmp($lastInsertedUrl, $currentUrl)) {
						$mergedUrls[] = $currentUrl;
						$lastInsertedUrl = $currentUrl;
					}
				} else {
					/* @var $currentUrl \Purl\Url */
					$currentUrl = $this->getSimplifiedUrl(\Purl\Url::parse($url));
					$mergedUrls[] = $currentUrl;
					$lastInsertedUrl = $currentUrl;
				}
			}

			$this->results = $mergedUrls;
		}

		return $this->results;
	}

	protected function getSimplifiedUrl(\Purl\Url $url) {
		$simplifiedUrl= '';

		$simplifiedUrl .= $url->get('scheme') . '://';
		$simplifiedUrl .= $url->get('host');
		$simplifiedUrl .= (!is_null($url->get('port')) ? $url->get('port') : '');

		return $simplifiedUrl;
	}

	protected function extractUrlsFrom($results) {
		$urls = array();
		foreach($results->d->results as $value) {
			#var_dump($value);
			switch ($value->__metadata->type) {
				case 'WebResult':
					#echo(PHP_EOL . $value->Url);
					$urls[] = $value->Url;
					break;
			}
		}

		return $urls;
	}

	public function reset() {
		$this->accountKey = $this->endpoint = $this->query = NULL;
		unset($this->results); $this->results = array();
		$this->isProcessed = FALSE;
		$this->format = 'json';
		$this->offset = 0;
		return $this;
	}
}
?>