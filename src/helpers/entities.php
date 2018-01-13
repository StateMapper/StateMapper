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
 

if (!defined('BASE_PATH'))
	die();


function parse_entities($options, $oval, $schema, $extraEnt = array(), $doubleChecking = false, $smapDebug = false){
	$options = (array) $options;
	$starting = !empty($options['starting']);

	$countrySchema = get_country_schema($schema);
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

				if ($smapDebug){
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

	$countrySchema = strpos($schema, '/') ? get_country_schema($schema) : $schema;
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


function query_statuses($query){
	$statuses = array();

	$query += array(
		'type' => null,
		'action' => null,
		'id' => null, // entity ID
		'ids' => array(),
		'date' => null,
		'include_other_names' => true,
		'target' => null,
		'related' => null,
		'issuing' => null,
	);

	if ((empty($query['id']) && empty($query['ids'])) || ($query['id'] && !is_numeric($query['id'])))
		return array();

	if (!empty($query['ids']))
		foreach ($query['ids'] as $id)
			if (!is_numeric($id))
				return array();

	if ($query['include_other_names'])
		$query['ids'] = array_merge($query['ids'], get_other_entities($query['id']));
	else if (!empty($query['id']))
		$query['ids'][] = $query['id'];
	if (empty($query['ids']))
		return array();

	$limit = 100;
	$where = '';

	$yearMode = !empty($query['date']) && is_numeric($query['date']);

	if (!empty($query['date'])){
		if ($yearMode) // year mode
			$where .= prepare(' AND YEAR(b.date) = %s', array($query['date']));
		else
			$where .= prepare(' AND b.date = %s', array($query['date']));
	}

	$join = '';
	$order = 'b.date DESC';

	if (!empty($query['type'])){
		$where .= prepare(' AND s.type = %s', array($query['type']));
		if ($query['type'] == 'capital'){
			$join .= 'LEFT JOIN amounts AS a ON s.amount = a.id ';
			$order = 'a.value DESC';
		}
	}
	$order .= ', e.name ASC, e.first_name ASC';

	if (!empty($query['action']))
		$where .= prepare(' AND s.action = %s', array($query['action']));

	if (empty($query['related']) && empty($query['issuing'])){

		$q = '
			SELECT s.id AS status_id, b.date AS date, b.external_id, b.bulletin_schema, p.title, p.text, p.bulletin_id,
			s.type AS _type, s.action AS _action, s.amount, s.contract_type_id, s.sector_id,
			s.target_id, s.related_id, p.issuing_id, p.id AS precept_id, s.note,
			e.name, e.first_name, e.type, e.subtype, e.country

			FROM precepts AS p
			LEFT JOIN statuses AS s ON p.id = s.precept_id
			LEFT JOIN bulletins AS b ON p.bulletin_id = b.id
			LEFT JOIN entities AS e ON p.issuing_id = e.id
			'.$join.'

			WHERE s.target_id IN ( '.implode(', ', $query['ids']).' )'.$where.'
			GROUP BY s.id
			ORDER BY '.$order.'
			LIMIT '.$limit.'

		';

		foreach (query($q) as $e)
			$statuses[$e['status_id']] = $e;
	}

	// what this entity adjudicated to
	if (empty($query['target'])){

		$q = '

			SELECT s.id AS status_id, b.date AS date, b.external_id, b.bulletin_schema,
			p.title, p.text, p.bulletin_id,
			s.type AS _type, s.action AS _action, s.amount, s.contract_type_id, s.sector_id,
			s.target_id, s.related_id, p.issuing_id, p.id AS precept_id, s.note,
			e.name, e.first_name, e.type, e.subtype, e.country

			FROM precepts AS p
			LEFT JOIN statuses AS s ON p.id = s.precept_id
			LEFT JOIN entities AS e ON s.target_id = e.id
			LEFT JOIN bulletins AS b ON p.bulletin_id = b.id
			'.$join.'

			WHERE s.related_id IN ( '.implode(', ', $query['ids']).' ) '.$where.'
			GROUP BY s.id
			ORDER BY '.$order.'
			LIMIT '.$limit.'

		';

		foreach (query($q) as $e)
			$statuses[$e['status_id']] = $e;
			
		$q = '

			SELECT s.id AS status_id, b.date AS date, b.external_id, b.bulletin_schema,
			p.title, p.text, p.bulletin_id,
			s.type AS _type, s.action AS _action, s.amount, s.contract_type_id, s.sector_id,
			s.target_id, s.related_id, p.issuing_id, p.id AS precept_id, s.note,
			e.name, e.first_name, e.type, e.subtype, e.country

			FROM precepts AS p
			LEFT JOIN statuses AS s ON p.id = s.precept_id
			LEFT JOIN entities AS e ON s.target_id = e.id
			LEFT JOIN bulletins AS b ON p.bulletin_id = b.id
			'.$join.'

			WHERE p.issuing_id IN ( '.implode(', ', $query['ids']).' ) '.$where.'
			GROUP BY s.id
			ORDER BY '.$order.'
			LIMIT '.$limit.'

		';

		foreach (query($q) as $e)
			$statuses[$e['status_id']] = $e;
	}
	return $statuses;
}

function print_statuses($statuses, $target = null, $headerEntityId = null, $default = array(), $printAsTopLevel = false){
	global $smap;
	$otherIds = get_other_entities($target['id']);

	foreach ($statuses as $p){
		$p += $default;

		$schema = get_schema($p['bulletin_schema']);

		$sector = get_var('SELECT value FROM options WHERE id = %s', array($p['sector_id']));
		$contract_type = get_var('SELECT value FROM options WHERE id = %s', array($p['contract_type_id']));

		$services = query('SELECT o.id AS id, o.value AS label FROM status_has_service AS ss LEFT JOIN options AS o ON ss.service_id = o.id AND o.name = "service" WHERE ss.status_id = %s', array($p['status_id']));

		$date = $p['date'];
		//if (empty($date))
			//$date = get_var('SELECT b.date FROM bulletin_uses_bulletin AS bb LEFT JOIN bulletins AS b ON bb.bulletin_in = b.id WHERE bb.bulletin_id = %s', array($p['bulletin_id']));

		$cleanId = null;
		if ($p['external_id']){
			$cleanId = $p['external_id'];
			$cleanId = preg_replace('#\b'.preg_quote($schema->shortName, '#').'\b#ius', '', $cleanId);
			$cleanId = ltrim($cleanId, '-');
			$cleanId = rtrim($cleanId, '-');
		}

		$icon = null;
		$label = $p['_action'].' '.$p['_type'];

		$labels = get_status_labels();

		if (isset($labels->{$p['_type']}, $labels->{$p['_type']}->{$p['_action']})){
			$config = $labels->{$p['_type']}->{$p['_action']};
			if (isset($config->icon))
				$icon = $config->icon;

			if (!empty($p['target_id']) && $target['id'] == $p['target_id'])
				$label = $config->own;
			else if ($target['id'] == $p['related_id'])
				$label = !empty($config->related) ? $config->related : $config->own;
			else if ($target['id'] == $p['issuing_id'] || in_array($p['related_id'], $otherIds) || in_array($p['target_id'], $otherIds))
				$label = $config->issuing;
			else
				$label = $config->own;

			if (is_object($label)){
				if (isset($label->icon))
					$icon = $label->icon;
				$label = $label->label;
			}
			//print_r($p);
			
			$note = '';
			if (!empty($p['note'])){
				$note = $p['note'];
				if ($p['_type'] == 'location'){
					$countrySchema = get_country_schema($schema);
					
					$locationObj = apply_filters('location_lint', null, $p['note'], $countrySchema);
					$note = $locationObj ? get_location_label($locationObj) : $note;
				}
				if ($note != '')
					$note = '<span class="status-note">"'.$note.'"</span>';
			}

			$label = strtr($label, array(
				'[target]' => '<a href="'.get_entity_url($p['target_id']).'" class="status-title-tag status-target"><i class="fa fa-'.get_entity_icon($p['target_id']).'"></i> '.get_entity_title($p['target_id']).'</a>',
				'[related]' => '<a href="'.get_entity_url($p['related_id']).'" class="status-title-tag status-target"><i class="fa fa-'.get_entity_icon($p['related_id']).'"></i> '.get_entity_title($p['related_id']).'</a>',
				'[issuing]' => '<a href="'.get_entity_url($p['issuing_id']).'" class="status-title-tag status-issuing"><i class="fa fa-'.get_entity_icon($p['issuing_id']).'"></i> '.get_entity_title($p['issuing_id']).'</a>',
				'[amount]' => !empty($p['amount']) ? '<span class="status-amount">'.print_amount($p['amount']).'</span>' : 'N/D',
				'[note]' => $note,
			));
		}

		// format and print source info and text

		$title = !empty($p['title']) ? strip_tags(preg_replace('#<([a-z0-9]+)>(\*\|.+?\|\*)</\1>#i', '$2', $p['title'])) : null;
		$text = strip_tags(preg_replace('#<([a-z0-9]+)>(\*\|.+?\|\*)</\1>#i', '$2', $p['text']));
		$icons = $alerts = array();

		// highlight amount in source text
		$smap['mem']['amount'] = !empty($p['amount']) ? get_amount($p['amount']) : null;
		$text = preg_replace_callback('#\b'.get_amount_pattern(true).'\b#i', function($m){
			global $smap;
			$hasCents = preg_match('#.*[,\.][0-9][0-9]$#', $m[0]);
			$val = intval(preg_replace('#([\.,\s])#', '', $m[0])) * ($hasCents ? 100 : 1);

			$isAmount = $smap['mem']['amount']['originalValue']
				&& $val == $smap['mem']['amount']['originalValue'];

			return !empty($m[2]) && ($isAmount || $val > 1000)
				? '<span class="text-tag '.($isAmount ? '' : 'text-tag-nolabel ').'text-tag-type-'.($isAmount ? 'amount' : 'amount-other').'">'.($isAmount ? '<span class="text-tag-icon">Amount</span>' : '').'<span class="text-tag-label">'.strip_tags($m[0]).'</span></span>'
				: $m[0];
		}, $text, -1, $count);

		// highlight note in source text
		if (!empty($p['note'])){

			$labels = get_status_labels();
			$noteLabel = null;
			if (isset($labels->{$p['_type']}, $labels->{$p['_type']}->{$p['_action']})){
				$config = $labels->{$p['_type']}->{$p['_action']};
				if (isset($config->note))
					$noteLabel = $config->note;
			}

			$inner = implode('\s+', array_map(function($e){ return preg_quote($e, '#'); }, explode(' ', $p['note'])));
			$text = preg_replace('#'.$inner.'#ius', '<span class="text-tag'.($noteLabel ? '' : 'text-tag-nolabel').'">'.($noteLabel ? '<span class="text-tag-icon">'.$noteLabel.'</span>' : '').$p['note'].'</span>', $text);
		}

		if (!$count)
			$alerts[] = '<i class="fa fa-warning" title="Amount not detected in extract"></i>';

		// highlight entities
		$entities = array();
		$hasTarget = false;

		if (!empty($p['target_id']) && ($ctarget = get_entity_by_id($p['target_id'])))
			$entities[] = array(
				'_type' => 'target',
			) + $ctarget;
		if (!empty($p['related_id']) && ($ctarget = get_entity_by_id($p['related_id'])))
			$entities[] = array(
				'_type' => 'related',
			) + $ctarget;
		if (!empty($p['issuing_id']) && ($ctarget = get_entity_by_id($p['issuing_id'])))
			$entities[] = array(
				'_type' => 'issuing',
			) + $ctarget;

		if ($otherEntities = parse_entities(array('strict' => true), $text, $p['bulletin_schema'], array('_type' => 'other', 'country' => get_country_schema($p['bulletin_schema'])->id)))
			$entities = array_merge($entities, $otherEntities);

/*
		$entities[] = array(
			'_type' => !empty($p['related_id']) ? 'target' : 'related',
		) + $target;*/

		// print_json($entities);

		$replace = array();
		foreach ($entities as $e){

			foreach (get_entity_patterns($e) as $pat)
				$replace[$pat] = '<span class="text-tag text-tag-type-'.$e['_type'].'"><span class="text-tag-icon">'.($e['_type'] != 'other' ? ucfirst($e['_type']) : 'Entity').'</span><span class="text-tag-label">$0</span></span>';

			if ($e['_type'] == 'target')
				$hasTarget = true;
		}
		if ($replace){
			$title = smap_replace($replace, $title);
			$text = smap_replace($replace, $text);
		}

		if (!$hasTarget)
			$alerts[] = '<i class="fa fa-warning" title="Target not detected in extract"></i>';

		$js = "jQuery(this).closest('.status-body').find('.folding').toggle(); return false;";

		$countEntities = count($entities);

		//$icons[] = '<a href="#" class="extract-entities-count" onclick="'.$js.'"><span title="'.$countEntities.' entities found in extract"><i class="fa fa-address-book-o"></i> '.$countEntities.'</span>'.($alerts ? ' '.implode('', $alerts) : '').'</a>';

		$docQuery = array(
			'schema' => $schema->id,
			'date' => $p['date'],
			'id' => $p['external_id'],
		);

		$topDocQuery = $docQuery;
		if (!empty($docQuery['id']))
			unset($topDocQuery['id']);

		if (!$printAsTopLevel){
		?>
		<div class="status-inline">
			<?php } ?>
			<!-- <div class="status-header-inline">
				<div class="status-debug debug">Status #<?= $p['status_id'] ?>: <?= $p['_type'].' / '.$p['_action'] ?></div>
			</div> -->
			<span class="date"><?= date_i18n('M j', strtotime($date)) ?><span><?= date_i18n(', Y', strtotime($date)) ?></span></span>
			<div class="status-body" <?= related(array('status_id' => $p['status_id'])) ?>>

				<div class="status-title">
					<?php if ($printAsTopLevel){ ?>
						<i class="status-icon fa fa-<?= ($icon ? $icon : 'info-circle') ?>"></i>
					<?php } ?>
				<?php
					echo '<span class="status-type">'.$label.'</span> ';

					?>
				</div>
				<?php if ($services || $contract_type || $sector){ ?>
					<div class="status-services">
						<?php foreach ($services as $v){ ?>
							<div><?= $v['label'] ?> <span>(<?= $v['id'] ?>)</span></div>
						<?php }
						if ($contract_type || $sector){
							if ($services)
								echo ' (';
							if ($contract_type)
								echo $contract_type;
							if ($sector)
								echo ' of '.$sector;
							if ($services)
								echo ')';
						}
						?>
					</div>
				<?php } ?>
				<div class="source">
					<?= get_buggy_button('status', 'Mark status #'.$p['status_id'].' as buggy') ?>
					<span class="status-date"><a title="<?= esc_attr('Published on '.date_i18n('M j, Y', strtotime($date)).'. <br>Click to show the original bulletin summary.') ?>" href="<?= url($topDocQuery, 'fetch') ?>" target="_blank"><i class="fa fa-clock-o"></i> <?= date_i18n('M j, Y', strtotime($date)) ?></a></span>
					<span class="status-bulletin"><a title="<?= esc_attr('Published in '.$schema->name.'. <br>Click to show the original bulletin document.') ?>" href="<?= url($docQuery, 'fetch') ?>" target="_blank"><i class="fa fa-book"></i><?= $schema->shortName ?></a><a href="#" title="Click to unfold the original text" class="extract-link" onclick="<?= $js ?>"><i class="fa fa-caret-down"></i></a></span><?php echo implode('', $icons); ?>
				</div>
				<div class="folding">
					<div class="extract">
						<div class="extract-header">
							<?php
							if (!empty($p['issuing_id']) && ($issuing = get_entity_by_id($p['issuing_id'])))
								$pat = 'Published by '.get_entity_title_html($issuing, false, 50).' as part of the %s';
							else if (!empty($docQuery['id']))
								$pat = 'Original text from %s';
							else
								$pat = 'Below is the %s';

							if (!empty($docQuery['id'])){
								$inner = get_format_label($docQuery, 'document').' <a href="'.url($docQuery, 'fetch').'" target="_blank">'.$p['external_id'].'</a> of <a href="'.url($topDocQuery, 'fetch').'" target="_blank">'.date_i18n('M j, Y', strtotime($docQuery['date'])).'</a>.';
								
							} else
								$inner = 'bulletin\'s '.get_format_label($docQuery, 'document').' from <a href="'.url($docQuery, 'fetch').'" target="_blank">'.date_i18n('M j, Y', strtotime($docQuery['date'])).'</a>.';

							if (is_admin())
								$inner .= ' <a class="precept-reparse" href="'.url($topDocQuery+array('precept' => $p['precept_id']), 'parse').'" target="_blank"><i class="fa fa-refresh"></i> reparse</a>';
							
							echo sprintf($pat, $inner);
							?>
						</div>
						<div class="extract-inner">
							<?php

							if ($title)
								echo nl2br(preg_replace("/\n+/", "\n", $title)).'<br><br>';

							echo nl2br(preg_replace("/\n+/", "\n", $text));

							?>
						</div>
					</div>
				</div>
			</div>
			<?php
			if (!$printAsTopLevel){ ?>
		</div>
		<?php
		}
	}
}


function print_entity_stats($stats, $target, $query){
	$labels = get_status_labels();
	$date = null;
	$items = array();
	$count = 0;
	$otherIds = get_other_entities($target['id']);

	foreach ($stats as $s){
		if (!$date || $s['date'] != $date){
			if ($date)
				print_entity_stats_for_date($date, $items, $count, $target, $query);
			$items = array();
			$count = 0;
			$date = $s['date'];
		}

		if (!empty($s['count']))
			$count += $s['count'];
			
		if (isset($labels->{$s['_type']}, $labels->{$s['_type']}->{$s['_action']}, $labels->{$s['_type']}->{$s['_action']}->stats)){
			$config = $labels->{$s['_type']}->{$s['_action']};
			$icon = isset($config->icon) ? $config->icon : null;

			$item = '<div class="entity-stat-wrap'.($s['count'] <= 5 ? ' entity-stat-children-filled entity-stat-children-open' : '').'">';
			
			$item .= '<div class="entity-stat" data-smap-related="'.esc_json(array('type' => $s['_type'], 'action' => $s['_action'])).'">';

			if ($s['count'] < 2){
				$statuses = query_statuses(array('type' => $s['_type'], 'action' => $s['_action']) + ($s['rel'] == 'target' ? array('target' => true) : array('related' => true)) + $query);
				if (empty($statuses))
					print_inline_error('no status returned ( < 2)');
				if (count($statuses) >= 2)
					print_inline_error('bad status count');
			}

			if ($s['count'] < 2 && $statuses){
				ob_start();
				print_statuses($statuses, $target, $query['id'], array('date' => $date), true);
				$item .= ob_get_clean();

				$item .= '</div>';
			} else {
				$item .= '<div class="status-title"><i class="status-icon fa fa-'.$icon.'"></i> '.strtr($config->stats, array(
					'[count]' => '<span class="status-count">'.number_format($s['count'], 0).'</span>',
					'[amount]' => '<span class="status-amount">'.number_format($s['amount']/100, 2).' '.$s['unit'].'</span>', // could be calculated better....
				)).' <i class="fa fa-angle-right entity-stat-children-filled-ind" title="Unfold statuses"></i><i class="fa fa-spinner fa-pulse entity-stat-children-loading-ind"></i></div></div>';

				if ($s['count'] <= 5){
					$statuses = query_statuses(array('type' => $s['_type'], 'action' => $s['_action']) + ($s['rel'] == 'target' ? array('target' => true) : array('related' => true)) + $query);
					if (empty($statuses))
						print_inline_error('no status returned ( <= 5)');
					ob_start();
					print_statuses($statuses, $target, $query['id'], array('date' => $date));
					$item .= '<div class="entity-stat-children-holder"><div class="entity-stat-children">'.ob_get_clean().'</div></div>';
				} else
					$item .= '<div class="entity-stat-children-holder"></div>';
			}

			$item .= '</div>';
			$items[] = $s + array('html' => $item);
		} else
			echo 'missing label for '.print_json($s, false).'<br>';
	}
	if ($date)
		print_entity_stats_for_date($date, $items, $count, $target, $query);
}

function print_entity_stats_for_date($date, $items, $count, $target, $query){
	usort($items, function($s1, $s2){
		$labels = get_status_labels();
		if ($s1['_type'] == $s2['_type']){
			$keys = array_keys((array) $labels->{$s2['_type']});
			$k1 = array_search($s1['_action'], $keys);
			$k2 = array_search($s2['_action'], $keys);
		} else {
			$keys = array_keys((array) $labels);
			$k1 = array_search($s1['_type'], $keys);
			$k2 = array_search($s2['_type'], $keys);
		}
		return $k1 > $k2;
	});
	echo '<div class="entity-stat-date" '.related(array('date' => $date)).'><span class="entity-stat-date-ind">'.$date.'</span><div class="entity-stat-right">';
	foreach ($items as $s)
		echo $s['html'];
	echo '</div></div>';
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

	return number_format((isset($a['value']) ? $a['value'] : $a['originalValue'])/100, 2).' '.(isset($a['unit']) ? $a['unit'] : $a['originalUnit']);
}

function print_entity_stat($c, $entity, &$details, $relation = 'related'){
	if ($v = get_col('SELECT '.(!empty($c['type']) ? $c['type'] : 'target_id').' FROM statuses WHERE '.(!empty($c['type']) && $c['type'] == 'related_id' ? 'target_id' : 'related_id').' = %s AND type = "'.$c['_type'].'" AND action IN ( '.(!empty($c['_action']) ? '"'.$c['_action'].'"' : '"new", "start", "update"').' ) ORDER BY id DESC', $entity['id'])){
		$isAmount = !empty($c['type']) && $c['type'] == 'amount';
		$details[$c['_type'].'_'.(!empty($c['_action']) ? $c['_action'] : 'new')] = array(
			'title' => $c['label'],
			'value' => $isAmount ? get_amount($v) : get_entities_by_id($v),
			'html' => $isAmount ? print_amount(array_pop($v)) : print_entities($v),
		);
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
		$and .= prepare('country = %s AND ', strtolower($country));

	$e = get_row('SELECT * FROM entities WHERE '.$and.'slug = %s LIMIT 1', $slug);
	return $e;
}

function get_entity_title($e, $short = false, $forTitle = false){
	if (is_numeric($e))
		$e = get_entity_by_id($e);
	return ($e['type'] == 'person' ? mb_strtoupper($e['name']).(!empty($e['first_name']) ? ', '.$e['first_name'] : '') : $e['name']).(!$short && !empty($e['subtype']) ? ($forTitle ? ', '.$e['subtype'] : '<span class="entity-subtype">'.$e['subtype'].'</span>') : '');
}

function get_entity_title_html($e, $icon = false, $maxLength = false){
	$title = get_entity_title($e);
	if ($maxLength && mb_strlen($title) > $maxLength){
		while (mb_substr($title, $maxLength - 5, 1) != ' ')
			$maxLength--;
		$title = mb_substr($title, 0, $maxLength - 5).'...';
	}
	$title .= ' ('.get_country_schema($e['country'])->name.')';
	return '<a href="'.get_entity_url($e).'">'.($icon ? '<i class="fa fa-'.get_entity_icon($e).'"></i> ' : '').$title.'</a>';
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



function get_entity_url($r){
	if (is_numeric($r))
		$r = get_entity_by_id($r);
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
		$c = get_country_schema($e['country']);

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
	// --------------------------------------------------- HERE -----------!!
	// http://localhost/boe/application/api/es/boe/2017-2018-01-05/parse
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


function get_entity_summary($entity){
	$details = array();

	// TODO: add total fundings
	// TODO: grep "Resultante suscrito" -> update capital

	// became

	$entities = query('SELECT e.country, e.name, e.first_name, e.type, e.subtype, e.slug
		FROM statuses AS s
		LEFT JOIN entities AS e ON s.target_id = e.id
		WHERE s.related_id = %s AND s.type = "name" AND s.action IN ( "update" )
		ORDER BY s.id DESC
	', $entity['id']);

	if ($entities)
		$details['new_names'] = array(
			'title' => 'Became',
			'html' => print_entities($entities),
		);

	// previous names

	$entities = get_previous_entities($entity['id']);

	if ($entities)
		$details['old_names'] = array(
			'title' => 'Old names',
			'html' => print_entities($entities),
		);

	// date founded

	$date = get_var('SELECT b.date AS date
		FROM statuses AS s
		LEFT JOIN precepts AS p ON s.precept_id = p.id
		LEFT JOIN bulletins AS b ON p.bulletin_id = b.id
		WHERE related_id = %s AND type = "capital" AND action IN ( "new" )
	', $entity['id']);

	if ($date)
		$details['founded'] = array(
			'title' => 'Founded',
			'value' => $date,
			'html' => date_i18n('M j, Y', strtotime($date)),
		);

	// object

	$object = get_var('SELECT note FROM statuses WHERE related_id = %s AND type = "object" AND action = "new" ORDER BY id DESC LIMIT 1', $entity['id']);
	if ($object)
		$details['object'] = array(
			'title' => 'Object',
			'html' => $object,
		);

	// location
	$location = get_var('SELECT note FROM statuses WHERE related_id = %s AND type = "location" AND action = "new" ORDER BY id DESC LIMIT 1', $entity['id']);
	$locationObj = $location ? herecom_convert_location($location, $entity['country']) : null;

	if ($location)
		$details['location'] = array(
			'title' => 'Location',
			'value' => $locationObj ? $locationObj : null,
			'html' => '<i>'.($locationObj ? get_location_label($locationObj) : $location).'</i>',
		);

	// creation capital

	print_entity_stat(array(
		'_type' => 'capital',
		'_action' => 'new',
		'type' => 'amount',
		'label' => 'Initial capital',
	), $entity, $details);


	// minimum capital

	$in = get_row('SELECT SUM(a.value) AS amount, a.unit FROM statuses AS s LEFT JOIN amounts AS a ON s.amount = a.id WHERE related_id = %s AND type = "capital" AND action IN ( "new", "increase" )', $entity['id']);
	$out = get_row('SELECT SUM(a.value) AS amount, a.unit FROM statuses AS s LEFT JOIN amounts AS a ON s.amount = a.id WHERE related_id = %s AND type = "capital" AND action IN ( "decrease" )', $entity['id']);
	$diff = ($in ? $in['amount'] : 0) - ($out ? $out['amount'] : 0);

	if ($diff > 0)
		$details['capital_min'] = array(
			'title' => 'Minimum capital',
			'html' => print_amount($diff, $in ? $in['unit'] : $out['unit']),
		);

	print_entity_stat(array(
		'type' => 'related_id',
		'_type' => 'owner',
		'label' => 'Owns',
	), $entity, $details);

	print_entity_stat(array(
		'type' => 'related_id',
		'_type' => 'owner',
		'_action' => 'end',
		'label' => 'Owned',
	), $entity, $details);

	print_entity_stat(array(
		'_type' => 'owner',
		'label' => 'Owners',
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

	return $details;
}


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
				if (count($etype) > 2 && ($s = get_country_schema(strtoupper($etype[1].'/'.$etype[2])))){
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
	if (($s = get_country_schema($country))
		&& isset($s->vocabulary, $s->vocabulary->legalEntityTypes, $s->vocabulary->legalEntityTypes->{$subtype}, $s->vocabulary->legalEntityTypes->{$subtype}->{$prop}))
		return $s->vocabulary->legalEntityTypes->{$subtype}->{$prop};
	return 'N/D';
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
	);
	$join = $where = $groupby = array();

	if (!empty($args['q']) && trim($args['q']) != ''){
		$query = sanitize_keywords($args['q']);

		foreach (explode(' ', $query) as $q)
			$where[] = prepare('e.keywords LIKE %s', array('%'.$q.'%'));
	}

	if ($args['etype'])
		$where[] = prepare('e.type = %s', $args['etype']);
	if ($args['esubtype'])
		$where[] = prepare('e.subtype = %s', $args['esubtype']);

	if ($args['country'])
		$where[] = prepare('e.country = %s', $args['country']);

	if ($args['locations']){
		// join with statuses on location type and filter where
		
		$cwhere = array();
		foreach ($args['locations'] as $l){
		
			if (!empty($l['country']))
				$where[] = prepare('e.country = %s', $l['country']);
			
			else {
				
				foreach ($l as $k => $v)
					$cwhere[] = prepare('l.'.$k.' = %s', $v);
			}
		}

		$join[] = 'LEFT JOIN statuses AS s ON e.id = s.related_id AND s.type = "location"';
		$join[] = 'LEFT JOIN locations AS l ON s.note = l.id';

		$groupby[] = 'e.id';
		
		if ($cwhere)
			$where[] = '( ( '.implode(' ) OR ( ', $cwhere).' ) )';
	}

	if ($args['etypes']){
		$subwhere = array();
		foreach ($args['etypes'] as $etype){
			$etype = explode('/', $etype);
			if (count($etype) < 3)
				$subwhere[] = prepare('e.type = %s', $etype[0]);
			else
				$subwhere[] = prepare('e.country = %s AND e.type = %s AND e.subtype = %s', array(strtolower($etype[1]), $etype[0], strtoupper($etype[2])));
		}
		$where[] = '( '.implode(' OR ', $subwhere).' )';
	}

	if (!empty($args['misc']) && $args['misc'] == 'buggy')
		$where[] = '(( e.type = "person" AND LENGTH(e.name) > 40 OR LENGTH(e.first_name) > 30) OR LENGTH(e.name) > 80 )';
		
	// build queries
	$count_q = $q = 'FROM entities AS e '.($join ? implode(' ', $join).' ' : '');
		
	// allow infinite scroll
	$limit = $args['limit'];
	if ($args['after_id']){
		$limit = 2 * $limit;
		if ($after_value = get_var('SELECT name FROM entities WHERE id = %s', $args['after_id']))
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
	
	$q = 'SELECT e.id, e.country, e.type, e.subtype, e.name, e.first_name, e.slug '.$q.' ORDER BY e.name ASC, e.first_name ASC'.($limit ? ' LIMIT '.($limit+1) : '');
	
	//echo $q.'<br>';
	$res = query($q);
	//debug($res);
	
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


function load_search_results(){
	global $smap;
	$ret = array('results' => array(), 'resultsCount' => 0);
				
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
			'limit' => min(200, !empty($smap['filters']['limit']) ? intval($smap['filters']['limit']) : DEFAULT_RESULTS_COUNT),
			'after_id' => !empty($smap['query']['after_id']) ? $smap['query']['after_id'] : null,
			'misc' => !empty($smap['filters']['misc']) ? $smap['filters']['misc'] : null,
			'loaded_count' => !empty($smap['query']['loaded_count']) ? $smap['query']['loaded_count'] : null,
		);
		$ret['results'] = query_entities($ret['query'], $ret['resultsLeft']);
		$ret['resultsCount'] = $smap['query']['loaded_count'] + count($ret['results']) + $ret['resultsLeft'];
	}
	$smap += $ret;
}

function get_results_count_label($count, $total){
	if ($count != $total)
		return sprintf(_('%s results out of %s'), number_format($count, 0), $total);
	else
		return sprintf(_('%s results'), $total);
}
