<?php
/**
 * Created by JetBrains PhpStorm.
 * User: marcus
 * Date: 26.05.13
 * Time: 16:11
 * To change this template use File | Settings | File Templates.
 */

function normalizeUrl($url) {
	$regex = '#^([a-zA-Z0-9\.\-]*://)*([\w\.\-\d]*)(:(\d+))*(/*)([^:]*)$#';
	$matches = array();
	preg_match($regex, $url, $matches);
var_dump($matches);

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

function resolveTargetUrl($url, &$content, &$curlInfo, &$curlErrno) {
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_POST, FALSE);
#	curl_setopt($curl, CURLOPT_NOBODY, TRUE);
	curl_setopt($curl, CURLOPT_HEADER, 0);
	curl_setopt($curl, CURLOPT_TIMEOUT, 60);
	curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($curl, CURLOPT_USERAGENT, 'T3census-Crawler/1.0');
	$content = curl_exec($curl);

	$curlInfo = curl_getinfo($curl);
	$curlErrno = curl_errno($curl);

	return $content;
}


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

function testTypo3Artefacts($url, &$curlInfo, &$curlErrno) {
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



$url = 'http://typo3.org/support/professional-services/reference/Agency/show//nawinfo-gmbh/';
$url = 'http://typo3.org//news/article/extbase-and-fluid-feature-overview/';

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

$hostname = $urlInfo['protocol'] . $urlInfo['host'] . $urlInfo['port'] . '/' . $urlInfo['path'];

$fileadminUrl = $hostname . 'uploads/';

$result = array();



#var_dump($curlInfo);

#var_dump(parseDomForGenerator($content));

if (strpos($content, 'TYPO3') !== FALSE) {
	#echo 'YES';
}

$curlInfo = array();
$curlErrno = array();
testTypo3Artefacts($fileadminUrl, $curlInfo, $curlErrno);

var_dump($curlInfo);


?>