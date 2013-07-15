<?php
set_error_handler('CliErrorHandler');


$dir = dirname(__FILE__);
$libraryDir = realpath($dir . '/../../../library/php');
$vendorDir = realpath($dir . '/../../../vendor');

require_once $libraryDir . '/Twitter/Exception/AuthenticationException.php';
require_once $libraryDir . '/Twitter/Exception/UserInvalidException.php';
require_once $libraryDir . '/Twitter/Exception/GeneralException.php';
require_once $vendorDir . '/autoload.php';


$mysqli = @new mysqli('127.0.0.1', '', '', '', 3306);
if ($mysqli->connect_errno) {
	fwrite(STDERR, sprintf('ERROR: Database-Server: %s (Errno: %u)' . PHP_EOL, $mysqli->connect_error, $mysqli->connect_errno));
	die(1);
}

$twitterAuthData = array(
	'consumer_key'    => 'W',
	'consumer_secret' => 'X',
	'token'           => 'Y',
	'secret'          => 'Z',
);


$isSuccessful = TRUE;
$isSuccessful = processNewSubscribedUsers($mysqli, $twitterAuthData);
sleep(5);
if (is_bool($isSuccessful) && $isSuccessful)  processExistingSubscribedUsers($mysqli, $twitterAuthData);
sleep(5);
if (is_bool($isSuccessful) && $isSuccessful)  processSearchQueries($mysqli, $twitterAuthData);

mysqli_close($mysqli);
fwrite(STDOUT, PHP_EOL);


if (is_bool($isSuccessful) && $isSuccessful) {
	exit(0);
} else {
	die(1);
}




function processNewSubscribedUsers($objMysql, $twitterAuthData) {
	$isSuccessful = TRUE;
	$selectQuery = 'SELECT user.user_id,user.user_name,user.twitter_id,COUNT(url.url_id) num_urls '
				 . 'FROM twitter_user user LEFT JOIN twitter_tweet t ON (user.user_id = t.fk_user_id) LEFT JOIN twitter_url url ON (t.tweet_id = url.fk_tweet_id) '
				 . 'WHERE user.subscribed=1 '
				 . 'GROUP BY user.user_id HAVING num_urls=0';
	$res = $objMysql->query($selectQuery);
	#fwrite(STDOUT, sprintf('DEBUG: Query: %s' . PHP_EOL, $selectQuery));

	if (is_object($res)) {
		if ($res->num_rows > 0) {
			while ($row = $res->fetch_assoc()) {
				fwrite(STDOUT, sprintf('DEBUG: process new user %s' . PHP_EOL, $row['user_name']));
				try {
					$result = processUserUrls($row['user_name'], intval($row['user_id']), intval($row['twitter_id']), $objMysql, $twitterAuthData);
					fwrite(STDOUT, sprintf('DEBUG: imported %u tweets and %u urls' . PHP_EOL, $result['num_tweet_import'], $result['num_url_import']));
				} catch (T3census\Twitter\Exception\AuthenticationException $e) {
					printf('EXCEPTION: %s' . PHP_EOL, $e->getMessage());
					$isSuccessful = FALSE;
					break;
				} catch (\T3census\Twitter\Exception\GeneralException $e) {
					printf('EXCEPTION: %s' . PHP_EOL, $e->getMessage());
					$isSuccessful = FALSE;
					break;
				} catch (\T3census\Twitter\Exception\UserInvalidException $e) {
					fwrite(STDOUT, sprintf('DEBUG: user %s is invalid' . PHP_EOL, $row['user_name']));
					continue;
				}
				sleep(1);
			}
		}
		$res->close();
	}

	return $isSuccessful;
}

function processExistingSubscribedUsers($objMysql, $twitterAuthData) {
	$isSuccessful = TRUE;

	$selectQuery = 'SELECT user.user_id,user.user_name,user.twitter_id, max(t.twitter_id) AS max '
				 . 'FROM twitter_user user LEFT JOIN twitter_tweet t ON (user.user_id = t.fk_user_id) LEFT JOIN twitter_url url ON (t.tweet_id = url.fk_tweet_id) '
				 . 'WHERE user.subscribed=1 '
				 . 'GROUP BY user.user_id';
	$res = $objMysql->query($selectQuery);
	#fwrite(STDOUT, sprintf('DEBUG: Query: %s' . PHP_EOL, $selectQuery));

	if (is_object($res)) {
		if ($res->num_rows > 0) {
			while ($row = $res->fetch_assoc()) {
				fwrite(STDOUT, sprintf('DEBUG: process existing user %s' . PHP_EOL, $row['user_name']));
				$since = (is_null($row['max']) ? NULL : intval($row['max']));
				try {
					$result = processUserUrls($row['user_name'], intval($row['user_id']), intval($row['twitter_id']), $objMysql, $twitterAuthData, $since);
					fwrite(STDOUT, sprintf('DEBUG: imported %u tweets and %u urls' . PHP_EOL, $result['num_tweet_import'], $result['num_url_import']));
				} catch (T3census\Twitter\Exception\AuthenticationException $e) {
					printf('EXCEPTION: %s' . PHP_EOL, $e->getMessage());
					$isSuccessful = FALSE;
					break;
				} catch (\T3census\Twitter\Exception\GeneralException $e) {
					printf('EXCEPTION: %s' . PHP_EOL, $e->getMessage());
					$isSuccessful = FALSE;
					break;
				} catch (\T3census\Twitter\Exception\UserInvalidException $e) {
					fwrite(STDOUT, sprintf('DEBUG: user %s is invalid' . PHP_EOL, $row['user_name']));
					continue;
				}
				sleep(1);
			}
		}
		$res->close();
	}

	return $isSuccessful;
}

function processSearchQueries($objMysql, $twitterAuthData) {
	$isSuccessful = TRUE;

	$selectQuery = 'SELECT user.user_id,user.user_name,user.twitter_id, max(t.twitter_id) AS max '
				 . 'FROM twitter_user user LEFT JOIN twitter_tweet t ON (user.user_id = t.fk_user_id) '
				 . 'LEFT JOIN twitter_url url ON (t.tweet_id = url.fk_tweet_id) '
				 . 'WHERE user.subscribed=0;';
	$res = $objMysql->query($selectQuery);
	#fwrite(STDOUT, sprintf('DEBUG: Query: %s' . PHP_EOL, $selectQuery));

	if (is_object($res)) {
		if ($res->num_rows > 0) {
			$row = $res->fetch_assoc();
			try {
				$result = processSearchUrls(array('TYPO3'), $objMysql, $twitterAuthData, intval($row['max']));
				fwrite(STDOUT, sprintf('DEBUG: imported %u tweets and %u urls' . PHP_EOL, $result['num_tweet_import'], $result['num_url_import']));
			} catch (T3census\Twitter\Exception\AuthenticationException $e) {
				printf('EXCEPTION: %s' . PHP_EOL, $e->getMessage());
				$isSuccessful = FALSE;
			} catch (\T3census\Twitter\Exception\GeneralException $e) {
				printf('EXCEPTION: %s' . PHP_EOL, $e->getMessage());
				$isSuccessful = FALSE;
			} catch (\T3census\Twitter\Exception\UserInvalidException $e) {
				fwrite(STDOUT, sprintf('DEBUG: user %s is invalid' . PHP_EOL, $row['user_name']));
			}
		}
		$res->close();
	}

	return $isSuccessful;
}

function processUserUrls($user_name, $user_id, $twitter_id, $objMysql, $twitterAuthData, $since = NULL, $max = NULL, $status = array()) {
	if (!array_key_exists('user_name', $status)) {
		$status['user_name'] = $user_name;
		$status['twitter_id'] = $twitter_id;
		$status['num_tweet_import'] = 0;
		$status['num_url_import'] = 0;
	}

	$requestData = array(
		'screen_name' => $user_name,
		'count' => 200,
	);
	if (!is_null($since) && is_int($since)) {
		$requestData['since_id'] = strval($since);
	}
	if (!is_null($max) && is_int($max)) {
		$requestData['max_id'] = strval($max);
	}

	$objTwitter = new tmhOAuth($twitterAuthData);
	$code = $objTwitter->request('GET', $objTwitter->url('/1.1/statuses/user_timeline.json'), $requestData);
	$response = json_decode($objTwitter->response['response']);

	if ($code === 200) {
		$oldestTwitterId = 0;
		if (is_array($response)) {
			$numTweets = 0;
			$numUrls = 0;
			$lastUrl = '';
			foreach ($response as $rawTweet) {
				if (!is_array($rawTweet->entities->urls) || count($rawTweet->entities->urls) == 0)  continue;

				if (is_int($status['twitter_id']) && $status['twitter_id'] === 0) {
					$updateQuery = sprintf('UPDATE twitter_user SET twitter_id=%u WHERE user_name LIKE \'%s\';',
						$rawTweet->user->id,
						mysqli_real_escape_string($objMysql, $user_name)
					);
					$updateResult= $objMysql->query($updateQuery);
					#fwrite(STDOUT, sprintf('DEBUG: Query: %s' . PHP_EOL, $updateQuery));
					if (!is_bool($updateResult) || !$updateResult) {
						fwrite(STDERR, sprintf('ERROR: %s (Errno: %u)' . PHP_EOL, $objMysql->error, $objMysql->errno));
						break;
					}
				}

				$insertQuery = sprintf('INSERT INTO twitter_tweet(tweet_text,twitter_id,tweet_processed,created,fk_user_id) VALUES(\'%s\',%u,FALSE,\'%s\',%u);',
					mysqli_real_escape_string($objMysql, $rawTweet->text),
					intval($rawTweet->id),
					DateTime::createFromFormat('D M j H:i:s O Y', $rawTweet->created_at)->format('Y-m-d H:i:s'),
					$user_id
				);
				$insertResult = $objMysql->query($insertQuery);
				#fwrite(STDOUT, sprintf('DEBUG: Query: %s' . PHP_EOL, $insertQuery));
				if (!is_bool($insertResult) || !$insertResult) {
					fwrite(STDERR, sprintf('ERROR: %s (Errno: %u)' . PHP_EOL, $objMysql->error, $objMysql->errno));
					break;
				}

				$tweetId = $objMysql->insert_id;
				$urls = $rawTweet->entities->urls;
				$lastUrl = '';
				foreach($urls as $url) {
					if ($lastUrl === $url->expanded_url)  continue;

					$insertQuery = sprintf('INSERT INTO twitter_url(url_text,fk_tweet_id) VALUES(\'%s\',%u);',
						mysqli_real_escape_string($objMysql, $url->expanded_url),
						$tweetId
					);
					$insertResult = $objMysql->query($insertQuery);
					#fwrite(STDOUT, sprintf('DEBUG: Query: %s' . PHP_EOL, $insertQuery));
					if (!is_bool($insertResult) || !$insertResult) {
						fwrite(STDOUT, sprintf('DEBUG: Query: %s' . PHP_EOL, $insertQuery));
						fwrite(STDERR, sprintf('ERROR: %s (Errno: %u)' . PHP_EOL, $objMysql->error, $objMysql->errno));
						break;
					}

					$lastUrl = $url->expanded_url;
					$numUrls += 1;
				}

				$oldestTwitterId = $rawTweet->id;
				$numTweets += 1;
			}

			$status['num_tweet_import'] += $numTweets;
			$status['num_url_import'] += $numUrls;

			#fwrite(STDOUT, sprintf('OLDEST Twitter ID: %u' . PHP_EOL, $oldestTwitterId));
			if (count($response) > 0 && (is_null($since) || (!empty($lastUrl) && $since < $oldestTwitterId)) && $oldestTwitterId !== $max) {
				unset($response);
				$status = processUserUrls($user_name, $twitter_id, $objMysql, $twitterAuthData, $since, $oldestTwitterId, $status);
			} else {
				unset($response);
			}
		}
	} else {
		handleTwitterErrors($code, $response->errors);
	}
	unset($objTwitter);

	return ($status);
}

function processSearchUrls($queries, $objMysql, $twitterAuthData, $since = NULL, $max = NULL, $status = array()) {
	if (!array_key_exists('num_tweet_import', $status)) {
		$status['num_tweet_import'] = 0;
		$status['num_url_import'] = 0;
	}

	if (is_array($queries) && count($queries) > 0) {
		foreach ($queries as $query) {
			$status = processSearchUrls($query, $objMysql, $twitterAuthData, $since, $max, $status);
		}
	} else {
		$requestData = array(
			'q' => $queries,
			'count' => 200,
		);
		if (!is_null($since) && is_int($since)) {
			$requestData['since_id'] = strval($since);
		}
		if (!is_null($max) && is_int($max)) {
			$requestData['max_id'] = strval($max);
		}

		$objTwitter = new tmhOAuth($twitterAuthData);
		$code = $objTwitter->request('GET', $objTwitter->url('1.1/search/tweets.json'), $requestData);
		$response = json_decode($objTwitter->response['response']);

		if ($code === 200) {
			$oldestTwitterId = 0;
			if (is_array($response->statuses)) {
				$numTweets = 0;
				$numUrls = 0;
				$lastUrl = '';
				foreach ($response->statuses as $rawTweet) {
					if (!is_array($rawTweet->entities->urls) || count($rawTweet->entities->urls) == 0)  continue;

					$twitterUser = array(
						'id'   => $rawTweet->user->id,
						'name' => $rawTweet->user->screen_name
					);

					$user_id = getTwitterUserId($objMysql, $twitterUser);
					$insertQuery = sprintf('INSERT INTO twitter_tweet(tweet_text,twitter_id,tweet_processed,created,fk_user_id) VALUES(\'%s\',%u,FALSE,\'%s\',%u);',
						mysqli_real_escape_string($objMysql, $rawTweet->text),
						intval($rawTweet->id),
						DateTime::createFromFormat('D M j H:i:s O Y', $rawTweet->created_at)->format('Y-m-d H:i:s'),
						$user_id
					);
					$insertResult = $objMysql->query($insertQuery);
					#fwrite(STDOUT, sprintf('DEBUG: Query: %s' . PHP_EOL, $insertQuery));
					if (!is_bool($insertResult) || !$insertResult) {
						fwrite(STDERR, sprintf('ERROR: %s (Errno: %u)' . PHP_EOL, $objMysql->error, $objMysql->errno));
						break;
					}


					$tweetId = $objMysql->insert_id;
					$urls = $rawTweet->entities->urls;
					$lastUrl = '';
					foreach($urls as $url) {
						if ($lastUrl === $url->expanded_url)  continue;

						$insertQuery = sprintf('INSERT INTO twitter_url(url_text,fk_tweet_id) VALUES(\'%s\',%u);',
							mysqli_real_escape_string($objMysql, $url->expanded_url),
							$tweetId
						);
						$insertResult = $objMysql->query($insertQuery);
						#fwrite(STDOUT, sprintf('DEBUG: Query: %s' . PHP_EOL, $insertQuery));
						if (!is_bool($insertResult) || !$insertResult) {
							fwrite(STDOUT, sprintf('DEBUG: Query: %s' . PHP_EOL, $insertQuery));
							fwrite(STDERR, sprintf('ERROR: %s (Errno: %u)' . PHP_EOL, $objMysql->error, $objMysql->errno));
							break;
						}

						$lastUrl = $url->expanded_url;
						$numUrls += 1;
					}

					$oldestTwitterId = $rawTweet->id;
					$numTweets += 1;

				}

				$status['num_tweet_import'] += $numTweets;
				$status['num_url_import'] += $numUrls;

				#fwrite(STDOUT, sprintf('OLDEST Twitter ID: %u' . PHP_EOL, $oldestTwitterId));
				if (count($response->statuses) > 0 && (is_null($since) || (!empty($lastUrl) && $since < $oldestTwitterId)) && $oldestTwitterId !== $max) {
					unset($response);
					$status = processSearchUrls($queries, $objMysql, $objTwitter, $since, $oldestTwitterId, $status);
				} else {
					unset($response);
				}
			}
		} else {
			handleTwitterErrors($code, $response->errors);
		}
		unset($objTwitter);
	}

	return ($status);
}

function getTwitterUserId($objMysql, $twitterUser) {
	$twitterUserId = NULL;

	$selectQuery = sprintf('SELECT user_id FROM twitter_user WHERE twitter_id=%u', $twitterUser['id']);
	$res = $objMysql->query($selectQuery);
	#fwrite(STDOUT, sprintf('DEBUG: Query: %s' . PHP_EOL, $selectQuery));

	if (is_object($res)) {
		if ($res->num_rows == 0) {
			$insertQuery = sprintf('INSERT INTO twitter_user(user_name,twitter_id) VALUES (\'%s\',%u);',
				mysqli_real_escape_string($objMysql, $twitterUser['name']),
				$twitterUser['id']
			);
			$insertResult = $objMysql->query($insertQuery);
			#fwrite(STDOUT, sprintf('DEBUG: Query: %s' . PHP_EOL, $insertQuery));
			if (!is_bool($insertResult) || !$insertResult) {
				fwrite(STDOUT, sprintf('DEBUG: Query: %s' . PHP_EOL, $insertQuery));
				fwrite(STDERR, sprintf('ERROR: %s (Errno: %u)' . PHP_EOL, $objMysql->error, $objMysql->errno));
			}

			$twitterUserId = $objMysql->insert_id;
		} else {
			$row = $res->fetch_assoc();
			$twitterUserId = intval($row['user_id']);
		}
		$res->close();
	}

	return $twitterUserId;
}

function handleTwitterErrors($code, $errors) {
	switch ($code) {
		case 400:
		case 401:
			$error = array_shift($errors);
			throw new \T3census\Twitter\Exception\AuthenticationException($error->message, intval($error->code));
			break;
		case 404:
			$error = array_shift($errors);
			throw new \T3census\Twitter\Exception\UserInvalidException($error->message, intval($error->code));
			break;
		default:
			$error = array_shift($errors);
			throw new \T3census\Twitter\Exception\GeneralException($error->message, intval($error->code));
	}
}

function CliErrorHandler($errno, $errstr, $errfile, $errline) {
	fwrite(STDERR, $errstr . ' in ' . $errfile . ' on ' . $errline . PHP_EOL);
}

?>