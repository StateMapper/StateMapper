<?php

if (!defined('BASE_PATH'))
	die();

class BulletinFetcherXml extends BulletinFetcherFormat {
	
	private $parent = null;
	
	public function __construct($parent = null){
		if ($parent){
			$this->parent = $parent;
		}
	}
	
	function getFormatLabel(){
		return 'XML document';
	}
	
	public function detectEncoding($content){
		return preg_match('#^\s*<\?xml[^>]*encoding="([^"]+)"#i', $content, $m) ? $m[1] : null;
	}
	
	public function getContentFilePath($filePath, $processedFilePrefix){
		return $filePath.($processedFilePrefix ? $processedFilePrefix : '');
	}
	
	public function fetchFileDone($filePath){
		if (preg_match('#^.{0,200}(<error)#is', file_get_contents($filePath)))
			return new KaosError('XML error returned for '.$filePath, array('type' => 'badFile'));
			
		return true;
	}
	
	public function serveBulletin($bulletin, $printMode = 'download', $title = null, $query = array()){
		if (empty($bulletin['filePath']))
			kaosDie('no filePath to serve');
		
		serveFile($bulletin['filePath'], 'application/xml', $printMode == 'download', $title);
	}	
}	
