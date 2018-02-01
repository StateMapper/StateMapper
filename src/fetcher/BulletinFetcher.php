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
	
class BulletinFetcher {
	
	public $args = array();

	public function fetch_bulletin($query, $redirect = false, $fetchProcessedPrefix = false){
		global $smap;
		if (empty($smap['fetched_origins'])) 
			$smap['fetched_origins'] = array();
			
		$query += array(
			'schema' => null,
			'protocoleId' => 'default',
			'format' => null,
		);
		
		if (empty($query['schema']))
			return new SMapError('missing arguments');
			
		if (!is_valid_schema_path($query['schema']))
			return new SMapError('invalid schema');
			
		$dbFormat = null;
//		if (!$redirect)
			$dbFormat = get_format_by_query($query);
		
		$fetchProtocole = $this->get_fetch_protocole($query);
		if (!$fetchProtocole)
			return new SMapError('fetchProtocole not found or malformed schema for '.$query['schema'].(!empty($query['type']) ? ' '.$query['type'] : '').' (query: '.json_encode($query, JSON_UNESCAPED_UNICODE).')');
			
		if (empty($query['type']) && !empty($p->type))
			$query['type'] = $p->type;

		$protocoleConfig = $this->get_protocole_config($fetchProtocole, $query);
		$formatFetcher = get_format_fetcher($dbFormat ? $dbFormat : $protocoleConfig->format, $this);
		
		if (!$redirect){
			
			if (is_error($formatFetcher))
				return $formatFetcher;
			
			// try getting from caches, in order
			$caches = explode(',', BULLETIN_CACHES_READ);
			if (!$caches)
				return new SMapError('no cache system');
				
			// check from caches
			foreach ($caches as $i => $cacheType){
				if ($fetcherCache = get_fetcher_cache($cacheType, $dbFormat ? $dbFormat : $protocoleConfig, $query, $this)){
					
					$fetchedOrigin = null;
					$content = $fetcherCache->retrieve_content($formatFetcher, false, $fetchedOrigin, $fetchProcessedPrefix, !empty($query['noFetch']));
					
					if (is_error($content))
						return $content;
					
					if ($content !== false){ // is cached content or SMapError

						if (!empty($query['noFetch']))
							return true;
						
						$smap['fetched_origins'][$fetcherCache->get_label().($fetchProcessedPrefix ? ' (parsed .'.$protocoleConfig->format.')' : ' (.'.$protocoleConfig->format.')')] = (isset($smap['fetched_origins'][$cacheType]) ? $smap['fetched_origins'][$cacheType] : 0) + 1;
						
						$inserted = insert_bulletin(array('format' => $protocoleConfig->format) + $query); 
						if (is_error($inserted))
							return $inserted;
							
						$content += array('format' => $protocoleConfig->format);
						
						return $content + $query;
					}
				}
			}
		}
		
		if ($fetchProcessedPrefix || !empty($query['noFetch']))
			return false;
			
		$inserted = insert_bulletin(array('format' => is_object($protocoleConfig) ? $protocoleConfig->format : $protocoleConfig) + $query);
		if (is_error($inserted))
			return $inserted;
		
		// really fetch bulletin
		$fetcherCache = get_fetcher_cache('local', $protocoleConfig, $query, $this);
		$fetched = $this->execute_protocole($fetchProtocole, $query, $formatFetcher, $fetcherCache, $redirect);
		
		if ($redirect){ 
			if ($redirect === 'return')
				return $fetched;

			// protect, just in case
			die_error('error in redirect');
		}
		
		if (is_error($fetched)){
			
			if (!empty($query['id']) || is_bulletin_expected($query['schema'], $query['date']))
				set_bulletin_error($query, !empty($query['id']) ? 'document not found' : 'summary not found');
			else
				set_bulletin_none($query);
			return $fetched;
		}
			
		$smap['fetched_origins']['origin'] = (isset($smap['fetched_origins']['origin']) ? $smap['fetched_origins']['origin'] : 0) + 1;
		
		if (!empty($smap['sumulateFetch']))
			return true;
			
		$fetched = $fetcherCache->save_content($fetched, $formatFetcher);
		if (is_error($fetched) || !is_array($fetched))
			return $fetched;

		$fetched += array('format' => $protocoleConfig->format);
			
		return $fetched + $query;
	}
	
	function save_processed_content($parsed, $filePrefix, $query){
		$query += array(
			'schema' => null,
			'protocoleId' => 'default',
			'format' => null
		);
		
		if (empty($query['schema']))
			return new SMapError('missing arguments');
			
		if (!is_valid_schema_path($query['schema']))
			return new SMapError('invalid schema');
		
		$fetchProtocole = $this->get_fetch_protocole($query);
		if (!$fetchProtocole)
			return new SMapError('can\'t save processed content, fetchProtocole not found or malformed schema for '.$query['schema'].(!empty($query['type']) ? ' '.$query['type'] : '').' (query: '.json_encode($query, JSON_UNESCAPED_UNICODE).')');
			
		$protocoleConfig = $this->get_protocole_config($fetchProtocole, $query);
		
		$formatFetcher = get_format_fetcher($protocoleConfig->format, $this);
		if (is_error($formatFetcher))
			return $formatFetcher;
			
		$fetcherCache = get_fetcher_cache('local', $protocoleConfig, $query, $this);
		return $fetcherCache->save_content($parsed, $formatFetcher, $filePrefix);
	}
	
	protected function get_protocole_config($fetchProtocole, $query){
		$config = $fetchProtocole->protocole->{$query['protocoleId']};
		if (empty($config->format) && !empty($fetchProtocole->input->format))
			$config->format = $fetchProtocole->input->format;
		return $config;
	}
	
	protected function execute_protocole($fetchProtocole, $query, &$formatFetcher, &$fetcherCache, $redirect = false){
		global $smap;
		
		$content = null;
		foreach ($fetchProtocole->protocole->{$query['protocoleId']}->steps as $step){
			
			switch ($step->type){
				
				case 'HTTP':
					$url = $step->url;
					
					// inject variables
					if (preg_match_all('#{([^}]+)}#i', $url, $matches, PREG_SET_ORDER))
						foreach ($matches as $m){
							$varId = $m[1];
							$varMethods = array();
							
							// detect injection variable methods
							if (preg_match_all('#(:([a-z0-9_]+?)\(([^\)]*)\))#i', $varId, $methods, PREG_SET_ORDER)){
								foreach ($methods as $parts)
									$varMethods[] = array(
										'fn' => trim($parts[2]),
										'args' => array(trim($parts[3])) // TODO: explode args between quotes..
									);
							}
							$varId = preg_replace('#^([a-z0-9_]+)(:.*)?$#i', '$1', $varId);
							
							if (!isset($query[$varId]))
								return new SMapError('missing injection variable '.$varId);

							$varValue = $query[$varId];
							if (is_array($varValue) && isset($varValue['value'])) // BUG!! shouldn't need this
								$varValue = $varValue['value'];
								
							// apply injection variable methods
							foreach ($varMethods as $method)
								switch ($method['fn']){
									
									case 'formatDate':
										$varValue = date($method['args'][0], strtotime($varValue));
										break;
										
									default:
										return new SMapError('unknown variable method '.$method['fn']);
								}
							
							// replace variable in url
							$url = str_replace($m[0], $varValue, $url);
						}
					
					// TODO: sanitize_url?
					
					if ($redirect){
						if ($redirect === 'return')
							return $url;
							
						if (IS_CLI){
							echo $url.PHP_EOL;
							exit();
						} 
						if ($smap['raw'])
							return_wrap(array('success' => true, 'url' => $url));
							
						redirect(anonymize($url));
					}
					$content = $fetcherCache->fetch_url($url, $formatFetcher);
					if (is_error($content))
						return $content;
						
					break;
					
				default:
					return new SMapError('unknown step type '.$step->type);
			}
		}
		
		if ($content === null)
			return new SMapError('no content fetched');
			
		return $content;
	}
	
	public function guess_query_parameters($query){
		if (!($schema = get_schema($query['schema'])))
			return false;
		
		if (!empty($schema->guesses)){
			foreach ($schema->guesses as $guessFor => $guessModel){
				foreach ($guessModel as $guessFieldId => $guessPattern){
					foreach (is_array($guessPattern) ? $guessPattern : array($guessPattern) as $curGuessPattern){
						if (empty($query[$guessFor]) && isset($query[$guessFieldId]) && preg_match($curGuessPattern->pattern, $query[$guessFieldId], $m)){
							
							$value = array('value' => $query[$guessFieldId]);
							if (!empty($curGuessPattern->transform))
								foreach ($curGuessPattern->transform as $tr)
									switch ($tr->type){
										
										case 'regexpMatch':
											$value['value'] = preg_replace($curGuessPattern->pattern, $tr->match, $value['value']);
											break;
											
										case 'assign':
											$value['value'] = $tr->value;
											break;
									}
							
							$query[$guessFor] = $value['value'];
							//echo "GUESSED $guessFor AS ".$value['value']." FROM $guessFieldId ".$query[$guessFieldId]."<br>";
							break;
						}
					}
				}
			}
		}
		if (!empty($query['url']) && empty($query['format']) && preg_match('#\.([a-z0-9_]+)$#iu', $query['url'], $m))
			$query['format'] = strtolower($m[1]);

		return $query;
	}
	
	public function get_fetch_protocole($query){
		if (!($schema = get_schema($query['schema'])))
			return false;
		
		$query = $this->guess_query_parameters($query);
		$else = null;
		
		if (!empty($schema->fetchProtocoles))
			foreach ($schema->fetchProtocoles as $protocoleId => $protocole){
				if (empty($query['type']) || $protocoleId == $query['type'] || (property_exists($protocole, 'type') && $protocole->type == $query['type'])){
					foreach ($protocole->protocoles as $p){
						
						// skip if format is specified and absent from config or different
						if (!empty($query['format']) && (empty($p->input->format) || $p->input->format != $query['format']))
							continue;
						
						// skip if all input parameters are not present in $query	
						$hasAll = true;
						foreach ($p->input->parameters as $parameterId){
							if (is_string($parameterId) && empty($query[$parameterId])){
								$hasAll = false;
								break;
							}
						}
							
						if ($hasAll){
							$p->protocoleType = $protocole->type;
							$p->protocoleId = $protocoleId;
							if (empty($p->format) && !empty($p->input->format))
								$p->format = $p->input->format;
								
							if (!empty($query['id']) && !in_array('id', $p->input->parameters))
								$else = $p;
							else
								return $p;
						}
					}
				}
			}
		
		return $else;
	}
	
	public function serve_bulletin($bulletin, $printMode = 'download', $title = null, $query = array()){
		$formatFetcher = get_format_fetcher($bulletin['format'], $this);
		if (is_error($formatFetcher))
			die_error('no such bulletin');
		return $formatFetcher->serve_bulletin($bulletin, $printMode, $title, $query);
	}
	
}

