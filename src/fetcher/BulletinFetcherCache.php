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
	

class BulletinFetcherCache {
	
	public $protocoleConfig = null;
	public $query = null;
	public $fileUri = null;
	private $parent = null;
	
	public function set_config($protocoleConfig, $query, $parent){
		$this->protocoleConfig = $protocoleConfig;
		$this->query = $query;
		$this->parent = $parent;
		
		// init filePath
		$this->fileUri = $this->get_content_uri();
	}
	
	public function get_content_uri(){
		$query = $this->parent->guess_query_parameters($this->query);
		if (!($fileUri = get_bulletin_uri(array(
			'format' => is_object($this->protocoleConfig) ? $this->protocoleConfig->format : $this->protocoleConfig,
		) + $query)))
			return new SMapError('not enough query parameters');

		return '/'.$fileUri;
		
		/*	
		$fileUri = '/'.$query['schema'];
		if (!empty($query['date'])){
		
			// file formats:
			// 2017/01/01.xml
			// 2017/01/01/id_document.xml
		
			$fileUri .= '/'.str_replace('-', '/', $query['date']); 
			if (!empty($query['id']))
				$fileUri .= '/'.$query['id'];
			
		} else
			return new SMapError('not enough query parameters');
			
		$fileUri .= '.'.(is_object($this->protocoleConfig) ? $this->protocoleConfig->format : $this->protocoleConfig);
		
		return $fileUri;
		*/
	}
}

