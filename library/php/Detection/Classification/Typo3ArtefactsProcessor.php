<?php
namespace T3census\Detection\Classification;


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
		$isClassificationSuccessful = FALSE;

		$objFetcher = new \T3census\Url\UrlFetcher();
		$objFetcher->setUserAgent('Opera/99.0');
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
						$pathString =  $pathString . '/' . $pathSegment;
					}
				} else {
					$pathString = $pathString . '/' . $pathSegment;
				}
			}
			$i++;
		}
		$urlFullPath .= $pathString;


		$fetcherErrnoHostOnly = $fetcherErrnoFullPath = 0;

		$objHostOnlyBackendUrl = new \Purl\Url($urlHostOnly);
		$objHostOnlyBackendUrl->path = 'typo3/index.php';
		$hostOnlyBackendUrl = $objHostOnlyBackendUrl->getUrl();
		$objFetcher->setUrl($hostOnlyBackendUrl)->fetchUrl(\T3census\Url\UrlFetcher::HTTP_GET, FALSE, FALSE);
		$fetcherErrnoHostOnly = $objFetcher->getErrno();
		$responseBodyHostOnly = $objFetcher->getBody();
		unset($objHostOnlyBackendUrl);


		$objFullPathBackendUrl = new \Purl\Url($urlFullPath);
		$objFullPathBackendUrl->path->add('typo3')->add('index.php');
		$fullPathBackendUrl = $objFullPathBackendUrl->getUrl();


		if (0 !== strcmp($hostOnlyBackendUrl, $fullPathBackendUrl)) {
			$objFetcher->setUrl($fullPathBackendUrl)->fetchUrl(\T3census\Url\UrlFetcher::HTTP_GET, FALSE, FALSE);
			$fetcherErrnoFullPath = $objFetcher->getErrno();
			$responseBodyFullPath = $objFetcher->getBody();
		} else {
			$fetcherErrnoFullPath = $fetcherErrnoHostOnly;
			$responseBodyFullPath = $responseBodyHostOnly;
		}
		unset($objFullPathBackendUrl);


		if ($fetcherErrnoFullPath === 0) {

			if (is_string($responseBodyHostOnly) && strlen($responseBodyHostOnly)) {
				$objParser = new \T3census\Detection\DomParser($responseBodyHostOnly);
				$objParser->parse();

				$metaGenerator = $objParser->getMetaGenerator();
				if (!is_null($metaGenerator) && is_string($metaGenerator) && strpos($metaGenerator, 'TYPO3') !== FALSE) {
					$matches = array();
					$isMatch = preg_match('/TYPO3 \d\.\d/', $metaGenerator, $matches);
					if (is_int($isMatch) && $isMatch === 1 && is_array($matches) && count($matches) == 1) {
						$context->setTypo3VersionString(array_shift($matches) . ' CMS');
					} else {
						$context->setTypo3VersionString($metaGenerator);
					}
					$isClassificationSuccessful = TRUE;
				} else {
					if (is_string($responseBodyFullPath) && strlen($responseBodyFullPath)) {
						$objParser->setContent($responseBodyFullPath);

						$metaGenerator = $objParser->getMetaGenerator();
						if (!is_null($metaGenerator) && is_string($metaGenerator) && strpos($metaGenerator, 'TYPO3') !== FALSE) {
							$matches = array();
							$isMatch = preg_match('/TYPO3 \d\.\d/', $metaGenerator, $matches);
							if (is_int($isMatch) && $isMatch === 1 && is_array($matches) && count($matches) == 1) {
								$context->setTypo3VersionString(array_shift($matches) . ' CMS');
							} else {
								$context->setTypo3VersionString($metaGenerator);
							}
							$isClassificationSuccessful = TRUE;
						}
					}
				}
				unset($metaGenerator, $objParser);
			}
		}
		unset($objFetcher, $objUrl);

		if (!is_null($this->successor) && !$isClassificationSuccessful) {
			$this->successor->process($context);
		}
	}
}