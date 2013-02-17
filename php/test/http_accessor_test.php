<?php

include('../http_accessor.inc.php');

class Maruamyu_Core_HttpAccessorTest extends PHPUnit_Framework_TestCase
{
	const TEST_HOST = 'localhost';
	
	# インスタンスを作成できる
	public function testNewInstance()
	{
		$httpAccessor = new Maruamyu_Core_HttpAccessor();
		$this->assertInstanceOf('Maruamyu_Core_HttpAccessor',$httpAccessor);
	}
	
	# ホストとポートを指定できる
	public function testSetRequestHostAndPort()
	{
		$httpAccessor = new Maruamyu_Core_HttpAccessor();
		$this->assertTrue($httpAccessor->setRequestHostAndPort(self::TEST_HOST,80));
		$this->assertTrue($httpAccessor->setRequestHostAndPort(self::TEST_HOST));	# 省略可能
	}
	
	# パスを指定できる
	public function testSetRequestPath()
	{
		$httpAccessor = new Maruamyu_Core_HttpAccessor();
		$this->assertTrue($httpAccessor->setRequestPath('/'));
	}
	
	# ホストとポートとパスをURLから指定できる
	public function testSetRequestUrl()
	{
		$httpAccessor = new Maruamyu_Core_HttpAccessor();
		$this->assertTrue($httpAccessor->setRequestUrl('http://'.self::TEST_HOST.'/'));
		$this->assertTrue($httpAccessor->setRequestUrl('https://'.self::TEST_HOST.'/haruka'));
		$this->assertTrue($httpAccessor->setRequestUrl('http://'.self::TEST_HOST.':80/'));
		$this->assertTrue($httpAccessor->setRequestUrl('http://'.self::TEST_HOST.':72/chihaya'));
	}
	
	# URLを指定してインスタンスを作成できる
	public function testNewInstanceSetUrl()
	{
		$httpAccessor = new Maruamyu_Core_HttpAccessor('http://'.self::TEST_HOST.'/?yukiho');
		$this->assertInstanceOf('Maruamyu_Core_HttpAccessor',$httpAccessor);
	}
	
	# ホストとポートとパスを指定してインスタンスを作成できる
	public function testNewInstanceSetHostAndPortAndPath()
	{
		$httpAccessor = new Maruamyu_Core_HttpAccessor(self::TEST_HOST, 841, '/yayoi');
		$this->assertInstanceOf('Maruamyu_Core_HttpAccessor',$httpAccessor);
	}
	
	# リクエストヘッダが指定できる
	public function testSetRequestHeader()
	{
		$httpAccessor = new Maruamyu_Core_HttpAccessor();
		$this->assertTrue($httpAccessor->setRequestHeader('User-Agent', '765PRO'));
	}
	
	# メソッドが指定できる
	public function testSetRequestMethod()
	{
		$httpAccessor = new Maruamyu_Core_HttpAccessor();
		$this->assertTrue($httpAccessor->setRequestMethod('GET'));
		$this->assertTrue($httpAccessor->setRequestMethod('POST'));
		$this->assertTrue($httpAccessor->setRequestMethod('HEAD'));
		$this->assertFalse($httpAccessor->setRequestMethod('PUT'));	# いまのところ未対応とする
		$this->assertFalse($httpAccessor->setRequestMethod('DELETE'));	# いまのところ未対応とする
	}
	
	# GETメソッドでアクセスできる
	public function testConnectGetMethod()
	{
		$httpAccessor = new Maruamyu_Core_HttpAccessor();
		$httpAccessor->setRequestUrl('http://'.self::TEST_HOST.'/');
		#	$httpAccessor->setRequestMethod('GET');	# デフォルトGET
		$this->assertTrue($httpAccessor->connect());
	}
	
	# POSTメソッドでアクセスできる
	public function testConnectPostMethod()
	{
		$httpAccessor = new Maruamyu_Core_HttpAccessor();
		$httpAccessor->setRequestUrl('http://'.self::TEST_HOST.'/post.php');
		$httpAccessor->setRequestMethod('POST');
		$httpAccessor->setPostData('ritsuko=megane');
		$this->assertTrue($httpAccessor->connect());
	}
	
	# HEADメソッドでアクセスできる
	public function testConnectHeadMethod()
	{
		$httpAccessor = new Maruamyu_Core_HttpAccessor();
		$httpAccessor->setRequestUrl('http://'.self::TEST_HOST.'/');
		$httpAccessor->setRequestMethod('HEAD');
		$this->assertTrue($httpAccessor->connect());
	}
	
	# マルチパートデータが設定できる
	public function testSetMultipartData()
	{
		$httpAccessor = new Maruamyu_Core_HttpAccessor();
		$this->assertTrue($httpAccessor->setMultiPartData('azusa', 'f91.txt', 'text/plain', 'toratan'));
	}
	
	# マルチパートでPOSTできる
	public function testSetMultipartDataAndPostConnect()
	{
		$httpAccessor = new Maruamyu_Core_HttpAccessor();
		$httpAccessor->setRequestUrl('http://'.self::TEST_HOST.'/post.php');
		$httpAccessor->setRequestMethod('POST');
		$httpAccessor->setMultiPartData('iori', 'deko.txt', 'text/plain', 'くぎゅううううう');
		$this->assertTrue($httpAccessor->connect());
	}
	
	# マルチパートとフォームデータを混在できる
	public function testSetMultipartDataAndPostData()
	{
		$httpAccessor = new Maruamyu_Core_HttpAccessor();
		$httpAccessor->setRequestUrl('http://'.self::TEST_HOST.'/post.php');
		$httpAccessor->setRequestMethod('POST');
		$httpAccessor->setPostData('makkomakko=rin');
		$httpAccessor->setMultiPartData('ami', 'mami.txt', 'text/plain', 'とかちつくちて');
		$this->assertTrue($httpAccessor->connect());
	}
	
	# HTTPステータスコードを受け取れる
	public function testGetResponseStatus()
	{
		$httpAccessor = new Maruamyu_Core_HttpAccessor();
		$httpAccessor->setRequestUrl('http://'.self::TEST_HOST.'/?miki=nano');
		$httpAccessor->connect();
		$this->assertNotNull($httpAccessor->getResponseStatus());
	}
	
	# レスポンスヘッダを受け取れる
	public function testGetResponseHeader()
	{
		$httpAccessor = new Maruamyu_Core_HttpAccessor();
		$httpAccessor->setRequestUrl('http://'.self::TEST_HOST.'/?hibiki=hamuzo');
		$httpAccessor->connect();
		$this->assertNotNull($httpAccessor->getResponseHeader('Date'));
	}
	
	# レスポンス内容を受け取れる
	public function testGetResponseBody()
	{
		$httpAccessor = new Maruamyu_Core_HttpAccessor();
		$httpAccessor->setRequestUrl('http://'.self::TEST_HOST.'/?takane=ramen');
		$httpAccessor->connect();
		$this->assertNotNull($httpAccessor->getResponseBody());
	}
}

return TRUE;

?>