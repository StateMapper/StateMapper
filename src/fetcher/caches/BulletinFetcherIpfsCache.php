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
 

// ipfs key list | grep Kaos155
// ipfs key gen --type=rsa --size=2048 Kaos155
// ipfs add -r . (on /data)

namespace StateMapper;

if (!defined('BASE_PATH'))
	die();

class BulletinFetcherIpfsCache extends BulletinFetcherCache {
	
	public function get_label(){
		return 'IPFS';
	}
	
	// TODO: save_content to uploadTo!

	public function retrieve_content(&$formatFetcher, $justCreated = false, &$fetchedOrigin = null, $fetchProcessedPrefix = null, $onlyTestIfExists = false){
		global $smapConfig;
		$filePath = $formatFetcher->get_content_path($this->fileUri, $fetchProcessedPrefix);
		$fetched = false;
		
		if ($onlyTestIfExists)
			return false;
			
		if (!empty($smapConfig['IPFS']) && !empty($smapConfig['IPFS']['fetchFrom'])){
			foreach ($smapConfig['IPFS']['fetchFrom'] as $nodeHash => $nodeName){
			
				// call IPFS API
				if (fetch(IPFS_API_URL.'/api/v0/cat?arg='.$nodeHash.$filePath, array(), true, DATA_PATH.$filePath, array(
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
			
			if (IS_CLI)
				print_log('deleted empty file from ipfs: '.$filePath, array('color' => 'red'));
			
//			return new SMapError('fetched empty file at '.$filePath);
			return false;
		}		
		if (!$content)
			return new SMapError('could not read file '.DATA_PATH.$filePath.' after IPFS fetch');
			
		return array(
			'format' => $this->protocoleConfig->format,
			'content' => $content,
			'cached' => !$justCreated,
			'filePath' => DATA_PATH.$this->fileUri
		);
	}

}	
