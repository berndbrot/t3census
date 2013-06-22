<?php
/**
 * Created by JetBrains PhpStorm.
 * User: marcus
 * Date: 29.05.13
 * Time: 22:52
 * To change this template use File | Settings | File Templates.
 */

// Encode the credentials and create the stream context.
$acctKey = '';
$rootUri = 'https://api.datamarket.azure.com/Bing/Search';

https://api.datamarket.azure.com/Bing/Search/v1/Composite?Sources=%27web%27&Query=%27ip%3A139.174.2.16%27
$query = urlencode('\'ip:139.174.2.16\'');
$requestUri = $rootUri . '/Web?$format=json&Query=' . $query;

$auth = base64_encode("$acctKey:$acctKey");

$data = array(

	'http' => array(

		'request_fulluri' => true,

// ignore_errors can help debug – remove for production. This option added in PHP 5.2.10

		'ignore_errors' => FALSE,

		'header' => "Authorization: Basic $auth")

);

$context = stream_context_create($data);

// Get the response from Bing.

$response = file_get_contents($requestUri, 0, $context);
$jsonObj = json_decode($response);
#var_dump($jsonObj->d->results);
#var_dump(get_object_vars($jsonObj->d));
var_dump($jsonObj->d->__next);

foreach($jsonObj->d->results as $value) {
	switch ($value->__metadata->type) {
		case 'WebResult':
			#echo(PHP_EOL . PHP_EOL . $value->Title);
			#echo(PHP_EOL . $value->Description);
			echo(PHP_EOL . $value->Url);
			#$resultStr .= "<a href=\"{$value->Url}\">{$value->Title}</a><p>{$value->Description}</p>";
			break;
		case 'ImageResult':
			$resultStr .= "<h4>{$value->Title} ({$value->Width}x{$value->Height}) " . "{$value->FileSize} bytes)</h4>" . "<a href=\"{$value->MediaUrl}\">" . "<img src=\"{$value->Thumbnail->MediaUrl}\"></a><br />";
			break;
	}
}


?>
