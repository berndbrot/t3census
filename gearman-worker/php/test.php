<?php
/**
 * Created by JetBrains PhpStorm.
 * User: marcus
 * Date: 26.05.13
 * Time: 16:11
 * To change this template use File | Settings | File Templates.
 */


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
$url = 'http://typo3.org/support/professional-services/reference/Agency/show//nawinfo-gmbh/';
$url = 'http://typo3.org//news/article/extbase-and-fluid-feature-overview/';
$url = 'http://web.archive.org/web/20110724075246/http://www.mediamarkt.ch/';
$url = 'http://www.slideshare.net/mayflowergmbh/html5-und-nodejs-grundlagen?ref=http://it-republik.de/php/news/Slideshow-zu-HTML5-und-Node.js-062186.html';
$url = 'http://bacolicio.us/http://typo3.org/';
$url = 'http://git.typo3.org/TYPO3v4/Core.git/history/HEAD:/t3lib/jsfunc.evalfield.js';
$url = 'http://pyfound.blogspot.de/2013/02/python-trademark-at-risk-in-europe-we.html?utm_source=feedburner&utm_medium=twitter&utm_campaign=Feed:+PythonSoftwareFoundationNews+%28Python+Software+Foundation+News%29';
$url = 'http://www.icondeposit.com/design:116';
$url = 'http://hsmaker.com/harlemshake.asp?url=http://www.network-publishing.de';

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

/*
require_once 'UrlNormalizer.php';
$normalizer = new UrlNormalizer();
$arrUrl = $normalizer->setOriginUrl('http://www.example.org/path')->getNormalizedUrl();
print_r($arrUrl);
*/

require_once 'UrlFetcher.php';
$fetcher = new UrlFetcher();
$fetcher->setUrl('http://www.example.org/path');
$fetcher->fetchUrl(UrlFetcher::HTTP_GET, TRUE);
?>