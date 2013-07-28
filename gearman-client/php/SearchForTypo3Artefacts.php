<?php
$dir = dirname(__FILE__);
$libraryDir = realpath($dir . '/../../library/php');
$vendorDir = realpath($dir . '/../../vendor');

require_once $libraryDir . '/Bing/Api/ReverseIpLookup.php';
require_once $libraryDir . '/Bing/Scraper/ReverseIpLookup.php';
require_once $vendorDir . '/autoload.php';



$gearmanHost = '127.0.0.1';
$gearmanStatus = getGearmanServerStatus($gearmanHost);

# available
if (is_array($gearmanStatus)) {
	$mysqli = new mysqli("127.0.0.1", "t3census_dbu", "t3census", "t3census_db", 3306);

	// construct a client object
	$client= new GearmanClient();
	// add the default server
	$client->addServer($gearmanHost, 4730);

	$results = array();
	try {
		$objLookup = new T3census\Bing\Api\ReverseIpLookup();
		$objLookup->setAccountKey('')->setEndpoint('https://api.datamarket.azure.com/Bing/Search');
		#$results = $objLookup->setQuery('fileadmin/user_upload')->setOffset(0)->setMaxResults(500)->getResults();
		#$results = $objLookup->setQuery('showuid')->setOffset(0)->setMaxResults(500)->getResults();
		$results = $objLookup->setQuery('instreamset:(url):tx_ttnews')->setOffset(0)->setMaxResults(500)->getResults();
		unset($objLookup);
	} catch (\T3census\Bing\Api\Exception\ApiConsumeException $e) {
		$objLookup = new \T3census\Bing\Scraper\ReverseIpLookup();
		$objLookup->setEndpoint('http://www.bing.com/search');
		#$results = $objLookup->setQuery('fileadmin/user_upload')->setOffset(0)->setMaxResults(500)->getResults();
		#$results = $objLookup->setQuery('showuid')->setOffset(0)->setMaxResults(500)->getResults();
		$results = $objLookup->setQuery('instreamset:(url):tx_ttnews')->setOffset(0)->setMaxResults(500)->getResults();
		unset($objLookup);
	}

	foreach($results as $url) {
		$detectionResult = json_decode($client->do("TYPO3HostDetector", $url));

		if (is_object($detectionResult)) {
			if (is_null($detectionResult->port) || is_null($detectionResult->ip))  continue;

			$portId = getPortId($mysqli, $detectionResult->port);
			$serverId = getServerId($mysqli, $detectionResult->ip);
			persistServerPortMapping($mysqli, $serverId, $portId);

print_r($detectionResult);

			$result = $mysqli->query("SELECT 1 FROM host WHERE created IS NOT NULL AND host_name LIKE CONCAT('" . mysqli_real_escape_string($mysqli, $detectionResult->protocol) . "','" . mysqli_real_escape_string($mysqli, $detectionResult->host) . "') LIMIT 1;" );
			if ($result->num_rows == 0) {
				echo(PHP_EOL . 'persist');
				persistHost($mysqli, $serverId, $detectionResult);
			}
		}
	}

	mysqli_close($mysqli);
	echo(PHP_EOL);
}





function getGearmanServerStatus($host = '127.0.0.1', $port = 4730) {
	$status = null;

	$handle = fsockopen($host, $port, $errorNumber, $errorString, 30);
	if ($handle != null){
		fwrite($handle,"status\n");
		while (!feof($handle)) {
			$line = fgets($handle, 4096);
			if( $line==".\n"){
				break;
			}
			if( preg_match("~^(.*)[ \t](\d+)[ \t](\d+)[ \t](\d+)~",$line,$matches) ){
				$function = $matches[1];
				$status['operations'][$function] = array(
					'function' => $function,
					'total' => $matches[2],
					'running' => $matches[3],
					'connectedWorkers' => $matches[4],
				);
			}
		}
		fwrite($handle,"workers\n");
		while (!feof($handle)) {
			$line = fgets($handle, 4096);
			if( $line==".\n"){
				break;
			}
			// FD IP-ADDRESS CLIENT-ID : FUNCTION
			if( preg_match("~^(\d+)[ \t](.*?)[ \t](.*?) : ?(.*)~",$line,$matches) ){
				$fd = $matches[1];
				$status['connections'][$fd] = array(
					'fd' => $fd,
					'ip' => $matches[2],
					'id' => $matches[3],
					'function' => $matches[4],
				);
			}
		}
		fclose($handle);
	}

	return $status;
}

function getServerId($mysqli, $server) {
	$serverId = NULL;
	/* Select queries return a resultset */
	if ($result = $mysqli->query("SELECT server_id FROM server WHERE server_ip = INET_ATON('" . mysqli_real_escape_string($mysqli, $server) . "');" )) {

		if ($result->num_rows == 0) {
			$date = new DateTime();
			$foo = $mysqli->query("INSERT INTO server(server_ip,created) VALUES (INET_ATON('" . mysqli_real_escape_string($mysqli, $server) . "'), '" . $date->format('Y-m-d H:i:s') . "')");
			if (!$foo)  echo "error-2: (" . $mysqli->errno . ") " . $mysqli->error;
			$serverId = $mysqli->insert_id;
		} else {
			$row = $result->fetch_assoc();
			$serverId = intval($row['server_id']);
		}

		/* free result set */
		$result->close();
	}

	return $serverId;
}

function getPortId($mysqli, $port) {
	$portId = NULL;
	/* Select queries return a resultset */
	if ($result = $mysqli->query("SELECT port_id FROM port WHERE port_number=" . intval($port) . " LIMIT 1")) {

		if ($result->num_rows == 0) {
			$foo = $mysqli->query("INSERT INTO port(port_number) VALUES (" . intval($port) . ")");
			if (!$foo)  echo "error-1: (" . $mysqli->errno . ") " . $mysqli->error;
			$portId = $mysqli->insert_id;
		} else {
			$row = $result->fetch_assoc();
			$portId = intval($row['port_id']);
		}

		/* free result set */
		$result->close();
	}

	return $portId;
}

function persistServerPortMapping($mysqli, $serverId, $portId) {
	if ($result = $mysqli->query("SELECT fk_port_id FROM server_port WHERE fk_port_id = " . intval($portId) . " AND fk_server_id = " . intval($serverId))) {

		if ($result->num_rows == 0) {
			$foo = $mysqli->query("INSERT INTO server_port(fk_port_id,fk_server_id) VALUES (" . intval($portId) . ", " . intval($serverId) . ")");
			if (!$foo)  echo "error-3: (" . $mysqli->errno . ") " . $mysqli->error;
		}

		/* free result set */
		$result->close();
	}
}

function persistHost($objMysql, $serverId, $host) {
	$selectQuery = sprintf('SELECT host_id FROM host WHERE fk_server_id=%u AND host_subdomain LIKE %s AND host_domain LIKE \'%s\' LIMIT 1',
		$serverId,
		(is_null($host->subdomain) ? 'NULL' : '\'' . mysqli_real_escape_string($objMysql, $host->subdomain) . '\''),
		$host->registerableDomain
	);
	#fwrite(STDOUT, sprintf('DEBUG: Query: %s' . PHP_EOL, $selectQuery));
	$selectRes = $objMysql->query($selectQuery);

	if (is_object($selectRes)) {
		$date = new DateTime();

		if ($selectRes->num_rows == 0) {
			$insertQuery = sprintf('INSERT INTO host(typo3_installed,typo3_versionstring,host_name,host_scheme,host_subdomain,host_domain,host_suffix,host_path,created,fk_server_id) ' .
				'VALUES(%u,%s,NULL,\'%s\',%s,\'%s\',%s,%s,\'%s\',%u);',
				($host->TYPO3 ? 1 : 0),
				($host->TYPO3 && !empty($host->TYPO3version) ? '\'' . mysqli_real_escape_string($objMysql, $host->TYPO3version)  . '\'' : 'NULL'),
				mysqli_real_escape_string($objMysql, $host->scheme),
				(is_null($host->subdomain) ? 'NULL' : '\'' . mysqli_real_escape_string($objMysql,$host->subdomain) . '\''),
				$host->registerableDomain,
				(is_null($host->publicSuffix) ? 'NULL' : '\'' . mysqli_real_escape_string($objMysql,$host->publicSuffix) . '\''),
				(is_null($host->path) ? 'NULL' : '\'' . mysqli_real_escape_string($objMysql,$host->path) . '\''),
				$date->format('Y-m-d H:i:s'),
				$serverId
			);
			$insertResult = $objMysql->query($insertQuery);
			#fwrite(STDOUT, sprintf('DEBUG: Query: %s' . PHP_EOL, $insertQuery));
			if (!is_bool($insertResult) || !$insertResult) {
				fwrite(STDERR, sprintf('ERROR: %s (Errno: %u)' . PHP_EOL, $objMysql->error, $objMysql->errno));
			}
		} else {
			$row = $selectRes->fetch_assoc();

			$updateQuery = sprintf('UPDATE host SET typo3_installed=%u,typo3_versionstring=%s,host_name=NULL,host_scheme=\'%s\',host_subdomain=%s,host_domain=\'%s\',host_suffix=%s,host_path=%s,created=\'%s\' WHERE created IS NULL AND host_id=%u',
				($host->TYPO3 ? 1 : 0),
				($host->TYPO3 && !empty($host->TYPO3version) ? '\'' . mysqli_real_escape_string($objMysql, $host->TYPO3version)  . '\'' : 'NULL'),
				mysqli_real_escape_string($objMysql, $host->scheme),
				(is_null($host->subdomain) ? 'NULL' : '\'' . mysqli_real_escape_string($objMysql,$host->subdomain) . '\''),
				$host->registerableDomain,
				(is_null($host->publicSuffix) ? 'NULL' : '\'' . mysqli_real_escape_string($objMysql,$host->publicSuffix) . '\''),
				(is_null($host->path) ? 'NULL' : '\'' . mysqli_real_escape_string($objMysql,$host->path) . '\''),
				$date->format('Y-m-d H:i:s'),
				$row['host_id']
			);
			$updateResult= $objMysql->query($updateQuery);
			#fwrite(STDOUT, sprintf('DEBUG: Query: %s' . PHP_EOL, $updateQuery));
			if (!is_bool($updateResult) || !$updateResult) {
				fwrite(STDERR, sprintf('ERROR: %s (Errno: %u)' . PHP_EOL, $objMysql->error, $objMysql->errno));
			}
		}
		$selectRes->close();
	}
}
?>