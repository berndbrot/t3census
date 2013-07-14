<?php

$dir = dirname(__FILE__);
$libraryDir = realpath($dir . '/../../library/php');
$vendorDir = realpath($dir . '/../../vendor');

require_once $libraryDir . '/Bing/Api/ReverseIpLookup.php';
require_once $libraryDir . '/Bing/Scraper/ReverseIpLookup.php';
require_once $vendorDir . '/autoload.php';

try {
	$objLookup = new T3census\Bing\Api\ReverseIpLookup();
	$objLookup->setAccountKey('')->setEndpoint('https://api.datamarket.azure.com/Bing/Search');
	$results = $objLookup->setQuery('ip:84.39.222.198')->getResults();
	print_r($results);
	unset($objLookup);
} catch (\T3census\Bing\Api\Exception\ApiConsumeException $e) {
	$objLookup = new \T3census\Bing\Scraper\ReverseIpLookup();
	$objLookup->setEndpoint('http://www.bing.com/search');
	$results = $objLookup->setQuery('ip:84.39.222.198')->getResults();
	print_r($results);
	unset($objLookup);
}

?>