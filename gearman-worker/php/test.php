<?php
$dir = dirname(__FILE__);
$libraryDir = realpath($dir . '/../../library/php');
$vendorDir = realpath($dir . '/../../vendor');

require_once $libraryDir . '/Url/UrlFetcher.php';
require_once $libraryDir . '/Url/UrlNormalizer.php';
require_once $vendorDir . '/autoload.php';


function parseDomForGenerator($content) {
	libxml_use_internal_errors(TRUE);

	$metaGenerator = '';
	$dom = new DOMDocument();
	$dom->loadHTML($content);
	$xpath = new DOMXPath($dom);

	// Look for the content attribute of description meta tags
	$generators = $xpath->query('/html/head/meta[@name="generators"]/@content');

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

/*
XML prolog - generator in DOM not working:
$url= 'http://kamerakind.net';
$url = 'http://t3uni.typo3-fr.org';
$url = 'http://www.typovision.de/de/home/';

$curlInfo = array();
$curlErrno = array();
$content = '';

resolveTargetUrl($url, $content, $curlInfo, $curlErrno);

if ($curlErrno === 0
		&& array_key_exists('redirect_count', $curlInfo) && $curlInfo['redirect_count'] >= 0
		&& array_key_exists('url', $curlInfo) && !empty($curlInfo['url'])) {
	$url = $curlInfo['url'];
}


$urlInfo = normalizeUrl($url);
*/


$url = 'http://www.danatranslation.com/index.php?option=com_content&view=article&id=167:time-management&catid=29:blog-posts&Itemid=222';
$normalizer = new UrlNormalizer();
$arrUrl = $normalizer->setOriginUrl($url)->getNormalizedUrl();
#print_r($arrUrl);

$objUrl = \Purl\Url::parse($url);
$result = array();
#$result['ip'] = $fetcher->getIpAddress();
#$result['port'] = $fetcher->getPort();
$result['scheme'] = $objUrl->get('scheme');
$result['protocol'] = $objUrl->get('scheme') . '://';
$result['host'] = $objUrl->get('host');
$result['subdomain'] = $objUrl->get('subdomain');
$result['registerableDomain'] = $objUrl->get('registerableDomain');
$result['publicSuffix'] = $objUrl->get('publicSuffix');
$result['path'] = $objUrl->get('path')->getPath();
print_r($result);




$fetcher = new UrlFetcher();
$fetcher->setUrl('http://www.example.org/path');
$fetcher->fetchUrl(UrlFetcher::HTTP_GET, TRUE);
?>