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


class HostOnlyProcessor extends \T3census\Detection\AbstractProcessor implements \T3census\Detection\ProcessorInterface {

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
		$objUrl = \Purl\Url::parse($context->getUrl());

		$urlHostOnly = $objUrl->get('scheme') . '://' . $objUrl->get('host');
		$objFetcher->setUrl($urlHostOnly)->fetchUrl(\T3census\Url\UrlFetcher::HTTP_GET, FALSE, TRUE);


		if ($objFetcher->getErrno() === 0) {
			$responseBody = $objFetcher->getBody();

			if (is_string($responseBody) && strlen($responseBody)) {
				$objParser = new \T3census\Detection\DomParser($responseBody);
				$objParser->parse();

				$metaGenerator = $objParser->getMetaGenerator();
				if (!is_null($metaGenerator) && is_string($metaGenerator) && strpos($metaGenerator, 'TYPO3') !== FALSE) {
					$matches = array();
					$isMatch = preg_match('/TYPO3 \d\.\d CMS/', $metaGenerator, $matches);
					if (is_int($isMatch) && $isMatch === 1 && is_array($matches) && count($matches) == 1) {
						$context->setTypo3VersionString(array_shift($matches));
					} else {
						$context->setTypo3VersionString($metaGenerator);
					}
					$isClassificationSuccessful = TRUE;
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