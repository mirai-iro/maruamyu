<?php

include('../http_accessor.inc.php');
include('../query_string_dto.inc.php');
include('../oauth_accessor.inc.php');

define('CONSUMER_KEY',        'your_consumer_key');
define('CONSUMER_SECRET',     'your_consumer_secret');
define('ACCESS_TOKEN',        'your_access_token');
define('ACCESS_TOKEN_SECRET', 'your_access_token_secret');

$oAuthAccessor = new Maruamyu_Core_OAuthAccessor(CONSUMER_KEY, CONSUMER_SECRET);
$oAuthAccessor->setAccessToken(ACCESS_TOKEN, ACCESS_TOKEN_SECRET);

$apiUrl = 'https://api.twitter.com/1.1/users/show.json';
$apiParam = array('screen_name' => 'mirai_iro');

$responseDto = $oAuthAccessor->connect('GET', $apiUrl, $apiParam);

var_dump($responseDto);

?>