<?php

/*
	oauth_accessor.inc.php - OAuth1.0aアクセサ
		written by にゃー (mirai_iro)
		managed by まるあみゅ.ねっと (http://maruamyu.net/)
	
	以下のライブラリが必要です
		http_accessor.inc.php - HTTPアクセサ
		query_string_dto.inc.php - QUERY_STRING操作
*/

# 通信結果が格納されるDTO
class Maruamyu_Core_OAuthResponseDto
{
	public $status = NULL;
	public $header = array();
	public $body = '';
}

# マルチパートデータでリクエストする際に設定データを格納するDTO
class Maruamyu_Core_OAuthRequestMultipartDto
{
	public $name = '';
	public $fileName = '';
	public $mimeType = 'application/octet-stream';
	public $body = '';
}

# OAuth1.0aアクセサ本体
class Maruamyu_Core_OAuthAccessor
{
	private $consumerKey;
	private $consumerSecret;
	private $accessToken;
	private $accessTokenSecret;
	
	public function __construct($consumerKey, $consumerSecret)
	{
		self::initialize($consumerKey, $consumerSecret);
	}
	
	public function initialize($consumerKey, $consumerSecret)
	{
		$this->consumerKey = $consumerKey;
		$this->consumerSecret = $consumerSecret;
		$this->accessToken = '';
		$this->accessTokenSecret = '';
	}
	
	public function setAccessToken($accessToken, $accessTokenSecret)
	{
		$this->accessToken = $accessToken;
		$this->accessTokenSecret = $accessTokenSecret;
	}
	
	# $param は string, array, Maruamyu_Core_QueryStringDto どれでもOK
	public function makeAuthorizationHeader($method, $url, $param = NULL)
	{
		if(!preg_match('/^(https?):\/\/([^\/]+)(\/.*)?/u', $url, $match)){return FALSE;}
		
		$realm = ''.$match[1].'://'.$match[2].'/';
		
		$signatureQueryStringDto = NULL;
		
		if(is_object($param) && strcmp(get_class($param),'Maruamyu_Core_QueryStringDto') == 0){
			$signatureQueryStringDto = clone $param;
		} else {
			$signatureQueryStringDto = new Maruamyu_Core_QueryStringDto($param);
		}
		
		$nonce = md5(uniqid(microtime()));
		$timestamp = time();
		
		$signatureQueryStringDto->update('oauth_version', '1.0');
		$signatureQueryStringDto->update('oauth_signature_method', 'HMAC-SHA1');
		$signatureQueryStringDto->update('oauth_timestamp', $timestamp);
		$signatureQueryStringDto->update('oauth_nonce', $nonce);
		$signatureQueryStringDto->update('oauth_consumer_key', $this->consumerKey);
		if(strlen($this->accessToken) > 0){
			$signatureQueryStringDto->update('oauth_token', $this->accessToken);
		}
		
		$signatureQueryString = $signatureQueryStringDto->getOAuthQueryString();
		
		$signatureMessage = rawurlencode($method).'&'.rawurlencode($url).'&'.rawurlencode($signatureQueryString);
		$signatureSalt = rawurlencode($this->consumerSecret).'&'.rawurlencode($this->accessTokenSecret);
		
		if(version_compare(PHP_VERSION, '5.3.0') < 0){	# PHP 5.3.0 以前は ~ がエンコードされている
			$signatureMessage = str_replace('%7E', '~', $signatureMessage);
			$signatureSalt = str_replace('%7E', '~', $signatureSalt);
		}
		
		$signatureString = base64_encode( hash_hmac('sha1', $signatureMessage, $signatureSalt, TRUE) );
		
		$headerString  = 'OAuth realm="'.$realm.'", ';
		$headerString .= 'oauth_consumer_key="'.$this->consumerKey.'", ';
		$headerString .= 'oauth_nonce="'.rawurlencode($nonce).'", ';	# nonceはmd5で~を含まない
		$headerString .= 'oauth_signature="'.rawurlencode($signatureString).'", ';
		$headerString .= 'oauth_signature_method="HMAC-SHA1", ';
		$headerString .= 'oauth_timestamp="'.$timestamp.'", ';
		if(strlen($this->accessToken) > 0){
			$headerString .= 'oauth_token="'.$this->accessToken.'", ';
		}
		$headerString .= 'oauth_version="1.0"';
		
		return $headerString;
	}
	
	# $param は string, array, Maruamyu_Core_QueryStringDto どれでもOK
	# $multipartDtoList は array( Maruamyu_Core_OAuthRequestMultipartDto, ...) のみ
	public function connect($method, $url, $param = NULL, $requestMultipartDtoList = array() )
	{
		$requestQueryStringDto = NULL;
		$signatureQueryStringDto = NULL;
		
		if(is_object($param) && strcmp(get_class($param),'Maruamyu_Core_QueryStringDto') == 0){
			$requestQueryStringDto = clone $param;
		} else {
			$requestQueryStringDto = new Maruamyu_Core_QueryStringDto($param);
		}
		
		$signatureQueryStringDto = clone $requestQueryStringDto;
		
		if(count($requestMultipartDtoList) > 0){
			$signatureQueryStringDto = new Maruamyu_Core_QueryStringDto();
		}
		
		$authorizationHeader = self::makeAuthorizationHeader($method, $url, $signatureQueryStringDto);
		
		$httpAccessor = new Maruamyu_Core_HttpAccessor($url);
		$httpAccessor->setRequestMethod($method);
		$httpAccessor->setRequestHeader('Authorization', $authorizationHeader);
		
		if($method == 'GET'){
			$httpAccessor->setQueryString($requestQueryStringDto->getOAuthQueryString());
		} else {
			$httpAccessor->setPostData($requestQueryStringDto->getOAuthQueryString());
		}
		
		foreach( $requestMultipartDtoList as $requestMultipartDto ){
			$httpAccessor->setMultipartData($requestMultipartDto->name, $requestMultipartDto->fileName, $requestMultipartDto->mimeType, $requestMultipartDto->body);
		}
		
		$httpAccessor->connect();
		
		$responseDto = new Maruamyu_Core_OAuthResponseDto();
		$responseDto->status = $httpAccessor->getResponseStatus();
		$responseDto->header = $httpAccessor->getResponseHeaderAll();
		$responseDto->body = $httpAccessor->getResponseBody();
		
		return $responseDto;
	}
}

return TRUE;

?>