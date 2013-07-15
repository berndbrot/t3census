<?php
namespace T3census\Detection\Identification;


class FullPathProcessor extends \T3census\Detection\AbstractProcessor implements \T3census\Detection\ProcessorInterface {

	protected $allowRedirect = FALSE;


	/**
	 * Class constructor.
	 *
	 * @param  \T3census\Detection\ProcessorInterface|null  $successor
	 * @param  bool $allowRedirect
	 */
	public function __construct($successor = NULL, $allowRedirect = FALSE) {
		if (!is_null($successor)) {
			$this->successor = $successor;
		}

		if (!is_bool($allowRedirect)) {
			throw new InvalidArgumentException(
				sprintf('Invalid argument for constructor of %s',
					get_class($this)
				),
				1373924180
			);
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

		$urlFullPath = $objUrl->get('scheme') . '://' . $objUrl->get('host');
		$path = $objUrl->get('path')->getPath();
		$urlFullPath .= (is_string($path) && strlen($path) > 0 && 0 !== strcmp('/', $path) ? $path  : '');

		$objFetcher->setUrl($urlFullPath)->fetchUrl(\T3census\Url\UrlFetcher::HTTP_GET, TRUE, $this->allowRedirect);
		$objRequest->setRequestUrl($urlFullPath)->setResponseUrl($urlFullPath);

		if ($objFetcher->getErrno() === 0) {
			$objRequest->setRequestUrl($urlFullPath)->setResponseUrl($urlFullPath);
			if ($objFetcher->getNumRedirects() >= 0) $objRequest->setResponseUrl($objFetcher->getUrl());

			if (is_null($context->getIp()))  $context->setIp($objFetcher->getIpAddress());
			if (is_null($context->getPort()))  $context->setPort($objFetcher->getPort());

			$objRequest->setResponseCode($objFetcher->getResponseHttpCode());
			$responseBody = $objFetcher->getBody();
			$objRequest->setBody($responseBody);
			$responseCookies = $objFetcher->getResponseCookies();
			$objRequest->setCookies($responseCookies);

			if (is_array($responseCookies)) {
				$typo3CookiesKeys = array('fe_typo_user', 'be_typo_user');
				$cookieKeys = array_keys($responseCookies);
				$isTypo3Cookies = array_intersect($typo3CookiesKeys, $cookieKeys);
				if (is_array($isTypo3Cookies) && count($isTypo3Cookies)) {
					$context->setIsTypo3Cms(TRUE);
					$isIdentificationSuccessful = TRUE;
				}
			}

			if (!$isIdentificationSuccessful && is_string($responseBody) && strlen($responseBody)) {
				//TODO
				$objParser = new \T3census\Detection\DomParser($responseBody);
				$objParser->parse();

				if (!is_null($objParser->getMetaGenerator()) && is_string($objParser->getMetaGenerator()) && strpos($objParser->getMetaGenerator(), 'TYPO3') !== FALSE) {
					$context->setIsTypo3Cms(TRUE);
					$isIdentificationSuccessful = TRUE;
				}
				unset($objParser);
			}

			$context->addRequest($objRequest);
		}
		unset($responseCookies, $responseBody, $urlHostOnly, $objUrl, $objFetcher, $objRequest);

		if (!is_null($this->successor) && !$isIdentificationSuccessful) {
			$this->successor->process($context);
		}
	}
}