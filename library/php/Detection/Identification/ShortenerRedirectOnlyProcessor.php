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


class ShortenerRedirectOnlyProcessor extends \T3census\Detection\AbstractProcessor implements \T3census\Detection\ProcessorInterface {

	protected $shortenerServices = array(
		'b-gat.es',
		'bit.ly',
		'buff.ly',
		'csc0.ly',
		'eepurl.com',
		'fb.me',
		'dlvr.it',
		'goo.gl',
		'indu.st',
		'is.gd',
		'j.mp',
		'kck.st',
		'krz.ch',
		'lnkr.ch',
		'moreti.me',
		'myurl.to',
		'npub.li',
		'nkirch.de',
		'nkor.de',
		'opnstre.am',
		'ow.ly',
		'shar.es',
		't3n.me',
		'tinyurl.com',
		'ur1.ca',
		'xing.com',
		'zite.to',
	);

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

		$objUrl = \Purl\Url::parse($context->getUrl());

		$urlHost = $objUrl->get('host');

		if (in_array($urlHost, $this->shortenerServices, TRUE)) {
			$objFetcher = new \T3census\Url\UrlFetcher();
			$objFetcher->setUrl($context->getUrl())->fetchUrl(\T3census\Url\UrlFetcher::HTTP_GET, FALSE, TRUE);

			if ($objFetcher->getErrno() === 0) {
				$context->setUrl($objFetcher->getUrl());
			}
		}

		if (!is_null($this->successor)) {
			$this->successor->process($context);
		}
	}
}