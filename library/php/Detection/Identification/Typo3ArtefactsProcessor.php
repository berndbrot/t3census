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
		$objRequest->setRequestUrl($urlHostOnly)->setResponseUrl($urlHostOnly);


		$fetcherHttpCodeFileadmin = $fetcherHttpCodeUploads = NULL;
		$fetcherErrnoFileadmin = $fetcherErrnoUploads = 0;

		$objFileadminUrl = new \Purl\Url($urlHostOnly);
		$objFileadminUrl->path = 'fileadmin/';
		$fileadminUrl = $objFileadminUrl->getUrl();
		$objFetcher->setUrl($fileadminUrl)->fetchUrl(\T3census\Url\UrlFetcher::HTTP_GET, FALSE, FALSE);
		$fetcherHttpCodeFileadmin = $objFetcher->getResponseHttpCode();
		$fetcherErrnoFileadmin = $objFetcher->getErrno();

		$objUploadsUrl = new \Purl\Url($urlHostOnly);
		$objUploadsUrl->path = 'uploads/';
		$uploadsUrl = $objUploadsUrl->getUrl();
		$objFetcher->reset()->setUrl($uploadsUrl)->fetchUrl(\T3census\Url\UrlFetcher::HTTP_GET, FALSE, FALSE);
		$fetcherHttpCodeUploads = $objFetcher->getResponseHttpCode();
		$fetcherErrnoUploads = $objFetcher->getErrno();

		if ($fetcherErrnoFileadmin === 0 && $fetcherErrnoUploads === 0
				&& $fetcherHttpCodeFileadmin === 403 && $fetcherHttpCodeUploads === 403) {
			if (is_null($context->getIp()))  $context->setIp($objFetcher->getIpAddress());
			if (is_null($context->getPort()))  $context->setPort($objFetcher->getPort());
			$context->setUrl($urlHostOnly);
			$context->setIsTypo3Cms(TRUE);
			$isIdentificationSuccessful = TRUE;
		} else {

			$urlFullPath = $objUrl->get('scheme') . '://' . $objUrl->get('host');
			$path = $objUrl->get('path')->getPath();
			$urlFullPath .= (is_string($path) && strlen($path) > 0 && 0 !== strcmp('/', $path) ? $path  : '');

			if (TRUE || 0 !== strcmp($urlHostOnly, $urlFullPath)) {
				$objRequest->setRequestUrl($urlFullPath)->setResponseUrl($urlFullPath);

				$fetcherHttpCodeFileadmin = $fetcherHttpCodeUploads = NULL;
				$fetcherErrnoFileadmin = $fetcherErrnoUploads = 0;

				$objFileadminUrl = new \Purl\Url($urlFullPath);
				$objFileadminUrl->path = 'fileadmin/';
				$fileadminUrl = $objFileadminUrl->getUrl();
				$objFetcher->setUrl($fileadminUrl)->fetchUrl(\T3census\Url\UrlFetcher::HTTP_GET, FALSE, FALSE);
				$fetcherHttpCodeFileadmin = $objFetcher->getResponseHttpCode();
				$fetcherErrnoFileadmin = $objFetcher->getErrno();

				$objUploadsUrl = new \Purl\Url($urlFullPath);
				$objUploadsUrl->path = 'uploads/';
				$uploadsUrl = $objUploadsUrl->getUrl();
				$objFetcher->reset()->setUrl($uploadsUrl)->fetchUrl(\T3census\Url\UrlFetcher::HTTP_GET, FALSE, FALSE);
				$fetcherHttpCodeUploads = $objFetcher->getResponseHttpCode();
				$fetcherErrnoUploads = $objFetcher->getErrno();

				if ($fetcherErrnoFileadmin === 0 && $fetcherErrnoUploads === 0
						&& $fetcherHttpCodeFileadmin === 403 && $fetcherHttpCodeUploads === 403) {
					if (is_null($context->getIp()))  $context->setIp($objFetcher->getIpAddress());
					if (is_null($context->getPort()))  $context->setPort($objFetcher->getPort());
					$context->setUrl($urlHostOnly);
					$context->setIsTypo3Cms(TRUE);
					$isIdentificationSuccessful = TRUE;
				}
			}
			unset($urlFullPath);
		}
		$context->addRequest($objRequest);

		unset($urlHostOnly, $objFileadminUrl, $objUploadsUrl, $objUrl, $objFetcher, $objRequest);

		if (!is_null($this->successor) && !$isIdentificationSuccessful) {
			$this->successor->process($context);
		}
	}
}
?>