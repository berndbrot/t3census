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
$url = 'http://www.cultofmac.com/apple-may-be-invisibly-filtering-your-outgoing-mobileme-email-exclusive/103703?utm_source=feedburner&utm_medium=feed&utm_campaign=Feed:+cultofmac/bFow+%28Cult+of+Mac%29';
$url = 'http://webcache.googleusercontent.com/search?q=cache:Ph3kNuO48m0J:https://www.gema.de/impressum.html+gema.de/impressum&cd=1&hl=de&ct=clnk&gl=de&client=firefox-a&source=www.google.de';
$url = 'http://www.gamona.de/videos/gamescom-2011,gamona-pwned-rtl-hq:video,1978237.html';
$url = 'http://it.wikipedia.org/wiki/Wikipedia:Comunicato_4_ottobre_2011/en';
$url = 'http://www.youtube.com/api/moderator/g/yt/?channame=bundesregierung&hl=de&embed=http://www.youtube.com/bundesregierung#8/e=be767';
$url = 'http://www.ftd.de/it-medien/it-telekommunikation/:einigung-auf-neuen-standard-nokia-zieht-im-sim-streit-die-patentkarte/70015786.html#link_position=300_26&utm_source=newsletter&utm_medium=unternehmen_persoenlich_text&utm_campaign=%campaign';
$url = 'https://metrics.typo3.org/drilldown/violations/org.typo3:extension-direct_mail';
$url = 'http://www.google.com/finance?client=ob&q=NASDAQ:FB';
$url = 'http://wiki.typo3.org/User_talk:Alex_schnitzler';
$url = 'http://tools.pingdom.com/fpt/tZQWuMvtd/http://www.redroot.de';

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