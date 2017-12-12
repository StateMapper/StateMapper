<?php

if (!defined('BASE_PATH'))
	die();

class BulletinFetcher {
	
	public $args = array();

	public function fetchBulletin($query, $redirect = false, $fetchProcessedPrefix = false){
		global $kaosCall;
		if (empty($kaosCall['fetchOrigins'])) 
			$kaosCall['fetchOrigins'] = array();
			
		$query += array(
			'schema' => null,
			'protocoleId' => 'default',
			'format' => null,
		);
		
		if (empty($query['schema']))
			return new KaosError('missing arguments');
			
		if (!kaosIsValidSchemaPath($query['schema']))
			return new KaosError('invalid schema');
			
		$dbFormat = null;
//		if (!$redirect)
			$dbFormat = kaosGetFormatByQuery($query);
		
		$fetchProtocole = $this->getFetchProtocole($query);
		if (!$fetchProtocole)
			return new KaosError('fetchProtocole not found or malformed schema for '.$query['schema'].(!empty($query['type']) ? ' '.$query['type'] : '').' (query: '.json_encode($query, JSON_UNESCAPED_UNICODE).')');
			
		if (empty($query['type']) && !empty($p->type))
			$query['type'] = $p->type;

		$protocoleConfig = $this->getFetchProtocoleConfig($fetchProtocole, $query);
		$formatFetcher = kaosGetFormatFetcher($dbFormat ? $dbFormat : $protocoleConfig->format, $this);
		
		if (!$redirect){
			
			if (kaosIsError($formatFetcher))
				return $formatFetcher;
			
			// try getting from caches, in order
			$caches = explode(',', BULLETIN_CACHES_READ);
			if (!$caches)
				return new KaosError('no cache system');
				
			// check from caches
			foreach ($caches as $i => $cacheType){
				if ($fetcherCache = kaosGetFetcherCache($cacheType, $dbFormat ? $dbFormat : $protocoleConfig, $query, $this)){
					
					$fetchedOrigin = null;
					$content = $fetcherCache->retrieveContent($formatFetcher, false, $fetchedOrigin, $fetchProcessedPrefix, !empty($query['noFetch']));
					
					if (kaosIsError($content))
						return $content;
					
					if ($content !== false){ // is cached content or KaosError
						//echo "IN CACHE ".$cacheType."<br>";
						
						if (!empty($query['noFetch']))
							return true;
						
						$kaosCall['fetchOrigins'][$fetcherCache->getLabel().($fetchProcessedPrefix ? ' (parsed .'.$protocoleConfig->format.')' : ' (.'.$protocoleConfig->format.')')] = (isset($kaosCall['fetchOrigins'][$cacheType]) ? $kaosCall['fetchOrigins'][$cacheType] : 0) + 1;
						
						$inserted = insertBulletin(array('format' => $protocoleConfig->format) + $query); 
						if (kaosIsError($inserted))
							return $inserted;
							
						$content += array('format' => $protocoleConfig->format);
						
						return $content + $query;
					}
				}
			}
		}
		
		if ($fetchProcessedPrefix || !empty($query['noFetch']))
			return false;
			
		$inserted = insertBulletin(array('format' => is_object($protocoleConfig) ? $protocoleConfig->format : $protocoleConfig) + $query);
		if (kaosIsError($inserted))
			return $inserted;
		
		// really fetch bulletin
		$fetcherCache = kaosGetFetcherCache('local', $protocoleConfig, $query, $this);
		$fetched = $this->doFetchProtocole($fetchProtocole, $query, $formatFetcher, $fetcherCache, $redirect);
		
		if ($redirect){ 
			if ($redirect === 'return')
				return $fetched;

			// protect, just in case
			kaosDie('error in redirect');
		}
		
		if (kaosIsError($fetched)){
			
			if (!empty($query['id']) || kaosIsBulletinExpected($query['schema'], $query['date']))
				setBulletinError($query, !empty($query['id']) ? 'document not found' : 'summary not found');
			else
				setBulletinNone($query);
			return $fetched;
		}
			
		$kaosCall['fetchOrigins']['origin'] = (isset($kaosCall['fetchOrigins']['origin']) ? $kaosCall['fetchOrigins']['origin'] : 0) + 1;
		
		if (!empty($kaosCall['sumulateFetch']))
			return true;
			
		$fetched = $fetcherCache->saveContent($fetched, $formatFetcher);
		if (kaosIsError($fetched) || !is_array($fetched))
			return $fetched;

		$fetched += array('format' => $protocoleConfig->format);
			
		return $fetched + $query;
	}
	
	function saveProcessedContent($parsed, $filePrefix, $query){
		$query += array(
			'schema' => null,
			'protocoleId' => 'default',
			'format' => null
		);
		
		if (empty($query['schema']))
			return new KaosError('missing arguments');
			
		if (!kaosIsValidSchemaPath($query['schema']))
			return new KaosError('invalid schema');
		
		$fetchProtocole = $this->getFetchProtocole($query);
		if (!$fetchProtocole)
			return new KaosError('can\'t save processed content, fetchProtocole not found or malformed schema for '.$query['schema'].(!empty($query['type']) ? ' '.$query['type'] : '').' (query: '.json_encode($query, JSON_UNESCAPED_UNICODE).')');
			
		$protocoleConfig = $this->getFetchProtocoleConfig($fetchProtocole, $query);
		
		$formatFetcher = kaosGetFormatFetcher($protocoleConfig->format, $this);
		if (kaosIsError($formatFetcher))
			return $formatFetcher;
			
		$fetcherCache = kaosGetFetcherCache('local', $protocoleConfig, $query, $this);
		return $fetcherCache->saveContent($parsed, $formatFetcher, $filePrefix);
	}
	
	protected function getFetchProtocoleConfig($fetchProtocole, $query){
		$config = $fetchProtocole->protocole->{$query['protocoleId']};
		if (empty($config->format) && !empty($fetchProtocole->input->format))
			$config->format = $fetchProtocole->input->format;
		return $config;
	}
	
	protected function doFetchProtocole($fetchProtocole, $query, &$formatFetcher, &$fetcherCache, $redirect = false){
		
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
								return new KaosError('missing injection variable '.$varId);

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
										return new KaosError('unknown variable method '.$method['fn']);
								}
							
							// replace variable in url
							$url = str_replace($m[0], $varValue, $url);
						}
					
					// TODO: sanitize_url?
					
					if ($redirect){
						if ($redirect === 'return')
							return $url;
							
						if (KAOS_IS_CLI){
							echo $url.PHP_EOL;
							exit();
						}
						header('Location: '.kaosAnonymize($url));
						exit;
					}
					$content = $fetcherCache->fetchUrl($url, $formatFetcher);
					if (kaosIsError($content))
						return $content;
						
					
					
					break;
					
				default:
					return new KaosError('unknown step type '.$step->type);
			}
		}
		
		if ($content === null)
			return new KaosError('no content fetched');
			
		return $content;
	}
	
	public function guessQueryParameters($query){
		if (!($schema = kaosGetSchema($query['schema'])))
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
	
	public function getFetchProtocole($query){
		if (!($schema = kaosGetSchema($query['schema'])))
			return false;
		
		$query = $this->guessQueryParameters($query);
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
							if (empty($query[$parameterId])){
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
	
	public function serveBulletin($bulletin, $printMode = 'download', $title = null, $query = array()){
		if (kaosIsError($formatFetcher = kaosGetFormatFetcher($bulletin['format'], $this)))
			kaosDie('no such bulletin');
		return $formatFetcher->serveBulletin($bulletin, $printMode, $title, $query);
	}
	
}

class BulletinFetcherFormat {
	
	public function detectEncoding($content){
		return null;
	}
	
}

class BulletinFetcherCache {
	
	public $protocoleConfig = null;
	public $query = null;
	public $fileUri = null;
	private $parent = null;
	
	public function setConfig($protocoleConfig, $query, $parent){
		$this->protocoleConfig = $protocoleConfig;
		$this->query = $query;
		$this->parent = $parent;
		
		// init filePath
		$this->fileUri = $this->getContentUri();
	}
	
	public function getContentUri(){
		$query = $this->parent->guessQueryParameters($this->query);
		
		$cachePath = '/'.$query['schema'];
		if (!empty($query['id']))
			$cachePath .= '/byId';
		else if (!empty($query['date']))
			$cachePath .= '/byDate';
		else
			return new KaosError('not enough query parameters');
			
		$fileUri = $cachePath.'/';
		
		if (!empty($query['id']))
			$fileUri .= $query['id'];
		else if (!empty($query['date']))
			$fileUri .= $query['date'];

		$fileUri .= '.'.(is_object($this->protocoleConfig) ? $this->protocoleConfig->format : $this->protocoleConfig);
		
		//echo 'ContentPath: '.$filePath.'<br>';
		return $fileUri;
	}
}


function kaosGetFetcherCache($cacheType, $protocoleConfig, $query, $parent){
	$filePath = __DIR__.'/caches/BulletinFetcher'.ucfirst($cacheType).'Cache.php';
	if (file_exists($filePath)){
		require_once $filePath;
		$class = 'BulletinFetcher'.ucfirst($cacheType).'Cache';
		$fetcherCache = new $class();
		$fetcherCache->setConfig($protocoleConfig, $query, $parent);
		return $fetcherCache;
	} 
	return null;
}


