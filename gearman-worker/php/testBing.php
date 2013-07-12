<?php

$dir = dirname(__FILE__);
$libraryDir = realpath($dir . '/../../library/php');
$vendorDir = realpath($dir . '/../../vendor');

require_once $libraryDir . '/Api/Bing/BingApi.php';
require_once $vendorDir . '/autoload.php';

/*
84.39.222.198Array
(
    [0] => http://www.umar.si
    [1] => http://www.umar.gov.si
    [2] => http://www.spletisvojokariero.si
    [3] => http://vrata.koper.si
    [4] => http://test.jpp.si
    [5] => http://sq.ess.gov.si
    [6] => http://ribkat.mkgp.gov.si8080
    [7] => http://ribkat.mkgp.gov.si
    [8] => http://pycc.ess.gov.si
    [9] => http://prostor.zgs.gov.si
    [10] => http://mk.ess.gov.si
    [11] => http://jpp.si
    [12] => http://fito-gis.mko.gov.si
    [13] => http://english.ess.gov.si
    [14] => http://bshrsr.ess.gov.si
)
 */

$bingApi = new BingApi();
$bingApi->setAccountKey('')->setEndpoint('https://api.datamarket.azure.com/Bing/Search');

$results = $bingApi->setQuery('ip:139.174.2.16')->getResults();

?>