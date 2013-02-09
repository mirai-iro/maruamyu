<?php

/*
	rdf.inc.php - RSS1.0ファイル生成
*/

abstract class Maruamyu_Core_RdfDtoAbstract
{
	public $uri = '';
	public $title = '';
	public $link = '';
	public $description = '';
	
	public function isValid()
	{
		if(strlen($this->uri) > 0 && strlen($this->title) > 0){
			return TRUE;
		} else {
			return FALSE;
		}
	}
	
	public function getLinkUrl()
	{
		$linkUrl = $this->uri;
		
		if(strlen($this->link) > 0){
			$linkUrl = $this->link;
		}
		
		return $linkUrl;
	}
	
	public function getEscapedUri(){
		return $this->escape($this->uri);
	}
	public function getEscapedTitle(){
		return $this->escape($this->title);
	}
	public function getEscapedLinkUrl(){
		return $this->escape($this->getLinkUrl());
	}
	public function getEscapedDescription(){
		return $this->escape($this->description);
	}
	
	public static function escape($buffer){
		return self::htmlspecialchars($buffer);
	}
	
	# オリジナル htmlspecialchars (二重変換防止)
	private static function htmlspecialchars($buffer)
	{
		$buffer = self::htmlspecialchars_decode($buffer);
		$buffer = htmlspecialchars($buffer, ENT_QUOTES);
		$buffer = str_replace('&#039;', '&#39;', $buffer);
		$buffer = str_replace('&amp;hearts;', '&hearts;', $buffer);
		$buffer = str_replace('&amp;#9825;',  '&#9825;',  $buffer);	# 白抜きハート
		$buffer = str_replace('&amp;eacute;', '&eacute;', $buffer);	# POK[e']MON
		return $buffer;
	}
	
	# オリジナル htmlspecialchars_decode
	private static function htmlspecialchars_decode($buffer)
	{
		$buffer = str_replace('&apos;', '\'', $buffer);
		$buffer = htmlspecialchars_decode($buffer, ENT_QUOTES);	# &lt; &gt; &quot; &amp; &#39; &#039;
		return $buffer;
	}
}

class Maruamyu_Core_RdfDto extends Maruamyu_Core_RdfDtoAbstract
{
	private $rdfImageDto = NULL;
	private $rdfItemDtoList = array();
	
	public function addItem($rdfItemDto)
	{
		if($rdfItemDto->isValid()){
			$this->rdfItemDtoList[] = $rdfItemDto;
			return TRUE;
		} else {
			return FALSE;
		}
	}
	
	public function setImage($rdfImageDto)
	{
		if($rdfImageDto->isValid()){
			$this->rdfImageDto = $rdfImageDto;
			return TRUE;
		} else {
			return FALSE;
		}
	}
	
	public function build()
	{
		if(!$this->isValid()){return NULL;}
		
		$rdfItemResourceTag = '';
		$rdfItemTag = '';
		foreach( $this->rdfItemDtoList as $rdfItemDto ){
			$rdfItemResourceTag .= $rdfItemDto->buildRdfItemResourceTag()."\n";
			$rdfItemTag .= $rdfItemDto->buildRdfItemTagHasLineBreak()."\n";
		}
		
		$rdfImageResourceTag = '';
		$rdfImageTag = '';
		if($this->rdfImageDto && $this->rdfImageDto->isValid()){
			$rdfImageResourceTag = $this->rdfImageDto->buildRdfImageResourceTag()."\n";
			$rdfImageTag = $this->rdfImageDto->buildRdfImageTagHasLineBreak()."\n";
		}
		
		$rdfTag  = '<?xml version="1.0" encoding="utf-8"?>'."\n";
		$rdfTag .= '<rdf:RDF xmlns="http://purl.org/rss/1.0/" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:dc="http://purl.org/dc/elements/1.1/" xml:lang="ja">'."\n\n";
		$rdfTag .= '<channel rdf:about="'.$this->getEscapedUri().'">'."\n";
		$rdfTag .= '<title>'.$this->getEscapedTitle().'</title>'."\n";
		$rdfTag .= '<link>'.$this->getEscapedLinkUrl().'</link>'."\n";
		$rdfTag .= '<description>'.$this->getEscapedDescription().'</description>'."\n";
		$rdfTag .= $rdfImageResourceTag;
		$rdfTag .= '<items>'."\n";
		$rdfTag .= '<rdf:Seq>'."\n";
		$rdfTag .= $rdfItemResourceTag;
		$rdfTag .= '</rdf:Seq>'."\n";
		$rdfTag .= '</items>'."\n";
		$rdfTag .= '</channel>'."\n\n";
		$rdfTag .= $rdfImageTag;
 		$rdfTag .= $rdfItemTag;
		$rdfTag .= '</rdf:RDF>';
		
		return $rdfTag;
	}
}

class Maruamyu_Core_RdfImageDto extends Maruamyu_Core_RdfDtoAbstract
{
	public $url = '';
	
	public function getUrl()
	{
		$url = $this->uri;
		
		if(strlen($this->url) > 0){
			$url = $this->url;
		}
		
		return $url;
	}
	
	public function getEscapedUrl(){
		return $this->escape($this->getUrl());
	}
	
	public function buildRdfImageResourceTag()
	{
		$rdfImageResourceTag = '';
		
		if($this->isValid()){
			$rdfImageResourceTag = '<image rdf:resource="'.$this->getEscapedUri().'" />';
		}
		
		return $rdfImageResourceTag;
	}
	
	public function buildRdfImageTag($hasLineBreak = FALSE)
	{
		$rdfImageTag = '';
		
		if($this->isValid()){
			$lineEnd = ($hasLineBreak) ? "\n" : '';
			
			$rdfImageTag .= '<image rdf:about="'.$this->getEscapedUri().'">'.$lineEnd;
			$rdfImageTag .= '<title>'.$this->getEscapedTitle().'</title>'.$lineEnd;
			$rdfImageTag .= '<link>'.$this->getEscapedLinkUrl().'</link>'.$lineEnd;
			$rdfImageTag .= '<url>'.$this->getEscapedUrl().'</url>'.$lineEnd;
			$rdfImageTag .= '</image>'.$lineEnd;
		}
		
		return $rdfImageTag;
	}
	
	public function buildRdfImageTagHasLineBreak()
	{
		return $this->buildRdfImageTag(TRUE);
	}
}

class Maruamyu_Core_RdfItemDto extends Maruamyu_Core_RdfDtoAbstract
{
	public $date = NULL;
	public $creator = '';
	public $subject = '';
	
	public function getFormattedDateTime()
	{
		$iso8601DateTime = '';
		
		if($this->date){
			$iso8601DateTime = gmstrftime('%Y-%m-%dT%H:%M:%S+00:00', $this->date);
		}
		
		return $iso8601DateTime;
	}
	
	public function getEscapedCreator(){
		return $this->escape($this->creator);
	}
	public function getEscapedSubject(){
		return $this->escape($this->subject);
	}
	
	public function buildRdfItemResourceTag()
	{
		$rdfItemResourceTag = '';
		
		if($this->isValid()){
			$rdfItemResourceTag = '<rdf:li rdf:resource="'.$this->getEscapedUri().'"/>';
		}
		
		return $rdfItemResourceTag;
	}
	
	public function buildRdfItemTag($hasLineBreak = FALSE)
	{
		$itemTag = '';
		
		if($this->isValid()){
			$lineEnd = ($hasLineBreak) ? "\n" : '';
			
			$itemTag .= '<item rdf:about="'.$this->getEscapedUri().'">'.$lineEnd;
			$itemTag .= '<title>'.$this->getEscapedTitle().'</title>'.$lineEnd;
			$itemTag .= '<link>'.$this->getEscapedLinkUrl().'</link>'.$lineEnd;
			$itemTag .= '<description>'.$this->getEscapedDescription().'</description>'.$lineEnd;
			if($this->date){
				$itemTag .= '<dc:date>'.$this->getFormattedDateTime().'</dc:date>'.$lineEnd;
			}
			if(strlen($this->creator) > 0){
				$itemTag .= '<dc:creator>'.$this->getEscapedCreator().'</dc:creator>'.$lineEnd;
			}
			if(strlen($this->subject) > 0){
				$itemTag .= '<dc:subject>'.$this->getEscapedSubject().'</dc:subject>'.$lineEnd;
			}
			$itemTag .= '</item>'.$lineEnd;
		}
		
		return $itemTag;
	}
	
	public function buildRdfItemTagHasLineBreak()
	{
		return $this->buildRdfItemTag(TRUE);
	}
}

?>