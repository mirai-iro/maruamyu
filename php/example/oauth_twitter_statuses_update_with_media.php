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

$apiUrl = 'https://api.twitter.com/1.1/statuses/update_with_media.json';
$apiParam = array('status' => 'はかせだにゃん');

$multipartDto = new Maruamyu_Core_OAuthRequestMultipartDto();
$multipartDto->name = 'media[]';
$multipartDto->fileName = 'hakase.png';
$multipartDto->mimeType = 'image/png';
$multipartDto->body = file_get_contents('./hakase.png');

$responseDto = $oAuthAccessor->connect('POST', $apiUrl, $apiParam, array($multipartDto) );

var_dump($responseDto);

?>