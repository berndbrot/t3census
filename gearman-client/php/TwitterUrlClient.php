<?php
require_once '../../gearman-worker/php/UrlNormalizer.php';


/**
 * Created by JetBrains PhpStorm.
 * User: marcus
 * Date: 26.05.13
 * Time: 23:28
 * To change this template use File | Settings | File Templates.
 */

// construct a client object
#$client= new GearmanClient();
// add the default server
#$client->addServer();

// print the output of the job. first parameter is the job name, second one is the parameter
#print $client->do("process_string", "Hello Gearman!");

$gearmanHost = '127.0.0.1';

$gearmanStatus = getStatus($gearmanHost);

# available
if (is_array($gearmanStatus)) {

	// construct a client object
	$client= new GearmanClient();
	// add the default server
	$client->addServer($gearmanHost, 4730);

	$mysqli = new mysqli("127.0.0.1", "X", "Y", "Z", 3306);

#	if ($res = $mysqli->query("SELECT t.tweet_id,t.created,t.tweet_processed,u.url_text FROM twitter_tweet t JOIN twitter_url u ON (t.tweet_id = u.fk_tweet_id) WHERE t.tweet_processed = FALSE AND t.tweet_text LIKE '%to TYPO3%' ORDER BY t.created ASC")) {
#	if ($res = $mysqli->query("SELECT t.tweet_id,t.created,t.tweet_processed,u.url_text FROM twitter_tweet t JOIN twitter_url u ON (t.tweet_id = u.fk_tweet_id) WHERE t.tweet_processed = FALSE AND t.tweet_text LIKE '%TYPO3%' ORDER BY t.created ASC")) {
	if ($res = $mysqli->query("SELECT t.tweet_id,t.created,t.tweet_processed,u.url_text FROM twitter_tweet t JOIN twitter_url u ON (t.tweet_id = u.fk_tweet_id) WHERE t.tweet_processed = FALSE ORDER BY t.created ASC LIMIT 300")) {
		while ($row = $res->fetch_assoc()) {
#var_dump($row);
echo(PHP_EOL . $row['url_text'] . PHP_EOL);

			$normalizer = new UrlNormalizer();
			$urlInfo = $normalizer->setOriginUrl($row['url_text'])->getNormalizedUrl();
#var_dump($urlInfo);

			if (0 !== strcmp($urlInfo['host'], 'bit.ly')
					&& 0 !== strcmp($urlInfo['host'], 'fb.me')
					&& 0 !== strcmp($urlInfo['host'], 'is.gd')
					&& 0 !== strcmp($urlInfo['host'], 'j.mp')
					&& 0 !== strcmp($urlInfo['host'], 'kck.st')
					&& 0 !== strcmp($urlInfo['host'], 'npub.li')
					&& 0 !== strcmp($urlInfo['host'], 'ow.ly')
					&& 0 !== strcmp($urlInfo['host'], 't3n.me')
					&& 0 !== strcmp($urlInfo['host'], 'tinyurl.com')
					&& 0 !== strcmp($urlInfo['host'], 'xing.com')) {
				$result = $mysqli->query("SELECT 1 FROM host WHERE created IS NOT NULL AND host_name LIKE CONCAT('%','" . mysqli_real_escape_string($mysqli, $urlInfo['host']) . "') LIMIT 1;" );
				if ($result->num_rows > 0) {
					echo(' - PASS');
					$result->close();
					$mysqli->query("UPDATE twitter_tweet SET tweet_processed = 1 WHERE tweet_id = " . intval($row['tweet_id']));
					continue;
				}
				$result->close();
			}


			$detectionResult = json_decode($client->do("TYPO3HostDetector", $row['url_text']));
#var_dump($detectionResult);

			if (is_object($detectionResult)) {
				if (is_null($detectionResult->port) || is_null($detectionResult->ip))  continue;

				$portId = getPortId($mysqli, $detectionResult->port);

				$serverId = getServerId($mysqli, $detectionResult->ip);

				persistServerPortMapping($mysqli, $serverId, $portId);
				persistHost($mysqli, $serverId, $detectionResult);

				$mysqli->query("UPDATE twitter_tweet SET tweet_processed = 1 WHERE tweet_id = " . intval($row['tweet_id']));
			} else if (is_bool($detectionResult) && !$detectionResult) {
				$mysqli->query("UPDATE twitter_tweet SET tweet_processed = 1 WHERE tweet_id = " . intval($row['tweet_id']));
			}
		}
		$res->close();

		// print the output of the job. first parameter is the job name, second one is the parameter
		#print $client->do("process_string", "Hello Gearman!");
	}

	mysqli_close($mysqli);
	echo(PHP_EOL);
}

function persistHost($mysqli, $serverId, $host) {
	#echo("SELECT host_id FROM host WHERE fk_server_id = " . intval($serverId) . " AND host_name = '" . mysqli_real_escape_string($mysqli, $host->protocol . $host->host) . "';");
	if ($result = $mysqli->query("SELECT host_id FROM host WHERE fk_server_id = " . intval($serverId) . " AND host_name = '" . mysqli_real_escape_string($mysqli, $host->protocol . $host->host) . "';" )) {

		$date = new DateTime();
		if ($result->num_rows == 0) {
			$foo1 = $mysqli->query("INSERT INTO host(host_name,host_domain,fk_server_id,typo3_installed,host_path,typo3_versionstring,created) "
				. "VALUES ('" . mysqli_real_escape_string($mysqli, $host->protocol . $host->host) . "',"
				. "'" . mysqli_real_escape_string($mysqli, $host->host) . "',"
				. $serverId . ","
				. ($host->TYPO3 ? 1 : 0) . ","
				. "'" . mysqli_real_escape_string($mysqli, $host->path) . "',"
				. ($host->TYPO3 && !empty($host->TYPO3version) ? "'" . mysqli_real_escape_string($mysqli, $host->TYPO3version)  . "'" : 'NULL') . ","
				. "'" . $date->format('Y-m-d H:i:s') . "');");
			if (!$foo1)  echo "error-4: (" . $mysqli->errno . ") " . $mysqli->error;
		} else {
			$row = $result->fetch_assoc();
			$hostId = intval($row['host_id']);
			$foo2 = $mysqli->query("UPDATE host SET "
				. "typo3_installed=" . ($host->TYPO3 ? 1 : 0) . ","
				. "typo3_versionstring=" . ($host->TYPO3 && !empty($host->TYPO3version) ? "'" . mysqli_real_escape_string($mysqli, $host->TYPO3version)  . "'" : 'NULL') . ","
				. "host_path=" . "'" . mysqli_real_escape_string($mysqli, $host->path) . "',"
				. "created=" . "'" . $date->format('Y-m-d H:i:s') . "'"
				. " WHERE created IS NULL AND host_id=" .$hostId);
			if (!$foo2)  echo "error-4: (" . $mysqli->errno . ") " . $mysqli->error;
			echo (PHP_EOL . 'UPDATE');
		}

		/* free result set */
		$result->close();
	}
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

/**
 * @return array | null
 */
function getStatus($host = '127.0.0.1', $port = 4730) {
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

?>