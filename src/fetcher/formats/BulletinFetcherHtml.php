<?php
/*
 * StateMapper: worldwide, collaborative, public data reviewing and monitoring tool.
 * Copyright (C) 2017-2018  StateMapper.net <statemapper@riseup.net>
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 * 
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */ 
 

if (!defined('BASE_PATH'))
	die();

class BulletinFetcherHtml extends BulletinFetcherFormat {
	
	private $parent = null;
	
	public function __construct($parent = null){
		if ($parent){
			$this->parent = $parent;
		}
	}
	
	public function get_content_path($filePath, $processedFilePrefix = null){
		return $filePath;
	}
	
	public function fetch_is_done($filePath, $fetchProcessedPrefix){
		return true;
	}
	
	public function serve_bulletin($bulletin, $printMode = 'download', $title = null, $query = array()){
		if (empty($bulletin['filePath']))
			die_error('no filePath to serve');
			
		if ($printMode == 'download')
			serve_file($bulletin['filePath'], 'text/html', $printMode == 'download', $title);
		
		$content = preg_replace('#^.*<body[^>]*>(.*)</body>.*$#ius', '$1', $bulletin['content']); // keep only body content
		
		$content = preg_replace('#<(?:br|img)[^>]*>#ius', "\n", $content); // remove br and img nodes

		$content = preg_replace('#<!--.*?-->#ius', '', $content); // remove HTML comments
		
		$content = preg_replace('#<((?:no)?script|style|svg|object|iframe)[^>]*>(.*?)</\1>#ius', '', $content); // remove useless HTML nodes
		
		if ($printMode == 'lint'){
			
			// extract URLs from HTML tags
			$content = preg_replace('/<([^>]*(https?:\/\/[^>\s"\']+)[^>]*)>/', "\n".'$2'."\n\n", $content);
			
			$baseUrl = $this->parent->fetch_bulletin($query, 'return');
			$baseUrl = parse_url($baseUrl);
			$this->args['baseUrl'] = $baseUrl['scheme'].'://'.$baseUrl['host'];
			$this->args['baseUri'] = rtrim($baseUrl['path'], '/');
			
			$content = preg_replace_callback('/<([^>]*href=(["\'])((?!#)[^>"\']+)\2[^>]*)>/', array(&$this, 'replace_urls'), $content);
			
			// remove all HTML tags (leaving inner content)
			$content = preg_replace('/<([^>]*)>/', '', $content); 
		}
		
		$content = trim(preg_replace("#(\n\s*){2,}#ius", "\n\n", $content)); // lint double line-breaks
		
		return $content;
	}
	
	function replace_urls($m){
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
