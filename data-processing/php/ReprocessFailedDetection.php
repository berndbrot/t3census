<?php
set_error_handler('CliErrorHandler');

$dir = dirname(__FILE__);
$libraryDir = realpath($dir . '/../../library/php');
$vendorDir = realpath($dir . '/../../vendor');

require_once $libraryDir . '/Gearman/Serverstatus.php';
require_once $vendorDir . '/autoload.php';

$mysqli = @new mysqli('127.0.0.1', 'X', 'Y', 'Z', 3306);
if ($mysqli->connect_errno) {
	fwrite(STDERR, sprintf('ERROR: Database-Server: %s (Errno: %u)' . PHP_EOL, $mysqli->connect_error, $mysqli->connect_errno));
	die(1);
}

$gearmanHost = '127.0.0.1';
$gearmanPort = 4730;
$gearmanFunction = 'TYPO3HostDetector';

try {
	$gearmanStatus = new T3census\Gearman\Serverstatus();
	$gearmanStatus->setHost($gearmanHost)->setPort($gearmanPort);
	$gearmanStatus->poll();

	if (!$gearmanStatus->hasFunction($gearmanFunction)) {
		fwrite(STDERR, sprintf('ERROR: Job-Server: Requested function %s not available (Errno: %u)' . PHP_EOL, $gearmanFunction, 1373751780));
		die(1);
	}
	if (!$gearmanStatus->getNumberOfWorkersByFunction($gearmanFunction) > 0) {
		fwrite(STDERR, sprintf('ERROR: Job-Server: No workers for function %s available (Errno: %u)' . PHP_EOL, $gearmanFunction, 1373751783));
		die(1);
	}
} catch (GearmanException $e) {
	fwrite(STDERR, sprintf('ERROR: Job-Server: %s (Errno: %u)' . PHP_EOL, $e->getMessage(), $e->getCode()));
	die(1);
}
unset($gearmanStatus);


$isSuccessful = TRUE;
$selectQuery = 'SELECT * FROM host WHERE typo3_installed=1 AND typo3_versionstring IS NULL;';
$res = $mysqli->query($selectQuery);
fwrite(STDOUT, sprintf('DEBUG: Query: %s' . PHP_EOL, $selectQuery));

if (is_object($res)) {

	if ($mysqli->affected_rows > 0) {

		$date = new DateTime();
		$client= new GearmanClient();
		$client->addServer($gearmanHost, $gearmanPort);

		while ($row = $res->fetch_assoc()) {
			$url = '';
			$url .= $row['host_scheme'] . '://';
			$url .= (is_null($row['host_subdomain']) ? '' : $row['host_subdomain'] . '.');
			$url .= $row['host_domain'];
			$url .= (is_null($row['host_path']) ? '' : '/' . ltrim($row['host_path'], '/'));
			#fwrite(STDOUT, $url . PHP_EOL);

			$detectionResult = json_decode($client->doNormal($gearmanFunction, $url));
			if (is_object($detectionResult)) {

				if (!property_exists($detectionResult, 'TYPO3version')) {
					#print_r($row);
					fwrite(STDOUT, $url . PHP_EOL);
					#print_r($detectionResult);
					break;
				}

				if (property_exists($detectionResult, 'TYPO3') && is_bool($detectionResult->TYPO3) && !$detectionResult->TYPO3) {
					#print_r($row);
					fwrite(STDOUT, 'NO TYPO3: ' .$url . PHP_EOL);
					#print_r($detectionResult);
					break;
				}

				if (property_exists($detectionResult, 'TYPO3version') && is_string($detectionResult->TYPO3version)) {
					#print_r($row);
					#fwrite(STDOUT, $url . PHP_EOL);
					#print_r($detectionResult);

					$updateQuery = sprintf('UPDATE host SET typo3_versionstring=%s,host_path=%s,updated=\'%s\' WHERE host_id=%u;',
						(is_null($detectionResult->TYPO3version) ? 'NULL' : '\'' . mysqli_real_escape_string($mysqli, $detectionResult->TYPO3version) . '\''),
						(is_null($detectionResult->path) ? 'NULL' : '\'' . mysqli_real_escape_string($mysqli, $detectionResult->path) . '\''),
						$date->format('Y-m-d H:i:s'),
						$row['host_id']
					);
					fwrite(STDOUT, sprintf('DEBUG: Query: %s' . PHP_EOL, $updateQuery));
					$updateResult= $mysqli->query($updateQuery);
					if (!is_bool($updateResult) || !$updateResult) {
						fwrite(STDERR, sprintf('ERROR: %s (Errno: %u)' . PHP_EOL, $mysqli->error, $mysqli->errno));
						$isSuccessful = FALSE;
						break;
					}
				}

			}

			unset($detectionResult);
		}
	}

	mysqli_close($mysqli);
	fwrite(STDOUT, PHP_EOL);
} else {
	fwrite(STDERR, sprintf('ERROR: %s (Errno: %u)' . PHP_EOL, $mysqli->error, $mysqli->errno));
	$isSuccessful = FALSE;
}

if (is_bool($isSuccessful) && $isSuccessful) {
	exit(0);
} else {
	die(1);
}


function CliErrorHandler($errno, $errstr, $errfile, $errline) {
	fwrite(STDERR, $errstr . ' in ' . $errfile . ' on ' . $errline . PHP_EOL);
}
?>