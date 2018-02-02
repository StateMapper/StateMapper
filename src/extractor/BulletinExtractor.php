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

class BulletinExtractor {
	
	public $parsed = null;
	
	public function __construct($parsed){
		$this->parsed = $parsed;
	}
	
	public function extract($query, $save = false){
		$schemaObj = get_schema($this->parsed['schema']);
		$ret = array();
		
		if (empty($schemaObj->extractProtocoles))
			return new SMapError('extractProtocoles not found for '.$this->parsed['schema']);
			
		if ($save)
			set_bulletin_extracting($query);
		
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
						$cur = get_object_path($attrBit, $cur, $extractQuery);
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
					$extractQuery = merge_extract_objects($extractQuery, $cur);
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
					merge_extract_objects($extractQuery, $cCur);
					$nCurs[] = $nCur;
				}
				$cur = $isAssoc ? $nCurs : ($nCurs ? $nCurs[0] : null);
			}
			
			$ret[$extractId] = $cur;
		}
		
		if ($save)
			$this->save_extract($ret, $query);
			
		return $ret;
	}
	
	function preview($obj, $query){
		global $smap;
		$schemaObj = get_schema($query['schema']);
		
		$this->save_extract($obj, $query);
		
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
						$nCur[$part] = isset($cur[$key]) ? (is_string($cur[$key]) ? make_foldable($cur[$key]) : $cur[$key]) : null;
						
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
										$currency = get_schema($schemaObj->id)->officialCurrencies[0];
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
											
											$val = '<a target="_blank" href="'.url($args).'">'.$val.'</a>';
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
					//print_json($nCur);
					$trs[] = $nCur;
				}
			}
			if ($trs){
				//die();
				$th = array();
				foreach ($schemaObj->extractProtocoles->{$extractId}->previewParts as $part => $config)
					if ($part[0] != '_')
						$th[] = is_object($config) && !empty($config->title) ? $config->title : $part;

				echo '<div class="extract-preview-table">';
				print_table($trs, $th);
				echo '</div>';
				
				$smap['collapse_api_return'] = true;
			}
				
				/*
				foreach ($item['contractTargetEntities'] as $ent)
					$str[] = $ent['name'];
				echo number_format($item['amount']).' EUR for '.implode(', ', $str).'<br>';
				*/
		}
	}

	function save_extract($obj, $query){
		$schemaObj = get_schema($query['schema']);
		$countrySchema = get_schema($schemaObj);
		
		if (!IS_CLI && !empty($_GET['filter']))
			return;
		
		ignore_user_abort(true);
		
		// clean extracted precepts and statuses from same bulletin and date

		if (!IS_CLI && is_admin() && (!empty($_GET['precept']) && is_numeric($_GET['precept']))){
			// reparsing one precept.. (experimental)
			query('DELETE s FROM bulletins AS b LEFT JOIN precepts AS p ON b.id = p.bulletin_id LEFT JOIN statuses AS s ON p.id = s.precept_id WHERE b.bulletin_schema = %s AND b.date = %s AND p.id = %s', array($query['schema'], $query['date'], $_GET['precept']));
			query('DELETE p FROM bulletins AS b LEFT JOIN precepts AS p ON b.id = p.bulletin_id WHERE b.bulletin_schema = %s AND b.date = %s AND p.id = %s', array($query['schema'], $query['date'], $_GET['precept']));
		
		} else {
			query('DELETE s FROM bulletins AS b LEFT JOIN precepts AS p ON b.id = p.bulletin_id LEFT JOIN statuses AS s ON p.id = s.precept_id WHERE b.bulletin_schema = %s AND b.date = %s', array($query['schema'], $query['date']));
			query('DELETE p FROM bulletins AS b LEFT JOIN precepts AS p ON b.id = p.bulletin_id WHERE b.bulletin_schema = %s AND b.date = %s', array($query['schema'], $query['date']));

		}
		
		//echo 'saving with query: '.print_json($query, false).'<br>';
		
		// IMPROVE: hook into the parser instead? (and build the table from "_type" attribute)
		foreach ($obj as $extractId => $items){
			$curI = 0;
			foreach ($items as $cur){
				// will stop if aborted
				
				if (DEV_REDUCE_ENTITIES && $curI >= DEV_REDUCE_ENTITIES)
					break;

				$curI++;
				
				$bulletin_id = null;
				
				if (empty($cur['schema']))
					$cur['schema'] = $schemaObj->id;
				
				merge_extract_objects($query, $cur);

				//echo (!empty($cur['id']) ? 'id '.$cur['id'] : 'no id').' / ';
				//echo (!empty($cur['date']) ? 'date '.$cur['date'] : 'no date').' / ';

				if (!empty($cur['id']))	
					$bulletin_id = get_var('SELECT id FROM bulletins WHERE bulletin_schema = %s AND external_id = %s', array($cur['schema'], $cur['id']));
				else if (!empty($cur['date']))
					$bulletin_id = get_var('SELECT id FROM bulletins WHERE bulletin_schema = %s AND date = %s', array($cur['schema'], $cur['date']));
				else {
					echo 'missing bulletin_id or bulletin_date in extractor: '.print_json($cur, false).'<br>';
					continue;
				}
					
				
				if (!$bulletin_id){
					echo 'missing bulletin_id in extractor: '.print_json($cur, false).'<br>';
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
					//print_json($nCur);
					//echo '<br>';
					continue;
				}
				
				/*				
				if (preg_match_all('#\b[a-z0-9-]+\b#ius', $nCur['text'], $matches)){
					print_r($matches);
					foreach ($matches as $m){}
				}
				*/
				
				if (!($country = get_schema($schemaObj->id)->id))
					die('bad country in extractor');
					
				$issuingE = !empty($nCur['issuing']) ? $nCur['issuing'] : array(array(
					'name' => get_schema($schemaObj->providerId)->name,
					'type' => 'institution'
				));
				if (empty($issuingE)){
					echo "NO ISSUING";
					print_json($nCur);
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
					print_json($issuingE);
					print_json($cur);
					die();
				}
				
				// insert issuing
				$issuing_obj = array(
					'name' => $issuingE['name'],
					'type' => $issuingE['type'],
					'country' => $country,
				);
				$issuing = insertget_entity($issuing_obj);
				
				if (!$issuing)
					die('cant insert issuing');
				
				// insert precept
				$p = array(
					'bulletin_id' => $bulletin_id,
					'issuing_id' => $issuing,
					'title' => !empty($nCur['title']) ? array_to_str($nCur['title']) : null,
					'text' => array_to_str($nCur['text']),
				);
				$pid = insertget_precept($p);
				
				set_used_name_for($issuing, $issuing_obj['name'], $pid);
				
				if (!$pid)
					die('cant insert precept: '.print_r($p, true));
					
				// insert all related
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
						$related_id = insertget_entity($e, $pid);

						if (!$related_id)
							die('cant insert related: '.print_r($related, true));
							
						$e['id'] = $related_id;
						$e += $related;
						$relateds[] = $e;
					}
						
					// insert all targets
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
							$ctarget = insertget_entity($e, $pid);

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
							print_json($u);
							die();
						}*/
						
						// error detection 
						// TODO: log these errors somehow!
						switch ($u['_type']){
							case 'location':
							case 'object':
								if (empty($u['note']))
									continue;
									
								/* TODO: geoloc on the fly or in a different spider? (anyway, go through filter 'location_lint')
								 * 
								 * if ($u['_type'] == 'location'){
									$loc = herecom_convert_location($u['note'], $countrySchema);
									if ($loc)
										$u['note'] = insert_location($loc);
								}*/
								break;
						}
						
						switch ($u['_action']){
							case 'decrease':
								if (!empty($u['amount']))
									$u['amount'] = -$u['amount'];
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
												if ($related['groups'] && in_array($group, $related['groups'])){ 
													
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
							foreach ($amounts as $a){
								if (is_array($a) && count($a) == 1 && isset($a['value']))
									$a = $a['value'];
								
								if ($a && ($amount = convert_amount($a, $schemaObj->id)))
									$amountIds[] = insert('amounts', $amount);
							}

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
									$ctarget = insertget_entity($e, $pid);

									if (!$ctarget)
										die('cant insert target entity: '.print_r($e, true).' #4');
									
									$ctargets[] = $ctarget;
								}
							}
							
							// add a target-less status if no target at all
							if (!$ctargets)
								$ctargets[] = null;
							
							
							//echo 'targets: ';
							//print_json($ctargets);
							
							foreach ($ctargets as $target){

								foreach ($amountIds as $amountId){
									$status = array(
										'precept_id' => $pid,
										'target_id' => $target,
										'type' => !empty($u['_type']) ? $u['_type'] : (!empty($nCur['_type']) ? $nCur['_type'] : null),
										'action' => !empty($u['_action']) ? $u['_action'] : (!empty($nCur['_action']) ? $nCur['_action'] : 'new'),
										'amount' => $amountId,
										'related_id' => $related['id'],
										'contract_type_id' => !empty($u['type']) ? addget_option('contractType', $u['type']) : null,
										'sector_id' => !empty($u['sector']) ? addget_option('sector', $u['sector']) : null,
										'note' => !empty($u['note']) ? $u['note'] : null,
									);
									
									if (!($sid = insert('statuses', $status)))
										die('cant insert status: '.print_r($status, true));
										
									if (!empty($u['service']))
										foreach (is_array($u['service']) ? $u['service'] : array($u['service']) as $service)
											insert('status_has_service', array(
												'status_id' => $sid,
												'service_id' => addget_option('service', $service)
											));
								}
							}
						}
					}
				}
			}
		}

		set_bulletin_extracted($query);
		ignore_user_abort(false);
	}	
}

function get_object_path($bit, $cur, &$extractQuery){
	//if (isset($cur['id']))
	//	echo 'getobjpath id: '.$cur['id'].'<br>';
		
	$extractQuery = merge_extract_objects($extractQuery, $cur);
	
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
			$ret = array_merge($ret, get_object_path($bit, $v, $extractQuery));
		}

	foreach ($ret as &$r)
		$extractQuery = merge_extract_objects($extractQuery, $r);
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
	
function merge_extract_objects($extractQuery, &$ccur){
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
