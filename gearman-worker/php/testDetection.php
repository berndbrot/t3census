<?php
$dir = dirname(__FILE__);
$libraryDir = realpath($dir . '/../../library/php');
$vendorDir = realpath($dir . '/../../vendor');

require_once $libraryDir . '/Detection/Context.php';
require_once $libraryDir . '/Detection/Request.php';
require_once $libraryDir . '/Detection/Identification/HostOnlyProcessor.php';
require_once $libraryDir . '/Detection/Identification/FullPathProcessor.php';
require_once $libraryDir . '/Detection/Identification/ShortenerRedirectOnlyProcessor.php';
require_once $libraryDir . '/Detection/Identification/Typo3ArtefactsProcessor.php';
require_once $libraryDir . '/Detection/Classification/ExistingRequestsProcessor.php';
require_once $libraryDir . '/Detection/Classification/HostOnlyProcessor.php';
require_once $libraryDir . '/Detection/Classification/FullPathProcessor.php';
require_once $libraryDir . '/Detection/Classification/Typo3ArtefactsProcessor.php';

/*
$foo = new \T3census\Detection\Context();
$bar = new \T3census\Detection\Request();
$foo->addRequest($bar);
print_r($foo->getRequest());
*/
$context = new \T3census\Detection\Context();
$context->setUrl('http://www.typovision.de/de/agentur/aktivitaeten/');
$context->setUrl('http://kamerakind.net/');
$context->setUrl('http://bit.ly/nYswn2');
$context->setUrl('http://bit.ly/nYswn2');
$context->setUrl('http://www.bayernkurier.de/');
$context->setUrl('http://www.bergkristall.it//');
$context->setUrl('http://www.walthelm-gruppe.com/unternehmen/standorte/');
$context->setUrl('http://www.barsa.by');
$context->setUrl('http://www.colleen-rae-holmes.com/index.php');
#$context->setUrl('http://torontonews24.com/');
#$context->setUrl('http://www.1-von-uns.de/typo3/index.php');

$objArtefacts = new \T3census\Detection\Identification\Typo3ArtefactsProcessor(NULL, TRUE);
$objPathRedirect = new \T3census\Detection\Identification\FullPathProcessor($objArtefacts, TRUE);
$objPathNoRedirect = new \T3census\Detection\Identification\FullPathProcessor($objPathRedirect, FALSE);
$objHostRedirect = new \T3census\Detection\Identification\HostOnlyProcessor($objPathNoRedirect, TRUE);
$objHostNoRedirect = new \T3census\Detection\Identification\HostOnlyProcessor($objHostRedirect, FALSE);
$objArtefacts->process($context);

/*
$objHostRedirect = new \T3census\Detection\Identification\HostOnlyProcessor(NULL, TRUE);
$objHostRedirect->process($context);

/*
$objPathRedirect = new \T3census\Detection\Identification\FullPathProcessor(NULL, TRUE);
$objPathRedirect->process($context);
*/
print_r($context);

if (TRUE || is_bool($context->getIsTypo3Cms()) && $context->getIsTypo3Cms()) {
	$objArtefacts = new \T3census\Detection\Classification\Typo3ArtefactsProcessor();
	$objFullPath = new \T3census\Detection\Classification\FullPathProcessor($objArtefacts);
	$objHost = new \T3census\Detection\Classification\HostOnlyProcessor($objFullPath);
	$objRequest = new \T3census\Detection\Classification\ExistingRequestsProcessor($objHost);
	$objRequest->process($context);
	unset($objRequest, $objHost);
}

/*
$objShortener = new \T3census\Detection\Identification\ShortenerRedirectOnlyProcessor();
$objShortener->process($context);
*/
print_r($context);
?>