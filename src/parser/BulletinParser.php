<?php

/*
 * 
 * BOE's groupAmounts old first tr
{
	type: "regexpMatch",
	regexp: "#\bImporte(?:\s*o canon)?\s*de\s*adjudicaci[óo]n\s*[:\.]?\s*(.*?)([abcdefgh]\s*\).*)?$#ius",
	match: "$1"
},
GROUP: {
	name: "Grupo",
	shortName: "Grupo",
	namePosition: "before",
	strictPatterns: [
		"(Grupo){legalEntityPattern}"
	]
}*/

if (!defined('BASE_PATH'))
	die();
	
require_once(APP_PATH.'/helpers/entities.php');

class BulletinParser {
	
	private $query = array();
	private $bulletin = null;
	private $rootNode = null;
	private $formatParser = null;
	private $nodes = null;
	private $lastBulletinSchema = null;
	
	private static $fetched = array();

	private $varReplaceNode = array();
	
	public function __construct($parent = null){
		if ($parent){
			$this->lastBulletinSchema = $parent->lastBulletinSchema;
			$this->query['followLevels'] = $parent->query['followLevels'];
			$this->query['currentLevel'] = $parent->query['currentLevel'] + 1;
		}
	}
	
	public function getBulletin($attr = null){
		return $attr ? (isset($this->bulletin[$attr]) ? $this->bulletin[$attr] : null) : $this->bulletin;
	}
	
	public function fetchAndParseBulletin($query){
		global $kaosCall;
		$query += array(
			'followLevels' => 2
		);
		
		$bulletinFetcher = new BulletinFetcher();
		
		// try from parsed cache (.parsed.json)
		if (!empty($query['allowProcessedCache']) && KAOS_PROCESSED_FILE_CACHE){
			$bulletin = $bulletinFetcher->fetchBulletin($query, false, '.parsed.json');
			
			if (kaosIsError($bulletin))
				return $bulletin;
			if ($bulletin) // success (parsed file is in cache)
				return $bulletin;
		}
		
		$bulletin = $bulletinFetcher->fetchBulletin($query);
		
		if (!$bulletin || kaosIsError($bulletin)){
			unset($bulletinFetcher);
			
			if (!empty($query['id']) || kaosIsBulletinExpected($query['schema'], $query['date'])){
				setBulletinError($query, !empty($query['id']) ? 'document not found' : 'summary not found');
				return $bulletin ? $bulletin : new KaosError('Bulletin not found', array('type' => 'notFound'));
			}
			
			setBulletinNone($query);
			return true;
		}
		//kaosJSON($query);
		//kaosJSON($bulletin); die();
		/*
		if ($kaosCall['call'] == 'rewind' && 0){ // quick rewind!! (not ready, downloading too many stuff!!! :S)
			
			if (empty($query['noFollow'])){ // TODO: follow summaries though!!! (from $bulletin['type'] ??)
				
				$patterns = array();
				foreach (kaosAPIGetSchemas(preg_replace('#^([a-z]+)(/.*)#i', '$1', $query['schema'])) as $schema){
					$schema = kaosGetSchema($schema);
					if (!empty($schema->idFormats))
						$patterns = array_merge($patterns, $schema->idFormats);
				}
				self::$fetched = array();
				if (preg_match_all('#\b('.implode('|', $patterns).')\.([A-Z]+)#ius', $bulletin['content'], $ids, PREG_SET_ORDER)){
					foreach ($ids as $id){
						if (!in_array($id[1], self::$fetched)){
							if (0 && count(self::$fetched) > 20){
								echo "SHOULD FETCH ".$id[1].'<br>';
								continue;
							}
							
							if (!empty($_GET['simulate']))
								$kaosCall['sumulateFetch'] = (isset($kaosCall['sumulateFetch']) ? $kaosCall['sumulateFetch'] : 0) + 1;
							
							$parser = new BulletinParser();
							$bulletin = $parser->fetchAndParseBulletin(array( // recursing on another object
								'type' => null, // detect this
								'id' => $id[1],
								'url' => null,
								'format' => null, // detect this
								'noFollow' => true
							) + $query);
							unset($parser);
							
							if (!empty($_GET['simulate']))
								$kaosCall['sumulateFetch']--;
							
							if (kaosIsError($bulletin))
								return $bulletin;
							else if (!$bulletin)
								return new KaosError('no bulletin fetched');
								
							self::$fetched[] = $id[1];
						}
					}
				}
			}
			
			unset($bulletinFetcher);
			return true;
		}*/
		unset($bulletinFetcher);
		
		// really parse
		$parsed = $this->parseBulletin($bulletin, $query);
		
		if (kaosIsError($parsed)){
			setBulletinError($query, (!empty($query['id']) ? 'document cannot be parsed/followed' : 'summary cannot be parsed').': '.$parsed->msg);
			return $parsed;
		}
		
		if (empty($parsed)){
			setBulletinError($query, !empty($query['id']) ? 'document cannot be parsed/followed (empty result)' : 'summary cannot be parsed (empty result)');
			return new KaosError('nothing to parse');
		}

		setBulletinFetched($bulletin, $query);
		
		// save to parsed cache
		$bulletinFetcher = new BulletinFetcher();
		
		if (!KAOS_PROCESSED_FILE_CACHE)
			return $parsed;
		
		$saved = $bulletinFetcher->saveProcessedContent($parsed, '.parsed.json', $query);
		
		
		return kaosIsError($saved) ? $saved : $parsed;
	}

	public function parseBulletin($bulletin, $query = array()){
		$success = $this->doParseBulletin($bulletin, $query);
		if (kaosIsError($success))
			return $success;

		$this->nodes['schema'] = $this->query['schema'];
		return $this->nodes;
	} 
	
	public function doParseBulletin($bulletin, $query = array()){
		$this->bulletin = $bulletin;
		$this->query = $query + $this->query + array(
			'schema' => null,
			'type' => 'Summary',
			'followLevels' => 0,
			'currentLevel' => 0
		);
		
		if (empty($this->query['type']) || empty($query['schema']))
			return new KaosError('not enough arguments');
			
		//kaosJSON($bulletin);
		
		$formatParserClass = 'BulletinParser'.ucfirst(strtolower($bulletin['format']));
		$formatParserPath = __DIR__.'/formats/'.$formatParserClass.'.php';
		
		if (!file_exists($formatParserPath))
			return new KaosError('unknown parsing format '.$bulletin['format']);
			
		require_once $formatParserPath;
		$this->formatParser = new $formatParserClass($this);
		
		$this->rootNode = $this->formatParser->loadRootNode($bulletin['content']);
		if (kaosIsError($this->rootNode)){
			
			// failed to load the fetched document, we may delete all bulletin files sometime later.
			return $this->rootNode;
		}
		
		$this->nodes = $this->getParsingProtocole($this->nodes, $this->query['schema'], $this->query['type']);

		if (!kaosIsError($this->nodes))
			$this->nodes += $this->query;
			
		unset($this->formatParser);
			
		if ($filter = kaosGetFilter()){
			$objects = array();
			$this->nodes = $this->filterNodes($this->nodes, $filter, $objects);
		}
		return $this->nodes;
	}
	
	function filterNodes($nodes, $filter, &$objects = array()){
		$new = array();
		$assoc = empty($nodes[0]);
		foreach ($nodes as $k => $n){
			if (empty($n))
				continue;
			else if (is_array($n)){
				if (!empty($n[0]))
					$new[$k] = $this->filterNodes($n, $filter, $objects);
				else {
					$has = $this->filterNodeHas($n, $filter);
					if ($has === 1){
						$new[$k] = $n;
						$objects[] = $n;
					} else if ($has)
						$new[$k] = $this->filterNodes($n, $filter, $objects);
				}
			} else
				$new[$k] = $n;
		}
		return $assoc ? $new : array_values($new);
	}
	
	function filterNodeHas($node, $filter){
		if (is_array($node)){
			foreach ($node as $n)
				if (is_array($n) && $this->filterNodeHas($n, $filter))
					return true;
			return $this->filterNodeIs($node, $filter) ? 1 : false;
		}
		return false;
	}

	function filterNodeIs($node, $filter){
		return empty($node[0]) && !empty($node['title']) && preg_match('#.*'.preg_quote($filter, '#').'.*#ius', $node['title']);
	}
	
	/*function getPrecepts($nodes = null, $precepts = array()){
		foreach (($nodes ? $nodes : $this->nodes) as $n){
			if (is_array($n) || is_object($n)){
				$n = (object) $n;
				if (!empty($n->type) && $n->type == 'Precept')
					$precepts[] = new Precept($n);
				$precepts = $this->getPrecepts($n, $precepts);
			} 
		}
		return $precepts;
	}*/
	
	public function getParsingProtocole($nodes, $bulletinSchema, $objectType){
		if (!($schema = kaosGetSchema($bulletinSchema))){
			if (!$this->lastBulletinSchema)
				return 'cannot get BulletinSchema for '.$bulletinSchema;
			$schema = $this->lastBulletinSchema;
		} else
			$this->lastBulletinSchema = $schema;
		
		$protocole = null;
		foreach ($schema->parsingProtocoles as $protocoleId => $curProtocole){
			if (
				(
					$objectType == $protocoleId 
					|| (property_exists($curProtocole, 'type') && $curProtocole->type == $objectType)
				) && (
					empty($this->bulletin['format'])
					|| $curProtocole->format == $this->bulletin['format']
				)
			){
				$protocole = $curProtocole;
				$protocole->id = $protocoleId;
				break;
			}
		}
				
		if (!$protocole)
			return new KaosError('no such object '.$bulletinSchema.' '.$objectType.' (format: '.(!empty($this->bulletin['format']) ? $this->bulletin['format'] : 'not specified').')'.(1 ? '<br><br>'.$this->bulletin['content'].'<br><br>' : ''));

		$nodes = $this->initObject($protocole->protocole, $this->rootNode, $this->rootNode, true);
		$nodes['type'] = !empty($protocole->type) ? $protocole->type : $protocole->id;
		$nodes['protocole'] = $protocole->id;
		if (!empty($this->query['id']))
			$nodes['id'] = $this->query['id'];
		
		if (!empty($protocole->protocole->children)){
			$success = $this->buildObject($nodes, $protocole->protocole->children);
			if (kaosIsError($success))
				return $success;
		}
		return $nodes;
	}
	
	private function buildObject(&$nodes, $protocoleChildren, $rootNode = null){
		$i = 0;
		
		if (!empty($protocoleChildren))
			foreach ($protocoleChildren as $childId => $childConfig){
				
//				if (in_array($childId, array('follow', 'type', 'schema')))
	//				continue;
						
				$selection = $this->formatParser->getValueBySelector($childConfig, $childConfig, $rootNode, $this->rootNode, false, $this);
				
				//$selection = $this->formatParser->select($childConfig, $rootNode, $this->rootNode);
				
				if (is_array($selection) || is_object($selection)){
					foreach ($selection as $node){
						
						$obj = $this->buildChild($nodes, $node, $childConfig, $rootNode);
						if (kaosIsError($obj)) // error handling
							return $obj;
							
						if (!isset($nodes[$childId])) 
							$nodes[$childId] = array();
						$nodes[$childId][] = $obj;
					}
				}
				
				if (KAOS_DEV_REDUCE_ENTITIES && $i >= KAOS_DEV_REDUCE_ENTITIES)
					break;
				$i++;
			}
		return true;
	}
	
	private function initObject($childConfig, $node, $rootNode = null, $isRoot = false){
		$obj = array();
			
		$is_attr = false;
		$i = 0;
		$did = 0;
		foreach ($childConfig as $key => $config){
			if (in_array($key, array('schema', 'type')) || is_bool($config)) // passthrough keywords
				$obj[$key] = $config;
			else if (!in_array($key, array('selector', 'children', 'childrenWhere', 'regexp', 'transform', 'inject', 'else'))){ // not a reserved keyword
				$obj[$key] = $this->getChildValue($childConfig, $config, $node, $rootNode);
				
				// if returning array
				if (is_array($obj[$key])){
					$nobj = $obj[$key];
					if (!empty($nobj['merge'])){
						unset($obj[$key]);
						
						if (isset($nobj['value']))
							$obj[$key] = $nobj['value'];
						$obj += $nobj['merge'];
						
					} else if (isset($obj[$key]['value']))
						$obj[$key] = $obj[$key]['value'];
						
					//if (isset($obj[$key]['value']))
					//	$obj[$key] = $obj[$key]['value'];
				} 
				$did++;
			}
			$i++;
		}
		
		// simplify objects with with only a "value" attribute
		if ($did < 2 && isset($obj['value']))
			$obj = $obj['value'];
			
		//if (isset($obj['value']))
		//	$obj = $obj['value'];
		
		if (is_array($obj) && !array_key_exists(0, $obj) && (!empty($obj['schema']) || $isRoot))
			$obj += $this->query;
		
		return $obj;
	}

	private function buildChild($nodes, $node, $childConfig, $rootNode = null){
		$obj = $this->initObject($childConfig, $node, $rootNode);
				
		if (!empty($childConfig->children)){
			
			// test childrenWhere
			$build = true;
			if (!empty($childConfig->childrenWhere)){
				$build = false;
				foreach ($childConfig->childrenWhere as $op => $where){
					$build = false;
					foreach ($where as $attr => $regexp){
						if (isset($obj[$attr]) && $obj[$attr] !== null && preg_match($regexp, $obj[$attr])){ // valid condition
							
							if (strtolower($op) == 'or'){
								$build = true;
								break;
							
							} else { // and
								$build = true;
							}
						
						} else { // invalid conditions
							
							if (strtolower($op) == 'and'){
								$build = false;
								break;
							}
						}
					}
				}
				//if (0 && !in_array($obj['num'], array('1', '2A', '2B', '3', '4'))){
//					echo $obj['num'].': '.($build ? 'OK' : 'BAD').'<br>';
				//}
			}
			if ($build){
				$success = $this->buildObject($obj, $childConfig->children, $node);
				if (kaosIsError($success))
					return $success;
			}
		}
		
		if (!empty($obj['follow']) && $this->query['currentLevel'] < $this->query['followLevels']){
			if (empty($obj['type']))
				$obj['type'] = $this->query['type'];
			if (empty($obj['schema']))
				$obj['schema'] = $this->query['schema'];

			if (empty($obj['id'])){
				return 'cannot follow '.$obj['schema'].' '.$obj['type'].' (no id)';
			}

			$query = array(
				'schema' => $obj['schema'],
				'type' => $obj['type'],
				'id' => !empty($obj['id']) ? $obj['id'] : null,
				'followingParent' => $this->query,
			);
			
			if (empty($_GET['onlyFollowId']) || $query['id'] == $_GET['onlyFollowId']){
				
				$bulletinFetcher = new BulletinFetcher();
				
				if (!empty($obj['date']))
					$query['date'] = $obj['date'];
				
				//kaosJSON($query);
				
				if (!empty($obj['followUrl'])){
					foreach ($obj['followUrl'] as $format => $url){
						if (empty($url))
							continue;
							
						$query['url'] = $url;
						$query['format'] = $format; // to implement
						break;
					}
				} 

				$bulletin = $bulletinFetcher->fetchBulletin($query);

				if (kaosIsError($bulletin))
					return new KaosError('cannot fetch following '.$obj['schema'].' '.$obj['type'].' '.$obj['id'].': '.$bulletin->msg); 

				$subParser = new BulletinParser($this);
				$followed = $subParser->doParseBulletin($bulletin, array(
					'schema' => $obj['schema'],
					'type' => $obj['type']
				));
				if (kaosIsError($followed))
					return $followed;
				
				$obj['followed'] = array();
				foreach ($query as $k => $v)
					if (!isset($followed[$k]))
						$obj['followed'][$k] = $v;
				$obj['followed'] += $followed;
			
				// TODO: garbage delete BulletinParser and BulletinFetcher
				
				unset($bulletinFetcher);
				unset($subParser);
				
				//$obj = $followed + $obj;
			}
		}
		return $obj;
	}
	
	private function getChildElse($childConfig, $selector, $node, $rootNode = null){
		if (is_object($selector) && !empty($selector->else)){
			return $this->getChildValue($childConfig, $selector->else, $node, $rootNode);
		}
		return null;		
	}
	
	private function getChildValue($childConfig, $selector, $node, $rootNode = null){
		
		// is an object but has no selector, attribute is an object (recursion)
		if (is_object($selector) && !$this->formatParser->isSelector($selector)){

			$obj = array();
			foreach ($selector as $key => $selector){
				$obj[$key] = $this->getChildValue($selector, $selector, $node, $rootNode);
				
				// TODO: factorize (this is repeated earlier)
				if (!empty($obj[$key]['merge'])){
					$nobj = $obj[$key]['merge'];
					unset($obj[$key]);
					$obj += $nobj;
					
				} else if (isset($obj[$key]['value']))
					$obj[$key] = $obj[$key]['value'];
					
				//if (isset($obj[$key]['value']))
				//	$obj[$key] = $obj[$key]['value'];
					
				//if ($obj[$key] === null || $obj[$key] === '' || (is_array($obj[$key]) && count($obj[$key]) == 1 && isset($obj[$key]['value']) && $obj[$key]['value'] === null) || (is_object($obj[$key]) && property_exists($obj[$key], 'value') && $obj->value === null)) // shouldn't be there (having bad attributes)
					//unset($obj[$key]);
			}
			//if (isset($obj['value']))
			//	$obj = $obj['value'];
			$obj += $this->query;
			return $obj;
		}
		
		$oselector = $selector;
		if (is_string($selector))
			$selector = $this->applyPatternVars($selector, $childConfig);
		
		$value = $this->formatParser->getValueBySelector($selector, $childConfig, $node, $rootNode, true);

		if (is_object($value))
			$value = strip_tags($this->formatParser->getNodeContent($value));
//		else if (is_array($value))
//s			$value = array_shift($value); // get first value in array
		else if (!is_array($value))
			$value = (string) $value;

		if ($value === null)
			return $this->getChildElse($childConfig, $oselector, $node, $rootNode);
		
		//echo 'VALUE: '.print_r($value, true).'<br>';
		if (is_string($value)){
			$value = strtr($value, array(
				'—' => '-',
				'“' => '"',
				'”' => '"',
			));
			$value = preg_replace('#(-\n\s+)#', '', $value);
			$value = trim(preg_replace('#^(\s*[-\.:;·])*(.*?)(\s*[-\.:;·])*\s*$#is', '$2', $value));
		}
		
		if (empty($value))
			return $this->getChildElse($childConfig, $oselector, $node, $rootNode);
			
		// apply transformations	
	
		$value = array('value' => $value);
		
		$args = is_string($selector) ? (array) $childConfig : (array) $selector;
		//$args = (array) $childConfig;

		if (!empty($args['transform'])){
			foreach (is_array($args['transform']) ? $args['transform'] : array($args['transform']) as $tr){
				$value = $this->applyTransform($tr, $value, $childConfig);
				if ($value === null || (isset($value['value']) && $value['value'] === null))
					break;
			}
		}
		if ($value === null || (isset($value['value']) && $value['value'] === null))
			return $this->getChildElse($childConfig, $oselector, $node, $rootNode);
			
		if (count($value) < 2 && isset($value['value']))
			$value = $value['value'];
		return $value;
	}
	
	private function queueGroup(&$groups, &$group, $queue, $options){
		if ($group){
			if (!empty($group['group']))
				$group['group'] = implode(', ', kaosGroupLabelLint($options, $group['group']));

			if (empty($group['amountType']) && $groups && !empty($groups[count($groups)-1]['amountType']))
				$group['amountType'] = $groups[count($groups)-1]['amountType'];
			
			if ($queue){
				$groups[] = $group;
			
			} else if (!empty($group['group'])){
		
				// reorder queue
				$lastAmountType = null;
				foreach ($groups as $i => $g)
					if (!empty($g['amountType']) && ($lastAmountType === null || !$i || empty($groups[$i-1]['amountType']) || $groups[$i-1]['amountType'] != $g['amountType']))
						$lastAmountType = $i;
				
				$lastVal = null;
				if ($lastAmountType !== null){
						
					//echo '$lastAmountType: '.$lastAmountType.'<br>';
							
					foreach ($groups as $j => &$g)
						if ($j >= $lastAmountType){
							$v = $g;
							if ($lastVal !== null)
								$g['value'] = $lastVal['value'];
							else
								$g['value'] = null;
							$lastVal = $v;
						}
					unset($g);
				}
					
				if ($lastVal){
					$group['amountType'] = $lastVal['amountType'];
					$group['value'] = $lastVal['value'];
				}
				$groups[] = $group;
						
				/*
				echo '<br><br>';
				print_r($groups);
				echo '<br><br>';
				print_r($group);
				die();
				*/
			}
		}
		$group = array();
	}
		
	function applyTransform($tr, $value, $childConfig){
		$oval = $value['value'];
		switch ($tr->type){
			
			case 'parseMonetary':
			case 'parseNumber':
				// check "orte neto: 6.39" at http://localhost/boe/application/api/es/boe/2017-01-05/parse
				$debug = false;//stripos($value['value'], '854.057,00 euros') !== false;
				$countrySchema = kaosGetCountrySchema($this->query['schema']);
				
				if (!empty($tr->stripBeforeMatch))
					foreach ($tr->stripBeforeMatch as $m)
						$value['value'] = preg_replace($m, '', $value['value']);
				
				$wrapGroups = cutByGroups($tr, $value['value']);
				$groups = array();


				// DEBUG: 
				/*static $iiii = 0;
				if ($iiii % 2 && !empty($tr->groups) && empty($tr->outputAsGroup)) $wrapGroups = array();
					$iiii++;
				*/
				
				foreach ($wrapGroups as $ccvalue){

					if (!empty($tr->groups) && ($cgroups = preg_split('#('.implode('|', $tr->groups).')#ius', $ccvalue['value'], -1, PREG_SPLIT_DELIM_CAPTURE)) && count($cgroups) > 1){
						
						//echo $value['value'].'<br><br>';
						//kaosJSON($cgroups);
						
						$group = array();
						//$groups = 
						
						$started = false;
						$amountType = null;
						for ($i=0; $i<count($cgroups); $i++){
							
							$cgroups[$i] = preg_replace('#^([\(\):;\.,\s]+)#ius', '', $cgroups[$i]);
							$cgroups[$i] = preg_replace('#([\(\):;\.,\s]+)$#ius', '', $cgroups[$i]);
							$oword = $cgroups[$i];
							
							$sep = preg_match('#('.implode('|', $tr->groups).')#ius', $cgroups[$i]);
							
							$started = true;
							
							if ($debug) echo 'STRING: '.$cgroups[$i].'<br>';
							
							if ($sep){
								if ($debug) echo 'GOT SEP<br>';
								
								$this->queueGroup($groups, $group, true, $tr);//(!empty($group['group']) || !empty($group['amountType'])) && !empty($group['value']));
								

								$group['group'] = $cgroups[$i];
							}
							
							if (!$sep){	
								
								if (!empty($tr->amountTypeDelimiters))
									foreach ($tr->amountTypeDelimiters as $lim => $type)
										if (preg_match('#^\s*('.kaosEscapePatterns($lim).')[;:\s]*(.*)$#ius', $cgroups[$i], $m2)){
											
											$this->queueGroup($groups, $group, (!empty($group['group']) || !empty($group['amountType'])) && isset($group['value']), $tr);
												
											$group = array();
											
											$amountType = $group['amountType'] = $type;
											$cgroups[$i] = $m2[2];
											if ($debug) echo 'GOT TYPE: '.$type.'<br>';
											break;
										}
										
								while (empty($group['value']) || empty($group['entities'])){
									
									$cgroups[$i] = preg_replace('#^([\(\):;\.,\s]+)(.*?)([\):;\.,\s]+)$#ius', '$2', $cgroups[$i]);
									
									if (empty($group['value']) && preg_match('#^\s*('.kaosGetPatternNumber($tr->type == 'parseMonetary').').*#ius', trim($cgroups[$i]), $m)){
										
										if (!empty($group['value'])){
											$this->queueGroup($groups, $group, true, $tr);
											$group = array();
										}
										
										$group['value'] = $m[1];
										
										$group['value'] = preg_replace('#^([\(\):;\.,\s]+)#ius', '', $group['value']);
										$group['value'] = preg_replace('#([\(\):;\.,\s]+)$#ius', '', $group['value']);
										
										if ($debug) echo 'GOT VALUE: '.$group['value'].'<br>';
										$cgroups[$i] = str_replace($m[1], '', $cgroups[$i]);
									}
									
									else if (empty($group['entities']) && ($entities = kaosGetEntities(array(
										'starting' => true,
										'strict' => true,
										
									), $cgroups[$i], $this->query['schema']))){
										
										$group['entities'] = $entities;
										if ($debug) echo 'GOT ENTS: '.print_r($entities, true).'<br>';
										
									} else {
										
										// requeue
										if ($cgroups[$i] != '' && $oword != $cgroups[$i]){
											if ($debug) echo 'REQUEUING: '.$cgroups[$i].'<br>';
											array_splice($cgroups, $i+1, 0, array($cgroups[$i]));
										} else if ($cgroups[$i] != ''){
											if ($debug) echo 'NOTHING MORE: '.$cgroups[$i].'<br>';
										}
										
										break;
									}
										
									$cgroups[$i] = preg_replace('#^([:\(\)\.,;\s]*)(.*?)([:\(\)\.,;\s]*)$#ius', '$2', $cgroups[$i]);
								}
							}
						}
						$this->queueGroup($groups, $group, (!empty($group['group']) || !empty($group['amountType'])) && !empty($group['value']), $tr);
							
					} else {
						
						//$group = !empty(['group']) ? $ccvalue['group'] : '';
						//$groups[$group] = $ccvalue;
						$this->queueGroup($groups, $ccvalue, true, $tr);
					}
				}

				// factorize to a beautiful tree
				$values = array();
				foreach ($groups as $val){
					
					if (empty($val['value'])){
						$values[] = $val;
						
					} else {
						
						foreach ($countrySchema->vocabulary->dateFormats as $format){
							$formatDetect = kaosDateRegexpConvert($format);
							$val['value'] = preg_replace('#'.$formatDetect.'#ius', '', $val['value']);
						}
						
						$value = $val;
						//$val['value'] = preg_replace('#[;:\.\s,]*$#', '', trim($val['value']));
						//$val['value'] = trim(preg_replace('#^[;:\.\s,]*#', '', $val['value']));
						
						do {
							$found = false;
										
							if (!empty($tr->amountTypeDelimiters))
								foreach ($tr->amountTypeDelimiters as $lim => $type)
									if (preg_match('#^\s*('.kaosEscapePatterns($lim).')[;:\s]*(.*)$#ius', $val['value'], $m2)){
										
										$value['amountType'] = $type;
										$val['value'] = preg_replace('#[;:\.\s,]*$#', '', trim($m2[2]));
										$value['value'] = $val['value'] = trim(preg_replace('#^[;:\.\s,]*#', '', $val['value']));
										$found = true;
										break;
									}
							
							if (preg_match('#^\s*'.kaosGetPatternNumber($tr->type == 'parseMonetary').'#ius', trim($val['value']), $m)){

								$original = trim($m[0]);

								$hasCents = preg_match('#.*[,\.]\s*[0-9][0-9]$#', trim($m[1]));
								$value['value'] = intval(preg_replace('#([\.,\s])#', '', $m[1])) / ($hasCents ? 100 : 1);
								
								$val['value'] = str_replace($m[0], '', $val['value']);
								$val['value'] = preg_replace('#[;:\.\s,]*$#', '', $val['value']);
								$val['value'] = trim(preg_replace('#^[;:\.\s,]*#', '', $val['value']));
				
								if ($tr->type == 'parseMonetary'){
									$currency = empty($m[2]) || trim($m[2]) == '' ? $countrySchema->officialCurrencies[0] : trim($m[2]); // TODO: replace by country's schema currency
									
									$value['type'] = 'currency';
									$value['amount'] = $value['value'];
									unset($value['value']);

									$value['unit'] = $currency;
									$value['original'] = $original;
									
								}
								$found = true;
								$values[] = $value;

							} 
							
							if (empty($val['value']))
								break;
							
							if (!$found){
								// DEBUG: echo 'regexp not matched: '.$val['value'].'<br>';
								break;
							}
						
						} while (true);
					}
				}
				
				if (!$values)
					return null;
				$value = !empty($tr->groups) ? ($values ? $values : null) : array_shift($values);
				
				if ($value && empty($tr->groups) && !empty($tr->outputAsGroup))
					$value = array($value);

				if (!empty($tr->groups) || !empty($tr->outputAsGroup)){
				
					if (0){
						if (!empty($tr->outputAsGroup))
							echo 'OUTPUT AS: <br>';
						else
							echo 'NORMAL GROUP: <br>';
						kaosJSON($value);
						echo '-----------------------------------<br>';
						echo $oval.'<br><br>';
					}
					
					$ret = $errors = array();
					foreach ($value as $v){
						if (empty($v['amountType'])){
							$v['amountType'] = 'total';
							
							//$errors[] = 'missing amountType in '.print_r($v, true);
							//continue;
						}
						$type = $v['amountType'];
						unset($v['amountType']);

						if (!isset($ret[$type]))
							$ret[$type] = array(
								'total' => null,
								'groups' => array(),
							);

						if (empty($v['group'])){
							unset($v['group']);
							$ret[$type]['total'] = isset($v['amount']) ? $v : null;

						} else {
							$ccgroups = is_array($v['group']) ? $v['group'] : array($v['group']);
							unset($v['group']);
							
							foreach ($ccgroups as $g)
								$ret[$type]['groups'][$g] = $v;
						}
					}
					
					// check
					$count = null;
					foreach ($ret as $type => &$c){
						if ($count === null)
							$count = count($c['groups']);
						else if (count($c['groups']) != $count){
							$c['errors'][] = $errors[] = 'mismatching group counts: '.$c['total']['amount'].' != '.$total;
						}
							
						if (empty($c['groups'])){
							if (empty($c['total'])){
								if (!isset($c['errors']))
									$c['errors'] = array();
								// REENABLE?? $c['errors'][] = $errors[] = 'missing information';
							}
							continue;
						}
							
						$total = 0;
						foreach ($c['groups'] as $g => $v)
							if (!empty($v['amount']))
								$total += $v['amount'];
						if (!empty($c['total']) && isset($c['total']['amount']) && $c['total']['amount'] != $total){
							if (!isset($c['errors']))
								$c['errors'] = array();
							$c['errors'][] = $errors[] = 'mismatching total and group amounts: '.$c['total']['amount'].' != '.$total;
						}
					}
					unset($c);
					
					if (0 && ($debug || $errors)){
						if ($errors)
							kaosJSON($errors);
						else 
							echo 'ALL CHECKS OK!<br>';
						kaosJSON($ret);
						die();
					}
					
					$count = 0;
					foreach ($ret as &$c){
						if (empty($c['groups'])){
							unset($c['groups']);
							if (!empty($c['total']))
								$count++;
						} else
							$count++;
					}
					unset($c);
					
					if (!$count)
						return null;
						
					$value = $ret;
				}
				
				break;
			
			case 'parseDateDebug':
			case 'parseDate':
			case 'parseDatetime':
				$value['value'] = preg_replace('#([\n]+)#', '', $value['value']);
				//echo "PARSE: ".$value['value'].'<br>';
				
				$countrySchema = kaosGetCountrySchema($this->query['schema']);
				if (empty($tr->dateFormat))
					$tr->dateFormat = 'auto';
					
				if (
					!empty($countrySchema) 
					&& !kaosIsError($countrySchema) 
					&& !empty($countrySchema->vocabulary)){
					
					// convert litteral number to integer
					if (!empty($countrySchema->vocabulary->numberLabels)){
						
						foreach ($countrySchema->vocabulary->numberLabels as $i => $timePattern)
							foreach (is_array($timePattern) ? $timePattern : array($timePattern) as $cTimePattern)
								if (preg_match_all('#(\b'.str_replace(' ', '\s+', $cTimePattern).'\b)#ims', $value['value'], $matches, PREG_SET_ORDER)){
									$replace = $i ? ($i <= 24 ? $i : (20 + (10 * ($i - 24)))) : '0';
									foreach ($matches as $m){
										$value['value'] = str_replace($m[0], $replace, $value['value']);
										//$value['time'] = preg_replace('#([^0-9]+)#', '', $m[intval(ltrim($match, '$'))+1]);
										//$value['time'] = substr($value['time'], 0, 2).':'.substr($value['time'], 2);
									}
									//echo $replace.' in '.$value['value'].'<br>';
								}
					}	
				}
				//$value['time'] = preg_replace('#([^0-9]+)#', '', $m[intval(ltrim($match, '$'))+1]);
				if ($tr->type == 'parseDateDebug')
					return $value;
				
				$countrySchema = kaosGetCountrySchema($this->query['schema']);
				
				/* for testing only */
				/*
				$tr->dateFormat = 'auto';
				$tr->type = 'parseDatetime';
				foreach (array(
					"A las 10:00 horas del 15 de mayo de 2017",
					"8 de abril",
					"8 de abril a las 8.20",
					"8 de abril 8:20 horas",
				) as $val){
					$value['value'] = $val;
			*/
				$outputFormat = $tr->type == 'parseDate' ? 'Y-m-d' : 'Y-m-d '.(!empty($value['time']) ? $value['time'] : 'H:i').':s';
				
				$newValue = array();
			
				if ($tr->dateFormat == 'auto'){
					
					foreach ($countrySchema->vocabulary->dateFormats as $format){
						//echo "TESTING ".$format.'<br>';
						
						// translate date format to regexp
						$formatDetect = kaosDateRegexpConvert($format);
						
						if (preg_match('#'.$formatDetect.'#ius', $value['value'], $m)){
							//echo $format.' detected => '.$m[0].'<br>';
							
							// abstract months!
							$engValuePart = str_ireplace(
								$countrySchema->vocabulary->months, 
								array('january', 'february', 'march', 'april', 'may', 'june', 'july', 'august', 'september', 'october', 'november', 'december'), 
								$m[0]);
								
							if (!($dateArray = date_parse_from_format($format, $engValuePart)))
								continue;
							
							//echo '$dateArray: '.print_r($dateArray, true).'<br>';
							
							$checkDateArray = $dateArray;
							foreach (array(
								'year' => 'Y', 
								'month' => 'm',
								'day' => 'd', 
								'hour' => 'H', 
								'minute' => 'i',
								'second' => 's'
							) as $k => $v) 
								if (!isset($checkDateArray[$k]) || $checkDateArray[$k] === false)
									$checkDateArray[$k] = date($v);
									
							$check = date($format, mktime($checkDateArray['hour'], $checkDateArray['minute'], $checkDateArray['second'], $checkDateArray['month'], $checkDateArray['day'], $checkDateArray['year'])); 
							
							if (!strcasecmp($check, $engValuePart)){
								foreach (array('year', 'month', 'day', 'hour', 'minute', 'second') as $k)
									if (!isset($newValue[$k]) && isset($dateArray[$k]) && $dateArray[$k] !== false){
										//echo 'setting '.$k.' to '.$dateArray[$k].'<br>';
										$newValue[$k] = $dateArray[$k];
									}
							} //else
								//echo 'check failed:'.$check.' != '.$engValuePart.'<br>';
						}
						
						$done = true;
						foreach (array('year', 'month', 'day', 'hour', 'minute') as $k)
							if (!isset($newValue[$k]))
								$done = false;
						if ($done)
							break;
					}
					//print_r($newValue);
					
					if (isset($newValue['month']) && isset($newValue['day'])){
						foreach (array(
							'year' => 'Y', 
							'month' => 'm',
							'day' => 'd', 
						) as $k => $v) 
							if (!isset($newValue[$k]))
								$newValue[$k] = date($v);
						
						foreach (array('hour', 'minute', 'second') as $k) 
							if (!isset($newValue[$k]))
								$newValue[$k] = 0;
								
						$value['value'] = date($outputFormat, mktime($newValue['hour'], $newValue['minute'], $newValue['second'], $newValue['month'], $newValue['day'], $newValue['year'])); 
					}
						
				} else {
					if (!($date_array = date_parse_from_format($tr->dateFormat, $value['value'])))
						return null;
						
					$value['value'] = date($outputFormat, mktime($date_array['hour'], $date_array['minute'], $date_array['second'], $date_array['month'], $date_array['day'], $date_array['year'])); 
				}
				
				if (!empty($value['time']) && preg_match('#^\d{4}-\d{2}-\d{2} d{2}-\d{2}-\d{2}$#', $value['value']))
					$value['value'] = preg_replace('#^(\d{4}-\d{2}-\d{2} )(d{2}-\d{2}-\d{2})$#', '$1'.$value['time'].':00', $value['value']);
				
				/* for testing only */
				/*
				echo "<strong>$val => ".$value['value']."</strong><br>";
				}
				die();
				*/
				// TODO: check date is ok? */
				
				//echo "PARSED: ".$value['value'].'<br>';
				break;
				
			case 'assign':
				$value['value'] = str_replace('{value}', $value['value'], $tr->value);
				break;

			case 'lint': // array compatible
				$value['value'] = kaosLint($value['value']);
				if (is_string($value['value']))
					$value['value'] = trim($value['value']);
				break;

			case 'regexpReplace':
				$value['value'] = preg_replace($tr->regexp, $tr->replace, $value['value']);
				if (is_string($value['value']))
					$value['value'] = trim($value['value']);
				break;
				
			case 'splitBy':
				$regexp = $tr->regexp;
				$regexp = $this->applyPatternVars($regexp, $childConfig);

				if (!empty($tr->includeSeparator))
					$m = preg_split($regexp, $value['value'], -1, PREG_SPLIT_DELIM_CAPTURE);
				else 
					$m = preg_split($regexp, $value['value']);
					
				if (count($m) == 1 && property_exists($tr, 'ifNoMatch'))
					$value['value'] = $tr->ifNoMatch;
				else {
					$value['value'] = array();
					for ($i=0; $i<count($m); $i++){
						$cm = trim($m[$i]);
						if ($cm != ''){
							$next = !empty($tr->includeSeparator) && $i < count($m)-1 ? $m[$i+1] : '';
							$value['value'][] = $cm.$next;
							if (!empty($tr->includeSeparator))
								$i++;
						}
					}
				}
				break;

			case 'parseList':
				if (preg_match('#^(([0-9a-z]+?)\s*'.preg_quote($tr->delimiter, '#').'\s*)(.*)$#i', $value['value'], $m)){
					$value['value'] = trim($m[3]);
					$value['listIntro'] = trim($m[2]);
				}
				break;
				
			case 'convertLegalEntityType':
				$countrySchema = kaosGetCountrySchema($this->query['schema']);
				
				if (!empty($countrySchema->vocabulary) && !empty($countrySchema->vocabulary->legalEntity) && !empty($countrySchema->vocabulary->legalEntityTypes)){
					$val = preg_replace('#([\[\]\(\)])#', '', $value['value']);
					
					foreach ($countrySchema->vocabulary->legalEntityTypes as $legalEntityType => $legalEntityConfig){
						if (preg_match('#^('.implode('|', $legalEntityConfig->patterns).')$#iu', $val)){
							$value['value'] = $legalEntityType;
							break;
						}
					}
				}
				break;
				
			case 'grepLegalEntities':
				$debug = false;
				if ($debug)
					echo 'grep transformation: '.$value['value'].'<br><br>';
				
				$value['value'] = kaosGetEntities($tr, $value['value'], $this->query['schema'], array(), false, $debug);
				
				if ($debug)
					kaosJSON($value['value']);
					
				if (empty($value['value']))
					return null;
				break;

			case 'grepNationalIds':
				$countrySchema = kaosGetCountrySchema($this->query['schema']);
				
				if (!empty($countrySchema->vocabulary) && !empty($countrySchema->vocabulary->legalIdNumbers)){
					$patterns = array();
					foreach ($countrySchema->vocabulary->legalIdNumbers as $formatId => $formatConfig)
						$patterns[] = '\b'.$formatConfig->pattern.'\b';
					$value['value'] = preg_match_all('#('.implode('|', $patterns).')#iusm', $value['value'], $m, PREG_SET_ORDER) ? $m[0] : null;
				} else 
					$value['value'] = null;
				break;

			case 'regexpMatch':
				if (!empty($tr->regexp)){
					$regexp = $this->applyPatternVars($tr->regexp, $childConfig);
					
					$oval = $value['value'];
					$value['value'] = array();
					
					foreach (!is_array($oval) || !array_key_exists(0, $oval) ? array($oval) : $oval as $v){
					
						if (preg_match($regexp, $v, $matches)){
						
							$this->varReplaceNode = (array) $matches; // passed from previous regexpAttr (*)
								
							$value['value'][] = trim(preg_replace_callback('#(\$([0-9]+))#', array(&$this, 'varReplaceCallback'), $tr->match));
						
						} 
					}
					if (!is_array($oval) || !array_key_exists(0, $oval))
						$value['value'] = array_shift($value['value']);
					
					if (empty($value['value']))
						return null;

				}
				//if (isset($value['value']))
				//	$value = $value['value'];
				break;
				
			case 'grepSentence':
				$nCur = array();
				$isAssoc = is_array($value['value']) && $value['value'] && array_key_exists(0, $cur);
				foreach ($isAssoc ? $value['value'] : array($value['value']) as $cCur){
					$countrySchema = kaosGetCountrySchema($this->query['schema']);
					
					if (
						!empty($tr->vocabulary) 
						&& !empty($countrySchema) 
						&& !kaosIsError($countrySchema) 
						&& !empty($countrySchema->vocabulary) 
						&& !empty($countrySchema->vocabulary->legalTaxonomies) 
						&& !empty($countrySchema->vocabulary->legalTaxonomies->{$tr->vocabulary})){
						
						foreach ($countrySchema->vocabulary->legalTaxonomies->{$tr->vocabulary} as $pattern => $patConfig){
							$pattern = str_replace(' ', '\s+', $pattern);
							$pattern = '#\s*([^\.]*)('.$pattern.')(.*?)$#is';
							if (preg_match_all($pattern, $cCur, $m, PREG_SET_ORDER))
								foreach ($m as $cm)
									$nCur[] = $cm[1].$cm[2].$cm[count($cm)-1];
						}
					}
				}
				$value['value'] = $isAssoc ? $nCur : ($nCur ? $nCur[0] : null);
				if (empty($value['value']))
					return null;
				break;
				
			default:
				throw new Exception('transform not found');
		}
		return $value;
	}
	
	function varReplaceCallback($matches){
		$i = intval($matches[2]);
		return isset($this->varReplaceNode[$i]) ? $this->varReplaceNode[$i] : '';
	}
	
	protected function applyPatternVars($selector, $childConfig){
		$selector = str_replace('{legalEntityPattern}', kaosGetLegalEntityPattern($this->query['schema']), $selector);
		if (!empty($childConfig->inject))
			foreach ($childConfig->inject as $name => $val)
				$selector = str_replace('{'.$name.'}', $val, $selector);
		return $selector;
	}
	
	public function getLegalEntityPattern(){
		return kaosGetLegalEntityPattern($this->query['schema']);
	}
	
	
}

function kaosGetNonEmpty($values, $from, $index){
	$c = 0;
	for ($i=$from; $i<count($values); $i++)
		if (!empty($values[$i])){
			if ($c == $index)
				return $values[$i];
			$c++;
		}
	return null;
}


function kaosGetFilter(){
	if (!empty($_GET['filter']))
		return $_GET['filter'];
	if (!empty($_GET['precept']))
		return get('SELECT title FROM precepts WHERE id = %s', $_GET['precept']);
	return null;
}
