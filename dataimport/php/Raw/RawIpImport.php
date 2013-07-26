<?php
set_error_handler('CliErrorHandler');


$dir = dirname(__FILE__);
$libraryDir = realpath($dir . '/../../../library/php');
$vendorDir = realpath($dir . '/../../../vendor');


$filename = 'rawIPs.txt';
if (!file_exists(realpath($dir . '/' . $filename)) || !is_readable(realpath($dir . '/' . $filename))) {
	fwrite(STDERR, sprintf('ERROR: File %s not accessible' . PHP_EOL, $filename));
	die(1);
}

$mysqli = @new mysqli('127.0.0.1', '', '', '', 3306);
if ($mysqli->connect_errno) {
	fwrite(STDERR, sprintf('ERROR: Database-Server: %s (Errno: %u)' . PHP_EOL, $mysqli->connect_error, $mysqli->connect_errno));
	die(1);
}


$isSuccessful = TRUE;

if ($fh = fopen(realpath($dir . '/' . $filename), 'r')) {
	$date = new DateTime();

	while ( !feof($fh) ) {
		$ipAddress = trim(fgets($fh));
		fwrite(STDOUT, $ipAddress . PHP_EOL);

		$insertQuery = sprintf('INSERT INTO server(server_ip,created) VALUES (INET_ATON(\'%s\'),\'%s\');',
			$ipAddress,
			$date->format('Y-m-d H:i:s')
		);
		#fwrite(STDOUT, sprintf('DEBUG: Query: %s' . PHP_EOL, $insertQuery));
		$insertResult = $mysqli->query($insertQuery);
		if (!is_bool($insertResult) || !$insertResult) {
			fwrite(STDERR, sprintf('ERROR: %s (Errno: %u)' . PHP_EOL, $mysqli->error, $mysqli->errno));
		}
	}
	fclose($fh);
}

mysqli_close($mysqli);
fwrite(STDOUT, PHP_EOL);


if (is_bool($isSuccessful) && $isSuccessful) {
	exit(0);
} else {
	die(1);
}



function CliErrorHandler($errno, $errstr, $errfile, $errline) {
	fwrite(STDERR, $errstr . ' in ' . $errfile . ' on ' . $errline . PHP_EOL);
}

?>