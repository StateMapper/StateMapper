<?php
// ipfs key list | grep Kaos155
// ipfs key gen --type=rsa --size=2048 Kaos155
// ipfs add -r . (on /data)

if (!defined('BASE_PATH'))
	die();

class BulletinFetcherIpfsCache extends BulletinFetcherCache {
	
	public function getLabel(){
		return 'IPFS';
	}
	
	// TODO: saveContent to uploadTo!

	public function retrieveContent(&$formatFetcher, $justCreated = false, &$fetchedOrigin = null, $fetchProcessedPrefix = null, $onlyTestIfExists = false){
		global $kaosConfig;
		$filePath = $formatFetcher->getContentFilePath($this->fileUri, $fetchProcessedPrefix);
		$fetched = false;
		
		if ($onlyTestIfExists)
			return false;
			
		if (!empty($kaosConfig['IPFS']) && !empty($kaosConfig['IPFS']['fetchFrom'])){
			foreach ($kaosConfig['IPFS']['fetchFrom'] as $nodeHash => $nodeName){
			
				// call IPFS API
				if (kaosFetch(IPFS_API_URL.'/api/v0/cat?arg='.$nodeHash.$filePath, array(), true, DATA_PATH.$filePath, array(
					'allowTor' => false, 
					'countAsFetch' => false
				))){
					$fetched = true;
					$fetchedOrigin = $nodeName;
					break;
				}
			}
		}
		if (!$fetched){
			return false;
		}
		
		$content = @file_get_contents(DATA_PATH.$filePath);

		if (trim($content) == ''){
			@unlink($this->fileUri);
			@unlink($filePath);
			
			if (KAOS_IS_CLI)
				kaosPrintLog('delete empty file from ipfs: '.$filePath, array('color' => 'red'));
			
//			return new KaosError('fetched empty file at '.$filePath);
			return false;
		}		
		if (!$content)
			return new KaosError('cannot read file '.DATA_PATH.$filePath.' after IPFS fetch');
			
		return array(
			'format' => $this->protocoleConfig->format,
			'content' => $content,
			'cached' => !$justCreated,
			'filePath' => DATA_PATH.$this->fileUri
		);
	}

}	
