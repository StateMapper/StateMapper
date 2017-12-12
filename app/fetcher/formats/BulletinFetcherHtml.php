<?php

if (!defined('BASE_PATH'))
	die();

class BulletinFetcherHtml extends BulletinFetcherFormat {
	
	private $parent = null;
	
	public function __construct($parent = null){
		if ($parent){
			$this->parent = $parent;
		}
	}
	
	public function getContentFilePath($filePath, $processedFilePrefix = null){
		return $filePath;
	}
	
	public function fetchFileDone($filePath, $fetchProcessedPrefix){
		return true;
	}
	
	public function serveBulletin($bulletin, $printMode = 'download', $title = null, $query = array()){
		if (empty($bulletin['filePath']))
			kaosDie('no filePath to serve');
			
		if ($printMode == 'download')
			serveFile($bulletin['filePath'], 'text/html', $printMode == 'download', $title);
		
		$content = preg_replace('#^.*<body[^>]*>(.*)</body>.*$#ius', '$1', $bulletin['content']); // keep only body content
		
		$content = preg_replace('#<(?:br|img)[^>]*>#ius', "\n", $content); // remove br and img nodes

		$content = preg_replace('#<!--.*?-->#ius', '', $content); // remove HTML comments
		
		$content = preg_replace('#<((?:no)?script|style|svg|object|iframe)[^>]*>(.*?)</\1>#ius', '', $content); // remove useless HTML nodes
		
		if ($printMode == 'lint'){
			
			// extract URLs from HTML tags
			$content = preg_replace('/<([^>]*(https?:\/\/[^>\s"\']+)[^>]*)>/', "\n".'$2'."\n\n", $content);
			
			$baseUrl = $this->parent->fetchBulletin($query, 'return');
			$baseUrl = parse_url($baseUrl);
			$this->args['baseUrl'] = $baseUrl['scheme'].'://'.$baseUrl['host'];
			$this->args['baseUri'] = rtrim($baseUrl['path'], '/');
			
			$content = preg_replace_callback('/<([^>]*href=(["\'])((?!#)[^>"\']+)\2[^>]*)>/', array(&$this, 'replaceUrls'), $content);
			
			// remove all HTML tags (leaving inner content)
			$content = preg_replace('/<([^>]*)>/', '', $content); 
		}
		
		$content = trim(preg_replace("#(\n\s*){2,}#ius", "\n\n", $content)); // lint double line-breaks
		
		return $content;
	}
	
	function replaceUrls($m){
		$url = $m[3];
		if (!preg_match('#^https?://.*#iu', $url)){
			if ($url[0] == '/')
				$url = $this->args['baseUrl'].$url;
			else
				$url = $this->args['baseUrl'].$this->args['baseUri'].'/'.$url;
		}
		return "\n".$url."\n\n";
	}
}
