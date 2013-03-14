<?php

/**
 * twitter_api_accessor.inc.php - Twitter REST API アクセサ
 * 
 * @author written by にゃー (mirai_iro)
 * @author managed by まるあみゅ.ねっと (http://maruamyu.net/)
 * 
 * 以下のファイルが必要です
 *  oauth_accessor.inc.php - OAuth1.0aアクセサ
 *  http_accessor.inc.php - HTTPアクセサ
 *  query_string_dto.inc.php - QUERY_STRING操作
 */

/**
 * Twitter REST API アクセサ
 */
class Maruamyu_Twitter_ApiAccessor extends Maruamyu_Core_OAuthAccessor
{
	/** GET users/lookup の取得単位 */
	const GET_USERS_LOOKUP_BASE_COUNT = 100;
	
	/** GET friends/ids や GET followers/ids の取得単位 */
	const GET_IDS_BASE_COUNT = 5000;
	
	/** ツイートのリトライ回数 */
	const TWEET_RETRY_COUNT = 3;
	
	private $apiVersion = '1.1';
	private $rateLimitStatus = array();
	
	/**
	 * コンストラクタ
	 * 
	 * @param string $consumerKey コンシューマーキー
	 * @param string $consumerSecret コンシューマーシークレット
	 * @param string $apiVersion APIのバージョン
	 */
	public function __construct($consumerKey, $consumerSecret, $apiVersion = '1.1')
	{
		parent::initialize($consumerKey, $consumerSecret);
		$this->setApiVersion($apiVersion);
		$this->rateLimitStatus = array();
	}
	
	/**
	 * アクセストークンを設定する
	 * 
	 * @param string $accessToken アクセストークン
	 * @param string $accessTokenSecret アクセストークンシークレット
	 * @param bool $refreshRateLimitStatus インスタンスが持っているRate Limitカウンタを再読み込みする場合TRUE
	 */
	public function setAccessToken($accessToken, $accessTokenSecret, $refreshRateLimitStatus = FALSE)
	{
		parent::setAccessToken($accessToken, $accessTokenSecret);
		if($refreshRateLimitStatus){$this->refreshRateLimitStatus();}
	}
	
	/**
	 * APIバージョンを設定する
	 * 
	 * @param string $apiVersion APIのバージョン
	 */
	public function setApiVersion($apiVersion)
	{
		$this->apiVersion = $apiVersion;
	}
	
	/**
	 * インスタンスが持っているRate Limitカウンタを再読み込みする
	 * 
	 * @return bool 成功ならTRUE, 失敗ならFALSE
	 */
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
	
	/**
	 * インスタンスが持っているRate Limitカウンタから値を取得する
	 * 
	 * @param string $apiPath APIパス(先頭の/は不要)
	 * @param bool $refresh APIから最新の情報を取得する場合はTRUE
	 * @return array [ 'limit' => 残り回数, 'remaining' => 制限初期値, 'reset' => 次回の制限リセット時刻 ];
	 * @return false インスタンスが持っているRate Limitカウンタが初期化されておらず、またAPIを利用した更新もできなかった場合FALSE
	 * @return false APIバージョン1.0の場合は未対応なのでFALSE
	 * 
	 * @link https://dev.twitter.com/docs/rate-limiting/1.1 RateLimitに関する情報
	 */
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
	
	/**
	 * 任意のAPIにアクセスし値を取得する
	 * 
	 * @param string $method メソッド 'GET' もしくは 'POST'
	 * @param string $apiPath APIパス(先頭の/は不要)
	 * @param array|Maruamyu_Core_QueryStringDto $param APIにパラメータとして渡す値(連想配列)
	 * @param Maruamyu_Core_OAuthRequestMultipartDto[] multipart/form-dataのデータを送信する場合、Maruamyu_Core_OAuthRequestMultipartDto のインスタンスのリスト
	 * @return array APIレスポンス(連想配列)
	 * @return false 通信に失敗した場合FALSE
	 */
	public function connect($method, $apiPath, $param = NULL, $multipartDtoList = array() )
	{
		$oAuthResponseDto = $this->connectRaw($method, $apiPath, $param, $multipartDtoList);
		if(!$oAuthResponseDto || $oAuthResponseDto->status != 200){return FALSE;}
		
		if(isset($oAuthResponseDto->header['x-rate-limit-limit'])){
			$this->rateLimit[$apiPath] = array(
				'limit' => $oAuthResponseDto->header['x-rate-limit-limit'],
				'remaining' => $oAuthResponseDto->header['x-rate-limit-remaining'],
				'reset' => $oAuthResponseDto->header['x-rate-limit-reset']
			);
		}
		
		return json_decode($oAuthResponseDto->body, TRUE);
	}
	
	/**
	 * ユーザーIDを指定してユーザー情報を取得する (GET users/show)
	 * 
	 * @link https://dev.twitter.com/docs/api/1.1/get/users/show
	 * 
	 * @param string $userId ユーザーID
	 * @return array ユーザー情報(連想配列)
	 */
	public function getUserDataByUserId($userId)
	{
		if(!self::validateUserId($userId)){return FALSE;}
		
		$param = array( 'user_id' => $userId );
		
		return $this->connect('GET', 'users/show', $param);
	}
	
	/**
	 * ユーザー名(screen_name)を指定してユーザー情報を取得する (GET users/show)
	 * 
	 * @link https://dev.twitter.com/docs/api/1.1/get/users/show
	 * 
	 * @param string $screenName ユーザー名
	 * @return array ユーザー情報(連想配列)
	 */
	public function getUserDataByScreenName($screenName)
	{
		if(!self::validateScreenName($screenName)){return FALSE;}
		
		$param = array( 'screen_name' => $screenName );
		
		return $this->connect('GET', 'users/show', $param);
	}
	
	/**
	 * 指定したユーザーIDのリストに対応するユーザー情報リストを取得する (GET users/lookup)
	 * 
	 * @link https://dev.twitter.com/docs/api/1.1/get/users/lookup
	 * 
	 * @param string[] $userIdList ユーザーIDのリスト
	 * @return array ユーザー情報リスト(ユーザーIDをキーにした連想配列)
	 */
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
	
	/**
	 * 指定したユーザー名のリストに対応するユーザー情報リストを取得する (GET users/lookup)
	 * 
	 * @link https://dev.twitter.com/docs/api/1.1/get/users/lookup
	 * 
	 * @param string[] $screenNameList ユーザー名のリスト
	 * @return array ユーザー情報リスト(ユーザーIDをキーにした連想配列)
	 */
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
	
	/**
	 * 指定したユーザーIDのユーザーがフォローしているユーザーのユーザーID一覧を取得する (GET friends/ids)
	 * 
	 * @link https://dev.twitter.com/docs/api/1.1/get/friends/ids
	 * 
	 * @param string $sourceUserId 取得元ユーザーID
	 * @return array $sourceUserId で指定したユーザーがフォローしているユーザーのユーザーID一覧
	 */
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
	
	/**
	 * 指定したユーザーIDのユーザーをフォローしているユーザーのユーザーID一覧を取得する (GET followers/ids)
	 * 
	 * @link https://dev.twitter.com/docs/api/1.1/get/followers/ids
	 * 
	 * @param string $sourceUserId 取得元ユーザーID
	 * @return array $sourceUserId で指定したユーザーをフォローしているユーザーのユーザーID一覧
	 */
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
	
	/**
	 * 指定した2つのユーザーIDのユーザーのフォロー関係を取得する (GET friendships/show)
	 * 
	 * @link https://dev.twitter.com/docs/api/1.1/get/friendships/show
	 * 
	 * @param string $sourceUserId 起点となるユーザーのユーザーID
	 * @param string $targetUserId 関係を調べるユーザーのユーザーID
	 * @return array フォロー関係(連想配列)
	 */
	public function getRelationshipByUserId($sourceUserId, $targetUserId)
	{
		$param = array(
			'source_id' => $sourceUserId,
			'target_id' => $targetUserId
		);
		
		return $this->connect('GET', 'friendships/show', $param);
	}
	
	/**
	 * 指定した2つのユーザー名のユーザーのフォロー関係を取得する (GET friendships/show)
	 * 
	 * @link https://dev.twitter.com/docs/api/1.1/get/friendships/show
	 * 
	 * @param string $sourceScreenName 起点となるユーザーのユーザー名
	 * @param string $targetScreenName 関係を調べるユーザーのユーザー名
	 * @return array フォロー関係(連想配列)
	 */
	public function getRelationshipByScreenName($sourceScreenName, $targetScreenName)
	{
		$param = array(
			'source_screen_name' => $sourceScreenName,
			'target_screen_name' => $targetScreenName
		);
		
		return $this->connect('GET', 'friendships/show', $param);
	}
	
	/**
	 * ツイートする (POST statuses/update, POST statuses/update_with_media)
	 * 
	 * (最大TWEET_RETRY_COUNT回リトライする)
	 * 
	 * @link https://dev.twitter.com/docs/api/1.1/post/statuses/update
	 * @link https://dev.twitter.com/docs/api/1.1/post/statuses/update_with_media
	 * 
	 * @param string $status ツイート内容
	 * @param string $inReplyToStatusId 返信先ツイートのID
	 * @param array $mediaFile 画像投稿する場合、キーをファイル名、値を画像のバイナリデータにした連想配列
	 * @return array ツイートに成功した場合、ツイート情報(連想配列)
	 * @return false ツイートに失敗した場合FALSE
	 */
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
	
	/**
	 * 指定したユーザーIDのユーザーをフォローする (POST friendships/create)
	 * 
	 * @link https://dev.twitter.com/docs/api/1.1/post/friendships/create
	 * 
	 * @param string $userId フォローするユーザーのユーザーID
	 * @return array APIからのレスポンス(ユーザー情報(連想配列))
	 */
	public function createFriendshipsByUserId($userId)
	{
		return $this->connect('POST', 'friendships/create', array('user_id' => $userId) );
	}
	
	/**
	 * 指定したユーザー名のユーザーをフォローする (POST friendships/create)
	 * 
	 * @link https://dev.twitter.com/docs/api/1.1/post/friendships/create
	 * 
	 * @param string $screenName フォローするユーザーのユーザーID
	 * @return array APIからのレスポンス(ユーザー情報(連想配列))
	 */
	public function createFriendshipsByScreenName($screenName)
	{
		return $this->connect('POST', 'friendships/create', array('screen_name' => $screenName) );
	}
	
	/**
	 * ユーザー名/リスト名/追加したいユーザーのユーザーIDを指定してリストに追加する (POST lists/members/create)
	 * 
	 * @link https://dev.twitter.com/docs/api/1.1/post/lists/members/create
	 * 
	 * @param string $listsOwnerScreenName リスト管理者のユーザー名
	 * @param string $listsSlug リストのリスト名
	 * @param string $userId リストに追加したいユーザーのユーザーID
	 * @return array APIからのレスポンス(連想配列)
	 */
	public function createListsMembers($listsOwnerScreenName, $listsSlug, $userId)
	{
		$param = array(
			'owner_screen_name' => $listsOwnerScreenName,
			'slug' => $listsSlug,
			'user_id' => $userId,
		);
		
		return $this->connect('POST', 'lists/members/create', $param);
	}
	
	/**
	 * ユーザー名/リスト名/削除したいユーザーのユーザーIDを指定してリストから削除する (POST lists/members/destroy)
	 * 
	 * @link https://dev.twitter.com/docs/api/1.1/post/lists/members/destroy
	 * 
	 * @param string $listsOwnerScreenName リスト管理者のユーザー名
	 * @param string $listsSlug リストのリスト名
	 * @param string $userId リストから削除したいユーザーのユーザーID
	 * @return array APIからのレスポンス(連想配列)
	 */
	public function destroyListsMembers($listsOwnerScreenName, $listsSlug, $userId)
	{
		$param = array(
			'owner_screen_name' => $listsOwnerScreenName,
			'slug' => $listsSlug,
			'user_id' => $userId,
		);
		
		return $this->connect('POST', 'lists/members/destroy', $param);
	}
	
	/**
	 * 設定値(t.co化後のURL長さなど)を取得 (GET help/configuration)
	 * 
	 * @link https://dev.twitter.com/docs/api/1.1/get/help/configuration
	 * 
	 * @return array APIからのレスポンス(連想配列)
	 */
	public function getConfiguration()
	{
		return $this->connect('GET', 'help/configuration');
	}
	
	/**
	 * OAuth : RequestTokenを取得する
	 * (インスタンスに AccessToken, AccessTokenSecretを設定している場合、クリアされ使用できなくなる)
	 * 
	 * @link https://dev.twitter.com/docs/api/1/post/oauth/request_token
	 * @link https://dev.twitter.com/docs/api/1/get/oauth/authorize
	 * @link https://dev.twitter.com/docs/api/1/get/oauth/authenticate
	 * 
	 * @param string $callback 認証画面からのコールバック先URL
	 * @return array APIからのレスポンス(連想配列)
	 */
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
	
	/**
	 * OAuth : RequestTokenをAccessTokenと交換する
	 * 
	 * (交換に成功した場合、インスタンスのAccessToken, AccessTokenSecretが変更される)
	 * 
	 * @link https://dev.twitter.com/docs/api/1/post/oauth/access_token
	 * @link https://dev.twitter.com/docs/api/1/get/oauth/authorize
	 * @link https://dev.twitter.com/docs/api/1/get/oauth/authenticate
	 * 
	 * @param string $verifier コールバック先URLに渡されてきたverifierパラメータ or ユーザーから入力された暗証番号
	 * @return array APIからのレスポンス(連想配列)
	 */
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
	
	/**
	 * Twitter REST API のエンドポイントURLを取得する
	 * 
	 * @param string $apiPath APIのパス (例: 'users/show')
	 * @return string エンドポイントURL
	 */
	public function getApiUrl($apiPath)
	{
		return 'https://api.twitter.com/'.$this->apiVersion.'/'.$apiPath.'.json';
	}
	
	/**
	 * インスタンスが持っているAccessToken, AccessTokenSecretをもとに署名を作成し、HTTPヘッダ Authorization で使用できる形式で返す
	 * 
	 * OAuth Echoで使用する
	 * 
	 * @link https://dev.twitter.com/docs/auth/oauth/oauth-echo OAuth Echo
	 * @link https://dev.twitter.com/docs/api/1.1/get/account/verify_credentials GET account/verify_credentials
	 * 
	 * @param string $method メソッド。GET または POST
	 * @param string $apiPath APIのパス (例: 'account/verify_credentials')
	 * @return string HTTPヘッダ Authorization に設定するヘッダ文字列
	 */
	public function makeAuthorizationHeader($method, $apiPath, $param = array() )
	{
		return parent::makeAuthorizationHeader($method, $this->getApiUrl($apiPath), $param);
	}
	
	/**
	 * 任意のAPIにアクセスし値を取得する(内部処理用)
	 * 
	 * @param string $method メソッド 'GET' もしくは 'POST'
	 * @param string $apiPath APIパス(先頭の/は不要)
	 * @param array|Maruamyu_Core_QueryStringDto $param APIにパラメータとして渡す値(連想配列)
	 * @param Maruamyu_Core_OAuthRequestMultipartDto[] multipart/form-dataのデータを送信する場合、Maruamyu_Core_OAuthRequestMultipartDto のインスタンスのリスト
	 * @return Maruamyu_Core_OAuthResponseDto APIからのレスポンス
	 */
	private function connectRaw($method, $apiPath, $param = NULL, $multipartDtoList = array() )
	{
		return parent::connect($method, $this->getApiUrl($apiPath), $param, $multipartDtoList);
	}
	
	/**
	 * インスタンスに設定されているAPIバージョンが1.1未満かどうかを確認する
	 * 
	 * @return bool ver1.1未満ならTRUE, ver1.1以上ならFALSE
	 */
	private function isLegacyApiVersion()
	{
		return (floatval($this->apiVersion) < 1.1);
	}
	
	/**
	 * ユーザーIDが正しい形式かどうか確かめる
	 * 
	 * @param string $userId ユーザーID
	 * @return bool 数値のみの文字列であればTRUE, それ以外はFALSE
	 */
	public static function validateUserId($userId)
	{
		return !!preg_match('/^(\d+)$/u', $userId);
	}
	
	/**
	 * ユーザー名が正しい形式かどうか確かめる
	 * 
	 * @param string $screenName ユーザー名
	 * @return bool 英数字・アンダーバーのみの文字列であればTRUE, それ以外はFALSE
	 */
	public static function validateScreenName($screenName)
	{
		return !!preg_match('/^([a-zA-Z0-9_]+)$/u', $screenName);
	}
}

return TRUE;

?>