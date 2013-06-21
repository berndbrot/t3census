<?php
require_once 'UrlFetcher.php';
require_once 'UrlNormalizer.php';

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
	private $urlFetcher;
	private $urlNormalizer;

	private $userAgent = 'T3census-Crawler/1.0';

	public function __construct($host = '127.0.0.1', $port = 4730) {
		$this->host = $host;
		$this->port = $port;
	}

	public function setUp() {
		$this->gearmanWorker = new GearmanWorker();
		$this->gearmanWorker->addServer($this->host, $this->port);
		$this->gearmanWorker->addFunction("TYPO3HostDetector", array($this, "fetchUrl"));
	}

	public function fetchUrl(GearmanJob $job) {
		$result = FALSE;
		$url = $job->workload();

		$content = '';

		$fetcher = $this->getUrlFetcher()->setUrl($url);
		$fetcher->fetchUrl(UrlFetcher::HTTP_GET, TRUE);

		if ($fetcher->getErrno() === 0) {
			if ($fetcher->getNumRedirects() >= 0)  $url = $fetcher->getUrl();

			$result = array();

			$urlInfo = $this->getUrlNormalizer()->setOriginUrl($url)->getNormalizedUrl();

			$result['ip'] = $fetcher->getIpAddress();
			$result['port'] = $fetcher->getPort();
			$result['protocol'] = $urlInfo['protocol'];
			$result['host'] = $urlInfo['host'];
			$result['path'] = $urlInfo['path'];

			$content = $fetcher->getBody();
			if (is_string($content) && strlen($content)) {
				$metaGenerator = $this->parseDomForGenerator($content);
				$cookies = $fetcher->getResponseCookies();
				$isTypo3Cookies = array();
				if (is_array($cookies)) {
					$typo3CookiesKeys = array('fe_typo_user', 'be_typo_user');
					$cookieKeys = array_keys($cookies);
					$isTypo3Cookies = array_intersect($typo3CookiesKeys, $cookieKeys);
				}

				if (strlen($metaGenerator)) {
					if (strpos($metaGenerator, 'TYPO3') !== FALSE) {
						$result['TYPO3'] = TRUE;
						$result['TYPO3version'] = $metaGenerator;
					} else {
						$result['TYPO3'] = FALSE;
					}
					unset($content);
				} elseif(is_array($isTypo3Cookies) && count($isTypo3Cookies)) {
					$result['TYPO3'] = TRUE;
					$result['TYPO3version'] = FALSE;
				} else {
					$hostname = $urlInfo['protocol'] . $urlInfo['host'] . $urlInfo['port'] . '/';

					$fetcherHttpCodeFileadmin = $fetcherHttpCodeUploads = NULL;
					$fetcherErrnoFileadmin = $fetcherErrnoUploads = 0;

					$fetcher->reset()->setUrl($hostname . 'fileadmin/');
					$fetcherHttpCodeFileadmin = $fetcher->getResponseHttpCode();
					$fetcherErrnoFileadmin = $fetcher->getErrno();

					$fetcher->reset()->setUrl($hostname . 'uploads/');
					$fetcherHttpCodeUploads = $fetcher->getResponseHttpCode();
					$fetcherErrnoUploads = $fetcher->getErrno();

					if ($fetcherErrnoFileadmin === 0 && $fetcherErrnoUploads === 0
							&& $fetcherHttpCodeFileadmin === 403 && $fetcherHttpCodeUploads === 403) {
						$result['TYPO3'] = TRUE;
						$result['TYPO3version'] = FALSE;
					} else {
						$hostname .= $urlInfo['path'];

						$fetcherHttpCodeFileadmin = $fetcherHttpCodeUploads = NULL;
						$fetcherErrnoFileadmin = $fetcherErrnoUploads = 0;

						$fetcher->reset()->setUrl($hostname . 'fileadmin/');
						$fetcherHttpCodeFileadmin = $fetcher->getResponseHttpCode();
						$fetcherErrnoFileadmin = $fetcher->getErrno();

						$fetcher->reset()->setUrl($hostname . 'uploads/');
						$fetcherHttpCodeUploads = $fetcher->getResponseHttpCode();
						$fetcherErrnoUploads = $fetcher->getErrno();

						if ($fetcherErrnoFileadmin === 0 && $fetcherErrnoUploads === 0
								&& $fetcherHttpCodeFileadmin === 403 && $fetcherHttpCodeUploads === 403) {
							$result['TYPO3'] = TRUE;
							$result['TYPO3version'] = FALSE;
						} else {
							$result['TYPO3'] = FALSE;
						}
					}

					unset($fetcher, $fetcherHttpCodeFileadmin, $fetcherErrnoFileadmin, $fetcherHttpCodeUploads, $fetcherErrnoUploads, $hostname, $content);
				}
			}

			unset($urlInfo);
		}

		return json_encode($result);
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
			foreach ($generators as $generator) {
				$metaGenerator .= $generator->value;
			}
		}

		libxml_clear_errors();
		return $metaGenerator;
	}

	protected function getUrlNormalizer() {
		if (!is_object($this->urlNormalizer) && !($this->urlNormalizer instanceof UrlNormalizer)) {
			$this->urlNormalizer = new UrlNormalizer();
		}

		return $this->urlNormalizer;
	}

	protected function getUrlFetcher() {
		if (!is_object($this->urlFetcher) && !($this->urlFetcher instanceof UrlFetcher)) {
			$this->urlFetcher = new UrlFetcher();
		}

		return $this->urlFetcher;
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