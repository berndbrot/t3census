<?php
/**
 * Created by JetBrains PhpStorm.
 * User: marcus
 * Date: 25.05.13
 * Time: 21:11
 * To change this template use File | Settings | File Templates.
 */
class Typo3HostDetectorWorker {

	private $host;
	private $port;
	private $gearmanWorker;

	private $userAgent = 'T3census-Crawler/1.0';

	public function __construct($host = '127.0.0.1', $port = 4730) {
		$this->host=$host;
		$this->port=$port;
	}

	public function setUp() {
		$this->gearmanWorker = new GearmanWorker();
		$this->gearmanWorker->addServer($this->host, $this->port);
		$this->gearmanWorker->addFunction("TYPO3HostDetector", array($this, "fetchUrl"));
	}

	public function fetchUrl(GearmanJob $job) {
		$result = FALSE;
		$url = $job->workload();

		$curlInfo = array();
		$curlErrno = 0;
		$content = '';

		$this->resolveTargetUrl($url, $content, $curlInfo, $curlErrno);

		if ($curlErrno === 0) {
			if (is_array($curlInfo) && array_key_exists('redirect_count', $curlInfo) && $curlInfo['redirect_count'] >= 0
				&& array_key_exists('url', $curlInfo) && !empty($curlInfo['url'])) {
				$url = $curlInfo['url'];
			}

			$result = array();
			$urlInfo = $this->normalizeUrl($url);

			// new cURL versions provide ip and port
			if (array_key_exists('primary_ip', $curlInfo) && array_key_exists('primary_port', $curlInfo)) {
				$result['ip'] = $curlInfo['primary_ip'];
				$result['port'] = $curlInfo['primary_port'];
			} else {
				$ip = gethostbyname($urlInfo['host']);
				$result['ip'] = ($ip !== $urlInfo['host'] ? $ip : NULL);
				if ($urlInfo['protocol'] === 'http://') {
					$result['port'] = 80;
				} elseif ($urlInfo['protocol'] === 'http://') {
					$result['port'] = 443;
				} else {
					$result['port'] = NULL;
				}
			}
			$result['protocol'] = $urlInfo['protocol'];
			$result['host'] = $urlInfo['host'];
			$result['path'] = $urlInfo['path'];

			unset($curlInfo, $curlErrno);

			if (strlen($content)) {
				$metaGenerator = $this->parseDomForGenerator($content);

				if (strlen($metaGenerator)) {
					if (strpos($content, 'TYPO3') !== FALSE) {
						$result['TYPO3'] = TRUE;
						$result['TYPO3version'] = $metaGenerator;
					} else {
						$result['TYPO3'] = FALSE;
					}
					unset($content);
				} else {
					$hostname = $urlInfo['protocol'] . $urlInfo['host'] . $urlInfo['port'] . '/';

					$curlInfoFileadmin = $curlInfoUploads = array();
					$curlErrnoFileadmin = $curlErrnoUploads = 0;

					$this->testTypo3Artefacts($hostname . 'fileadmin/', $curlInfoFileadmin, $curlErrnoFileadmin);
					$this->testTypo3Artefacts($hostname . 'uploads/', $curlInfoUploads, $curlErrnoUploads);

					if ($curlErrnoFileadmin === 0 && $curlErrnoUploads == 0
							&& $curlInfoFileadmin['http_code'] === 403 && $curlInfoUploads['http_code'] === 403) {
						$result['TYPO3'] = TRUE;
						$result['TYPO3version'] = FALSE;
					} else {
						$hostname .= $urlInfo['path'];

						$curlInfoFileadmin = $curlInfoUploads = array();
						$curlErrnoFileadmin = $curlErrnoUploads = 0;

						$this->testTypo3Artefacts($hostname . 'fileadmin/', $curlInfoFileadmin, $curlErrnoFileadmin);
						$this->testTypo3Artefacts($hostname . 'uploads/', $curlInfoUploads, $curlErrnoUploads);

						if ($curlErrnoFileadmin === 0 && $curlErrnoUploads == 0
								&& $curlInfoFileadmin['http_code'] === 403 && $curlInfoUploads['http_code'] === 403) {
							$result['TYPO3'] = TRUE;
							$result['TYPO3version'] = FALSE;
						} else {
							$result['TYPO3'] = FALSE;
						}
					}

					unset($curlInfoFileadmin, $curlInfoUploads, $curlErrnoFileadmin, $curlErrnoUploads, $hostname, $content);
				}
			}

			unset($urlInfo);
		}

		return json_encode($result);
	}

	protected function resolveTargetUrl($url, &$content, &$curlInfo, &$curlErrno) {
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_POST, FALSE);
		curl_setopt($curl, CURLOPT_HEADER, 0);
		curl_setopt($curl, CURLOPT_TIMEOUT, 60);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($curl, CURLOPT_USERAGENT, 'T3census-Crawler/1.0');
		$content = curl_exec($curl);

		$curlInfo = curl_getinfo($curl);
		$curlErrno = curl_errno($curl);
		unset($curl);

		return $content;
	}

	protected function testTypo3Artefacts($url, &$curlInfo, &$curlErrno) {
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_POST, FALSE);
		curl_setopt($curl, CURLOPT_NOBODY, TRUE);
		curl_setopt($curl, CURLOPT_HEADER, 0);
		curl_setopt($curl, CURLOPT_TIMEOUT, 60);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($curl, CURLOPT_USERAGENT, 'T3census-Crawler/1.0');
		curl_exec($curl);

		$curlInfo = curl_getinfo($curl);
		$curlErrno = curl_errno($curl);
		unset($curl);
	}

	protected function normalizeUrl($url) {
		$regex = '#^(.*?//)*([\w\.\-\d]*)(:(\d+))*(/*)(.*)$#';
		$matches = array();
		preg_match($regex, $url, $matches);

		$urlInfo['protocol'] = $matches[1];
		$urlInfo['port'] = $matches[4];
		$urlInfo['host'] = $matches[2];
		$urlInfo['path'] = $matches[6];

		if (empty($urlInfo['protocol'])) {
			$urlInfo['protocol'] = 'http://';
		}

		$patterns = array();
		$patterns[0] = '#fileadmin#';
		$patterns[1] = '#//#';
		$replacements = array();
		$replacements[0] = '';
		$replacements[1] = '/';
		$urlInfo['path'] = preg_replace($patterns, $replacements, $urlInfo['path']);

		if (!empty($urlInfo['path']) && $urlInfo['path'] == '/') {
			$urlInfo['path'] = '';
		}

		return $urlInfo;
	}

	protected function parseDomForGenerator($content) {
		libxml_use_internal_errors(TRUE);

		$metaGenerator = '';
		$dom = new DOMDocument();
		$dom->loadHTML($content);
		$xpath = new DOMXPath($dom);

		// Look for the content attribute of description meta tags
		$generators = $xpath->query('/html/head/meta[@name="generator"]/@content');

		// If nothing matches the query
		if ($generators->length == 0) {
			// Found one or more descriptions, loop over them
		} else {
			$metaGenerator = '';
			foreach ($generators as $generator) {
				$metaGenerator .= $generator->value;
			}
		}

		libxml_clear_errors();
		return $metaGenerator;
	}

	public function run() {
		$this->setUp();
		$this->gearmanWorker->setTimeout(5000); //wake up after 5 seconds
		echo "Starting...\n";
		while(1) {
			@$this->gearmanWorker->work();
			if ($this->gearmanWorker->returnCode() == GEARMAN_TIMEOUT)
			{
				//do some other work here
				continue;
			}
			if($this->gearmanWorker->returnCode() != GEARMAN_SUCCESS) {
				// do some error handling here
				die("An error occured");
			}

		}
	}
}

?>