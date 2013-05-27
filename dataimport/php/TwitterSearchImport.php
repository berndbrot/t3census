<?php
require_once 'library/tmhOAuth/tmhOAuth.php';

$tmhOAuth = new tmhOAuth(array(
	'consumer_key'    => 'fRISmI7Xpc4IfjhzWNVGRQ',
	'consumer_secret' => 'l28EvcfTAYAHky5SLftODkAmmnsDrfSox2mrEbm2M',
	'user_token'      => '62495551-A0ZuNCJnYYaVCffOyjdeImISgkGT30RXFhoSmWaSr',
	'user_secret'     => 'GHwrOiRyvLn2UvtSmgVfDjXJ8AyxpdwzOhUUUusNrE',
));


$code = $tmhOAuth->request('GET', $tmhOAuth->url('1.1/search/tweets.json'), array(
	'count' => 180,
	'q' => 'TYPO3',
	'since' => '339084095395078144',
));

$mysqli = new mysqli("127.0.0.1", "t3census_dbu", "t3census", "t3census_db", 3306);

if ($code == 200) {
	$tweets = json_decode($tmhOAuth->response['response']);
	$tweets = $tweets->statuses;
#var_dump(get_object_vars($tweets));

#var_dump($tweets);

	foreach ($tweets as $rawTweet) {
#var_dump(get_object_vars($rawTweet));
		if (!is_array($rawTweet->entities->urls) || count($rawTweet->entities->urls) == 0)  continue;

		$twitterUser = array();
		$twitterUser['id'] = $rawTweet->user->id;
		$twitterUser['name'] = $rawTweet->user->screen_name;
		$twitterUserId = getTwitterUserId($mysqli, $twitterUser);
#var_dump($twitterUser);

		$tweet = array();
		$tweet['id'] = $rawTweet->id;
		$tweet['text'] = $rawTweet->text;
		$tweet['created_at'] = DateTime::createFromFormat('D M j H:i:s O Y', $rawTweet->created_at);
var_dump($tweet);

#var_dump($rawTweet->entities->urls);

			if (!is_null($twitterUserId) && $mysqli->query("INSERT INTO twitter_tweet(tweet_text,twitter_id,tweet_processed, created,fk_user_id) "
					. "VALUES ('" . mysqli_real_escape_string($mysqli, $tweet['text']) . "', " . intval($tweet['id']) . ", FALSE, '" . $tweet['created_at']->format('Y-m-d H:i:s') . "', " . $twitterUserId . ")")) {
				$tweetId = $mysqli->insert_id;
				$urls = $rawTweet->entities->urls;
				$lastUrl = '';
				foreach($urls as $url) {
					if ($lastUrl === $url->expanded_url)  continue;

					$mysqli->query("INSERT INTO twitter_url(url_text,fk_tweet_id) VALUES('". mysqli_real_escape_string($mysqli, $url->expanded_url) . "', " . intval($tweetId) . ")");

					$lastUrl = $url->expanded_url;
				}
			} else {
				#echo "error: (" . $mysqli->errno . ") " . $mysqli->error;
			}
	}
} else {
	tmhUtilities::pr(htmlentities($tmhOAuth->response['response']));
}

mysqli_close($mysqli);


function getTwitterUserId($mysqli, $twitterUser) {
	$twitterUserId = NULL;
	/* Select queries return a resultset */
	if ($result = $mysqli->query("SELECT user_id FROM twitter_user WHERE twitter_id=" . intval($twitterUser['id']))) {

		if ($result->num_rows == 0) {
			$mysqli->query("INSERT INTO twitter_user(user_name,twitter_id) VALUES ('" . mysqli_real_escape_string($mysqli, $twitterUser['name']) . "', " . intval($twitterUser['id']) . ")");
			$twitterUserId = $mysqli->insert_id;

		} else {
			$row = $result->fetch_assoc();
			$twitterUserId = intval($row['user_id']);
		}

		/* free result set */
		$result->close();
	}

	return $twitterUserId;
}
?>