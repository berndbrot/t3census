<?php

$dir = dirname(__FILE__);
$libraryDir = realpath($dir . '/../../library/php');
$vendorDir = realpath($dir . '/../../vendor');

require_once $libraryDir . '/Api/Bing/BingApi.php';
require_once $vendorDir . '/autoload.php';


$bingApi = new BingApi();
$bingApi->setAccountKey('')->setEndpoint('https://api.datamarket.azure.com/Bing/Search');

$results = $bingApi->setQuery('ip:139.174.2.16')->getResults();

?>