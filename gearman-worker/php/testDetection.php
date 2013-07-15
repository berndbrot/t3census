<?php
$dir = dirname(__FILE__);
$libraryDir = realpath($dir . '/../../library/php');
$vendorDir = realpath($dir . '/../../vendor');

require_once $libraryDir . '/Detection/Context.php';
require_once $libraryDir . '/Detection/Request.php';
require_once $libraryDir . '/Detection/Identification/HostOnlyProcessor.php';
require_once $libraryDir . '/Detection/Identification/FullPathProcessor.php';

/*
$foo = new \T3census\Detection\Context();
$bar = new \T3census\Detection\Request();
$foo->addRequest($bar);
print_r($foo->getRequest());
*/


$context = new \T3census\Detection\Context();
$context->setUrl('http://www.typovision.de/de/agentur/aktivitaeten/');
$context->setUrl('http://kamerakind.net/');
$context->setUrl('http://www.typovision.de/de/home/');


$objPathRedirect = new \T3census\Detection\Identification\FullPathProcessor(NULL, TRUE);
$objPathNoRedirect = new \T3census\Detection\Identification\FullPathProcessor($objPathRedirect, FALSE);
$objHostRedirect = new \T3census\Detection\Identification\HostOnlyProcessor($objPathNoRedirect, TRUE);
$objHostNoRedirect = new \T3census\Detection\Identification\HostOnlyProcessor($objHostRedirect, FALSE);
$objHostNoRedirect->process($context);
/*
$objHostRedirect = new \T3census\Detection\Identification\HostOnlyProcessor(NULL, TRUE);
$objHostRedirect->process($context);
*/
print_r($context);
?>