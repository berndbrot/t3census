<?php
$dir = dirname(__FILE__);
$libraryDir = realpath($dir . '/../../library/php');
$vendorDir = realpath($dir . '/../../vendor');

require_once $libraryDir . '/Url/UrlFetcher.php';
require_once $vendorDir . '/autoload.php';


class Typo3HostDetectorWorker {

	private $host;
	private $port;
	private $gearmanWorker;
	private $urlFetcher;

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
		$fetcher->fetchUrl(T3census\Url\UrlFetcher::HTTP_GET, TRUE);

		if ($fetcher->getErrno() === 0) {
			if ($fetcher->getNumRedirects() >= 0)  $url = $fetcher->getUrl();

			$result = array();

			$objUrl = \Purl\Url::parse($url);

			$result['ip'] = $fetcher->getIpAddress();
			$result['port'] = $fetcher->getPort();
			$result['scheme'] = $objUrl->get('scheme');
			$result['protocol'] = $objUrl->get('scheme') . '://';
			$result['host'] = $objUrl->get('host');
			$result['subdomain'] = $objUrl->get('subdomain');
			$result['registerableDomain'] = $objUrl->get('registerableDomain');
			$result['publicSuffix'] = $objUrl->get('publicSuffix');
			$result['path'] = $objUrl->get('path')->getPath();

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
					$hostname = $objUrl->get('scheme') . '://' . $objUrl->get('host') . (!is_null($objUrl->get('port') ? $objUrl->get('port') : '')) . '/';

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
						$hostname .= $objUrl->get('path')->getPath();

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

			unset($objUrl);
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

	protected function getUrlFetcher() {
		if (!is_object($this->urlFetcher) && !($this->urlFetcher instanceof T3census\Url\UrlFetcher)) {
			$this->urlFetcher = new T3census\Url\UrlFetcher();
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