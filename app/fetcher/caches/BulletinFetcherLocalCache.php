<?php

if (!defined('BASE_PATH'))
	die();

class BulletinFetcherLocalCache extends BulletinFetcherCache {

	public function getLabel(){
		return 'local cache';
	}
		
	public function getContentUri(){
		return DATA_PATH.parent::getContentUri();
	}
	
	public function fetchUrl($url, &$formatFetcher){
//		echo 'fetchUrl: '.$url.' to '.$this->filePath.'<br>';
		
		$fileDir = $this->initFileDir($this->fileUri);
		
		if (kaosIsError($fileDir))
			return $fileDir;
			
		if (!kaosFetch($url, array(), true, $this->fileUri))
			return new KaosError('cannot fetch url '.$url);
		
		return array(
			'format' => $this->protocoleConfig->format,
			'cached' => false,
			'schema' => $this->query['schema'],
			'filePath' => $this->fileUri
		);
	}
	
	public function initFileDir($filePath){
		//echo "INIT DIR ".$filePath.'<br>';
		$fileDir = dirname($filePath);
		if (!file_exists($fileDir)){
			if (!is_writable(DATA_PATH))
				return new KaosError('folder '.DATA_PATH.' not writtable');
			if (!mkdir($fileDir, 0777, true))
				return new KaosError('folder '.$fileDir.' cannot be created');
		}
		return $fileDir;
	}
	
	public function saveContent($fetched, &$formatFetcher, $processedFilePrefix = false){
//		echo 'saveContent<br>';
		
		if ($processedFilePrefix !== false){ 
			// really save this, the others are directly saved to disk via curl

			if (empty($processedFilePrefix))
				kaosDie('bad $processedFilePrefix');
			
			$fileDir = $this->initFileDir($this->fileUri);
			if (kaosIsError($fileDir))
				return $fileDir;
				
			//echo "WRITTING CACHE TO ".$this->fileUri.$processedFilePrefix.'<br>';
			
			if (is_object($fetched) || is_array($fetched)){
				$output = @json_encode($fetched, JSON_UNESCAPED_UNICODE);
				if (empty($output)){
					$output = @json_encode(kaosUTF8RecursiveEncode($fetched), JSON_UNESCAPED_UNICODE);
					if (empty($output))
						return new KaosError('error saving content to local cache: '.json_last_error_msg());
				}
			
			} else if (empty($output))
				return new KaosError('error saving content: empty content');
			
			else
				$output = $fetched;
				
			
			for ($i = 1; $i <= 5; $i++){ // try writing each file up to 5 times
				
				if (file_put_contents($this->fileUri.$processedFilePrefix, $output)){
					
					if (KAOS_IS_CLI)
						kaosPrintLog('saved parsed file to '.$this->fileUri.$processedFilePrefix);
						
					return true;
				
				} else if ($i != 5) // except last failed attempt
					usleep(500000 * max($i, 2)); // wait half a second between first and second write attempt, then 1s
			}
				
			return new KaosError('could not save processed content to '.$this->fileUri.$processedFilePrefix.' (tries 5 times)');
		}
		
		$success = $formatFetcher->fetchFileDone($this->fileUri, $processedFilePrefix);
		if (kaosIsError($success)){
			
			// clean if error
			@unlink($this->fileUri);
			return $success;
		}
		
		$content = $this->retrieveContent($formatFetcher, true);
		if (!$content)
			return new KaosError('bad content after fetched: '.$this->fileUri);
		return $content;
	}
	
	public function retrieveContent(&$formatFetcher, $justCreated = false, &$fetchedOrigin = null, $processedFilePrefix = null, $onlyTestIfExists = false){
		$filePath = $formatFetcher->getContentFilePath($this->fileUri, $processedFilePrefix);

		if (!file_exists($filePath)){
			//echo "NOT FOUND: ".$filePath."<br>";
			if (KAOS_IS_CLI)
				kaosPrintLog('not found in cache: '.$filePath, array('color' => 'grey'));
			return false;
		}
		//echo "FOUND: ".$filePath."<br>";
		
		if ($onlyTestIfExists)
			return true;
		
		if (KAOS_IS_CLI)
			kaosPrintLog('found in cache: '.$filePath, array('color' => 'grey'));
		
		$content = file_get_contents($filePath);
		
		if (trim($content) == ''){
			@unlink($this->fileUri);
			@unlink($filePath);
			
			if (KAOS_IS_CLI)
				kaosPrintLog('delete empty file from local disk: '.$filePath, array('color' => 'red'));
			
//			return new KaosError('fetched empty file at '.$filePath);
			return false;
		}
			
		if (!$content)
			return new KaosError('cannot read file '.$filePath.' from local disk');
			
		if ($processedFilePrefix)
			return json_decode($content, true);

		$content = str_replace('●', "\n", $content); 
		
		$encoding = $formatFetcher->detectEncoding($content);
		
		//$content = kaosConvertEncoding($content, $encoding);
		
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
