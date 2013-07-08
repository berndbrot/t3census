<?php

$dir = dirname(__FILE__);
$libraryDir = realpath($dir . '/../../library/php');
$vendorDir = realpath($dir . '/../../vendor');

require_once $libraryDir . '/Api/Bing/BingApi.php';
require_once $vendorDir . '/autoload.php';

class ReverseIpLookupWorker {

	private $host;
	private $port;
	private $accountKey;
	private $gearmanWorker;

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

		$bingApi = new BingApi();
		$bingApi->setMaxResults(1000);
		$bingApi->setAccountKey($this->accountKey)->setEndpoint('https://api.datamarket.azure.com/Bing/Search');

		$results = $bingApi->setQuery('ip:' . $ip)->getResults();

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