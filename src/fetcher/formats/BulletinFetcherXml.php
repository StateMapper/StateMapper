<?php
/*
 * StateMapper: worldwide, collaborative, public data reviewing and monitoring tool.
 * Copyright (C) 2017  StateMapper.net <statemapper@riseup.net>
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
