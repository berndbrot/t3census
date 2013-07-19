<?php
namespace T3census\Detection\Identification;


$dir = dirname(__FILE__);
$libraryDir = realpath($dir . '/../../../../library/php');
$vendorDir = realpath($dir . '/../../../../vendor');

require_once $libraryDir . '/Detection/AbstractProcessor.php';
require_once $libraryDir . '/Detection/ProcessorInterface.php';
require_once $libraryDir . '/Detection/DomParser.php';
require_once $libraryDir . '/Url/UrlFetcher.php';
require_once $vendorDir . '/autoload.php';


class Typo3ArtefactsProcessor extends \T3census\Detection\AbstractProcessor implements \T3census\Detection\ProcessorInterface {


	/**
	 * Class constructor.
	 *
	 * @param  \T3census\Detection\ProcessorInterface|null  $successor
	 */
	public function __construct($successor = NULL) {
		if (!is_null($successor)) {
			$this->successor = $successor;
		}
	}

	/**
	 * Processes context.
	 *
	 * @param  \T3census\Detection\Context  $context
	 * @return  void
	 */
	public function process(\T3census\Detection\Context $context) {
		$isIdentificationSuccessful = FALSE;

		$objRequest = new \T3census\Detection\Request();
		$objFetcher = new \T3census\Url\UrlFetcher();
		$objUrl = \Purl\Url::parse($context->getUrl());

		$urlHostOnly = $objUrl->get('scheme') . '://' . $objUrl->get('host');
		$urlFullPath = $objUrl->get('scheme') . '://' . $objUrl->get('host');
		$path = $objUrl->path->getData();
		$path = array_reverse($path);
		$pathString = '';
		$i=0;
		foreach ($path as $pathSegment) {
			if (!empty($pathSegment)) {
				if ($i === 0) {
					if (!is_int(strpos($pathSegment, '.'))) {
						$pathString =  $pathSegment . '/' . $pathString . '/';
					} else {
						#$pathString =  '/' . $pathString ;
					}
				} else {
					$pathString =  '/' . $pathSegment . $pathString;
				}
			}
			$i++;
		}
		$urlFullPath .= $pathString;

		$objRequest->setRequestUrl($urlHostOnly)->setResponseUrl($urlHostOnly);


		$fetcherHttpCodeFileadmin = $fetcherHttpCodeSysext = $fetcherHttpCodeRandom = NULL;
		$fetcherErrnoFileadmin = $fetcherErrnoSysext = $fetcherErrnoRandom = 0;

		$objFileadminUrl = new \Purl\Url($urlHostOnly);
		$objFileadminUrl->path = 'fileadmin/';
		$fileadminUrl = $objFileadminUrl->getUrl();
		$objFetcher->setUrl($fileadminUrl)->fetchUrl(\T3census\Url\UrlFetcher::HTTP_GET, FALSE, FALSE);
		$fetcherHttpCodeFileadmin = $objFetcher->getResponseHttpCode();
		$fetcherErrnoFileadmin = $objFetcher->getErrno();

		$objSysextUrl = new \Purl\Url($urlHostOnly);
		$objSysextUrl->path = 'typo3/sysext/';
		$sysextUrl = $objSysextUrl->getUrl();
		$objFetcher->reset()->setUrl($sysextUrl)->fetchUrl(\T3census\Url\UrlFetcher::HTTP_GET, FALSE, FALSE);
		$fetcherHttpCodeSysext = $objFetcher->getResponseHttpCode();
		$fetcherErrnoSysext = $objFetcher->getErrno();

		$objRandomUrl = new \Purl\Url($urlHostOnly);
		$objRandomUrl->path = md5(time()) . '/';
		$randomUrl = $objRandomUrl->getUrl();
		$objFetcher->reset()->setUrl($randomUrl)->fetchUrl(\T3census\Url\UrlFetcher::HTTP_GET, FALSE, FALSE);
		$fetcherHttpCodeRandom = $objFetcher->getResponseHttpCode();
		$fetcherErrnoRandom = $objFetcher->getErrno();

		if ($fetcherErrnoFileadmin === 0 && $fetcherErrnoSysext === 0 && $fetcherErrnoRandom === 0
				&& $fetcherHttpCodeFileadmin === 403 && $fetcherHttpCodeSysext === 403 && $fetcherHttpCodeRandom !== 403) {
			if (is_null($context->getIp()))  $context->setIp($objFetcher->getIpAddress());
			if (is_null($context->getPort()))  $context->setPort($objFetcher->getPort());
			$context->setUrl($urlHostOnly);
			$context->setIsTypo3Cms(TRUE);
			$isIdentificationSuccessful = TRUE;
		} else {
			if (0 !== strcmp($urlHostOnly, $urlFullPath)) {
				$objRequest->setRequestUrl($urlFullPath)->setResponseUrl($urlFullPath);

				$fetcherHttpCodeFileadmin = $fetcherHttpCodeSysext = NULL;
				$fetcherErrnoFileadmin = $fetcherErrnoSysext = 0;

				$objFileadminUrl = new \Purl\Url($urlFullPath);
				$objFileadminUrl->path->add('fileadmin/');
				$fileadminUrl = $objFileadminUrl->getUrl();
				$objFetcher->setUrl($fileadminUrl)->fetchUrl(\T3census\Url\UrlFetcher::HTTP_GET, FALSE, FALSE);
				$fetcherHttpCodeFileadmin = $objFetcher->getResponseHttpCode();
				$fetcherErrnoFileadmin = $objFetcher->getErrno();

				$objSysextUrl = new \Purl\Url($urlFullPath);
				$objSysextUrl->path->add('typo3/sysext/');
				$sysextUrl = $objSysextUrl->getUrl();
				$objFetcher->reset()->setUrl($sysextUrl)->fetchUrl(\T3census\Url\UrlFetcher::HTTP_GET, FALSE, FALSE);
				$fetcherHttpCodeSysext = $objFetcher->getResponseHttpCode();
				$fetcherErrnoSysext = $objFetcher->getErrno();

				$objRandomUrl = new \Purl\Url($urlFullPath);
				$objRandomUrl->path->add(md5(time()) . '/');
				$randomUrl = $objRandomUrl->getUrl();
				$objFetcher->reset()->setUrl($randomUrl)->fetchUrl(\T3census\Url\UrlFetcher::HTTP_GET, FALSE, FALSE);
				$fetcherHttpCodeRandom = $objFetcher->getResponseHttpCode();
				$fetcherErrnoRandom = $objFetcher->getErrno();

				if ($fetcherErrnoFileadmin === 0 && $fetcherErrnoSysext === 0 && $fetcherErrnoRandom === 0
						&& $fetcherHttpCodeFileadmin === 403 && $fetcherHttpCodeSysext === 403 && $fetcherHttpCodeRandom !== 403) {
					if (is_null($context->getIp()))  $context->setIp($objFetcher->getIpAddress());
					if (is_null($context->getPort()))  $context->setPort($objFetcher->getPort());
					$context->setUrl($urlFullPath);
					$context->setIsTypo3Cms(TRUE);
					$isIdentificationSuccessful = TRUE;
				}
			}
			unset($urlFullPath);
		}
		$context->addRequest($objRequest);

		unset($urlHostOnly, $objFileadminUrl, $objSysextUrl, $objUrl, $objFetcher, $objRequest);

		if (!is_null($this->successor) && !$isIdentificationSuccessful) {
			$this->successor->process($context);
		}
	}
}
?>