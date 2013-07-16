<?php
set_error_handler('CliErrorHandler');


$dir = dirname(__FILE__);
$vendorDir = realpath($dir . '/../../vendor');

require_once $vendorDir . '/autoload.php';


$mysqli = @new mysqli('127.0.0.1', 'X', 'Y', 'Z', 3306);
if ($mysqli->connect_errno) {
	printf('ERROR: %s (Errno: %u)' . PHP_EOL, $mysqli->connect_error, $mysqli->connect_errno);
	die(1);
}

$isSuccessful = TRUE;
$selectQuery = 'SELECT host_id,host_name,host_domain FROM host WHERE typo3_installed=1 AND (host_scheme IS NULL OR host_domain IS NULL);';
$res = $mysqli->query($selectQuery);
fwrite(STDOUT, sprintf('DEBUG: Query: %s' . PHP_EOL, $selectQuery));

if (is_object($res = $mysqli->query($selectQuery))) {
	while ($row = $res->fetch_assoc()) {
		try {
			$objUrl = \Purl\Url::parse($row['host_name']);
			$result = array();
			$result['scheme'] = $objUrl->get('scheme');
			$result['host'] = $objUrl->get('host');
			$result['subdomain'] = $objUrl->get('subdomain');
			$result['registerableDomain'] = $objUrl->get('registerableDomain');
			$result['publicSuffix'] = $objUrl->get('publicSuffix');
		} catch (Exception $e) {
			printf('EXCEPTION: %s' . PHP_EOL, $e->getMessage());
			print_r($row);
			$isSuccessful = FALSE;
			break;
		}

		$updateQuery = sprintf('UPDATE host SET host_scheme=%s,host_subdomain=%s,host_domain=%s,host_suffix=%s,host_name=NULL WHERE host_id=%u;',
			(is_null($result['scheme']) ? NULL : '\'' . mysqli_real_escape_string($mysqli, $result['scheme']) . '\''),
			(is_null($result['subdomain']) ? 'NULL' : '\'' . mysqli_real_escape_string($mysqli, $result['subdomain']) . '\''),
			(is_null($result['registerableDomain']) ? NULL : '\'' . mysqli_real_escape_string($mysqli, $result['registerableDomain']) . '\''),
			(is_null($result['publicSuffix']) ? NULL : '\'' . mysqli_real_escape_string($mysqli, $result['publicSuffix']) . '\''),
			$row['host_id']);
		fwrite(STDOUT, sprintf('DEBUG: Query: %s' . PHP_EOL, $updateQuery));
		$updateResult= $mysqli->query($updateQuery);
		if (!is_bool($updateResult) || !$updateResult) {
			fwrite(STDERR, sprintf('ERROR: %s (Errno: %u)' . PHP_EOL, $mysqli->error, $mysqli->errno));
			$isSuccessful = FALSE;
			break;
		}
		unset($result, $objUrl);
	}

	mysqli_close($mysqli);
	echo(PHP_EOL);
} else {
	printf('ERROR: %s (Errno: %u)' . PHP_EOL, $mysqli->error, $mysqli->errno);
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