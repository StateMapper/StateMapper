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

namespace StateMapper; 

if (!defined('BASE_PATH'))
	die();

class BulletinFetcherPdf extends BulletinFetcherFormat {
	
	private $parent = null;
	
	public function __construct($parent = null){
		if ($parent){
			$this->parent = $parent;
		}
	}
	
	function get_format_label(){
		return 'PDF document';
	}
	
	public function get_content_path($filePath, $processedFilePrefix = null){
		return $filePath.($processedFilePrefix ? $processedFilePrefix : '.txt');
	}
	
	public function fetch_is_done($filePath, $fetchProcessedPrefix = false){
		$txtFilePath = $this->get_content_path($filePath, $fetchProcessedPrefix);

		if (!file_exists($txtFilePath)){
			
			$cmd = 'cd "'.dirname($txtFilePath).'" && pdftotext -eol unix -nopgbrk -layout -enc UTF-8 "'.basename($filePath).'" "'.basename($txtFilePath).'"';
			
			for ($i=0; $i<3; $i++){ // max 3 retries
				@unlink($txtFilePath);
				
				$return_var = 1;
				@exec($cmd, $output, $return_var);
				if (empty($return_var) && file_exists($txtFilePath))
					break;
				sleep(1); // wait 1s between retries
			}
			
			if (!empty($return_var) || !file_exists($txtFilePath))
				return new SMapError('cannot pdftotext '.strip_root($filePath).' to '.strip_root($txtFilePath));
			
			if (IS_CLI)
				print_log('pdftotext written to '.strip_root($txtFilePath));
		}
		return true;
	}
	
	/*
	 * download mode: downloading a file
	 * fetch mode: viewing a file from an iframe
	 * lint: ??
	 */
	 
	public function serve_bulletin($bulletin, $printMode = 'download', $title = null, $query = array()){
		if (empty($bulletin['filePath']))
			die_error('no filePath to serve');
		
		//if ($printMode == 'download' || $printMode == 'fetch'){
		//if (empty($_GET['human'])){
			
			if (!empty($query['lint'])){
				
				// regenerate lint on-the-fly
				$filePath = $this->get_content_path($bulletin['filePath']);
				if (!file_exists($filePath)){
					$error = $this->fetch_is_done($bulletin['filePath']);
					if ($error !== true)
						die_error($error);
				}
					
				serve_file($filePath, 'text/plain', $printMode == 'download', $title);
			
			} else {
				serve_file($bulletin['filePath'], 'application/pdf', $printMode == 'download', $title);
			}
				
//		} else 
	//		return $bulletin['content']; // 'lint' printMode
	}
}
