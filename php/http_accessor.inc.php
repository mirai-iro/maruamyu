<?php

/*
	http_accessor.inc.php - HTTPアクセサ
		written by にゃー (mirai_iro)
		managed by まるあみゅ.ねっと (http://maruamyu.net/)
*/

class Maruamyu_Core_HttpAccessor
{
	const CRLF = "\r\n";
	const DEFAULT_TIMEOUT_SEC = 10;
	const READ_CHUNK_SIZE = 8192;
	
	public static $ARROW_HTTP_METHODS = array('GET', 'POST', 'HEAD');
	
	private $requestHost = '';
	private $requestPort = 80;
	private $requestPath = '/';
	private $requestHeader = array( 'User-Agent' => 'maruamyu.net http_accessor' );
	private $requestMethod = 'GET';
	private $postData = '';
	private $multipartData = array();
	
	private $responseStatus = NULL;
	private $responseHeader = array();
	private $responseBody = '';
	
	public function __construct()
	{
		$argNum = func_num_args();
		$args = func_get_args();
		
		if($argNum == 1){	# URL
			$this->setRequestUrl($args[0]);
			
		} else if($argNum >= 2){	# Host,Port,Path
			list($host, $port, $path) = $args;
			
			$this->setRequestHostAndPort($host,$port);
			$this->setRequestPath($path);
		}
	}
	
	public function setRequestHostAndPort($host, $port = 80)
	{
		if(strlen($host) < 1){return FALSE;}
		$this->requestHost = $host;
		
		$port = intval($port,10);
		if($port > 0){$this->requestPort = $port;}
		
		return TRUE;
	}
	
	public function setRequestPath($path)
	{
		if(strlen($path) < 1){return FALSE;}
		
		$this->requestPath = $path;
		return TRUE;
	}
	
	public function setRequestUrl($requestUrl)
	{
		$parsedUrl = parse_url($requestUrl);
		if(!$parsedUrl){return FALSE;}
		
		$port = $parsedUrl['port'];
		if(!$port){$port = ($parsedUrl['scheme'] == 'https') ? 443 : 80;}
		
		$isValidHostAndPort = $this->setRequestHostAndPort($parsedUrl['host'],$port);
		if(!$isValidHostAndPort){return FALSE;}
		
		$this->setRequestPath($parsedUrl['path']);
		$this->setQueryString($parsedUrl['query']);
		
		return TRUE;
	}
	
	public function setRequestHeader($key, $value)
	{
		if(strlen($key) < 1){return FALSE;}
		
		$this->requestHeader[$key] = $value;
		return TRUE;
	}
	
	public function setRequestMethod($method)
	{
		if(!in_array($method, self::$ARROW_HTTP_METHODS)){return FALSE;}
		
		$this->requestMethod = $method;
		return TRUE;
	}
	
	public function setPostData($postData)
	{
		if(strlen($postData) < 1){return FALSE;}
		
		$this->postData = $postData;
		return TRUE;
	}
	
	public function setQueryString($queryString)
	{
		if(strlen($queryString) < 1){return FALSE;}
		
		if(strpos($this->requestPath,'?') !== FALSE){
			$this->requestPath .= '&'.$queryString;
		} else {
			$this->requestPath .= '?'.$queryString;
		}
		
		return TRUE;
	}
	
	public function setMultipartData($name, $fileName, $mimeType, $body)
	{
		if(strlen($name) < 1){return FALSE;}
		if(strlen($fileName) < 1){return FALSE;}
		if(strlen($mimeType) < 1){$mimeType = 'application/octet-stream';}
		if(strlen($body) < 1){return FALSE;}
		
		$this->multipartData[] = array(
			'name' => $name,
			'file_name' => $fileName,
			'mime_type' => $mimeType,
			'body' => $body,
		);
		
		return TRUE;
	}
	
	public function connect($timeoutSec = 0)
	{
		if($timeoutSec < 1){$timeoutSec = self::DEFAULT_TIMEOUT_SEC;}
		
		$fsockopenHost = $this->requestHost;
		if($this->requestPort == 443){$fsockopenHost = 'tls://'.$this->requestHost;}	# HTTPS
		
		$sock = @fsockopen($fsockopenHost, $this->requestPort, $errno, $errstr, $timeoutSec);
		if(!$sock){return FALSE;}
		
		$this->prepareHttpRequestHeaderAndPostDataForConnect();
		
		#----------------------------------------
		
		$requestBuffer  = $this->requestMethod.' '.$this->requestPath.' HTTP/1.1'.self::CRLF;
		$requestBuffer .= 'Host: '.$this->requestHost.''.self::CRLF;
		foreach($this->requestHeader as $key => $value){
			if(strlen($value) > 0){
				$requestBuffer .= $key.': '.$value.''.self::CRLF;
			}
		}
		$requestBuffer .= 'Connection: close'.self::CRLF;
		$requestBuffer .= self::CRLF;
		$requestBuffer .= $this->postData;
		
		@fwrite($sock, $requestBuffer);
		
		#----------------------------------------
		
		$this->readHttpResponseStatusAndHeader($sock);
		$this->responseBody = '';

		$responseBuffer = '';
		
		while(!@feof($sock)){
			$responseBuffer .= @fread($sock, self::READ_CHUNK_SIZE);
		}
		
		@fclose($sock);
		
		#----------------------------------------
		
		$transferEncoding = strtolower($this->responseHeader['transfer-encoding']);
		$contentEncoding = strtolower($this->responseHeader['content-encoding']);
		
		if($transferEncoding == 'chunked'){
			$unchunk = '';
			
			$cursor = 0;
			$length = strlen($responseBuffer);
			
			while($cursor < $length){
				$chunkSizeEndPos = strpos($responseBuffer, self::CRLF, $cursor);
				if($chunkSizeEndPos === FALSE){break;}
				
				$chunkSize = hexdec( substr($responseBuffer, $cursor, ($chunkSizeEndPos - $cursor)) );
				
				$cursor = $chunkSizeEndPos + 2;	# strlen("\r\n") = 2
				
				$unchunk .= substr($responseBuffer, $cursor, $chunkSize);
				
				$cursor += $chunkSize + 2;	# strlen("\r\n") = 2
			}
			
			$responseBuffer = $unchunk;
		}
		
		$this->responseBody = self::uncompress($contentEncoding, $responseBuffer);
		
		#----------------------------------------
		
		# HTTPステータスが 200番台, 300番台だったらTRUE、それ以外はFALSE
		return (200 <= $this->responseStatus && $this->responseStatus < 400) ? TRUE : FALSE;
	}
	
	/**
	 * このメソッドが呼び出される以前に設定されたPOSTデータ, multipart/form-dataをもとに
	 * HTTPリクエストヘッダを調整する
	 */
	private function prepareHttpRequestHeaderAndPostDataForConnect()
	{
		if(strlen($this->postData) > 0){
			# Content-Typeの判別(指定がない場合のみ)
			if(!$this->requestHeader['Content-Type']){
				if(strpos($this->postData,'<?xml') === 0){	# SOAP
					$this->requestHeader['Content-Type'] = 'text/xml';
				} else {	# フォームデータとして扱う
					$this->requestHeader['Content-Type'] = 'application/x-www-form-urlencoded';
				}
			}
		}
		
		if(count($this->multipartData) > 0){
			# multipart/form-data
			$multipartFormData = '';
			
			$boundary = ''.md5(uniqid(microtime())).''.md5(uniqid(microtime())).'';
			
			# フォームデータ部
			if(strlen($this->postData) > 0){
				if($this->requestHeader['Content-Type'] == 'application/x-www-form-urlencoded'){
					$formData = explode('&', $this->postData);
					foreach( $formData as $line ){
						list($key, $value) = explode('=', $line);
						
						$multipartFormData .= '--'.$boundary.''.self::CRLF;
						$multipartFormData .= 'Content-Disposition: form-data; name="'.str_replace('"','\\"',rawurldecode($key)).'"'.self::CRLF;
						$multipartFormData .= self::CRLF;
						$multipartFormData .= rawurldecode($value).''.self::CRLF;
					}
				} else {	# フォームデータ以外の場合はそのまま送る(たぶん使われない)
					$multipartFormData .= '--'.$boundary.''.self::CRLF;
					$multipartFormData .= 'Content-Type: '.$this->requestHeader['Content-Type'].''.self::CRLF;
					$multipartFormData .= self::CRLF;
					$multipartFormData .= $this->postData.''.self::CRLF;
				}
			}
			
			# ファイル部
			foreach( $this->multipartData as $data ){
				$multipartFormData .= '--'.$boundary.''.self::CRLF;
				$multipartFormData .= 'Content-Type: '.$data['mime_type'].''.self::CRLF;
				$multipartFormData .= 'Content-Disposition: form-data; name="'.str_replace('"','\\"',$data['name']).'"; filename="'.str_replace('"','\\"',$data['file_name']).'"'.self::CRLF;
				$multipartFormData .= self::CRLF;
				$multipartFormData .= $data['body'].''.self::CRLF;
			}
			
			$multipartFormData .= '--'.$boundary.'--';	# 終端
			
			# 上書き設定
			$this->requestHeader['Content-Type'] = 'multipart/form-data; boundary='.$boundary;
			$this->postData = $multipartFormData;
		}
		
		if(strlen($this->postData) > 0){
			$this->requestHeader['Content-Length'] = strlen($this->postData);
		}
		
		$acceptEncodingList = self::getAcceptEncodingList();
		if(count($acceptEncodingList) > 0){
			$this->requestHeader['Accept-Encoding'] = join(', ', $acceptEncodingList);
		}
	}
	
	/**
	 * 引数として渡されたストリームからHTTPレスポンス(ステータスとヘッダ)を読み取る
	 * 
	 * @param resource $sock fsockopenなどで開いたストリーム
	 * @return bool HTTPレスポンスステータスが読み取れた場合true
	 */
	private function readHttpResponseStatusAndHeader($sock)
	{
		$line = rtrim(fgets($sock));
		if (!preg_match('/^HTTP\/1\.\d (\d+)/', $line, $match)) {
			return false;
		}
		
		$this->responseStatus = intval($match[1]);
		$this->responseHeader = array();
		
		while (strlen($line) > 0) {
			$line = rtrim(fgets($sock));
			if (preg_match('/^([^:]+): (.+)$/', $line, $match)) {
				$key = strtolower($match[1]);
				$this->responseHeader[$key] = $match[2];
			}
		}
		
		return true;
	}
	
	/**
	 * TCP接続してsocketを返す(StreamingAPI用)
	 * 
	 * 注意: このメソッドは十分にテストされていません
	 * 
	 * @param float $timeoutSec stream_socket_clientに引数として渡すタイムアウト秒数
	 * @return resource stream_socket_clientの返り値
	 */
	public function connectStreaming($timeoutSec = 0)
	{
		if ($timeoutSec < 1) {$timeoutSec = self::DEFAULT_TIMEOUT_SEC;}
		
		$transport = 'tcp';
		if ($this->requestPort == 443) {$transport = 'tls';}
		
		$streamURI = sprintf("%s://%s:%d", $transport, $this->requestHost, $this->requestPort);
		
		$socket = stream_socket_client($streamURI, $errno, $errstr, $timeoutSec);
		if (!$socket) {return false;}
		
		$this->prepareHttpRequestHeaderAndPostDataForConnect();
		
		$requestBuffer  = $this->requestMethod.' '.$this->requestPath.' HTTP/1.1'.self::CRLF;
		$requestBuffer .= 'Host: '.$this->requestHost.''.self::CRLF;
		foreach ($this->requestHeader as $key => $value) {
			if(strlen($value) > 0){
				$requestBuffer .= $key.': '.$value.''.self::CRLF;
			}
		}
		$requestBuffer .= 'Connection: close'.self::CRLF;
		$requestBuffer .= self::CRLF;
		$requestBuffer .= $this->postData;
		
		fwrite($socket, $requestBuffer);
		
		$this->readHttpResponseStatusAndHeader($socket);
		
		return $socket;
	}
	
	public function getResponseStatus()
	{
		return $this->responseStatus;
	}
	
	public function getResponseHeader($key)
	{
		$key = strtolower($key);
		return $this->responseHeader[$key];
	}
	
	public function getResponseHeaderAll()
	{
		return $this->responseHeader;
	}
	
	public function getResponseBody()
	{
		return $this->responseBody;
	}
	
	private static function getAcceptEncodingList()
	{
		$acceptEncodingList = array();
		if(function_exists('gzinflate')){$acceptEncodingList[] = 'gzip';}
		if(function_exists('gzuncompress')){$acceptEncodingList[] = 'deflate';}
		return $acceptEncodingList;
	}
	
	private static function uncompress($encoding, $compressed)
	{
		switch($encoding){
			case 'deflate': return gzuncompress($compressed);
			case 'gzip': return gzinflate(substr($compressed,10));	# zlibヘッダを取ればgzinflateでデコードできる
			default: return $compressed;
		}
	}
}

return TRUE;

?>