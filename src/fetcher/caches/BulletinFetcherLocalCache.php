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

class BulletinFetcherLocalCache extends BulletinFetcherCache {

	public function get_label(){
		return 'local cache';
	}
		
	public function get_content_uri(){
		return DATA_PATH.parent::get_content_uri();
	}
	
	public function fetch_url($url, &$formatFetcher){
//		echo 'fetch_url: '.$url.' to '.$this->filePath.'<br>';
		
		$fileDir = $this->init_file_dir($this->fileUri);
		
		if (is_error($fileDir))
			return $fileDir;
			
		if (!fetch($url, array(), true, $this->fileUri))
			return new SMapError('cannot fetch url '.$url);
		
		return array(
			'format' => $this->protocoleConfig->format,
			'cached' => false,
			'schema' => $this->query['schema'],
			'filePath' => $this->fileUri
		);
	}
	
	public function init_file_dir($filePath){
		//echo "INIT DIR ".$filePath.'<br>';
		$fileDir = dirname($filePath);
		if (!file_exists($fileDir)){
			if (!is_writable(DATA_PATH))
				return new SMapError('folder '.DATA_PATH.' not writtable');
			if (!mkdir($fileDir, 0777, true))
				return new SMapError('folder '.$fileDir.' cannot be created');
		}
		return $fileDir;
	}
	
	public function save_content($fetched, &$formatFetcher, $processedFilePrefix = false){
//		echo 'save_content<br>';
		
		if ($processedFilePrefix !== false){ 
			// really save this, the others are directly saved to disk via curl

			if (empty($processedFilePrefix))
				die_error('bad $processedFilePrefix');
			
			$fileDir = $this->init_file_dir($this->fileUri);
			if (is_error($fileDir))
				return $fileDir;
				
			//echo "WRITTING CACHE TO ".$this->fileUri.$processedFilePrefix.'<br>';
			
			if (is_object($fetched) || is_array($fetched)){
				$output = @json_encode($fetched, JSON_UNESCAPED_UNICODE);
				if (empty($output)){
					$output = @json_encode(utf8_recursive_encode($fetched), JSON_UNESCAPED_UNICODE);
					if (empty($output))
						return new SMapError('error saving content to local cache: '.json_last_error_msg());
				}
			
			} else if (empty($output))
				return new SMapError('error saving content: empty content');
			
			else
				$output = $fetched;
				
			
			for ($i = 1; $i <= 5; $i++){ // try writing each file up to 5 times
				
				if (file_put_contents($this->fileUri.$processedFilePrefix, $output)){
					
					if (IS_CLI)
						print_log('saved parsed file to '.$this->fileUri.$processedFilePrefix);
						
					return true;
				
				} else if ($i != 5) // except last failed attempt
					usleep(500000 * max($i, 2)); // wait half a second between first and second write attempt, then 1s
			}
				
			return new SMapError('could not save processed content to '.$this->fileUri.$processedFilePrefix.' (tries 5 times)');
		}
		
		$success = $formatFetcher->fetch_is_done($this->fileUri, $processedFilePrefix);
		
		if (is_error($success)){
			
			// clean if error
			@unlink($this->fileUri);
			return $success;
		}
		
		$content = $this->retrieve_content($formatFetcher, true);
		if (!$content)
			return new SMapError('bad content after fetched: '.$this->fileUri);
		return $content;
	}
	
	public function retrieve_content(&$formatFetcher, $justCreated = false, &$fetchedOrigin = null, $processedFilePrefix = null, $onlyTestIfExists = false){
		$filePath = $formatFetcher->get_content_path($this->fileUri, $processedFilePrefix);

		if (!file_exists($filePath)){
			//echo "NOT FOUND: ".$filePath."<br>";
			if (IS_CLI)
				print_log('not found in cache: '.strip_root($filePath), array('color' => 'grey'));
			return false;
		}
		//echo "FOUND: ".$filePath."<br>";
		
		if ($onlyTestIfExists)
			return true;
		
		if (IS_CLI)
			print_log('found in cache: '.strip_root($filePath), array('color' => 'grey'));
		
		$content = file_get_contents($filePath);
		
		if (trim($content) == ''){
			@unlink($this->fileUri);
			@unlink($filePath);
			
			if (IS_CLI)
				print_log('deleted empty file from local disk: '.strip_root($filePath), array('color' => 'red'));
			
//			return new SMapError('fetched empty file at '.$filePath);
			return false;
		}
			
		if (!$content)
			return new SMapError('cannot read file '.strip_root($filePath).' from local disk');
			
		if ($processedFilePrefix)
			return json_decode($content, true);

		$content = str_replace('●', "\n", $content); 
		
		$encoding = $formatFetcher->detect_encoding($content);
		
		//$content = convert_encoding($content, $encoding);
		
//		$enc = 'ISO-8859-1'; /// mb_detect_encoding($content, mb_detect_order(), true)
		//$content = iconv($enc, "UTF-8", $content);

		return array(
			'format' => is_object($this->protocoleConfig) ? $this->protocoleConfig->format : $this->protocoleConfig,
			'content' => $content,
			'cached' => !$justCreated,
			'filePath' => $this->fileUri
		);
	}

}	
