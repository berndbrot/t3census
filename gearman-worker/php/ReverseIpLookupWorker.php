<?php

$dir = dirname(__FILE__);
$libraryDir = realpath($dir . '/../../library/php');
$vendorDir = realpath($dir . '/../../vendor');

require_once $libraryDir . '/Bing/Api/ReverseIpLookup.php';
require_once $libraryDir . '/Bing/Scraper/ReverseIpLookup.php';
require_once $vendorDir . '/autoload.php';

class ReverseIpLookupWorker {

	private $host;
	private $port;
	private $accountKey;
	private $gearmanWorker;
	private $useFallback = FALSE;

	public function __construct($host = '127.0.0.1', $port = 4730, $accountKey = '') {
		$this->host = $host;
		$this->port = $port;
		$this->accountKey = $accountKey;
	}

	public function setUp() {
		$this->gearmanWorker = new GearmanWorker();
		$this->gearmanWorker->addServer($this->host, $this->port);
		$this->gearmanWorker->addFunction("ReverseIpLookup", array($this, 'fetchHostnames'));
	}

	public function fetchHostnames(GearmanJob $job) {
		$result = FALSE;
		$ip = $job->workload();

		if ($this->useFallback) {
			$objLookup = new \T3census\Bing\Scraper\ReverseIpLookup();
			$objLookup->setEndpoint('http://www.bing.com/search');
			$results = $objLookup->setQuery('ip:' . $ip)->getResults();
			unset($objLookup);
		} else {
			try {
				$objLookup = new T3census\Bing\Api\ReverseIpLookup();
				$objLookup->setAccountKey($this->accountKey)->setEndpoint('https://api.datamarket.azure.com/Bing/Search');
				$results = $objLookup->setQuery('ip:' . $ip)->getResults();
				unset($objLookup);
			} catch (\T3census\Bing\Api\Exception\ApiConsumeException $e) {
				$this->useFallback = TRUE;
				$objLookup = new \T3census\Bing\Scraper\ReverseIpLookup();
				$objLookup->setEndpoint('http://www.bing.com/search');
				$results = $objLookup->setQuery('ip:' . $ip)->getResults();
				unset($objLookup);
			}
		}

		return json_encode($results);
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