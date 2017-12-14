<?php 

class BulletinExtractor {
	
	public $parsed = null;
	
	public function __construct($parsed){
		$this->parsed = $parsed;
	}
	
	public function extract($query, $save = false){
		$schemaObj = kaosGetSchema($this->parsed['schema']);
		$ret = array();
		
		if (!empty($schemaObj->extractProtocoles)){
			
			if ($save)
				update('bulletins', array(
					'status' => 'extracting'
				), array(
					'bulletin_schema' => $query['schema'],
					'date' => $query['date'],
				));
			
			foreach ($schemaObj->extractProtocoles as $extractId => $p){
				$extractQuery = $query;
				
				$cur = $this->parsed;
				$pathMode = null;
				foreach (preg_split('#(//?|@)#', $p->selector, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) as $bit){
					
					
					if ($bit == '//' || $bit == '/' || $bit == '@'){
						$pathMode = $bit;
						continue;
					}
					
					$attrBits = preg_split('#([\[\]])#', $bit, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
					$attrBit = array_shift($attrBits);
					
					switch ($pathMode){
						case '//':	
							$cur = kaosGetObjPath($attrBit, $cur, $extractQuery);
							break;
						case '/':
						case '@':
							if (is_array($cur) && array_key_exists(0, $cur)){
								$cret = array();
								foreach ($cur as $v)
									if (isset($v[$attrBit])){
										$ccur = $v[$attrBit];
										$cret[] = $ccur;
									}
										
								$cur = $cret;
							} else
								$cur = isset($cur[$attrBit]) ? $cur[$attrBit] : null;
							break;
					}

					if ($cur === null)
						break;
						
					if (is_array($cur) && $cur && !isset($cur[0])){ // is assoc array
						$extractQuery = kaosMergeExtractQuery($extractQuery, $cur);
					}
						
					// filtering
					$prepend = null;
					
					while ($attrBit = array_shift($attrBits)){
							
						// impair quotes, prepend to next (this is to allow [title='#[0-9]+#'] without breaking)
						$quoteChange = substr_count($attrBit, "'") % 2 == 1;
						if ($quoteChange){
							if ($prepend){
								$attrBit = $prepend.$attrBit;
								$prepend = null; // end prepending
							} else
								$prepend = ''; // init prepending
						}
						if ($prepend !== null){ 
							$prepend = ($prepend ? $prepend.$attrBit : $attrBit);
							continue;
						}
						
						if ($attrBit != '[' && $attrBit != ']'){
							
							// nth node selector
							if (is_numeric($attrBit))
								$cur = is_array($cur) && array_key_exists(intval($attrBit), $cur) ? $cur[intval($attrBit)] : null;
							else {
								// we may split better, with preg_split and avoiding OR's between quotes
								$cAttrBits = explode(' OR ', $attrBit);

								foreach ($cAttrBits as $cAttrBitsI => $cAttrBit){
								
									// attribute matching
									
									$nCur = array();
									if ($cur !== null && preg_match('#^\s*([a-z]+)\s*((=|<=|<|>|>=|!=|<>)\s*[\']?(.*?)[\']?)?\s*$#i', $cAttrBit, $m)){
											
										$isAssoc = is_array($cur) && array_key_exists(0, $cur);
										foreach ($isAssoc ? $cur : array($cur) as $cCur){
											
											if (empty($m[3])){
												if (!empty($cCur[$m[1]]))
													$nCur[] = $cCur;
											} else 
												switch ($m[3]){
													case '=':
														if (isset($cCur[$m[1]]) && $cCur[$m[1]] !== null && preg_match($m[4], $cCur[$m[1]]))
															$nCur[] = $cCur;
														break;
													case '<>':
													case '!=':
														if (!isset($cCur[$m[1]]) || $cCur[$m[1]] === null || !preg_match($m[4], $cCur[$m[1]]))
															$nCur[] = $cCur;
														break;
													case '>':
														if (isset($cCur[$m[1]]) && $cCur[$m[1]] !== null && $cCur[$m[1]] > floatval($m[4]))
															$nCur[] = $cCur;
														break;
													case '>=':
														if (isset($cCur[$m[1]]) && $cCur[$m[1]] !== null && $cCur[$m[1]] >= floatval($m[4]))
															$nCur[] = $cCur;
														break;
													case '<':
														if (isset($cCur[$m[1]]) && $cCur[$m[1]] !== null && $cCur[$m[1]] < floatval($m[4]))
															$nCur[] = $cCur;
														break;
													case '<=':
														if (isset($cCur[$m[1]]) && $cCur[$m[1]] !== null && $cCur[$m[1]] <= floatval($m[4]))
															$nCur[] = $cCur;
														break;
												}
												
										}
									}
									
									// attribute matching
									else if ($cur !== null && preg_match('#^([a-z]+)!=(.*?)$#i', $cAttrBit, $m)){
											
										$nCur = array();
										$isAssoc = is_array($cur) && array_key_exists(0, $cur);
										foreach ($isAssoc ? $cur : array($cur) as $cCur)
											if (isset($cCur[$m[1]]) && $cCur[$m[1]] !== null && $cCur[$m[1]] != $m[2])
												$nCur[] = $cCur;
									} else
										continue;
										
									if ($nCur || $cAttrBitsI == count($cAttrBits) - 1){
										$cur = $nCur;
										if (!$isAssoc)
											$cur = $cur ? $cur[0] : null;
										break;
									}
								}
							}
								
							if ($cur === null)
								break;
						}
					}
				}	
				
				// extract parts only, if specified
				if (!empty($p->parts)){
					$isAssoc = is_array($cur) && array_key_exists(0, $cur);
					$nCurs = array();
					foreach ($isAssoc ? $cur : array($cur) as $cCur){
						$nCur = array();
						foreach ($p->parts as $part)
							$nCur[$part] = isset($cCur[$part]) ? $cCur[$part] : null;
						kaosMergeExtractQuery($extractQuery, $cCur);
						$nCurs[] = $nCur;
					}
					$cur = $isAssoc ? $nCurs : ($nCurs ? $nCurs[0] : null);
				}
				
				$ret[$extractId] = $cur;
			}
			
			if ($save){
				$this->saveExtract($ret, $query);
			
				update('bulletins', array(
					'status' => 'extracted'
				), array(
					'bulletin_schema' => $query['schema'],
					'date' => $query['date'],
				));
			}
		}
		return $ret;
	}
	
	function preview($obj, $query){
		global $kaosCall;
		$schemaObj = kaosGetSchema($query['schema']);
		
		$this->saveExtract($obj, $query);
		
		foreach ($obj as $extractId => $items){
			$trs = array();
			
			if (!empty($schemaObj->extractProtocoles->{$extractId}->title))
				echo '<h3 class="extract-protocole-title">'.$schemaObj->extractProtocoles->{$extractId}->title.'</h3>';
			
			foreach ($items as $cur){
				
				if (!empty($schemaObj->extractProtocoles->{$extractId}->previewParts)){
					$nCur = array();
					foreach ($schemaObj->extractProtocoles->{$extractId}->previewParts as $part => $config){
						if ($part[0] == '_')
							continue;
							
						$key = isset($config->var) ? $config->var : $part;
						$nCur[$part] = isset($cur[$key]) ? (is_string($cur[$key]) ? kaosCutString($cur[$key]) : $cur[$key]) : null;
						
						// filter output columns
						if (!empty($nCur[$part]) && !empty($config->columns)){
							$isAssoc = is_array($nCur[$part]) && isset($nCur[$part][0]);
							
							$newRet = array();
							foreach ($isAssoc ? $nCur[$part] : array($nCur[$part]) as $e){
								$newE = array();
								foreach ($config->columns as $col)
									$newE[$col] = isset($e[$col]) ? $e[$col] : null;
								$newRet[] = $newE;
							}
							$nCur[$part] = $newRet ? ($isAssoc ? $newRet : $newRet[0]) : ($isAssoc ? array() : null);
						}
						
						if (empty($nCur['issuing']))
							continue;
						
						if (!empty($config->transform))
							foreach ($config->transform as $tr)
								switch ($tr->type){
									
									case 'formatCurrency':
										$currency = kaosGetCountrySchema($schemaObj->id)->officialCurrencies[0];
										if (is_array($nCur[$part])){
											$nCur[$part] = isset($nCur[$part]['amount']) ? $nCur[$part]['amount'] : array_shift($nCur[$part]);
											if (isset($nCur[$part]['unit']))
												$currency = $nCur[$part]['unit'];
										}
											
										$nCur[$part] = is_array($nCur[$part]) ? null : (!empty($nCur[$part]) 
											? ($nCur[$part] > 1000
												? number_format(round($nCur[$part]/1000)).'K '
												: number_format($nCur[$part], 2).' ').$currency 
											: '');
										break;
				
									case 'linkBulletin':
										$val = !empty($nCur[$part]) ? $nCur[$part] : (!empty($cur['schema']) ? $cur['schema'].' (missing ID)' : 'missing schema and ID');
										
										if (!empty($cur['schema']) && !empty($nCur[$part])){
											$args = array();
											$args[$part] = $nCur[$part];
											$args += array(
												'schema' => $cur['schema'], 
												'date' => !empty($nCur['date']) ? $nCur['date'] : (!empty($cur['date']) ? $cur['date'] : (isset($query['date']) ? $query['date'] : null)),
											);
											
											$val = '<a target="_blank" href="'.kaosGetBulletinUrl($args).'">'.$val.'</a>';
										}
										
										$nCur[$part] = $val;
										break;
										
									case 'formatGroupAmounts':
										if (!empty($nCur[$part])){
											$val = $nCur[$part];
											$nCur[$part] = array();
											foreach ($val as $type => $v){
												$groups = array();
												if (!empty($v['groups']))
													foreach ($v['groups'] as $k => $v2)
														$groups[] = array(
															'group' => $k,
															'amount' => $v2['amount'],
															'unit' => $v2['unit']
														);
												if (!empty($v['total']))
													$groups[] = array(
														'group' => 'total',
														'amount' => $v['total']['amount'],
														'unit' => $v['total']['unit'],
													);
													
												$nCur[$part][] = array(
													'type' => $type,
													'groups' => $groups
												);
											}
										}
										break;
										
									case 'formatEntities':
										if (!empty($nCur[$part])){
											$val = $nCur[$part];
											$nCur[$part] = array();
											foreach ($val as $v){
												$nCur[$part][] = array(
													'groups' => $v['groups'],
													'name' => $v['name'],
													'subtype' => $v['subtype'],
												);
											}
										}
										break;
								}
					}
					//echo 'nCur: ';
					//kaosJSON($nCur);
					$trs[] = $nCur;
				}
			}
			if ($trs){
				//die();
				$th = array();
				foreach ($schemaObj->extractProtocoles->{$extractId}->previewParts as $part => $config)
					if ($part[0] != '_')
						$th[] = is_object($config) && !empty($config->title) ? $config->title : $part;

				echo '<div class="kaos-extract-preview-table">';
				kaosPrintTable($trs, $th);
				echo '</div>';
				
				$kaosCall['collapseAPIReturn'] = true;
			}
				
				/*
				foreach ($item['contractTargetEntities'] as $ent)
					$str[] = $ent['name'];
				echo number_format($item['amount']).' EUR for '.implode(', ', $str).'<br>';
				*/
		}
	}

	function saveExtract($obj, $query){
		$schemaObj = kaosGetSchema($query['schema']);
		$countrySchema = kaosGetCountrySchema($schemaObj);
		
		// clean extracted precepts and statuses from same bulletin and date
		
		if (!empty($_GET['filter']))
			return;
		
		ignore_user_abort(true);
		
		if (!empty($_GET['precept']) && is_numeric($_GET['precept'])){
			query('DELETE s FROM bulletins AS b LEFT JOIN bulletin_uses_bulletin AS bb ON b.id = bb.bulletin_in LEFT JOIN bulletins AS b_id ON bb.bulletin_id = b_id.id LEFT JOIN precepts AS p ON b_id.id = p.bulletin_id LEFT JOIN statuses AS s ON p.id = s.precept_id WHERE b.bulletin_schema = %s AND b.date = %s AND p.id = %s', array($query['schema'], $query['date'], $_GET['precept']));
			query('DELETE p FROM bulletins AS b LEFT JOIN bulletin_uses_bulletin AS bb ON b.id = bb.bulletin_in LEFT JOIN bulletins AS b_id ON bb.bulletin_id = b_id.id LEFT JOIN precepts AS p ON b_id.id = p.bulletin_id WHERE b.bulletin_schema = %s AND b.date = %s AND p.id = %s', array($query['schema'], $query['date'], $_GET['precept']));
		
		} else {
			query('DELETE s FROM bulletins AS b LEFT JOIN precepts AS p ON b.id = p.bulletin_id LEFT JOIN statuses AS s ON p.id = s.precept_id WHERE b.bulletin_schema = %s AND b.date = %s', array($query['schema'], $query['date']));
			query('DELETE p FROM bulletins AS b LEFT JOIN precepts AS p ON b.id = p.bulletin_id WHERE b.bulletin_schema = %s AND b.date = %s', array($query['schema'], $query['date']));

		}
		
		//echo 'saving with query: '.kaosJSON($query, false).'<br>';
		
		// IMPROVE: hook into the parser instead? (and build the table from "_type" attribute)
		foreach ($obj as $extractId => $items){
			$curI = 0;
			foreach ($items as $cur){
				// will stop if aborted
				
				if (KAOS_DEV_REDUCE_ENTITIES && $curI >= KAOS_DEV_REDUCE_ENTITIES)
					break;

				$curI++;
				
				$bulletin_id = null;
				
				if (empty($cur['schema']))
					$cur['schema'] = $schemaObj->id;
				
				kaosMergeExtractQuery($query, $cur);

				//echo (!empty($cur['id']) ? 'id '.$cur['id'] : 'no id').' / ';
				//echo (!empty($cur['date']) ? 'date '.$cur['date'] : 'no date').' / ';

				if (!empty($cur['id']))	
					$bulletin_id = get('SELECT id FROM bulletins WHERE bulletin_schema = %s AND external_id = %s', array($cur['schema'], $cur['id']));
				else if (!empty($cur['date']))
					$bulletin_id = get('SELECT id FROM bulletins WHERE bulletin_schema = %s AND date = %s', array($cur['schema'], $cur['date']));
				else {
					echo 'missing bulletin_id or bulletin_date in extractor: '.kaosJSON($cur, false).'<br>';
					continue;
				}
					
				
				if (!$bulletin_id){
					echo 'missing bulletin_id in extractor: '.kaosJSON($cur, false).'<br>';
					continue;
				}
				//echo 'inserting for bulletin_id '.$bulletin_id.'<br>';
				
				$nCur = $updates = array();
				if (!empty($schemaObj->extractProtocoles->{$extractId}->previewParts)){
					foreach ($schemaObj->extractProtocoles->{$extractId}->previewParts as $part => $config){
						if ($part[0] == '_'){
							$nCur[$part] = $config;
							continue;
						}
							
						$key = isset($config->var) ? $config->var : $part;
						if (!empty($config->_type)){
							if (isset($cur[$key])){
								$cupdate = array(
									'_type' => $config->_type,
									'_action' => !empty($config->_action) ? $config->_action : null,
								);
								if (!empty($config->_attr))
									$cupdate[$config->_attr] = $cur[$key];
								$updates[] = $cupdate;
							}
						} 
						$nCur[$part] = isset($cur[$key]) ? $cur[$key] : null;
					}
				}
				
				if (empty($nCur['schema']) && !empty($cur['schema']))
					$nCur['schema'] = $cur['schema'];
					
				if (empty($nCur['related'])){ // empty($nCur['amount']) || 
					//echo "NO RELATED";
					//kaosJSON($nCur);
					//echo '<br>';
					continue;
				}
				
				/*				
				if (preg_match_all('#\b[a-z0-9-]+\b#ius', $nCur['text'], $matches)){
					print_r($matches);
					foreach ($matches as $m){}
				}
				*/
				
				if (!($country = kaosGetCountrySchema($schemaObj->id)->id))
					die('bad country in extractor');
					
				$issuingE = !empty($nCur['issuing']) ? $nCur['issuing'] : array(array(
					'name' => kaosGetSchema($schemaObj->providerId)->name,
					'type' => 'institution'
				));
				if (empty($issuingE)){
					echo "NO ISSUING";
					kaosJSON($nCur);
					echo '<br>';
					continue;
				}
				
				if (is_array($issuingE) && isset($issuingE[0]) && count($issuingE) > 1)
					die("several issuing in extractor");
				
				if (is_array($issuingE) && isset($issuingE[0]))
					$issuingE = $issuingE[0];
				
				if (empty($issuingE['name'])){
//					if (empty($nCur['amount']))
//						continue;
						
					echo 'bad issuing entity: <br>';
					kaosJSON($issuingE);
					kaosJSON($cur);
					die();
				}
				
				$issuing = insertGetEntity(array(
					'name' => $issuingE['name'],
					'type' => $issuingE['type'],
					'country' => $country,
				));
				
				if (!$issuing)
					die('cant insert issuing');
					
				
				$p = array(
					'bulletin_id' => $bulletin_id,
					'issuing_id' => $issuing,
					'title' => !empty($nCur['title']) ? kaosArrayToStr($nCur['title']) : null,
					'text' => kaosArrayToStr($nCur['text']),
				);
				$pid = insertGetPrecept($p);
				
				if (!$pid)
					die('cant insert precept: '.print_r($p, true));
				
				if ($nCur['related']){

					if (!empty($nCur['_type']))
						array_unshift($updates, $nCur);
						
					$relateds = array();
					foreach (is_array($nCur['related']) && isset($nCur['related'][0]) ? $nCur['related'] : array($nCur['related']) as $related){
						$e = array(
							'name' => $related['name'],
							'type' => $related['type'],
							'subtype' => $related['subtype'],
							'country' => $country,
						);
						$related_id = insertGetEntity($e);

						if (!$related_id)
							die('cant insert related: '.print_r($related, true));
							
						$e['id'] = $related_id;
						$e += $related;
						$relateds[] = $e;
					}
						
					$targets = array();
					if (!empty($nCur['target']))
						foreach (is_array($nCur['target']) && isset($nCur['target'][0]) ? $nCur['target'] : array($nCur['target']) as $target){
							$e = array(
								'name' => $target['name'],
								'first_name' => isset($target['first_name']) ? $target['first_name'] : null,
								'type' => $target['type'],
								'subtype' => isset($target['subtype']) ? $target['subtype'] : null,
								'country' => $country,
							);
							$ctarget = insertGetEntity($e);

							if (!$ctarget)
								die('cant insert target entity: '.print_r($e, true).' #1');
								
							$targets[] = $ctarget;
						}
					
					// add a target-less status if no target at all
					if (!$targets)
						$targets[] = null;
						
					foreach ($updates as $u){
						/*
						if (!empty($u['_type']) && $u['_type'] != 'absorb'){
							kaosJSON($u);
							die();
						}*/
						
						// error detection 
						// TODO: log these errors somehow!
						switch ($u['_type']){
							case 'location':
							case 'object':
								if (empty($u['note']))
									continue;
									
								/* TODO: geoloc on the fly or in a different spider?
								 * 
								 * if ($u['_type'] == 'location'){
									$loc = kaosHereComConvertLocation($u['note'], $countrySchema);
									if ($loc)
										$u['note'] = kaosSaveLocation($loc);
								}*/
								break;
						}
						
						foreach ($relateds as $related){
							
							// calc amounts from groups
							$amounts = array();
							if (!empty($u['amount'])){
								
								// classic, single amount
								if (isset($u['amount']['amount'])){
									$amounts[] = $u['amount'];
									
								// complex, multidim amount
								} else {
									// take first of total or net amount that has groups, and add if matching them
									foreach (array('total', 'net') as $type)
										if (isset($u['amount'][$type], $u['amount'][$type]['groups'])){
											foreach ($u['amount'][$type]['groups'] as $group => $camount)
												if (in_array($group, $related['groups'])){ 
													
													// TODO: improve matching by tolerating mispelling (like an "I" becoming an "1", or the reverse)
													
													$amounts[] = $camount;
												}
											break;
										}
									
									// fallback to total/total or net/total
									if (!$amounts){
										foreach (array('total', 'net') as $type)
											if (isset($u['amount'][$type]) && isset($u['amount'][$type]['total'])){
												$amounts[] = $u['amount'][$type]['total'];
												break;
											}
									}
								}
							} 

							// store amounts in a different table
							$amountIds = array();
							foreach ($amounts as $a)
								if ($amount = kaosConvertAmount($a, $schemaObj->id))
									$amountIds[] = insert('amounts', $amount);

							// add an amount-less status if no amount at all
							if (!$amountIds)
								$amountIds[] = null;
								
							$ctargets = array();
							
							foreach (!empty($u['target']) ? $u['target'] : $targets as $target){
								if (is_array($target)){
									$e = array(
										'name' => $target['name'],
										'type' => $target['type'],
										'subtype' => $target['subtype'],
										'country' => $country,
									);
									$ctarget = insertGetEntity($e);

									if (!$ctarget)
										die('cant insert target entity: '.print_r($e, true).' #4');
									
									$ctargets[] = $ctarget;
								}
							}
							
							// add a target-less status if no target at all
							if (!$ctargets)
								$ctargets[] = null;
							
							
							//echo 'targets: ';
							//kaosJSON($ctargets);
							
							foreach ($ctargets as $target){

								foreach ($amountIds as $amountId){
									$status = array(
										'precept_id' => $pid,
										'target_id' => $target,
										'type' => !empty($u['_type']) ? $u['_type'] : (!empty($nCur['_type']) ? $nCur['_type'] : null),
										'action' => !empty($u['_action']) ? $u['_action'] : (!empty($nCur['_action']) ? $nCur['_action'] : 'new'),
										'amount' => $amountId,
										'related_id' => $related['id'],
										'contract_type_id' => !empty($u['type']) ? addGetOption('contractType', $u['type']) : null,
										'sector_id' => !empty($u['sector']) ? addGetOption('sector', $u['sector']) : null,
										'note' => !empty($u['note']) ? $u['note'] : null,
									);
									
									if (!($sid = insert('statuses', $status)))
										die('cant insert status: '.print_r($status, true));
										
									if (!empty($u['service']))
										foreach (is_array($u['service']) ? $u['service'] : array($u['service']) as $service)
											insert('status_has_service', array(
												'status_id' => $sid,
												'service_id' => addGetOption('service', $service)
											));
								}
							}
						}
					}
				}
			}
		}
		ignore_user_abort(false);
	}	
}

function insertGetEntity($e){
	if (empty($e['name']) || empty($e['type']) || empty($e['country']))
		return false;
		
	// TODO: add lock wait to not get duplicated entities
		
	$e['name'] = lintName($e['name']);
	
	// session cache
	static $cache = array();
	$key = mb_strtoupper($e['country'].'/'.$e['name'].(isset($e['first_name']) ? ' '.$e['first_name'] : ''));
	
	if (isset($cache[$key]))
		return $cache[$key];
		
	$e = kaosLintPerson($e);
	
	if (!empty($e['first_name']))
		$e['first_name'] = lintName($e['first_name']);
		
	$subtype = !empty($e['subtype']) ? queryPrepare(' AND subtype = %s', $e['subtype']) : '';
	$first_name = !empty($e['first_name']) ? queryPrepare(' AND first_name = %s', $e['first_name']) : ' AND first_name IS NULL';
	
	$q = queryPrepare('SELECT id FROM entities WHERE country = %s AND type = %s', array($e['country'], $e['type']));
	$qname = queryPrepare(' AND name = %s', $e['name']);
	
	//echo 'QUERY: '.$q.$subtype.$qname.$first_name.'<br>';
	$eid = get($q.$subtype.$qname.$first_name);

	$name = $e['name'].(!empty($e['first_name']) ? ' '.$e['first_name'] : '');
	
	if (!$eid){
	
		$eid = insert('entities', $e + array(
			'fetched' => date('Y-m-d H:i:s'),
			'keywords' => cleanKeywords($name),
			'slug' => kaosGetSlug('entities', 'slug', $name, 200),
		));
		
		if (KAOS_IS_CLI)
			kaosPrintLog('inserted entity '.$name, array('color' => 'grey'));
	
	} else if (KAOS_IS_CLI)
		kaosPrintLog('found entity '.$name, array('color' => 'grey'));
	
	// store to cache
	$cache[$key] = $eid;
	return $eid;
}


function kaosGetObjPath($bit, $cur, &$extractQuery){
	//if (isset($cur['id']))
	//	echo 'getobjpath id: '.$cur['id'].'<br>';
		
	$extractQuery = kaosMergeExtractQuery($extractQuery, $cur);
	
	$ret = array();
	if (is_object($cur))
		$cur = (array) $cur;
		
	if (isset($cur[$bit])){
		if (is_array($cur[$bit]) && array_key_exists(0, $cur[$bit]))
			$ret = array_merge($ret, $cur[$bit]);
		else
			$ret[] = $cur[$bit];
	}
	if (is_object($cur) || is_array($cur))
		foreach ($cur as $v){
			$ret = array_merge($ret, kaosGetObjPath($bit, $v, $extractQuery));
		}

	foreach ($ret as &$r)
		$extractQuery = kaosMergeExtractQuery($extractQuery, $r);
	unset($r);
		

/*
	foreach ($ret as &$ccur){
		if (is_array($ccur) && $ccur && !isset($ccur[0])){ // is assoc array
			if (empty($ccur['id']))
				$ccur['id'] = $docId;
			else
				$docId = $ccur['id']; // not good?
				
			if (empty($ccur['date']))
				$ccur['date'] = $docDate;
			else
				$docDate = $ccur['date']; // not good?
		}
	}
	unset($ccur);
*/
	
	//echo 'getObjPath ended with ID '.$docId.' / date '.$docDate.'<br>';
	return $ret;
}


	
function kaosMergeExtractQuery($extractQuery, &$ccur){
	if (is_array($ccur)){
	
	//	if (isset($ccur['id']))
	//		echo 'extractquery: '.$ccur['id'].'<br>';
			
		foreach (array('id', 'date', 'schema') as $k)
			if (empty($ccur[$k])){
				if (!empty($extractQuery[$k]))
					$ccur[$k] = $extractQuery[$k];
			} else
				$extractQuery[$k] = $ccur[$k];
	}
	return $extractQuery;
}
