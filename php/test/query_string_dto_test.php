<?php

include('../query_string_dto.inc.php');

class Maruamyu_Core_QueryStringDtoTest extends PHPUnit_Framework_TestCase
{
	# インスタンスを作成できる
	public function testNewInstance()
	{
		$queryStringDto = new Maruamyu_Core_QueryStringDto();
		$this->assertInstanceOf('Maruamyu_Core_QueryStringDto',$queryStringDto);
	}
	
	# 値を設定できる
	public function testInsertValue()
	{
		$queryStringDto = new Maruamyu_Core_QueryStringDto();
		$this->assertTrue($queryStringDto->insert('haruka','えりりん'));
	}
	
	# 値を文字列で取得できる
	public function testGetStringValue()
	{
		$queryStringDto = new Maruamyu_Core_QueryStringDto();
		$queryStringDto->insert('chihaya','ミンゴス');
		$this->assertSame('ミンゴス',$queryStringDto->getStringValue('chihaya'));
	}
	
	# 値を追加できる
	public function testInsertAndInsert()
	{
		$queryStringDto = new Maruamyu_Core_QueryStringDto();
		$queryStringDto->insert('yukiho','ゆりしー');
		$queryStringDto->insert('yukiho','あずみん');
		$this->assertSame('ゆりしーあずみん',$queryStringDto->getStringValue('yukiho'));
	}
	
	# 値を更新できる
	public function testUpdate()
	{
		$queryStringDto = new Maruamyu_Core_QueryStringDto();
		$queryStringDto->insert('ritsuko','ナオミッティー');
		$queryStringDto->update('ritsuko','神');
		$this->assertSame('神',$queryStringDto->getStringValue('ritsuko'));
	}
	
	# 値を削除できる
	public function testDelete()
	{
		$queryStringDto = new Maruamyu_Core_QueryStringDto();
		$queryStringDto->insert('miki','961pro');
		$queryStringDto->delete('miki');
		$this->assertSame('',$queryStringDto->getStringValue('961pro'));
	}
	
	# クエリストリングで入力できる
	public function testInputQueryString()
	{
		$queryStringDto = new Maruamyu_Core_QueryStringDto();
		$queryStringDto->inputQueryString('azusa=%E3%83%81%E3%82%A2%E3%82%AD%E3%83%B3%E3%82%B0&iori=%E3%81%8F%E3%81%8E%E3%82%85&makoto=%E3%81%B2%E3%82%8D%E3%82%8A%E3%82%93');
		$this->assertSame('チアキング',$queryStringDto->getStringValue('azusa'));
		$this->assertSame('くぎゅ',$queryStringDto->getStringValue('iori'));
		$this->assertSame('ひろりん',$queryStringDto->getStringValue('makoto'));
	}
	
	# 配列で入力できる
	public function testInputArray()
	{
		$queryStringDto = new Maruamyu_Core_QueryStringDto();
		$queryStringDto->inputArray( array( 'amimami' => 'あさぽん', 'kotori' => 'じゅりきち' ) );
		$this->assertSame('あさぽん',$queryStringDto->getStringValue('amimami'));
		$this->assertSame('じゅりきち',$queryStringDto->getStringValue('kotori'));
	}
	
	# クエリストリングからインスタンスを作成できる
	public function testNewInstanceSetQueryString()
	{
		$queryStringDto = new Maruamyu_Core_QueryStringDto('miki=%E3%82%A2%E3%83%83%E3%82%AD%E3%83%BC&hibiki=%E3%81%AC%E3%83%BC%E3%81%AC%E3%83%BC&takane=%E3%81%AF%E3%82%89%E3%81%BF%E3%83%BC');
		$this->assertSame('アッキー',$queryStringDto->getStringValue('miki'));
		$this->assertSame('ぬーぬー',$queryStringDto->getStringValue('hibiki'));
		$this->assertSame('はらみー',$queryStringDto->getStringValue('takane'));
	}
	
	# 配列からインスタンスを作成できる
	public function testNewInstanceSetArray()
	{
		$array = array(
			'udzuki' => 'はっしー',
			'rin' => 'ふーりん',
			'mika' => 'はるきゃん',
		);
		
		$queryStringDto = new Maruamyu_Core_QueryStringDto($array);
		
		$this->assertSame($array['udzuki'],$queryStringDto->getStringValue('udzuki'));
		$this->assertSame($array['rin'],$queryStringDto->getStringValue('rin'));
		$this->assertSame($array['mika'],$queryStringDto->getStringValue('mika'));
	}
	
	# 値を区切り文字を指定して文字列で取得できる
	public function testGetStringValueJoinStr()
	{
		$queryStringDto = new Maruamyu_Core_QueryStringDto();
		$queryStringDto->insert('MP01','春香');
		$queryStringDto->insert('MP01','雪歩');
		$queryStringDto->insert('MP01','律子');
		$this->assertSame('春香・雪歩・律子',$queryStringDto->getStringValue('MP01','・'));
	}
	
	# 存在しないキーを指定すると空文字
	public function testGetStringValueEmpty()
	{
		$queryStringDto = new Maruamyu_Core_QueryStringDto();
		$this->assertSame('',$queryStringDto->getStringValue('XENOGLOSSIA'));
	}
	
	# キーの一覧の初期値は空
	public function testGetKeysEmpty()
	{
		$queryStringDto = new Maruamyu_Core_QueryStringDto();
		$this->assertCount(0,$queryStringDto->getKeys());
	}
	
	# キーの一覧が取得できる
	public function testGetKeys()
	{
		$queryStringDto = new Maruamyu_Core_QueryStringDto();
		$queryStringDto->insert('765pro','高木');
		$queryStringDto->insert('961pro','黒井');
		$queryStringDto->insert('876pro','石川');
		
		$keys = $queryStringDto->getKeys();
		sort($keys);
		
		$this->assertCount(3,$keys);
		$this->assertSame(array('765pro','876pro','961pro'),$keys);
	}
	
	# OAuth用QUERY_STRINGが取得できる
	public function testGetOAuthQueryString()
	{
		$queryStringDto = new Maruamyu_Core_QueryStringDto();
		$queryStringDto->insert('aaa','~');
		$queryStringDto->insert('ccc','-');
		$queryStringDto->insert('bbb','_');
		$this->assertSame('aaa=~&bbb=_&ccc=-',$queryStringDto->getOAuthQueryString());
	}
}

?>