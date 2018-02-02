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


function parse_entities($options, $oval, $schema, $extraEnt = array(), $doubleChecking = false, $smapDebug = false){
	$options = (array) $options;
	$starting = !empty($options['starting']);

	$countrySchema = get_schema($schema);
	$extra = !empty($options['entityExtra']) ? (array) $options['entityExtra'] : array();

	//$prefix = !empty($options->allowEntityPrefix) ? $options->allowEntityPrefix : false;

	// TODO: allowEntityPrefix could be badly excluded from company name :S

	if (empty($countrySchema->vocabulary) || empty($countrySchema->vocabulary->legalEntityTypes))
		return null;

	// TODO: avoid being in an HTML tag (when converting entities)

	$and = '(?:\b'.$countrySchema->vocabulary->basicVocabulary->and.'\s+)*';
	$companyPattern = get_entity_pattern($schema, !empty($options['allowEntityPrefix']) ? $options['allowEntityPrefix'] : '', $companyNamePattern, !isset($options['strict']) || $options['strict'], $exceptPatterns);
	//echo "COMPANY PAT: ".$companyPattern.'<br>';

	// count parenthesis in particulePatterns
	//$particulePatternsMatchCount = preg_match_all('#((?<!\\\\)\()#', $particulePatterns, $m, PREG_SET_ORDER) ? count($m) : 0;

	/*
	$entityTypesPattern = array();
	foreach ($countrySchema->vocabulary->legalEntityTypes as $legalEntityType => $legalEntityConfig){
		if (!empty($legalEntityConfig->strictPatterns))
			foreach ($legalEntityConfig->strictPatterns as $pat){
				$pat = preg_replace('#(?<!\\\\)\((?!\?:)#', '(?:', $pat);
				$entityTypesPattern[] = str_replace('{legalEntityPattern}', '', $pat);
			}
	}
	$entityTypesPattern = $entityTypesPattern ? '(?='.implode('|', $entityTypesPattern).')' : '';
	//echo 'PAT: '.$entityTypesPattern.'<br>';
	*/
	$entityTypesPattern = '';

	$ret = array();
	foreach (is_array($oval) ? $oval : array($oval) as $applyVal){ // abstract if the input is a string or an array
		$added = false;
		$applyVal = html_entity_decode($applyVal);

		if (!$doubleChecking && !empty($options['stripBeforeMatch']))
			$applyVal = trim(preg_replace($options['stripBeforeMatch'], '', $applyVal));

		$wrapGroups = array();

		// cut by wrapGroups if any specified and matching
		$wrapGroups = split_by_groups($options, $applyVal, $smapDebug);

		if ($smapDebug){
			print_json($wrapGroups);
		}

		if ($wrapGroups){

			foreach ($wrapGroups as $m){

				if (!$doubleChecking && !empty($options['stripBeforeMatch']))
					$m['value'] = trim(preg_replace($options['stripBeforeMatch'], '', $m['value']));


				//echo "<br>OUTER SEL: ".$m.'<br>';

				//$cut = preg_split('#\b('.escape_patterns($companyPattern).')\b#uis', preg_replace('#\n#ius', '', $m), -1, PREG_SPLIT_DELIM_CAPTURE);

				$m['value'] = trim(preg_replace('#\n#ius', '', $m['value']));

				// TODO: PREG COMPANY TYPES ONE BY ONE?!?! (because UTE's are catching before SL now!)

				// split by groups
				$groups = array();
				$group = '';
				if (!empty($options['groups'])
					&& ($cgroups = preg_split('#('.implode('|', $options['groups']).')#ius', $m['value'], -1, PREG_SPLIT_DELIM_CAPTURE))
					&& count($cgroups) > 1){

					if ($smapDebug){
						echo 'CGROUPS: <br>';
						print_json($cgroups);
						echo '<br>';
					}

					$group = '';
					for ($i=0; $i<count($cgroups); $i++){
						$ccgroups = trim(preg_replace('#[;:\)\(\s]*$#', '', $cgroups[$i]));
						$ccgroups = trim(preg_replace('#^[;:\)\(\s]*#', '', $ccgroups));
						if (empty($ccgroups))
							continue;

						if (preg_match('#('.implode('|', $options['groups']).')#ius', $ccgroups)) // is sep
							$group = trim(preg_replace('#[;:]\s*$#', '', $ccgroups));
						else {
							$cval = trim(preg_replace('#[;:]\s*$#', '', $ccgroups));
							if ($cval != ''){
								if (!isset($groups[$group]))
									$groups[$group] = array();
								$groups[$group][] = $cval;
							}
							$group = '';
						}
					}

					// reorder if group delimiter last
					if ($group != ''){
						$keys = array_keys($groups);
						if ($keys[0] == ''){
							$ccgroups = array();
							$i = 0;
							foreach ($groups as $k => $g){
								$cgroup = $i == count($groups)-1 ? $group : $keys[$i+1];
								if (!isset($ccgroups[$cgroup]))
									$ccgroups[$cgroup] = array();
								$ccgroups[$cgroup] = array_merge($ccgroups[$cgroup], $g);
								$i++;
							}
							$groups = $ccgroups;
						}
					}

				} else {
					if (!empty($m['group']))
						$group = $m['group'];
					if (!isset($groups[$group]))
						$groups[$group] = array();
					$groups[$group][] = trim(preg_replace('#[;:]\s*$#', '', $m['value']));
				}

				if (0 && $smapDebug){
					echo "GROUPS: "; print_json($groups); echo '<br><br><br>';
				}

				$matches = array();

				foreach ($groups as $group => $m2s){

					foreach ($m2s as $m2){

						$prepattern = escape_patterns((!$doubleChecking && !empty($options['allowEntityPrefix']) ? $options['allowEntityPrefix'] : '').$exceptPatterns);

						if (preg_match_all('#'.($starting ? '^' : '').$prepattern.'(\b'.$companyPattern.'\b'.$entityTypesPattern.')#uis', $m2, $entities, PREG_SET_ORDER)){

							//foreach ($m2 as $cm2)
								//preg_match('#('.$companyNamePattern.')(.*
							//echo 'ENTS: '.print_r($m2, true).'<br>';

							foreach ($entities as $entity){
								$ent = array(
									'name' => $entity[1],
									'type' => !empty($extra['type']) ? $extra['type'] : null,
									'subtype' => !empty($extra['subtype']) ? $extra['subtype'] : null,
									'original' => $entity[1],
									'groups' => empty($group) ? null : lint_group_label($options, $group),
								);

								if (!$doubleChecking && !empty($options['allowEntityPrefix'])) // TODO: CONTINUE HERE: NOT WORKING: TRYING TO STRIP PREFIX OUT OF  http://localhost/boe/application/api/es/boe/2017-2018-09-22/extract/1?noProcessedCache=1
									$ent['name'] = preg_replace('#^('.$options['allowEntityPrefix'].')#us', '', $ent['name']);

								//print_r($ent);
								foreach ($countrySchema->vocabulary->legalEntityTypes as $legalEntityType => $legalEntityConfig){

									if (!empty($legalEntityConfig->strictPatterns))
										foreach ($legalEntityConfig->strictPatterns as $pat){

											$companyLeft = strpos($pat, '{legalEntityPattern}') === 0;
											$pat = str_replace('{legalEntityPattern}', ($companyLeft ? '' : '\s*,?\s*').'(\b'.$companyNamePattern.'\b)'.($companyLeft ? ',?' : '').'\s*', $pat);

											if (preg_match('#'.$pat.'$#ums', $ent['name'], $m3)){
												$ent['name'] = get_nth_non_empty($m3, 1, $companyLeft ? 0 : 1);
												$ent['type'] = 'company';
												$ent['subtype'] = $legalEntityType;
												break;
											}
										}

									if (empty($ent['subtype']) && !empty($legalEntityConfig->caseInsensitivePatterns))
										foreach ($legalEntityConfig->caseInsensitivePatterns as $pat){

											$companyLeft = strpos($pat, '{legalEntityPattern}') === 0;
											$pat = str_replace('{legalEntityPattern}', ($companyLeft ? '' : '\s*,?\s*').'(\b'.$companyNamePattern.'\b)'.($companyLeft ? ',?' : '').'\s*', $pat);

											if (preg_match('#'.$pat.'$#iums', $ent['name'], $m3)){
												$ent['name'] = get_nth_non_empty($m3, 1, $companyLeft ? 0 : 1);
												$ent['type'] = 'company';
												$ent['subtype'] = $legalEntityType;
												break;
											}
										}

								}
								//echo 'F: '.$ent['name'].'<br>';
								if (empty($ent['name']) || $ent['name'] != $ent['subtype'])
									$matches[] = $extraEnt + $ent;
							}

						} else if (empty($options['strict'])){ // type not detected

							if (empty($countrySchema->vocabulary->legalEntityName->exception) || !preg_match('#^'.$countrySchema->vocabulary->legalEntityName->exception.'$#us', $m2))

								$matches[] = $extraEnt + array(
									'name' => $m2,
									'type' => !empty($extra['type']) ? $extra['type'] : null,
									'subtype' => null,
									'original' => $m2,
									'groups' => $group == '' ? null : lint_group_label($options, $group),
								);
						}
					}
				}

				//echo "MATCHES: ".print_r($matches, true).'<br>';

				foreach ($matches as $c){

					$c['name'] = lint($c['name']);

					if (!$doubleChecking && !empty($options['stripFromMatch']))
						$c['name'] = lint(preg_replace($options['stripFromMatch'], '', $c['name']));

					$c['name'] = lint(preg_replace('#^('.(!empty($options['allowEntityPrefix']) ? $options['allowEntityPrefix'] : '').'\s*'.$and.'\s*)#u', '', $c['name']));

					$c['linted'] = get_entity_title($c);

					// lint company particules
					if (!empty($c['subtype'])){
						foreach ($countrySchema->vocabulary->legalEntityTypes as $legalEntityType => $legalEntityConfig){

							if (!empty($legalEntityConfig->exceptions) && preg_match('#^('.implode('|', $legalEntityConfig->exceptions).')$#ius', $c['linted'])){ // apply some exception rules
								$c['linted'] = '';
								break;
							}

							/*$strictPattern = str_replace('{legalEntityPattern}', $companyNamePattern, implode('|', $legalEntityConfig->strictPatterns));

							$caseInsensitivePattern = !empty($legalEntityConfig->caseInsensitivePatterns) ? str_replace('{legalEntityPattern}', $companyNamePattern, implode('|', $legalEntityConfig->caseInsensitivePatterns)) : null;

							if (preg_match('#\b('.$strictPattern.')\b$#ums', $c['type'])){
								$lintedName = $c['name'].', '.$legalEntityConfig->shortName;
								break;

							} else if ($caseInsensitivePattern && preg_match('#\b('.$caseInsensitivePattern.')\b$#iums', $c['type'])){
								$lintedName = $c['name'].', '.$legalEntityConfig->shortName;
								break;

							} */
						}
					}

					if ($c['linted'] != ''){
						$k = strtoupper($c['linted']);

						if (!isset($ret[$k]))
							$ret[$k] = $c;

						else if (!empty($c['groups'])){ // merge groups
							if (empty($ret[$k]['groups']))
								$ret[$k]['groups'] = array();
							$ret[$k]['groups'] = array_unique(array_merge($ret[$k]['groups'], $c['groups']));
						}
					}
					$added = true;
				}

//								$ret[] = print_r($matches, true);
			}
		}

				if (0 && !empty($options['wrapGroups']) && stripos($oval, 'Siemens') !== false){
					print_json($wrapGroups);
					print_json($matches);
					echo nl2br(htmlentities($oval)).'<br><br>';
					print_json($groups);
					print_json(array_values($ret));
					die('x');
				}

		//if (!$added && !empty($options['allowOtherValue']) && !empty($applyVal) && empty($options['strict']))
		//	$ret[strtoupper($applyVal['linted'])] = $applyVal;
	}

	/*
	if (!$doubleChecking){
		$toCheck = $ret;
		$added = 0;
		do {
			$newEntities = array();
			$checkRet = array();
			foreach ($toCheck as $i => $r){
				echo 'checking '.$r['original'].'<br>';
				$entities = parse_entities($options, $r['original'], $schema);
				echo '-> '.($entities ? count($entities) : 0).'<br>';
				if ($entities)
					foreach ($entities as $e){
						if ($e['original'] != $r['name']){
							$newEntities[] = $e;
							array_splice($ret, $i+$added, 0, array($e));
							$added++;
							echo "NEW E: ";
							print_json($e);
							print_json($r);
							$r['name'] = str_replace($e['original'], '', $r['name']);
							$r['original'] = str_replace($e['original'], '', $r['original']);
							$r['linted'] = $r['name'].(!empty($r['subtype']) ? ', '.$r['subtype'] : '');
						}
					}
			}

			if ($newEntities)
				$toCheck = $newEntities;
			else
				break;
			$ret = $checkRet;
		} while (true);
	}
	*/


	// DEBUG ONLY!!!
	/*
	$stop = 0
		? 'Basch'
		: false;

	if ($stop && !empty($options['groups']) && ($stop ? stripos($oval, $stop) !== false : stripos($oval, 'contratista:') !== false)){
		echo htmlentities($oval).'<br><br>';
		print_json(array_values($ret));
		echo '<br><br>';
		if ($stop)
			die();
	}
	* */
	return $ret ? array_values($ret) : null;
}


function insertget_precept($p){

	$pid = get_var('SELECT id FROM precepts WHERE bulletin_id = %s AND issuing_id = %s AND title = %s AND text = %s', array($p['bulletin_id'], $p['issuing_id'], $p['title'], $p['text']));

	if ($pid){
		if (IS_CLI)
			print_log('found precept "'.(!empty($p['title']) ? $p['title'] : $p['text']).'"', array('color' => 'grey'));
		return $pid;
	}
	if (IS_CLI)
		print_log('inserting precept "'.preg_replace("/\n+/", '', mb_substr(!empty($p['title']) ? $p['title'] : $p['text'], 0, 50)).'[...]"', array('color' => 'grey'));
	return insert('precepts', $p);
}


function get_entity_pattern($schema, $allowEntityPrefix = false, &$companyPattern = null, $strict = true, &$exceptPatterns = ''){
	// TODO: add cache!

	$countrySchema = strpos($schema, '/') ? get_schema($schema) : $schema;
	if (!empty($countrySchema->vocabulary) && !empty($countrySchema->vocabulary->legalEntityTypes)){
		$patterns = array();
		$companyPattern = $countrySchema->vocabulary->legalEntityName->pattern;

		// build except pattern
		$exceptPatterns = array();
		if (!empty($countrySchema->vocabulary->legalEntityName->exception))
			$exceptPatterns = $countrySchema->vocabulary->legalEntityName->exception;
		if (!is_array($exceptPatterns))
			$exceptPatterns = array($exceptPatterns);
		$exceptPatterns = '(?!'.implode('|', $exceptPatterns).')';

		$and = '(?:'.$countrySchema->vocabulary->basicVocabulary->and.'\s+)*';

		foreach ($countrySchema->vocabulary->legalEntityTypes as $formatId => $legalEntityConfig){
			//if ($formatId != 'SL')
			//	continue;

			// escape_patterns

			$strictPattern = str_replace('{legalEntityPattern}', ($allowEntityPrefix ? $allowEntityPrefix : '').$and.$exceptPatterns.'(\b'.$companyPattern.'\b),?\s*', ($legalEntityConfig->strictPatterns));

			$patterns = array_merge($patterns, $strictPattern);

			if (!empty($legalEntityConfig->caseInsensitivePatterns)){
				$caseInsensitivePattern = str_replace('{legalEntityPattern}', ($allowEntityPrefix ? $allowEntityPrefix : '').$and.$exceptPatterns.'(\b'.$companyPattern.'\b),?\s*', ($legalEntityConfig->caseInsensitivePatterns));

				$patterns = array_merge($patterns, $caseInsensitivePattern);
			}
		}
		if (!$strict)
			$patterns[] = ($allowEntityPrefix ? $allowEntityPrefix : '').$and.$exceptPatterns.'(\b'.$companyPattern.'\b)';

		foreach ($patterns as &$p)
			$p = '\b'.$p.'\b';
		unset($p);
		$patterns = implode('|', $patterns);

		//$companyPattern = "(lotes?\s*([0-9\.,y]+\s*)+)*";
		//$leftLimit = '\.:;~,\)\(\]\[';


		//$companyPattern = .'\b('.$companyPattern.')\b,?\s+\b('.$patterns.')\b';
		return $patterns;
	}
	die("MISSING legalEntityTypes voc");
	//return '[\s\pL-_0-9\.]+'; // default
}


function related($args){
	return 'data-smap-related="'.esc_json($args).'"';
}

function print_entities($ids){
	$str = '';
	foreach (is_array($ids) ? $ids : array($ids) as $i => $id)
		$str .= ($i ? ', ' : '').'<a href="'.get_entity_url($id).'"><i class="fa fa-'.get_entity_icon($id).'"></i> '.get_entity_title($id).'</a>';
	return $str;
}

function get_amount($amount_id){
	return get_row('SELECT * FROM amounts WHERE id = %s', $amount_id);
}

function print_amount($amount, $unit = null, $unit_in = 'EUR'){
	if ($amount === null)
		return 'N/D';

	$a = $unit ? array(
		'value' => $amount,
		'unit' => $unit,
	) : get_amount($amount);

	if (!$a)
		return '';

	$a = convert_currency($a['value'], $a['unit'], $unit_in);

	return number_format((isset($a['value']) ? $a['value'] : $a['original_value'])/100, 2).' '.(isset($a['unit']) ? $a['unit'] : $a['original_unit']);
}

function print_entity_stat($c, $entity, &$details, $relation = 'related'){
	if ($v = get_col('SELECT '.(!empty($c['type']) ? $c['type'] : 'target_id').' FROM statuses WHERE '.(!empty($c['type']) && $c['type'] == 'related_id' ? 'target_id' : 'related_id').' = %s AND type = "'.$c['_type'].'" AND action IN ( '.(!empty($c['_action']) ? '"'.$c['_action'].'"' : '"new", "start", "update"').' ) ORDER BY id DESC', $entity['id'])){
		$isAmount = !empty($c['type']) && $c['type'] == 'amount';
		$details[$c['_type'].'_'.(!empty($c['_action']) ? $c['_action'] : 'new')] = array(
			'label' => $c['label'],
			'value' => $isAmount ? get_amount($v) : get_entities_by_id($v),
			'html' => $isAmount ? print_amount(array_pop($v)) : print_entities($v),
		) + $c;
	}
}


function convert_entities($str){
	//$str = preg_replace_callback('#https?://[-a-zA-Z0-9@:%_\+.~\#?&//=]{2,256}\.[a-z]{2,4}\b(?:\/[-a-zA-Z0-9@:%_\+.~\#?&//=]*)?#si',

	$str = preg_replace_callback('#https?://[-a-zA-Z0-9@:%_\+.~\#?&//=]{2,256}\.[a-z]{2,4}\b(?:\/[-a-zA-Z0-9@:%_\+\.~\#\?&//=]*)?#si',function($m){
		return preg_match('#'.preg_quote(BASE_URL, '#').'#iu', $m[0])
			? '<a class="invert-url invert-url-internal" href="'.esc_attr(htmlentities($m[0])).'">'.$m[0].'</a>'
			: '<a class="invert-url invert-url-external" href="'.esc_attr(anonymize(htmlentities($m[0]))).'" target="_blank">'.$m[0].'</a>';
	}, html_entity_decode($str));

	return $str;
}


function get_entity_by_id($id){
	return get_row('SELECT * FROM entities WHERE id = %s LIMIT 1', array($id));
}

function get_entities_by_id($ids){
	$str = array();
	foreach (is_array($ids) ? $ids : array($ids) as $id)
		$str[] = prepare('%s', $id);
	return query('SELECT * FROM entities WHERE id IN ( '.implode(', ', $str).' )');
}

function get_entity_by_slug($slug, $type = null, $country = null){
	$slug = trim($slug);
	if ($slug == '')
		return false;

	$and = '';
	if ($type)
		$and .= prepare('type = %s AND ', $type);

	if ($country)
		$and .= prepare('country = %s AND ', strtoupper($country));

	$e = get_row('SELECT * FROM entities WHERE '.$and.'slug = %s LIMIT 1', $slug);
	return $e;
}

function get_entity_title($e, $short = false, $forTitle = false){
	if (is_numeric($e))
		$e = get_entity_by_id($e);
	return ($e['type'] == 'person' ? mb_strtoupper($e['name']).(!empty($e['first_name']) ? ', '.$e['first_name'] : '') : $e['name']).(!$short && !empty($e['subtype']) ? ($forTitle ? ', '.$e['subtype'] : '<span class="entity-subtype"><span class="hidden">, </span>'.$e['subtype'].'</span>') : '');
}

function get_entity_title_html($e, $opts = array()){ // /$icon = false, $maxLength = false){
	$opts += array(
		'icon' => false,
		'limit' => false,
		'append_country' => false,
		'class' => '',
		'icon_class' => '',
	);
	$title = get_entity_title($e);
	if ($opts['limit'] && mb_strlen($title) > $opts['limit']){
		while (mb_substr($title, $opts['limit'] - 5, 1) != ' ')
			$opts['limit']--;
		$title = mb_substr($title, 0, $opts['limit'] - 5).'...';
	}
	if ($opts['append_country'])
		$title .= ' ('.get_schema($e['country'])->name.')';
		
	if (is_dev() && !empty($_GET['stick'])){
		$stick = ' data-tippy-interactive="1" data-tippy-trigger="click" onclick="return false"';
		$url = '#';
	
	} else {
		$stick = '';
		$url = get_entity_url($e);
	}
	
	return '<a href="'.$url.'"'.$stick.' class="inline-entity '.$opts['class'].'" '.related(array(
		'entity_id' => is_array($e) ? $e['id'] : $e,
	)).'>'.($opts['icon'] ? '<i class="fa fa-'.get_entity_icon($e).' '.$opts['icon_class'].'"></i> ' : '').$title.'</a>';
}

function get_entity_icon($r){
	if (is_numeric($r))
		$r = get_entity_by_id($r);
	switch (is_array($r) ? $r['type'] : $r){
		case 'person':
			return 'user-circle';
		case 'institution':
			return 'university';
		case 'company':
			return 'industry';
		default:
			return 'question-circle';
	}
}

function get_entity_uri($r, $keep_entity_id = false){
	return get_uri_from_url(get_entity_url($r, $keep_entity_id));
}

function get_entity_url($r, $keep_entity_id = false){
	if (is_numeric($r))
		$r = get_entity_by_id($r);
	
	if ($keep_entity_id)
		return add_lang(BASE_URL.'entity/'.$r['id']);
	
	return add_lang(BASE_URL.strtolower($r['country']).'/'.$r['type'].'/'.$r['slug']);
}


function get_previous_entities($entityId){
	return query('SELECT e.id, e.country, e.name, e.first_name, e.type, e.subtype, e.slug
		FROM statuses AS s
		LEFT JOIN entities AS e ON s.related_id = e.id
		WHERE s.target_id = %s AND s.type = "name" AND s.action = "update"
		ORDER BY s.id DESC
	', $entityId);
}

function get_next_entities($entityId){
	return query('SELECT e.id, e.country, e.name, e.first_name, e.type, e.subtype, e.slug
		FROM statuses AS s
		LEFT JOIN entities AS e ON s.target_id = e.id
		WHERE s.related_id = %s AND s.type = "name" AND s.action = "update"
		ORDER BY s.id DESC
	', $entityId);
}


function get_other_entities($entityId){
	$ids = array(intval($entityId));
	foreach (get_previous_entities($entityId) as $e)
		$ids[] = intval($e['id']);
	foreach (get_next_entities($entityId) as $e)
		$ids[] = intval($e['id']);
	return $ids;
}

function get_entity_patterns($e){
	$patterns = array();

	$name = array();
	foreach (explode(' ', $e['name']) as $cname)
		$name[] = preg_quote($cname, '#');

	if ($e['type'] == 'person'){
		$first_name = array();
		if (!empty($e['first_name']))
			foreach (explode(' ', $e['first_name']) as $cfirst_name)
				$first_name[] = preg_quote($cfirst_name, '#');
		$patterns[] = '#'.implode('\s+', $name).'\s*,?\s+'.implode('\s+', $first_name).'#ius';
		$patterns[] = '#'.implode('\s+', $first_name).'\s*,?\s+'.implode('\s+', $name).'#ius';

	} else {
		$name = implode('\s+', $name);
		$c = get_schema($e['country']);

		$pats = array();
		if (isset($e['subtype'], $c->vocabulary->legalEntityTypes->{$e['subtype']})){
			if (!empty($c->vocabulary->legalEntityTypes->{$e['subtype']}->strictPatterns)){
				foreach ($c->vocabulary->legalEntityTypes->{$e['subtype']}->strictPatterns as $selector)
					$pats[] = str_replace('{legalEntityPattern}', '(\b'.$name.'\b),?\s*', $selector);
			}
			if (!empty($c->vocabulary->legalEntityTypes->{$e['subtype']}->caseInsensitivePatterns)){
				foreach ($c->vocabulary->legalEntityTypes->{$e['subtype']}->caseInsensitivePatterns as $selector)
					$pats[] = str_replace('{legalEntityPattern}', '(\b'.$name.'\b),?\s*', $selector);
			}
		}
		$patterns[] = '#('.($pats ? implode('|', $pats).'|' : '').'\b'.$name.'\b)#ius';
	}
	return $patterns;
}


function split_by_groups($options, $applyVal, $smapDebug = false, $recursion = false){
	$options = (array) $options;
	$wrapGroups = array();
	if ($smapDebug && !empty($options['wrapGroups'])) echo 'CUTTING: '.$applyVal.'<br>(WRAPGROUP PATTERN '.implode('|', $options['wrapGroups']).')<br><br>';

	if (!empty($options['outerSelector']) && !empty($options['wrapGroups'])
		&& ($cwrapGroups = preg_split('#('.escape_patterns(implode('|', $options['wrapGroups'])).')#ius', $applyVal, -1, PREG_SPLIT_DELIM_CAPTURE))
		&& count($cwrapGroups) > 4
		&& preg_match_all('#'.$options['outerSelector'].'#iums', $cwrapGroups[2])
		&& preg_match_all('#'.$options['outerSelector'].'#iums', $cwrapGroups[4])
		//&& preg_match('#.*Importe\s*[a-z]+\s*:?\s*[0-9]+.*#ius', $wrapGroups[4])
	){
		if ($smapDebug) echo '#1 ('.implode('|', $options['wrapGroups']).')<br>';
		$i = 0;
		foreach ($cwrapGroups as $c){
			if ($i){
				if ($i % 2)
					$group = array(
						'group' => $c
					);
				else {
					if (empty($options['outerSelector']) || preg_match('#'.$options['outerSelector'].'#iums', $c, $matches)){
						$group['value'] = empty($options['outerSelector']) ? $c : $matches[1];
						$wrapGroups[] = $group;
					}
				}
			}
			$i++;
		}

	} else if (!empty($options['outerSelector'])){
		if ($smapDebug) echo '#2 ('.$options['outerSelector'].')<br>';
		if (preg_match_all('#'.$options['outerSelector'].'#iums', $applyVal, $fmatches))
			foreach ($fmatches[1] as $m){
				$m = preg_replace('#[;:\.\s]*$#', '', $m);
				$m = preg_replace('#^[;:\.\s]*#', '', $m);
				if ($m != '')
					$wrapGroups[] = $m;
			}
		else if ($recursion)
			$wrapGroups[] = $applyVal;


		$cwrapGroups = $wrapGroups;
		$wrapGroups = array();
		if (!$recursion){
			if ($smapDebug) echo '#2.1<br>';
			foreach ($cwrapGroups as $ccwrapGroups)
				$wrapGroups = array_merge($wrapGroups, split_by_groups($options, $ccwrapGroups, $smapDebug, true));
		} else {
			if ($smapDebug) echo '#2.2<br>';
			foreach ($cwrapGroups as $ccwrapGroups)
				$wrapGroups[] = array('value' => $ccwrapGroups);
		}

	} else if (!empty($applyVal)){
		if ($smapDebug) echo '#3<br>';

		$wrapGroups[] = array(
			'value' => $applyVal
		);
	}
	if ($smapDebug)
		print_json($wrapGroups);
	return $wrapGroups;
}


function lint_group_label($tr, $val){
	$tr = (array) $tr;

	// parse group delimiter
	if (!empty($tr['groupsDelimiters'])){
		foreach ($tr['groupsDelimiters'] as $s){
			$ccgroups = array();
			if ($split = preg_split('#'.$s.'#ius', $val, -1, PREG_SPLIT_NO_EMPTY)){
				foreach ($split as $s2)
					if (trim($s2) != '')
						$ccgroups[] = trim($s2);
			}
			if ($ccgroups)
				$val = implode(',', $ccgroups);
		}
	}
	if (!is_array($val))
		$val = explode(',', $val);
	return $val;
}


/*
add_filter('entity_summary', 'entity_summary_stats', 200, 3);
function entity_summary_stats($details, $entity, $context){
	$labels = get_status_labels();

	// TODO: add total fundings
	// TODO: grep "Resultante suscrito" -> update capital

	if ($context == 'sheet-top'){

		// became
		
		$entities = query('SELECT e.country, e.name, e.first_name, e.type, e.subtype, e.slug
			FROM statuses AS s
			LEFT JOIN entities AS e ON s.target_id = e.id
			WHERE s.related_id = %s AND s.type = "name" AND s.action = "update"
			ORDER BY s.id DESC
		', $entity['id']);

		if ($entities)
			$details['new_names'] = array(
				'label' => 'Became',
				'html' => print_entities($entities),
			);

		// previous names
		
		$entities = get_previous_entities($entity['id']);

		if ($entities)
			$details['old_names'] = array(
				'label' => 'Old names',
				'html' => print_entities($entities),
			);
	}
	
	if (in_array($context, array('list', 'sheet-sidebar'))){

		// date founded

		$date = get_var('SELECT b.date AS date
			FROM statuses AS s
			LEFT JOIN precepts AS p ON s.precept_id = p.id
			LEFT JOIN bulletins AS b ON p.bulletin_id = b.id
			WHERE related_id = %s AND type = "capital" AND action IN ( "new" )
		', $entity['id']);

		if ($date)
			$details['founded'] = array(
				'label' => 'Founded',
				'value' => $date,
				'html' => '<span title="'.esc_attr(date_i18n('M jS, Y', strtotime($date))).'">'.date_i18n('Y', strtotime($date)).' ('.time_diff($date).')</span>',
			);
			
		// location

		$location = get_var('SELECT note FROM statuses WHERE related_id = %s AND type = "location" AND action = "new" ORDER BY id DESC LIMIT 1', $entity['id']);

		$locationObj = $location ? apply_filters('location_lint', null, $location, $entity['country']) : null;

		if ($location)
			$details['location'] = array(
				'label' => 'Location',
				'value' => $locationObj ? $locationObj : null,
				'html' => '<i>'.($locationObj ? get_location_label($locationObj, 'sheet') : $location).'</i>',
			);

	}
	
	if ($context == 'sheet-top'){

		// estimated capital

		$in = get_row('SELECT SUM(a.value) AS amount, a.unit FROM statuses AS s LEFT JOIN amounts AS a ON s.amount = a.id WHERE s.related_id = %s AND s.type = "capital" AND s.action IN ( "new", "increase" )', $entity['id']);
		
		$out = get_row('SELECT SUM(a.value) AS amount, a.unit FROM statuses AS s LEFT JOIN amounts AS a ON s.amount = a.id WHERE s.related_id = %s AND s.type = "capital" AND s.action IN ( "decrease" )', $entity['id']);

		$diff = ($in ? $in['amount'] : 0) - ($out ? $out['amount'] : 0);
		
		if ($diff > 0)
			$details['estimated_capital'] = array(
				'label' => 'Estimated capital',
				'html' => print_amount($diff, $in ? $in['unit'] : $out['unit']),
				'class' => 'mini',
			);

		// creation capital

		print_entity_stat(array(
			'_type' => 'capital',
			'_action' => 'new',
			'type' => 'amount',
			'label' => 'Initial capital',
			'class' => 'mini',
		), $entity, $details);

		// sum funded

		$in = get_row('SELECT SUM(a.value) AS amount, SUM(a.original_value) AS originalAmount, a.unit, a.original_unit FROM statuses AS s LEFT JOIN amounts AS a ON s.amount = a.id WHERE s.related_id = %s AND s.type = "fund" AND s.action IN ( "new" )', $entity['id']);
		if ($in && !empty($in['amount']))
			$details['fund'] = array(
				'label' => 'Total funded',
				'icon' => $labels->fund->new->icon,
				'html' => print_amount($in['originalAmount'], $in['original_unit']),
				'class' => 'mini',
			) + $in;
			
		// sum funding

		$in = get_row('SELECT SUM(a.value) AS amount, SUM(a.original_value) AS originalAmount, a.unit, a.original_unit FROM statuses AS s LEFT JOIN amounts AS a ON s.amount = a.id LEFT JOIN precepts AS p ON s.precept_id = p.id WHERE p.issuing_id = %s AND s.type = "fund" AND s.action IN ( "new" )', $entity['id']);
		if ($in && !empty($in['amount']))
			$details['funding'] = array(
				'label' => 'Total funding',
				'icon' => $labels->fund->new->issuing->icon,
				'html' => print_amount($in['originalAmount'], $in['original_unit']),
				'class' => 'mini',
			) + $in;
			
		// object

		$object = get_var('SELECT note FROM statuses WHERE related_id = %s AND type = "object" AND action = "new" ORDER BY id DESC LIMIT 1', $entity['id']);
		if ($object)
			$details['object'] = array(
				'label' => 'Corporate purpose',
				'html' => $object,
			);

		print_entity_stat(array(
			'type' => 'related_id',
			'_type' => 'owner',
			'label' => 'Currently owning',
		), $entity, $details);

		print_entity_stat(array(
			'type' => 'related_id',
			'_type' => 'owner',
			'_action' => 'end',
			'label' => 'Has owned',
		), $entity, $details);

		print_entity_stat(array(
			'_type' => 'owner',
			'label' => 'Owned by',
		), $entity, $details);

		print_entity_stat(array(
			'type' => 'related_id',
			'_type' => 'administrator',
			'label' => 'Administrates',
		), $entity, $details);

		print_entity_stat(array(
			'type' => 'related_id',
			'_type' => 'administrator',
			'_action' => 'end',
			'label' => 'Has administrated',
		), $entity, $details);

		print_entity_stat(array(
			'_type' => 'administrator',
			'label' => 'Administrated by',
		), $entity, $details);

		print_entity_stat(array(
			'_type' => 'administrator',
			'_action' => 'end',
			'label' => 'Was administrated by',
		), $entity, $details);

		print_entity_stat(array(
			'_type' => 'auditor',
			'label' => 'Auditors',
		), $entity, $details);
	}
	
	return $details;
}
*/


function get_company_label($filter = false, $key = 'plural'){
	global $smap;
	$str = array();
	if (empty($smap['filters']['etype']))
		return 'results';
		
	$types = get_entity_types();
	foreach (explode(' ', $smap['filters']['etype']) as $cetype){
		$etype = explode('/', $cetype);
		switch ($etype[0]){
			case 'person':
			case 'institution':
				$label = $types[$etype[0]][$key];
				$str[] = $filter ? ucfirst($label) : $label;
				break;
			case 'company':
				$label = $types[$etype[0]][$key];
				if (count($etype) > 2 && ($s = get_schema(strtoupper($etype[1].'/'.$etype[2])))){
					$label = get_subtype_prop($s->id, $cetype, $filter ? 'shortName' : 'name');
					if ($filter)
						$label .= ' ('.$s->name.')';
					else
						$label = $s->adjective.' '.$label;
				}
				$str[] = $filter ? ucfirst($label) : $label;
				break;
		}
	}
	return implode(', ', $str);
}

function get_subtype_prop($country, $subtype, $prop){
	if (!$country)
		$subtype = 'company/'.$country.'/'.$subtype;
	$country = explode('/', $subtype);
	$subtype = strtoupper($country[2]);
	$country = $country[1];
	if (($s = get_schema($country))
		&& isset($s->vocabulary, $s->vocabulary->legalEntityTypes, $s->vocabulary->legalEntityTypes->{$subtype}, $s->vocabulary->legalEntityTypes->{$subtype}->{$prop}))
		return $s->vocabulary->legalEntityTypes->{$subtype}->{$prop};
	return 'N/D';
}

function query_any($args, &$left){
	$results = array();
	$args += array(
		'include' => 'any',
	);
	if (!is_array($args['include']))
		$args['include'] = array($args['include']);
	$is_any = in_array('any', $args['include']);
	$query = explode(' ', remove_accents($args['q']));
	
	// query bulletins
	if ($is_any || in_array('bulletin', $args['include'])){
		foreach (get_schemas() as $file){
			$schema = get_schema($file);
			if ($schema && $schema->type == 'bulletin'){
				$schema_name = remove_accents($schema->name.' '.$schema->shortName);
				
				$has = true;
				foreach ($query as $q)
					if (!preg_match('#'.preg_quote($q, '#').'#iu', $schema_name)){
						$has = false;
						break;
					}
				
				if (!$has)
					continue;
					
				$results[] = array(
					'schema' => $schema->id,
					'id' => 'bulletin:'.$schema->id,
					'name' => $schema->name.' ('.$schema->shortName.')',
					'type' => 'bulletin',
					'country' => !empty($schema->providerId) && ($p = get_provider_schema($schema->providerId)) ? ($p->country ? $p->country : $p->continent) : null,
				);
			}
		}
	}
	
	// query locations
	if ($is_any || in_array('location', $args['include']))
		$results = array_merge($results, query_locations($args, $left));
	
	
	// query entities
	if ($is_any || in_array('entity', $args['include']))
		$results = array_merge($results, query_entities($args, $left));
	
	return $results;
}

function query_entities($args, &$left = null){
	$args += array(
		'q' => '',
		'etype' => null,
		'esubtype' => null,
		'after_id' => null,
		'country' => null,
		'locations' => null,
		'etypes' => array(), // array of person, institution, company or company/es/sl
		'misc' => null, // [buggy]
		'limit' => 0,
		'count' => false,
		'use_views' => DATABASE_USE_VIEWS,
	);
	$join = $where = $groupby = array();
	
	$table = $args['use_views'] ? 'entities_by_name' : 'entities';
	$table_count = 'entities';

	if (!empty($args['q']) && trim($args['q']) != ''){
		$query = sanitize_keywords($args['q']);

		foreach (explode(' ', $query) as $q)
			$where[] = prepare('e.keywords LIKE %s', array('%'.$q.'%'));
	}

	if ($args['etype'])
		$where[] = prepare('e.type = %s', $args['etype']);
	if ($args['esubtype'])
		$where[] = prepare('e.subtype = %s', strtoupper($args['esubtype']));

	if ($args['country'])
		$where[] = prepare('e.country = %s', strtoupper($args['country']));

	if ($args['etypes']){
		$subwhere = array();
		foreach ($args['etypes'] as $etype){
			$etype = explode('/', $etype);
			if (count($etype) < 3)
				$subwhere[] = prepare('e.type = %s', $etype[0]);
			else
				$subwhere[] = prepare('e.country = %s AND e.type = %s AND e.subtype = %s', array(strtoupper($etype[1]), $etype[0], strtoupper($etype[2])));
		}
		$where[] = '( '.implode(' OR ', $subwhere).' )';
	}

	if ($args['locations']){
		// join with statuses on location type and filter where
		
		$cwhere = array();
		foreach ($args['locations'] as $l){
		
			if (!empty($l['country']))
				$where[] = prepare('e.country = %s', strtoupper($l['country']));
			
			else {
				
				foreach ($l as $k => $v)
					$cwhere[] = prepare('l.'.$k.' = %s', $v);
			}
		}
		if ($cwhere){
			$join[] = 'LEFT JOIN statuses AS s ON e.id = s.related_id AND s.type = "location"';
			$join[] = 'LEFT JOIN locations AS l ON s.note = l.id';
			$where[] = '( '.implode(' ) OR ( ', $cwhere).' )';
		}
		$groupby[] = 'e.id';
	}

	if (!empty($args['misc']) && $args['misc'] == 'buggy')
		$where[] = '(( e.type = "person" AND LENGTH(e.name) > 40 OR LENGTH(e.first_name) > 30) OR LENGTH(e.name) > 80 )';
		
	// build queries
	$q = 'FROM '.$table.' AS e '.($join ? implode(' ', $join).' ' : '');
	$count_q = 'FROM '.$table_count.' AS e '.($join ? implode(' ', $join).' ' : '');
		
	// allow infinite scroll
	$limit = $args['limit'];
	if ($args['after_id']){
		$limit = 2 * $limit;
		if ($after_value = get_var('SELECT name FROM '.$table.' WHERE id = %s', $args['after_id']))
			$where[] = prepare('name >= %s', $after_value);
	}
	$count_where = $where;
	
	if ($where)
		$q .= 'WHERE '.implode(' AND ', $where);
	if ($count_where)
		$count_q .= 'WHERE '.implode(' AND ', $count_where);
	if ($groupby){
		$q .= ' GROUP BY '.implode(', ', $groupby);
		//$count_q .= ' GROUP BY '.implode(', ', $groupby);
	}
	
	if (!empty($args['count']))
		return get_var('SELECT COUNT(e.id) '.$q);
	
	$order = $args['use_views'] ? '' : 'ORDER BY e.name ASC, e.first_name ASC ';
	
	$q = 'SELECT e.id, e.country, e.type, e.subtype, e.name, e.first_name, e.slug '.$q.' '.$order.($limit ? ' LIMIT '.($limit+1) : '');
	
//	echo $q.'<br>';
	$res = query($q);
	
	$strip_count = 0;
	if ($args['after_id']){
		$strip_count = null;
		$i = 0;
		foreach ($res as $r)
			if ($r['id'] == $args['after_id']){
				$strip_count = $i;
				break;
			} else
				$i++;
		if ($strip_count !== null){
			$strip_count++;
			array_splice($res, 0, $strip_count);
		} else
			$res = array();
		if ($strip_count === null)
			$strip_count = 0;
	}
	
	if (!$args['limit'])
		$left = 0;
	else if (count($res) > $args['limit']){
		array_splice($res, $args['limit']);
		$left = get_var('SELECT COUNT(e.id) '.$count_q) - $strip_count - $args['limit'];
	}
	return $res;
}

function get_entity_count($type, $subtype = null){
	if ($subtype)
		return get_var('SELECT COUNT(*) FROM entities WHERE type = %s AND subtype = %s', array($type, $subtype));
	return get_var('SELECT COUNT(*) FROM entities WHERE type = %s', $type);
}


function get_results(){
	global $smap;
	$ret = array('items' => array(), 'count' => 0, 'total' => 0,'left' => 0);
				
	// generate search results/listing
	if (!empty($smap['filters']['q']) || has_filter()){
		$etype = !empty($smap['filters']['etype']) ? explode(' ', $smap['filters']['etype']) : null;
		$loc = null;
		if (!empty($smap['filters']['loc'])){
			$loc = array();
			foreach (explode(' ', $smap['filters']['loc']) as $l){
				$l = explode('/', $l);
				$cloc = array(
					'country' => array_shift($l),
					'state' => array_shift($l),
					'county' => array_shift($l),
					'city' => array_shift($l),
				);
				if ($cloc = get_location_filter_array($cloc))
					$loc[] = $cloc;
				else
					die_error('Location not found');
			}
		}
			
		$ret['query'] = $smap['query'] = array(
			'q' => !empty($smap['filters']['q']) ? $smap['filters']['q'] : null,
			'etypes' => $etype,
			'locations' => $loc,
			'limit' => min(MAX_RESULTS_COUNT, !empty($smap['filters']['limit']) ? intval($smap['filters']['limit']) : DEFAULT_RESULTS_COUNT),
			'after_id' => !empty($smap['query']['after_id']) ? $smap['query']['after_id'] : null,
			'misc' => !empty($smap['filters']['misc']) ? $smap['filters']['misc'] : null,
			'loaded_count' => !empty($smap['query']['loaded_count']) ? $smap['query']['loaded_count'] : null,
		);
		$ret['items'] = query_entities($ret['query'], $ret['left']);
		$ret['count'] = count($ret['items']);
		$ret['total'] = $smap['query']['loaded_count'] + $ret['count'] + $ret['left'];
		
		// fill entities
		foreach ($ret['items'] as &$entity){
			$entity['icon'] = get_entity_icon($entity);
			$entity['label'] = get_entity_title($entity, false, true);
			$entity['url'] = get_entity_url($entity);
		}
		unset($entity);
	}
	
	$smap += array('results' => $ret);
	return $ret;
}

function get_results_count_label($count, $total){
	//if ($count != $total)
		//return sprintf(_('%s results out of %s'), number_format($count, 0), $total);
	//else
		return sprintf(_('%s results'), number_format($total, 0));
}


add_action('body_after', 'entity_popup_body_open');
function entity_popup_body_open(){
	?>
	<div id="entity-popup" class="inline-popup entity-popup"><div class="inline-popup-inner"></div></div>
	<?php
}

function get_entity_actions_html(&$entity, $context){
	$entity['actions'] = array();
	$entity = apply_filters('entity_actions', $entity, $context);
	return $entity['actions'];
}

add_filter('inline_popup', 'entities_inline_popup', 0, 2);
function entities_inline_popup($ret, $args){
	if (!empty($args['entity_id']) && ($entity = get_entity_by_id($args['entity_id']))){
		$entity['summary'] = get_entity_summary($entity, 'popup');
		return array('success' => true, 'html' => get_template('parts/entity_popup', array('entity' => $entity)));
	}
	return $ret;
}

add_filter('entity_actions', 'entity_actions_entity_cross_search', 100, 2);
function entity_actions_entity_cross_search($entity, $context){
	if (!is_logged())
		return $entity;
		
	$entity['actions']['entity_cross_search'] = array(
		'label' => 'Show statuses in common with..',
		'icon' => 'random',
	);
	return $entity;
}

function insertget_entity($e, $precept_id = null){
	if (empty($e['name']) || empty($e['type']) || empty($e['country']))
		return false;
		
	// TODO: add lock wait to not get duplicated entities
	
	$initial_name = $e['name'].(!empty($e['first_name']) ? ' '.$e['first_name'] : '');
		
	$e['name'] = beautify_name($e['name'], $e['country']);
	
	// session cache
	static $cache = array();
	$key = mb_strtolower($e['country'].'/'.$e['name'].(isset($e['first_name']) ? ' '.$e['first_name'] : ''));
	
	if (isset($cache[$key]))
		return $cache[$key];
		
	$e = sanitize_person($e);
	if ($e['country'])
		$e['country'] = strtoupper($e['country']);
	
	if (!empty($e['first_name']))
		$e['first_name'] = beautify_name($e['first_name'], $e['country']);
		
	$subtype = !empty($e['subtype']) ? prepare(' AND subtype = %s', $e['subtype']) : '';
	$first_name = !empty($e['first_name']) ? prepare(' AND first_name = %s', $e['first_name']) : ' AND first_name IS NULL';
	
	$q = prepare('SELECT id FROM entities WHERE country = %s AND type = %s', array($e['country'], $e['type']));
	
	$e['normalized'] = normalize_name($e['name'], $e['country']);
	$qname = prepare(' AND normalized = %s', $e['normalized']);
	
	// really query 
	$eid = get_var($q.$subtype.$qname.$first_name);

	$name = $e['name'].(!empty($e['first_name']) ? ' '.$e['first_name'] : '');
	
	if (!$eid){
		$keywords = $name;
		if (!empty($e['subtype'])){
			
			// add the subtype's name and shortName to the keywords
			
			$keywords .= ' '.get_subtype_prop($e['country'], $e['type'].'/'.$e['country'].'/'.$e['subtype'], 'name');
			$keywords .= ' '.preg_replace('#[\.]#', '', get_subtype_prop($e['country'], $e['type'].'/'.$e['country'].'/'.$e['subtype'], 'shortName'));
		}
	
		$eid = insert('entities', $e + array(
			'fetched' => date('Y-m-d H:i:s'),
			'keywords' => sanitize_keywords($keywords),
			'slug' => generate_slug('entities', 'slug', $name, 200),
		));
		
		if (IS_CLI)
			print_log('inserted '.$e['type'].' "'.$name.'"', array('color' => 'grey'));
	
	} else if (IS_CLI)
		print_log('found '.$e['type'].' '.$name, array('color' => 'grey'));
	
	if ($precept_id)
		set_used_name_for($eid, $initial_name, $precept_id);
		
	// store to cache
	$cache[$key] = $eid;
	return $eid;
}

function set_used_name_for($entity_id, $initial_name, $precept_id){
	insert('entity_uses_name', array(
		'entity_id' => $entity_id,
		'used_name' => $initial_name,
		'precept_id' => $precept_id,
	));
}

function print_entity_summary($entity, $summary){
	
	$details = array();
	$mini = false;
	foreach ($summary as $id => $e){
		if (!empty($e['class']) && preg_match('#.*\bmini\b.*#', $e['class'])){
			if (!$mini){
				$mini = true;
				$details[] = '<div class="mini-wrap">';
			}
		} else if ($mini){
			$details[] = '</div>';
			$mini = false;
		}
			
		$details[] = '<div class="entity-sheet-detail entity-sheet-detail-'.$id.' live-wrap '.(!empty($e['class']) ? $e['class'] : '').'"><span class="entity-detail-label entity-stat-left">'.$e['label'].':</span><span class="entity-detail-body">'.$e['html'].'</span></div>';
	}
	if ($mini)
		$details[] = '</div>';
	echo '<div class="entity-sheet-details-wrap entity-details-wrap"><div class="entity-details entity-sheet-details">'.implode('', $details).'</div></div>';
}

function get_result_url($e){
	if ($e['type'] == 'bulletin')
		return url(array(
			'schema' => $e['schema'],
		), 'schema');
	
	return get_entity_url($e);
}

function get_result_icon($e){
	$modes = get_modes();
	if ($e['type'] == 'bulletin')
		return $modes['schema']['icon'];
	
	return get_entity_icon($e);
}

function get_result_title($e, $short = false, $forTitle = false){
	$modes = get_modes();
	if ($e['type'] == 'bulletin')
		return $e['name'];
	
	return get_entity_title($e, $short, $forTitle);
}
