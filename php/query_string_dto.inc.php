<?php

/*
	query_string_dto.inc.php - QUERY_STRING操作
		written by にゃー (mirai_iro)
		managed by まるあみゅ.ねっと (http://maruamyu.net/)
*/

class Maruamyu_Core_QueryStringDto
{
	private $data = array();
	
	public function __construct($input = NULL)
	{
		$this->initialize();
		
		if(is_array($input)){
			$this->inputArray($input);
		} else if(is_string($input)){
			$this->inputQueryString($input);
		}
	}
	
	public function initialize()
	{
		$this->data = array();
	}
	
	# QUERY_STRINGで入力
	public function inputQueryString($queryString)
	{
		if(strlen($queryString) > 0){
			$tmp = explode('&', $queryString);
			
			foreach( $tmp as $line ){
				list($key,$value) = explode('=', $line);
				
				$key = rawurldecode($key);
				$value = rawurldecode($value);
				
				if(!isset($this->data[$key])){$this->data[$key] = array();}
				$this->data[$key][] = $value;
			}
		}
	}
	
	# 配列で入力
	public function inputArray($queryArray)
	{
		if(count($queryArray) > 0){
			foreach( $queryArray as $key => $value ){
				if(!isset($this->data[$key])){$this->data[$key] = array();}
				
				if(is_array($value)){
					foreach( $value as $element ){
						$this->data[$key][] = $element;
					}
				} else {
					$this->data[$key][] = $value;
				}
			}
		}
	}
	
	# データの挿入
	public function insert($key, $value)
	{
		if(strlen($key) < 1){return FALSE;}
		
		if(!isset($this->data[$key])){$this->data[$key] = array();}
		$this->data[$key][] = $value;
		
		return TRUE;
	}
	
	# データの更新(置き換え)
	public function update($key, $value)
	{
		if(strlen($key) < 1){return FALSE;}
		
		$this->data[$key] = array($value);
		
		return TRUE;
	}
	
	# データの削除
	public function delete($key)
	{
		if(strlen($key) < 1){return FALSE;}
		
		unset($this->data[$key]);
		
		return TRUE;
	}
	
	# 値を文字列で取得
	public function getStringValue($key, $joinStr = '')
	{
		$buffer = '';
		
		if(isset($this->data[$key])){
			foreach( $this->data[$key] as $line ){
				if(strlen($line) > 0){
					$buffer .= $joinStr.$line;
				}
			}
			$buffer = substr($buffer, strlen($joinStr));
		}
		
		return $buffer;
	}
	
	# キーの一覧
	public function getKeys()
	{
		return array_keys($this->data);
	}
	
	# QUERY_STRINGの取得
	public function getQueryString()
	{
		$queryString = '';
		
		foreach( $this->data as $key => $values ){
			foreach( $values as $value ){
				$queryString .= '&'.rawurlencode($key).'='.rawurlencode($value);
			}
		}
		
		$queryString = substr($queryString,1);	# strlen('&') = 1
		
		return $queryString;
	}
	
	# OAuth用QUERY_STRINGの取得(keyのソート, RFC3986でのエンコード)
	public function getOAuthQueryString()
	{
		$queryString = '';
		
		$keys = array_keys($this->data);
		sort($keys, SORT_STRING);
		
		foreach( $keys as $key ){
			$values = $this->data[$key];
			
			if(count($values) > 0){
				sort($values, SORT_STRING);
			}
			
			foreach( $values as $value ){
				$queryString .= '&'.rawurlencode($key).'='.rawurlencode($value);
			}
		}
		
		$queryString = substr($queryString,1);	# strlen('&') = 1
		
		if(version_compare(PHP_VERSION, '5.3.0') < 0){	# PHP 5.3.0 以前は ~ がエンコードされている
			$queryString = str_replace('%7E', '~', $queryString);
		}
		
		return $queryString;
	}
}

return TRUE;

?>