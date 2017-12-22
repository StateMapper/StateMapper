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

class BulletinFetcherPdf extends BulletinFetcherFormat {
	
	private $parent = null;
	
	public function __construct($parent = null){
		if ($parent){
			$this->parent = $parent;
		}
	}
	
	function getFormatLabel(){
		return 'PDF document';
	}
	
	public function getContentFilePath($filePath, $processedFilePrefix = null){
		return $filePath.($processedFilePrefix ? $processedFilePrefix : '.txt');
	}
	
	public function fetchFileDone($filePath, $fetchProcessedPrefix){
		$txtFilePath = $this->getContentFilePath($filePath, $fetchProcessedPrefix);

		if (!file_exists($txtFilePath)){
			
			$cmd = 'cd "'.dirname($txtFilePath).'" && pdftotext -eol unix -nopgbrk -layout -enc UTF-8 "'.basename($filePath).'" "'.basename($txtFilePath).'"';
			
			for ($i=0; $i<3; $i++){
				@unlink($txtFilePath);
				
				$return_var = 1;
				@exec($cmd, $output, $return_var);
				if (empty($return_var) && file_exists($txtFilePath))
					break;
				sleep(1); // wait 1s
			}
			
			if (!empty($return_var) || !file_exists($txtFilePath))
				return new KaosError('cannot pdftotext '.$filePath.' to '.$txtFilePath);
			
			if (KAOS_IS_CLI)
				kaosPrintLog('pdftotext written to '.$txtFilePath);
		}
		return true;
	}
	
	public function serveBulletin($bulletin, $printMode = 'download', $title = null, $query = array()){
		if (empty($bulletin['filePath']))
			kaosDie('no filePath to serve');
			
		if ($printMode == 'download' || $printMode == 'fetch')
			serveFile($bulletin['filePath'], 'application/pdf', $printMode == 'download', $title);
		else 
			return $bulletin['content']; // lint version
	}
}
