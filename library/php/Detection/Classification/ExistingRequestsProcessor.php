<?php
namespace T3census\Detection\Classification;


$dir = dirname(__FILE__);
$libraryDir = realpath($dir . '/../../../../library/php');
$vendorDir = realpath($dir . '/../../../../vendor');

require_once $libraryDir . '/Detection/AbstractProcessor.php';
require_once $libraryDir . '/Detection/ProcessorInterface.php';
require_once $libraryDir . '/Detection/DomParser.php';
require_once $vendorDir . '/autoload.php';


class ExistingRequestsProcessor extends \T3census\Detection\AbstractProcessor implements \T3census\Detection\ProcessorInterface {

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

		$request = $context->getRequest();
		while (!is_null($request) && is_object($request)) {
			/* @var $request \T3census\Detection\Request */
			if (is_string($request->getBody())) {
				$objParser = new \T3census\Detection\DomParser($request->getBody());
				$objParser->parse();
				$metaGenerator = $objParser->getMetaGenerator();
				if (!is_null($objParser->getMetaGenerator()) && is_string($objParser->getMetaGenerator()) && strpos($objParser->getMetaGenerator(), 'TYPO3') !== FALSE) {
					$context->setTypo3VersionString($metaGenerator);
					$isClassificationSuccessful = TRUE;
				}
				unset($objParser);
			}

			$request = $context->getRequest();
		}
		unset($request);

		if (!is_null($this->successor) && !$isClassificationSuccessful) {
			$this->successor->process($context);
		}
	}
}