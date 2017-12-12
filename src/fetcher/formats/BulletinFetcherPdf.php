<?php

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
