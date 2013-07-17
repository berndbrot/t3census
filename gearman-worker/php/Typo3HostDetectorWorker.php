<?php
$dir = dirname(__FILE__);
$libraryDir = realpath($dir . '/../../library/php');
$vendorDir = realpath($dir . '/../../vendor');

require_once $libraryDir . '/Detection/Context.php';
require_once $libraryDir . '/Detection/Request.php';
require_once $libraryDir . '/Detection/Identification/ShortenerRedirectOnlyProcessor.php';
require_once $libraryDir . '/Detection/Identification/HostOnlyProcessor.php';
require_once $libraryDir . '/Detection/Identification/FullPathProcessor.php';
require_once $libraryDir . '/Detection/Identification/Typo3ArtefactsProcessor.php';
require_once $libraryDir . '/Detection/Classification/ExistingRequestsProcessor.php';
require_once $libraryDir . '/Detection/Classification/HostOnlyProcessor.php';
require_once $libraryDir . '/Detection/Classification/FullPathProcessor.php';
require_once $libraryDir . '/Detection/Classification/Typo3ArtefactsProcessor.php';
require_once $vendorDir . '/autoload.php';


class Typo3HostDetectorWorker {

	private $host;
	private $port;
	private $gearmanWorker;


	public function __construct($host = '127.0.0.1', $port = 4730) {
		$this->host = $host;
		$this->port = $port;
	}

	public function setUp() {
		$this->gearmanWorker = new GearmanWorker();
		$this->gearmanWorker->addServer($this->host, $this->port);
		$this->gearmanWorker->addFunction('TYPO3HostDetector', array($this, 'fetchUrl'));
	}

	public function fetchUrl(GearmanJob $job) {
		$result = array();

		$context = new \T3census\Detection\Context();
		$context->setUrl($job->workload());

		$objTypo3Artefacts = new \T3census\Detection\Identification\Typo3ArtefactsProcessor(NULL, TRUE);
		$objPathRedirect = new \T3census\Detection\Identification\FullPathProcessor($objTypo3Artefacts, TRUE);
		$objPathNoRedirect = new \T3census\Detection\Identification\FullPathProcessor($objPathRedirect, FALSE);
		$objHostRedirect = new \T3census\Detection\Identification\HostOnlyProcessor($objPathNoRedirect, TRUE);
		$objHostNoRedirect = new \T3census\Detection\Identification\HostOnlyProcessor($objHostRedirect, FALSE);
		$objShortener = new \T3census\Detection\Identification\ShortenerRedirectOnlyProcessor($objHostNoRedirect);
		$objShortener->process($context);
		unset($objShortener, $objHostNoRedirect, $objHostNoRedirect, $objHostRedirect, $objPathNoRedirect, $objPathRedirect);

		if (is_bool($context->getIsTypo3Cms()) && $context->getIsTypo3Cms()) {
			$objArtefacts = new \T3census\Detection\Classification\Typo3ArtefactsProcessor();
			$objFullPath = new \T3census\Detection\Classification\FullPathProcessor($objArtefacts);
			$objHost = new \T3census\Detection\Classification\HostOnlyProcessor($objFullPath);
			$objRequest = new \T3census\Detection\Classification\ExistingRequestsProcessor($objHost);
			$objRequest->process($context);
			unset($objRequest, $objHost);
		}

		$objUrl = new \Purl\Url($context->getUrl());
		$result['ip'] = $context->getIp();
		$result['port'] = $context->getPort();
		$result['scheme'] = $objUrl->get('scheme');
		$result['protocol'] = $objUrl->get('scheme') . '://';
		$result['host'] = $objUrl->get('host');
		$result['subdomain'] = $objUrl->get('subdomain');
		$result['registerableDomain'] = $objUrl->get('registerableDomain');
		$result['publicSuffix'] = $objUrl->get('publicSuffix');
		$path = $objUrl->get('path')->getPath();
		$result['path'] = (is_string($path) && strlen($path) > 0 && 0 !== strcmp('/', $path) ? $path  : NULL);
		$result['TYPO3'] = (is_bool($context->getIsTypo3Cms()) && $context->getIsTypo3Cms());
		$result['TYPO3version'] = $context->getTypo3VersionString();
		unset($objUrl, $context);

		return json_encode($result);
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