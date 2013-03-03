<?php

/*
	twitter_api_accessor.inc.php - Twitter REST API アクセサ
		written by にゃー (mirai_iro)
		managed by まるあみゅ.ねっと (http://maruamyu.net/)
	
	以下のライブラリが必要です
		oauth_accessor.inc.php - OAuth1.0aアクセサ
		http_accessor.inc.php - HTTPアクセサ
		query_string_dto.inc.php - QUERY_STRING操作
*/

class Maruamyu_Twitter_ApiAccessor extends Maruamyu_Core_OAuthAccessor
{
	const GET_USERS_LOOKUP_BASE_COUNT = 100;
	const GET_IDS_BASE_COUNT = 5000;
	const TWEET_RETRY_COUNT = 3;
	
	private $apiVersion = '1.1';
	private $rateLimitStatus = array();
	
	public function __construct($consumerKey, $consumerSecret, $apiVersion = '1.1')
	{
		parent::initialize($consumerKey, $consumerSecret);
		$this->setApiVersion($apiVersion);
		$this->rateLimitStatus = array();
	}
	
	public function setAccessToken($accessToken, $accessTokenSecret, $refreshRateLimitStatus = FALSE)
	{
		parent::setAccessToken($accessToken, $accessTokenSecret);
		if($refreshRateLimitStatus){$this->refreshRateLimitStatus();}
	}
	
	public function setApiVersion($apiVersion)
	{
		$this->apiVersion = $apiVersion;
	}
	
	public function refreshRateLimitStatus()
	{
		if($this->isLegacyApiVersion()){return FALSE;}
		
		$rateLimitStatus = $this->connect('GET', 'application/rate_limit_status');
		if(!$rateLimitStatus || !$rateLimitStatus['resources']){return FALSE;}
		
		$this->rateLimitStatus = array();
		foreach( $rateLimitStatus['resources'] as $resource => $lateLimitStatusList ){
			foreach( $lateLimitStatusList as $apiPath => $lateLimitStatus ){
				$apiPath = preg_replace('#^/#u', '', $apiPath);	# /homu/mado -> homu/mado
				$this->rateLimitStatus[$apiPath] = $lateLimitStatus;
			}
		}
		
		return TRUE;
	}
	
	public function getRateLimitStatus($apiPath = '', $refresh = FALSE)
	{
		if($this->isLegacyApiVersion()){return FALSE;}
		
		if(count($this->rateLimitStatus) < 1 || $refresh){
			$result = $this->refreshRateLimitStatus();
			if(!$result){return FALSE;}
		}
		
		$retVal = $this->rateLimitStatus;
		if(isset($this->rateLimitStatus[$apiPath])){
			$retVal = array($apiPath => $this->rateLimitStatus[$apiPath]);
		}
		
		return $retVal;
	}
	
	# 接続(汎用) return: json_encode(response, TRUE) # Array
	public function connect($method, $apiPath, $param = NULL, $multipartDtoList = array() )
	{
		$oAuthResponseDto = $this->connectRaw($method, $apiPath, $param, $multipartDtoList);
		if(!$oAuthResponseDto || $oAuthResponseDto->status != 200){return FALSE;}
		
		if(isset($oAuthResponseDto->header['X-Rate-Limit-Limit'])){
			$this->rateLimit[$apiPath] = array(
				'limit' => $oAuthResponseDto->header['X-Rate-Limit-Limit'],
				'remaining' => $oAuthResponseDto->header['X-Rate-Limit-Remaining'],
				'reset' => $oAuthResponseDto->header['X-Rate-Limit-Reset']
			);
		}
		
		return json_decode($oAuthResponseDto->body, TRUE);
	}
	
	public function getUserDataByUserId($userId)
	{
		if(!self::validateUserId($userId)){return FALSE;}
		
		$param = array( 'user_id' => $userId );
		
		return $this->connect('GET', 'users/show', $param);
	}
	
	public function getUserDataByScreenName($screenName)
	{
		if(!self::validateScreenName($screenName)){return FALSE;}
		
		$param = array( 'screen_name' => $screenName );
		
		return $this->connect('GET', 'users/show', $param);
	}
	
	public function getUserDataListByUserIdList($userIdList)
	{
		$lookupCount = count($userIdList);
		
		$lookupData = array();
		
		if($lookupCount > 0){
			$param = array( 'user_id' => '' );
			
			$pageMax = ceil($lookupCount / self::GET_USERS_LOOKUP_BASE_COUNT);
			for($page=0; $page<$pageMax; $page++){
				$targetList = array_slice($userIdList, (self::GET_USERS_LOOKUP_BASE_COUNT * $page), self::GET_USERS_LOOKUP_BASE_COUNT);
				if(count($targetList) < 1){continue;}
				
				$param['user_id'] = join(',', $targetList);
				
				$userDataList = $this->connect('GET', 'users/lookup', $param);
				if(!$userDataList){break;}
				
				foreach( $userDataList as $userData ){
					$userId = $userData['id_str'];
					$lookupData[$userId] = $userData;
				}
			}
		}
		
		return $lookupData;
	}
	
	public function getUserDataListByScreenNameList($screenNameList)
	{
		$lookupCount = count($screenNameList);
		
		$lookupData = array();
		
		if($lookupCount > 0){
			$param = array( 'screen_name' => '' );
			
			$pageMax = ceil($lookupCount / self::GET_USERS_LOOKUP_BASE_COUNT);
			for($page=0; $page<$pageMax; $page++){
				$targetList = array_slice($screenNameList, (self::GET_USERS_LOOKUP_BASE_COUNT * $page), self::GET_USERS_LOOKUP_BASE_COUNT);
				if(count($targetList) < 1){continue;}
				
				$param['screen_name'] = join(',', $targetList);
				
				$userDataList = $this->connect('GET', 'users/lookup', $param);
				if(!$userDataList){break;}
				
				foreach( $userDataList as $userData ){
					$userId = $userData['id_str'];
					$lookupData[$userId] = $userData;
				}
			}
		}
		
		return $lookupData;
	}
	
	public function getFollowingIds($sourceUserId)
	{
		$chkList = array();
		
		$param = array(
			'user_id' => $sourceUserId,
			'stringify_ids' => 'true',
			'count' => self::GET_IDS_BASE_COUNT,
			'cursor' => '-1'
		);
		
		for($i=0; $i<self::GET_IDS_BASE_COUNT; $i++){
			$oAuthResponseDto = $this->connectRaw('GET', 'friends/ids', $param);
			if(!$oAuthResponseDto || $oAuthResponseDto->status != 200){return FALSE;}
			
			if(strlen($oAuthResponseDto->body) < 1){break;}
			
			$get = json_decode($oAuthResponseDto->body, TRUE);
			
			if(count($get['ids']) < 1){break;}
			
			foreach( $get['ids'] as $getUserId ){
				#	if(!self::validateUserId($getUserId)){continue;}
				$chkList[$getUserId] = TRUE;
			}
			
			$nextCursor = $get['next_cursor_str'];
			if(strlen($nextCursor) < 1 || strcmp($nextCursor,'0') == 0 || strcmp($nextCursor,'-1') == 0){break;}
			
			$param['cursor'] = $nextCursor;
		}
		
		return array_keys($chkList);
	}
	
	public function getFollowerIds($sourceUserId)
	{
		$chkList = array();
		
		$param = array(
			'user_id' => $sourceUserId,
			'stringify_ids' => 'true',
			'count' => self::GET_IDS_BASE_COUNT,
			'cursor' => '-1'
		);
		
		for($i=0; $i<self::GET_IDS_BASE_COUNT; $i++){
			$oAuthResponseDto = $this->connectRaw('GET', 'followers/ids', $param);
			if(!$oAuthResponseDto || $oAuthResponseDto->status != 200){return FALSE;}
			
			if(strlen($oAuthResponseDto->body) < 1){break;}
			
			$get = json_decode($oAuthResponseDto->body, TRUE);
			
			if(count($get['ids']) < 1){break;}
			
			foreach( $get['ids'] as $getUserId ){
				#	if(!self::validateUserId($getUserId)){continue;}
				$chkList[$getUserId] = TRUE;
			}
			
			$nextCursor = $get['next_cursor_str'];
			if(strlen($nextCursor) < 1 || strcmp($nextCursor,'0') == 0 || strcmp($nextCursor,'-1') == 0){break;}
			
			$param['cursor'] = $nextCursor;
		}
		
		return array_keys($chkList);
	}
	
	public function getRelationshipByUserId($sourceUserId, $targetUserId)
	{
		$param = array(
			'source_id' => $sourceUserId,
			'target_id' => $targetUserId
		);
		
		return $this->connect('GET', 'friendships/show', $param);
	}
	
	public function getRelationshipByScreenName($sourceScreenName, $targetScreenName)
	{
		$param = array(
			'source_screen_name' => $sourceScreenName,
			'target_screen_name' => $targetScreenName
		);
		
		return $this->connect('GET', 'friendships/show', $param);
	}
	
	public function tweet($status, $inReplyToStatusId = '', $mediaFile = array() )
	{
		$param = array( 'status' => $status );
		
		if($inReplyToStatusId){
			$param['in_reply_to_status_id'] = $inReplyToStatusId;
		}
		
		$apiPath = 'statuses/update';
		$multipartDtoList = array();
		
		if(count($mediaFile) > 0){
			$apiPath = 'statuses/update_with_media';
			foreach( $mediaFile as $fileName => $fileData ){
				$multipartDto = new Maruamyu_Core_OAuthRequestMultipartDto();
				$multipartDto->name = 'media[]';
				$multipartDto->fileName = $fileName;
				$multipartDto->mimeType = 'application/octet-stream';
				$multipartDto->body = $fileData;
				
				$multipartDtoList[] = $multipartDto;
			}
		}
		
		for($i=0; $i<self::TWEET_RETRY_COUNT; $i++){
			$oAuthResponseDto = $this->connectRaw('POST', $apiPath, $param, $multipartDtoList);
			
			if($oAuthResponseDto){
				if($oAuthResponseDto->status == 200){
					return json_decode($oAuthResponseDto->body, TRUE);
				}
				if($oAuthResponseDto->status == 403){
					return FALSE;
				}
			}
			
			@usleep(500);
		}
		
		return FALSE;
	}
	
	public function createFriendshipsByUserId($userId)
	{
		return $this->connect('POST', 'friendships/create', array('user_id' => $userId) );
	}
	
	public function createFriendshipsByScreenName($screenName)
	{
		return $this->connect('POST', 'friendships/create', array('screen_name' => $screenName) );
	}
	
	public function createListsMembers($listsOwnerScreenName, $listsSlug, $userId)
	{
		$param = array(
			'owner_screen_name' => $listsOwnerScreenName,
			'slug' => $listsSlug,
			'user_id' => $userId,
		);
		
		return $this->connect('POST', 'lists/members/create', $param);
	}
	
	public function destroyListsMembers($listsOwnerScreenName, $listsSlug, $userId)
	{
		$param = array(
			'owner_screen_name' => $listsOwnerScreenName,
			'slug' => $listsSlug,
			'user_id' => $userId,
		);
		
		return $this->connect('POST', 'lists/members/destroy', $param);
	}
	
	public function getConfiguration()
	{
		return $this->connect('GET', 'help/configuration');
	}
	
	# 注意: 内部の accessToken, accessTokenSecretが変更される
	public function oAuthRequestToken($callback = '')
	{
		parent::setAccessToken('', '');
		
		$oauthRequestTokenUrl = 'https://api.twitter.com/oauth/request_token';
		$param = array('oauth_callback' => $callback);
		
		$oAuthResponseDto = parent::connect('POST', $oauthRequestTokenUrl, $param);
		
		$response = FALSE;
		
		if($oAuthResponseDto && $oAuthResponseDto->status == 200 ){
			$queryStringDto = new Maruamyu_Core_QueryStringDto($oAuthResponseDto->body);
			
			$oauthToken = $queryStringDto->getStringValue('oauth_token');
			$oauthTokenSecret = $queryStringDto->getStringValue('oauth_token_secret');
			$oauthCallbackConfirmed = ($queryStringDto->getStringValue('oauth_callback_confirmed') == 'true');
			
			parent::setAccessToken($oauthToken, $oauthTokenSecret);
			
			$response = array(
				'oauth_token' => $oauthToken,
				'oauth_token_secret' => $oauthTokenSecret,
				'oauth_callback_confirmed' => $oauthCallbackConfirmed,
				'oauth_authorize_url' => 'https://api.twitter.com/oauth/authorize?oauth_token='.$oauthToken,
				'oauth_authenticate_url' => 'https://api.twitter.com/oauth/authenticate?oauth_token='.$oauthToken,
			);
		}
		
		return $response;
	}
	
	# 注意: 内部の accessToken, accessTokenSecretが変更される
	public function oAuthAccessToken($verifier)
	{
		$oAuthAccessTokenUrl = 'https://api.twitter.com/oauth/access_token';
		$param = array('oauth_verifier' => $verifier);
		
		$oAuthResponseDto = parent::connect('POST', $oAuthAccessTokenUrl, $param);
		
		$response = FALSE;
		
		if($oAuthResponseDto && $oAuthResponseDto->status == 200 ){
			$queryStringDto = new Maruamyu_Core_QueryStringDto($oAuthResponseDto->body);
			
			$oauthToken = $queryStringDto->getStringValue('oauth_token');
			$oauthTokenSecret = $queryStringDto->getStringValue('oauth_token_secret');
			$userId = $queryStringDto->getStringValue('user_id');
			$screenName = $queryStringDto->getStringValue('screen_name');
			
			parent::setAccessToken($oauthToken, $oauthTokenSecret);
			
			$response = array(
				'oauth_token' => $oauthToken,
				'oauth_token_secret' => $oauthTokenSecret,
				'user_id' => $userId,
				'screen_name' => $screenName,
			);
		}
		
		return $response;
	}
	
	public function getApiUrl($apiPath)
	{
		return 'https://api.twitter.com/'.$this->apiVersion.'/'.$apiPath.'.json';
	}
	
	# OAuth Echo用 (account/verify_credentials)
	public function makeAuthorizationHeader($method, $apiPath, $param = array() )
	{
		return parent::makeAuthorizationHeader($method, $this->getApiUrl($apiPath), $param);
	}
	
	# 接続(内部処理用) return: Maruamyu_Core_OAuthResponseDto
	private function connectRaw($method, $apiPath, $param = NULL, $multipartDtoList = array() )
	{
		return parent::connect($method, $this->getApiUrl($apiPath), $param, $multipartDtoList);
	}
	
	private function isLegacyApiVersion()
	{
		return (floatval($this->apiVersion) < 1.1);
	}
	
	public static function validateUserId($userId)
	{
		return !!preg_match('/^(\d+)$/u', $userId);
	}
	
	public static function validateScreenName($screenName)
	{
		return !!preg_match('/^([a-zA-Z0-9_]+)$/u', $screenName);
	}
}

return TRUE;

?>