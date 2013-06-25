<?php

include '../http_accessor.inc.php';
include '../query_string_dto.inc.php';
include '../oauth_accessor.inc.php';

define('CONSUMER_KEY',        'your_consumer_key');
define('CONSUMER_SECRET',     'your_consumer_secret');
define('ACCESS_TOKEN',        'your_access_token');
define('ACCESS_TOKEN_SECRET', 'your_access_token_secret');

$oAuthAccessor = new Maruamyu_Core_OAuthAccessor(CONSUMER_KEY, CONSUMER_SECRET);
$oAuthAccessor->setAccessToken(ACCESS_TOKEN, ACCESS_TOKEN_SECRET);

$apiUrl = 'https://stream.twitter.com/1.1/statuses/sample.json';
$apiParam = array();

$socket = $oAuthAccessor->connectStreaming('GET', $apiUrl, $apiParam);

while (!feof($socket)) {
	var_dump(fread($socket, 65536));
}
