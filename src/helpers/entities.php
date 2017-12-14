<?php

if (!defined('BASE_PATH'))
	die();


function kaosGetEntities($options, $oval, $schema, $extraEnt = array(), $doubleChecking = false, $debug = false){
	$options = (array) $options;
	$starting = !empty($options['starting']);

	$countrySchema = kaosGetCountrySchema($schema);
	$extra = !empty($options['entityExtra']) ? (array) $options['entityExtra'] : array();

	//$prefix = !empty($options->allowEntityPrefix) ? $options->allowEntityPrefix : false;

	// TODO: allowEntityPrefix could be badly excluded from company name :S

	if (empty($countrySchema->vocabulary) || empty($countrySchema->vocabulary->legalEntityTypes))
		return null;

	// TODO: avoid being in an HTML tag (when converting entities)

	$and = '(?:\b'.$countrySchema->vocabulary->basicVocabulary->and.'\s+)*';
	$companyPattern = kaosGetLegalEntityPattern($schema, !empty($options['allowEntityPrefix']) ? $options['allowEntityPrefix'] : '', $companyNamePattern, !isset($options['strict']) || $options['strict'], $exceptPatterns);
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
		$wrapGroups = cutByGroups($options, $applyVal, $debug);

		if ($debug){
			kaosJSON($wrapGroups);
		}

		if ($wrapGroups){

			foreach ($wrapGroups as $m){

				if (!$doubleChecking && !empty($options['stripBeforeMatch']))
					$m['value'] = trim(preg_replace($options['stripBeforeMatch'], '', $m['value']));


				//echo "<br>OUTER SEL: ".$m.'<br>';

				//$cut = preg_split('#\b('.kaosEscapePatterns($companyPattern).')\b#uis', preg_replace('#\n#ius', '', $m), -1, PREG_SPLIT_DELIM_CAPTURE);

				$m['value'] = trim(preg_replace('#\n#ius', '', $m['value']));

				// TODO: PREG COMPANY TYPES ONE BY ONE?!?! (because UTE's are catching before SL now!)

				// split by groups
				$groups = array();
				$group = '';
				if (!empty($options['groups'])
					&& ($cgroups = preg_split('#('.implode('|', $options['groups']).')#ius', $m['value'], -1, PREG_SPLIT_DELIM_CAPTURE))
					&& count($cgroups) > 1){

					if ($debug){
						echo 'CGROUPS: <br>';
						kaosJSON($cgroups);
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

				if ($debug){
					echo "GROUPS: "; kaosJSON($groups); echo '<br><br><br>';
				}

				$matches = array();

				foreach ($groups as $group => $m2s){

					foreach ($m2s as $m2){

						$prepattern = kaosEscapePatterns((!$doubleChecking && !empty($options['allowEntityPrefix']) ? $options['allowEntityPrefix'] : '').$exceptPatterns);

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
									'groups' => empty($group) ? null : kaosGroupLabelLint($options, $group),
								);

								if (!$doubleChecking && !empty($options['allowEntityPrefix'])) // TODO: CONTINUE HERE: NOT WORKING: TRYING TO STRIP PREFIX OUT OF  http://localhost/boe/application/api/es/boe/2017-09-22/extract/1?noProcessedCache=1
									$ent['name'] = preg_replace('#^('.$options['allowEntityPrefix'].')#us', '', $ent['name']);

								//print_r($ent);
								foreach ($countrySchema->vocabulary->legalEntityTypes as $legalEntityType => $legalEntityConfig){

									if (!empty($legalEntityConfig->strictPatterns))
										foreach ($legalEntityConfig->strictPatterns as $pat){

											$companyLeft = strpos($pat, '{legalEntityPattern}') === 0;
											$pat = str_replace('{legalEntityPattern}', ($companyLeft ? '' : '\s*,?\s*').'(\b'.$companyNamePattern.'\b)'.($companyLeft ? ',?' : '').'\s*', $pat);

											if (preg_match('#'.$pat.'$#ums', $ent['name'], $m3)){
												$ent['name'] = kaosGetNonEmpty($m3, 1, $companyLeft ? 0 : 1);
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
												$ent['name'] = kaosGetNonEmpty($m3, 1, $companyLeft ? 0 : 1);
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
									'groups' => $group == '' ? null : kaosGroupLabelLint($options, $group),
								);
						}
					}
				}

				//echo "MATCHES: ".print_r($matches, true).'<br>';

				foreach ($matches as $c){

					$c['name'] = kaosLint($c['name']);

					if (!$doubleChecking && !empty($options['stripFromMatch']))
						$c['name'] = kaosLint(preg_replace($options['stripFromMatch'], '', $c['name']));

					$c['name'] = kaosLint(preg_replace('#^('.(!empty($options['allowEntityPrefix']) ? $options['allowEntityPrefix'] : '').'\s*'.$and.'\s*)#u', '', $c['name']));

					$c['linted'] = kaosGetEntityTitle($c);

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
					kaosJSON($wrapGroups);
					kaosJSON($matches);
					echo nl2br(htmlentities($oval)).'<br><br>';
					kaosJSON($groups);
					kaosJSON(array_values($ret));
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
				$entities = kaosGetEntities($options, $r['original'], $schema);
				echo '-> '.($entities ? count($entities) : 0).'<br>';
				if ($entities)
					foreach ($entities as $e){
						if ($e['original'] != $r['name']){
							$newEntities[] = $e;
							array_splice($ret, $i+$added, 0, array($e));
							$added++;
							echo "NEW E: ";
							kaosJSON($e);
							kaosJSON($r);
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
		kaosJSON(array_values($ret));
		echo '<br><br>';
		if ($stop)
			die();
	}
	* */
	return $ret ? array_values($ret) : null;
}


function insertGetPrecept($p){

	$pid = get('SELECT id FROM precepts WHERE bulletin_id = %s AND issuing_id = %s AND title = %s AND text = %s', array($p['bulletin_id'], $p['issuing_id'], $p['title'], $p['text']));

	if ($pid){
		if (KAOS_IS_CLI)
			kaosPrintLog('found precept "'.(!empty($p['title']) ? $p['title'] : $p['text']).'"', array('color' => 'grey'));
		return $pid;
	}
	if (KAOS_IS_CLI)
		kaosPrintLog('inserting precept "'.(!empty($p['title']) ? $p['title'] : $p['text']).'"', array('color' => 'grey'));
	return insert('precepts', $p);
}


function kaosGetLegalEntityPattern($schema, $allowEntityPrefix = false, &$companyPattern = null, $strict = true, &$exceptPatterns = ''){
	// TODO: add cache!

	$countrySchema = strpos($schema, '/') ? kaosGetCountrySchema($schema) : $schema;
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

			// kaosEscapePatterns

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


function kaosQueryStatuses($query){
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
		$query['ids'] = array_merge($query['ids'], kaosGetOtherEntities($query['id']));
	else if (!empty($query['id']))
		$query['ids'][] = $query['id'];
	if (empty($query['ids']))
		return array();

	$limit = 100;
	$where = '';

	$yearMode = !empty($query['date']) && is_numeric($query['date']);
	$dateStr = ($yearMode ? 'b_in.date, b.date' : 'b_in.date, b.date');

	if (!empty($query['date'])){
		if ($yearMode) // year mode
			$where .= queryPrepare(' AND (YEAR(b_in.date) = %s OR YEAR(b.date) = %s)', array($query['date'], $query['date']));
		else
			$where .= queryPrepare(' AND (b_in.date = %s OR b.date = %s)', array($query['date'], $query['date']));
	}

	$join = '';
	$order = 'COALESCE('.$dateStr.') DESC';

	if (!empty($query['type'])){
		$where .= queryPrepare(' AND s.type = %s', array($query['type']));
		if ($query['type'] == 'capital'){
			$join .= 'LEFT JOIN amounts AS a ON s.amount = a.id ';
			$order = 'a.value DESC';
		}
	}
	$order .= ', e.name ASC, e.first_name ASC';

	if (!empty($query['action']))
		$where .= queryPrepare(' AND s.action = %s', array($query['action']));

	if (empty($query['related']) && empty($query['issuing'])){

		$q = '
			SELECT s.id AS status_id, COALESCE('.$dateStr.') AS date, b.external_id, b.bulletin_schema, p.title, p.text, p.bulletin_id,
			s.type AS _type, s.action AS _action, s.amount, s.contract_type_id, s.sector_id,
			s.target_id, s.related_id, p.issuing_id, p.id AS precept_id, s.note,
			e.name, e.first_name, e.type, e.subtype, e.country

			FROM precepts AS p
			LEFT JOIN statuses AS s ON p.id = s.precept_id
			LEFT JOIN entities AS e ON p.issuing_id = e.id
			LEFT JOIN bulletin_uses_bulletin AS bb ON p.bulletin_id = bb.bulletin_id
			LEFT JOIN bulletins AS b_in ON bb.bulletin_in = b_in.id
			LEFT JOIN bulletins AS b ON p.bulletin_id = b.id
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

			SELECT s.id AS status_id, COALESCE('.$dateStr.') AS date, b.external_id, b.bulletin_schema,
			p.title, p.text, p.bulletin_id,
			s.type AS _type, s.action AS _action, s.amount, s.contract_type_id, s.sector_id,
			s.target_id, s.related_id, p.issuing_id, p.id AS precept_id, s.note,
			e.name, e.first_name, e.type, e.subtype, e.country

			FROM precepts AS p
			LEFT JOIN statuses AS s ON p.id = s.precept_id
			LEFT JOIN entities AS e ON s.target_id = e.id
			LEFT JOIN bulletin_uses_bulletin AS bb ON p.bulletin_id = bb.bulletin_id
			LEFT JOIN bulletins AS b_in ON bb.bulletin_in = b_in.id
			LEFT JOIN bulletins AS b ON p.bulletin_id = b.id
			'.$join.'

			WHERE (p.issuing_id IN ( '.implode(', ', $query['ids']).' ) OR s.related_id IN ( '.implode(', ', $query['ids']).' ) )'.$where.'
			GROUP BY s.id
			ORDER BY '.$order.'
			LIMIT '.$limit.'

		';

		foreach (query($q) as $e)
			$statuses[$e['status_id']] = $e;
	}
	return $statuses;
}

function kaosPrintStatuses($statuses, $target = null, $headerEntityId = null, $default = array(), $printAsTopLevel = false){
	global $kaosCall;
	$otherIds = kaosGetOtherEntities($target['id']);

	foreach ($statuses as $p){
		$p += $default;

		$schema = kaosGetSchema($p['bulletin_schema']);

		$sector = get('SELECT value FROM options WHERE id = %s', array($p['sector_id']));
		$contract_type = get('SELECT value FROM options WHERE id = %s', array($p['contract_type_id']));

		$services = query('SELECT o.id AS id, o.value AS label FROM status_has_service AS ss LEFT JOIN options AS o ON ss.service_id = o.id AND o.name = "service" WHERE ss.status_id = %s', array($p['status_id']));

		$date = $p['date'];
		if (empty($date))
			$date = get('SELECT b.date FROM bulletin_uses_bulletin AS bb LEFT JOIN bulletins AS b ON bb.bulletin_in = b.id WHERE bb.bulletin_id = %s', array($p['bulletin_id']));

		$cleanId = null;
		if ($p['external_id']){
			$cleanId = $p['external_id'];
			$cleanId = preg_replace('#\b'.preg_quote($schema->shortName, '#').'\b#ius', '', $cleanId);
			$cleanId = ltrim($cleanId, '-');
			$cleanId = rtrim($cleanId, '-');
		}

		$icon = null;
		$label = $p['_action'].' '.$p['_type'];

		$labels = kaosGetStatusLabels();

		if (isset($labels[$p['_type']], $labels[$p['_type']][$p['_action']])){
			$config = $labels[$p['_type']][$p['_action']];
			if (isset($config['icon']))
				$icon = $config['icon'];

			if (!empty($p['target_id']) && $target['id'] == $p['target_id'])
				$label = $config['own'];
			else if ($target['id'] == $p['related_id'])
				$label = !empty($config['related']) ? $config['related'] : $config['own'];
			else if ($target['id'] == $p['issuing_id'] || in_array($p['related_id'], $otherIds) || in_array($p['target_id'], $otherIds))
				$label = $config['issuing'];
			else
				$label = $config['own'];

			if (is_array($label)){
				if (isset($label['icon']))
					$icon = $label['icon'];
				$label = $label['label'];
			}
			//print_r($p);
			
			$note = '';
			if (!empty($p['note'])){
				$note = $p['note'];
				if ($p['_type'] == 'location'){
					$countrySchema = kaosGetCountrySchema($schema);
					
					$locationObj = apply_filters('location_lint', null, $p['note'], $countrySchema);
					$note = $locationObj ? kaosGetLocationLabel($locationObj) : $note;
				}
				if ($note != '')
					$note = '<span class="status-note">"'.$note.'"</span>';
			}

			$label = strtr($label, array(
				'[target]' => '<a href="'.kaosGetEntityUrl($p['target_id']).'" class="kaos-status-title kaos-status-target"><i class="fa fa-'.kaosGetEntityIcon($p['target_id']).'"></i> '.kaosGetEntityTitle($p['target_id']).'</a>',
				'[related]' => '<a href="'.kaosGetEntityUrl($p['related_id']).'" class="kaos-status-title kaos-status-target"><i class="fa fa-'.kaosGetEntityIcon($p['related_id']).'"></i> '.kaosGetEntityTitle($p['related_id']).'</a>',
				'[issuing]' => '<a href="'.kaosGetEntityUrl($p['issuing_id']).'" class="kaos-status-title kaos-status-issuing"><i class="fa fa-'.kaosGetEntityIcon($p['issuing_id']).'"></i> '.kaosGetEntityTitle($p['issuing_id']).'</a>',
				'[amount]' => !empty($p['amount']) ? '<span class="status-amount">'.kaosPrintAmount($p['amount']).'</span>' : '',
				'[note]' => $note
			));
		}

		// format and print source info and text

		$title = !empty($p['title']) ? strip_tags(preg_replace('#<([a-z0-9]+)>(\*\|.+?\|\*)</\1>#i', '$2', $p['title'])) : null;
		$text = strip_tags(preg_replace('#<([a-z0-9]+)>(\*\|.+?\|\*)</\1>#i', '$2', $p['text']));
		$icons = $alerts = array();

		// highlight amount in source text
		$kaosCall['mem']['amount'] = !empty($p['amount']) ? kaosGetAmount($p['amount']) : null;
		$text = preg_replace_callback('#\b'.kaosGetPatternNumber(true).'\b#i', function($m){
			global $kaosCall;
			$hasCents = preg_match('#.*[,\.][0-9][0-9]$#', $m[0]);
			$val = intval(preg_replace('#([\.,\s])#', '', $m[0])) * ($hasCents ? 100 : 1);

			$isAmount = $kaosCall['mem']['amount']['originalValue']
				&& $val == $kaosCall['mem']['amount']['originalValue'];

			return !empty($m[2]) && ($isAmount || $val > 1000)
				? '<span class="kaos-text-tag '.($isAmount ? '' : 'kaos-text-tag-nolabel ').'kaos-text-tag-type-'.($isAmount ? 'amount' : 'amount-other').'">'.($isAmount ? '<span class="kaos-text-tag-icon">Amount</span>' : '').'<span class="kaos-text-tag-label">'.strip_tags($m[0]).'</span></span>'
				: $m[0];
		}, $text, -1, $count);

		// highlight note in source text
		if (!empty($p['note'])){

			$labels = kaosGetStatusLabels();
			$noteLabel = null;
			if (isset($labels[$p['_type']], $labels[$p['_type']][$p['_action']])){
				$config = $labels[$p['_type']][$p['_action']];
				if (isset($config['note']))
					$noteLabel = $config['note'];
			}

			$inner = implode('\s+', array_map(function($e){ return preg_quote($e, '#'); }, explode(' ', $p['note'])));
			$text = preg_replace('#'.$inner.'#ius', '<span class="kaos-text-tag'.($noteLabel ? '' : 'kaos-text-tag-nolabel').'">'.($noteLabel ? '<span class="kaos-text-tag-icon">'.$noteLabel.'</span>' : '').$p['note'].'</span>', $text);
		}

		if (!$count)
			$alerts[] = '<i class="fa fa-warning" title="Amount not detected in extract"></i>';

		// highlight entities
		$entities = array();
		$hasTarget = false;

		if (!empty($p['target_id']) && ($ctarget = kaosGetEntityById($p['target_id'])))
			$entities[] = array(
				'_type' => 'target',
			) + $ctarget;
		if (!empty($p['related_id']) && ($ctarget = kaosGetEntityById($p['related_id'])))
			$entities[] = array(
				'_type' => 'related',
			) + $ctarget;
		if (!empty($p['issuing_id']) && ($ctarget = kaosGetEntityById($p['issuing_id'])))
			$entities[] = array(
				'_type' => 'issuing',
			) + $ctarget;

		if ($otherEntities = kaosGetEntities(array('strict' => true), $text, $p['bulletin_schema'], array('_type' => 'other', 'country' => kaosGetCountrySchema($p['bulletin_schema'])->id)))
			$entities = array_merge($entities, $otherEntities);

/*
		$entities[] = array(
			'_type' => !empty($p['related_id']) ? 'target' : 'related',
		) + $target;*/

		// kaosJSON($entities);

		$replace = array();
		foreach ($entities as $e){

			foreach (kaosGetEntityPatterns($e) as $pat)
				$replace[$pat] = '<span class="kaos-text-tag kaos-text-tag-type-'.$e['_type'].'"><span class="kaos-text-tag-icon">'.($e['_type'] != 'other' ? ucfirst($e['_type']) : 'Entity').'</span><span class="kaos-text-tag-label">$0</span></span>';

			if ($e['_type'] == 'target')
				$hasTarget = true;
		}
		if ($replace){
			$title = kaosReplace($replace, $title);
			$text = kaosReplace($replace, $text);
		}

		if (!$hasTarget)
			$alerts[] = '<i class="fa fa-warning" title="Target not detected in extract"></i>';

		$js = "jQuery(this).closest('.kaos-status-body').find('.kaos-folding').toggle(); return false;";

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
		<div class="kaos-status-inline">
			<?php } ?>
			<!-- <div class="kaos-status-header-inline">
				<div class="kaos-status-debug kaos-debug">Status #<?= $p['status_id'] ?>: <?= $p['_type'].' / '.$p['_action'] ?></div>
			</div> -->
			<span class="kaos-date"><?= date_i18n('M j', strtotime($date)) ?><span><?= date_i18n(', Y', strtotime($date)) ?></span></span>
			<div class="kaos-status-body">

				<div class="status-title">
					<?php if ($printAsTopLevel){ ?>
						<i class="status-icon fa fa-<?= ($icon ? $icon : 'info-circle') ?>"></i>
					<?php } ?>
				<?php
					echo '<span class="status-type">'.$label.'</span> ';

					?>
				</div>
				<div class="kaos-status-services">
					<?php foreach ($services as $v){ ?>
						<div><?= $v['label'] ?> <span>(<?= $v['id'] ?>)</span></div>
					<?php }
					if ($contract_type || $sector){
						echo ' (';
						if ($contract_type)
							echo $contract_type;
						if ($sector)
							echo ' of '.$sector;
						echo ')';
					}


					// .($cleanId ? ' '.$cleanId : '') ? of <?= date_i18n('M j, Y', strtotime($date)) ?
					?>
				</div>
				<div class="kaos-source">
					<span class="status-id">#<?= $p['status_id'] ?></span>
					<span class="status-date"><a href="<?= kaosGetUrl($topDocQuery, 'fetch') ?>" target="_blank"><i class="fa fa-clock-o"></i> <?= date_i18n('M j, Y', strtotime($date)) ?></a></span>
					<span class="status-bulletin"><a href="<?= kaosGetUrl($docQuery, 'fetch') ?>" target="_blank"><i class="fa fa-book"></i><?= $schema->shortName ?></a><a href="#" class="extract-link" onclick="<?= $js ?>"><i class="fa fa-caret-down"></i></a></span><?php echo implode('', $icons); ?>
				</div>
				<div class="kaos-folding">
					<div class="kaos-extract">
						<div class="kaos-extract-header">
							<?php
							if (!empty($p['issuing_id']) && ($issuing = kaosGetEntityById($p['issuing_id'])))
								$pat = 'Published by '.kaosGetFullEntityHtml($issuing, false, 50).' as part of the %s';
							else if (!empty($docQuery['id']))
								$pat = 'Original text from %s';
							else
								$pat = 'Below is the %s';

							if (!empty($docQuery['id'])){
								$inner = kaosGetFormatLabel($docQuery, 'document').' <a href="'.kaosGetUrl($docQuery, 'fetch').'" target="_blank">'.$p['external_id'].'</a> of <a href="'.kaosGetUrl($topDocQuery, 'fetch').'" target="_blank">'.date_i18n('M j, Y', strtotime($docQuery['date'])).'</a>.';
								
							} else
								$inner = 'bulletin\'s '.kaosGetFormatLabel($docQuery, 'document').' from <a href="'.kaosGetUrl($docQuery, 'fetch').'" target="_blank">'.date_i18n('M j, Y', strtotime($docQuery['date'])).'</a>.';

							if (isAdmin())
								$inner .= ' <a class="precept-reparse" href="'.kaosGetUrl($topDocQuery+array('precept' => $p['precept_id']), 'parse').'" target="_blank"><i class="fa fa-refresh"></i> reparse</a>';
							
							echo sprintf($pat, $inner);
							?>
						</div>
						<div class="kaos-extract-inner">
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


function kaosPrintEntityStats($stats, $target, $query){
	$labels = kaosGetStatusLabels();
	$date = null;
	$items = array();
	$count = 0;
	$otherIds = kaosGetOtherEntities($target['id']);

	foreach ($stats as $s){
		if (!$date || $s['date'] != $date){
			if ($date)
				kaosPrintEntityStatsForDate($date, $items, $count, $target, $query);
			$items = array();
			$count = 0;
			$date = $s['date'];
		}

		if (!empty($s['count']))
			$count += $s['count'];
		if (isset($labels[$s['_type']], $labels[$s['_type']][$s['_action']], $labels[$s['_type']][$s['_action']]['stats'])){
			$config = $labels[$s['_type']][$s['_action']];
			$icon = isset($config['icon']) ? $config['icon'] : null;

			$item = '<div class="entity-stat-wrap'.($s['count'] <= 5 ? ' entity-stat-children-filled entity-stat-children-open' : '').'"><div class="kaos-entity-stat" data-kaos-related="'.esc_json(array('type' => $s['_type'], 'action' => $s['_action'])).'">';

			if ($s['count'] < 2){
				$statuses = kaosQueryStatuses(array('type' => $s['_type'], 'action' => $s['_action']) + ($s['rel'] == 'target' ? array('target' => true) : array('related' => true)) + $query);
				if (empty($statuses))
					kaosInlineError('no status returned ( < 2)');
				if (count($statuses) >= 2)
					kaosInlineError('bad status count');
			}

			if ($s['count'] < 2 && $statuses){
				ob_start();
				kaosPrintStatuses($statuses, $target, $query['id'], array('date' => $date), true);
				$item .= ob_get_clean();

				$item .= '</div>';
			} else {
				$item .= '<div class="status-title"><i class="status-icon fa fa-'.$icon.'"></i> '.strtr($config['stats'], array(
					'[count]' => '<span class="status-count">'.number_format($s['count'], 0).'</span>',
					'[amount]' => '<span class="status-amount">'.number_format($s['amount']/100, 2).' '.$s['unit'].'</span>', // could be calculated better....
				)).' <i class="fa fa-angle-right entity-stat-children-filled-ind" title="Unfold statuses"></i><i class="fa fa-spinner fa-pulse entity-stat-children-loading-ind"></i></div></div>';

				if ($s['count'] <= 5){
					$statuses = kaosQueryStatuses(array('type' => $s['_type'], 'action' => $s['_action']) + ($s['rel'] == 'target' ? array('target' => true) : array('related' => true)) + $query);
					if (empty($statuses))
						kaosInlineError('no status returned ( <= 5)');
					ob_start();
					kaosPrintStatuses($statuses, $target, $query['id'], array('date' => $date));
					$item .= '<div class="entity-stat-children-holder"><div class="entity-stat-children">'.ob_get_clean().'</div></div>';
				} else
					$item .= '<div class="entity-stat-children-holder"></div>';
			}

			$item .= '</div>';
			$items[] = $s + array('html' => $item);
		} else
			echo 'missing label for '.kaosJSON($s, false).'<br>';
	}
	if ($date)
		kaosPrintEntityStatsForDate($date, $items, $count, $target, $query);
}

function kaosPrintEntityStatsForDate($date, $items, $count, $target, $query){
	usort($items, function($s1, $s2){
		$labels = kaosGetStatusLabels();
		if ($s1['_type'] == $s2['_type']){
			$keys = array_keys($labels[$s2['_type']]);
			$k1 = array_search($s1['_action'], $keys);
			$k2 = array_search($s2['_action'], $keys);
		} else {
			$keys = array_keys($labels);
			$k1 = array_search($s1['_type'], $keys);
			$k2 = array_search($s2['_type'], $keys);
		}
		return $k1 > $k2;
	});
	echo '<div class="entity-stat-date" '.kaosRelated(array('date' => $date)).'><span class="entity-stat-date-ind">'.$date.'</span><div class="entity-stat-right">';
	foreach ($items as $s)
		echo $s['html'];
	echo '</div></div>';
}

function kaosRelated($args){
	return 'data-kaos-related="'.esc_json($args).'"';
}

function kaosPrintEntities($ids){
	$str = '';
	foreach (is_array($ids) ? $ids : array($ids) as $i => $id)
		$str .= ($i ? ', ' : '').'<a href="'.kaosGetEntityUrl($id).'"><i class="fa fa-'.kaosGetEntityIcon($id).'"></i> '.kaosGetEntityTitle($id).'</a>';
	return $str;
}

function kaosGetAmount($amount_id){
	return getRow('SELECT * FROM amounts WHERE id = %s', $amount_id);
}

function kaosPrintAmount($amount, $unit = null, $unit_in = 'EUR'){
	if ($amount === null)
		return 'NaN';

	$a = $unit ? array(
		'value' => $amount,
		'unit' => $unit,
	) : kaosGetAmount($amount);

	if (!$a)
		return '';

	$a = kaosConvertCurrency($a['value'], $a['unit'], $unit_in);

	return number_format((isset($a['value']) ? $a['value'] : $a['originalValue'])/100, 2).' '.(isset($a['unit']) ? $a['unit'] : $a['originalUnit']);
}

function kaosPrintEntityStat($c, $entity, &$details, $relation = 'related'){
	if ($v = getCol('SELECT '.(!empty($c['type']) ? $c['type'] : 'target_id').' FROM statuses WHERE '.(!empty($c['type']) && $c['type'] == 'related_id' ? 'target_id' : 'related_id').' = %s AND type = "'.$c['_type'].'" AND action IN ( '.(!empty($c['_action']) ? '"'.$c['_action'].'"' : '"new", "start", "update"').' ) ORDER BY id DESC', $entity['id'])){
		$isAmount = !empty($c['type']) && $c['type'] == 'amount';
		$details[$c['_type'].'_'.(!empty($c['_action']) ? $c['_action'] : 'new')] = array(
			'title' => $c['label'],
			'value' => $isAmount ? kaosGetAmount($v) : kaosGetEntitiesById($v),
			'html' => $isAmount ? kaosPrintAmount(array_pop($v)): kaosPrintEntities($v),
		);
	}
}


function kaosConvertEntities($str){
	//$str = preg_replace_callback('#https?://[-a-zA-Z0-9@:%_\+.~\#?&//=]{2,256}\.[a-z]{2,4}\b(?:\/[-a-zA-Z0-9@:%_\+.~\#?&//=]*)?#si',

	$str = preg_replace_callback('#https?://[-a-zA-Z0-9@:%_\+.~\#?&//=]{2,256}\.[a-z]{2,4}\b(?:\/[-a-zA-Z0-9@:%_\+\.~\#\?&//=]*)?#si',function($m){
		return preg_match('#'.preg_quote(BASE_URL, '#').'#iu', $m[0])
			? '<a class="kaos-convert-url kaos-convert-url-internal" href="'.esc_attr(htmlentities($m[0])).'">'.$m[0].'</a>'
			: '<a class="kaos-convert-url kaos-convert-url-external" href="'.esc_attr(kaosAnonymize(htmlentities($m[0]))).'" target="_blank">'.$m[0].'</a>';
	}, html_entity_decode($str));

	return $str;
}


function kaosGetEntityById($id){
	return getRow('SELECT * FROM entities WHERE id = %s LIMIT 1', array($id));
}

function kaosGetEntitiesById($ids){
	$str = array();
	foreach (is_array($ids) ? $ids : array($ids) as $id)
		$str[] = queryPrepare('%s', $id);
	return query('SELECT * FROM entities WHERE id IN ( '.implode(', ', $str).' )');
}



function kaosGetEntityBySlug($slug, $type = null, $country = null){
	$slug = trim($slug);
	if ($slug == '')
		return false;

	$and = '';
	if ($type)
		$and .= queryPrepare('type = %s AND ', $type);

	if ($country)
		$and .= queryPrepare('country = %s AND ', strtolower($country));

	$e = getRow('SELECT * FROM entities WHERE '.$and.'slug = %s LIMIT 1', $slug);
	return $e;
}

function kaosGetFullEntityHtml($e, $icon = false, $maxLength = false){
	$title = kaosGetEntityTitle($e);
	if ($maxLength && mb_strlen($title) > $maxLength){
		while (mb_substr($title, $maxLength - 5, 1) != ' ')
			$maxLength--;
		$title = mb_substr($title, 0, $maxLength - 5).'...';
	}
	$title .= ' ('.kaosGetCountrySchema($e['country'])->name.')';
	return '<a href="'.kaosGetEntityUrl($e).'">'.($icon ? '<i class="fa fa-'.kaosGetEntityIcon($e).'"></i> ' : '').$title.'</a>';
}

function kaosGetEntityTitle($e, $short = false, $forTitle = false){
	if (is_numeric($e))
		$e = kaosGetEntityById($e);
	return ($e['type'] == 'person' ? mb_strtoupper($e['name']).(!empty($e['first_name']) ? ', '.$e['first_name'] : '') : $e['name']).(!$short && !empty($e['subtype']) ? ($forTitle ? ', '.$e['subtype'] : '<span class="entity-subtype">'.$e['subtype'].'</span>') : '');
}

function kaosGetEntityIcon($r){
	if (is_numeric($r))
		$r = kaosGetEntityById($r);
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



function kaosGetEntityUrl($r){
	if (is_numeric($r))
		$r = kaosGetEntityById($r);
	return BASE_URL.strtolower($r['country']).'/'.$r['type'].'/'.$r['slug'];
}


function kaosGetPreviousEntities($entityId){
	return query('SELECT e.id, e.country, e.name, e.first_name, e.type, e.subtype, e.slug
		FROM statuses AS s
		LEFT JOIN entities AS e ON s.related_id = e.id
		WHERE s.target_id = %s AND s.type = "name" AND s.action IN ( "update" )
		ORDER BY s.id DESC
	', $entityId);
}

function kaosGetNextEntities($entityId){
	return query('SELECT e.id, e.country, e.name, e.first_name, e.type, e.subtype, e.slug
		FROM statuses AS s
		LEFT JOIN entities AS e ON s.target_id = e.id
		WHERE s.related_id = %s AND s.type = "name" AND s.action IN ( "update" )
		ORDER BY s.id DESC
	', $entityId);
}


function kaosGetOtherEntities($entityId){
	$ids = array(intval($entityId));
	foreach (kaosGetPreviousEntities($entityId) as $e)
		$ids[] = intval($e['id']);
	foreach (kaosGetNextEntities($entityId) as $e)
		$ids[] = intval($e['id']);
	return $ids;
}

function kaosGetEntityPatterns($e){
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
		$c = kaosGetCountrySchema($e['country']);

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


function cutByGroups($options, $applyVal, $debug = false, $recursion = false){
	$options = (array) $options;
	$wrapGroups = array();
	if ($debug && !empty($options['wrapGroups'])) echo 'CUTTING: '.$applyVal.'<br>(WRAPGROUP PATTERN '.implode('|', $options['wrapGroups']).')<br><br>';

	if (!empty($options['outerSelector']) && !empty($options['wrapGroups'])
		&& ($cwrapGroups = preg_split('#('.kaosEscapePatterns(implode('|', $options['wrapGroups'])).')#ius', $applyVal, -1, PREG_SPLIT_DELIM_CAPTURE))
		&& count($cwrapGroups) > 4
		&& preg_match_all('#'.$options['outerSelector'].'#iums', $cwrapGroups[2])
		&& preg_match_all('#'.$options['outerSelector'].'#iums', $cwrapGroups[4])
		//&& preg_match('#.*Importe\s*[a-z]+\s*:?\s*[0-9]+.*#ius', $wrapGroups[4])
	){
		if ($debug) echo '#1 ('.implode('|', $options['wrapGroups']).')<br>';
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
		if ($debug) echo '#2 ('.$options['outerSelector'].')<br>';
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
			if ($debug) echo '#2.1<br>';
			foreach ($cwrapGroups as $ccwrapGroups)
				$wrapGroups = array_merge($wrapGroups, cutByGroups($options, $ccwrapGroups, $debug, true));
		} else {
			if ($debug) echo '#2.2<br>';
			foreach ($cwrapGroups as $ccwrapGroups)
				$wrapGroups[] = array('value' => $ccwrapGroups);
		}

	} else if (!empty($applyVal)){
		if ($debug) echo '#3<br>';

		$wrapGroups[] = array(
			'value' => $applyVal
		);
	}
	// --------------------------------------------------- HERE -----------!!
	// http://localhost/boe/application/api/es/boe/2017-01-05/parse
	if ($debug)
		kaosJSON($wrapGroups);
	return $wrapGroups;
}


function kaosGroupLabelLint($tr, $val){
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

function getEntityTypes(){
	return array(
		'institution' => array(
			'slug' => 'institutions',
			'title' => 'Institutions',
			'icon' => 'university',
		),
		'company' => array(
			'slug' => 'companies',
			'title' => 'Companies',
			'icon' => 'industry',
		),
		'person' => array(
			'slug' => 'people',
			'title' => 'People',
			'icon' => 'user-circle'
		),
	);
}


function kaosGetEntitySummary($entity){
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
			'html' => kaosPrintEntities($entities),
		);

	// previous names

	$entities = kaosGetPreviousEntities($entity['id']);

	if ($entities)
		$details['old_names'] = array(
			'title' => 'Old names',
			'html' => kaosPrintEntities($entities),
		);

	// date founded

	$date = get('SELECT COALESCE(b.date, b_in.date) AS date
		FROM statuses AS s
		LEFT JOIN precepts AS p ON s.precept_id = p.id
		LEFT JOIN bulletins AS b ON p.bulletin_id = b.id
		LEFT JOIN bulletin_uses_bulletin AS bb ON p.bulletin_id = bb.bulletin_id
		LEFT JOIN bulletins AS b_in ON bb.bulletin_in = b_in.id
		WHERE related_id = %s AND type = "capital" AND action IN ( "new" )
	', $entity['id']);

	if ($date)
		$details['founded'] = array(
			'title' => 'Founded',
			'value' => $date,
			'html' => date_i18n('M j, Y', strtotime($date)),
		);

	// object

	$object = get('SELECT note FROM statuses WHERE related_id = %s AND type = "object" AND action = "new" ORDER BY id DESC LIMIT 1', $entity['id']);
	if ($object)
		$details['object'] = array(
			'title' => 'Object',
			'html' => $object,
		);

	// location
	$location = get('SELECT note FROM statuses WHERE related_id = %s AND type = "location" AND action = "new" ORDER BY id DESC LIMIT 1', $entity['id']);
	$locationObj = $location ? kaosHereComConvertLocation($location, $entity['country']) : null;

	if ($location)
		$details['location'] = array(
			'title' => 'Location',
			'value' => $locationObj ? $locationObj : null,
			'html' => '<i>'.($locationObj ? kaosGetLocationLabel($locationObj) : $location).'</i>',
		);

	// creation capital

	kaosPrintEntityStat(array(
		'_type' => 'capital',
		'_action' => 'new',
		'type' => 'amount',
		'label' => 'Initial capital',
	), $entity, $details);


	// minimum capital

	$in = getRow('SELECT SUM(a.value) AS amount, a.unit FROM statuses AS s LEFT JOIN amounts AS a ON s.amount = a.id WHERE related_id = %s AND type = "capital" AND action IN ( "new", "increase" )', $entity['id']);
	$out = getRow('SELECT SUM(a.value) AS amount, a.unit FROM statuses AS s LEFT JOIN amounts AS a ON s.amount = a.id WHERE related_id = %s AND type = "capital" AND action IN ( "decrease" )', $entity['id']);
	$diff = ($in ? $in['amount'] : 0) - ($out ? $out['amount'] : 0);

	if ($diff > 0)
		$details['capital_min'] = array(
			'title' => 'Minimum capital',
			'html' => kaosPrintAmount($diff, $in ? $in['unit'] : $out['unit']),
		);

	kaosPrintEntityStat(array(
		'type' => 'related_id',
		'_type' => 'owner',
		'label' => 'Owns',
	), $entity, $details);

	kaosPrintEntityStat(array(
		'type' => 'related_id',
		'_type' => 'owner',
		'_action' => 'end',
		'label' => 'Owned',
	), $entity, $details);

	kaosPrintEntityStat(array(
		'_type' => 'owner',
		'label' => 'Owners',
	), $entity, $details);

	kaosPrintEntityStat(array(
		'type' => 'related_id',
		'_type' => 'administrator',
		'label' => 'Administrates',
	), $entity, $details);

	kaosPrintEntityStat(array(
		'type' => 'related_id',
		'_type' => 'administrator',
		'_action' => 'end',
		'label' => 'Has administrated',
	), $entity, $details);

	kaosPrintEntityStat(array(
		'_type' => 'administrator',
		'label' => 'Administrated by',
	), $entity, $details);

	kaosPrintEntityStat(array(
		'_type' => 'administrator',
		'_action' => 'end',
		'label' => 'Was administrated by',
	), $entity, $details);

	kaosPrintEntityStat(array(
		'_type' => 'auditor',
		'label' => 'Auditors',
	), $entity, $details);

	return $details;
}


function kaosGetCompanyFilter(){
	$str = array();
	foreach (explode(' ', $_GET['etype']) as $cetype){
		$etype = explode('/', $cetype);
		switch ($etype[0]){
			case 'person':
				$str[] = 'People';
				break;
			case 'institution':
				$str[] = 'Institutions';
				break;
			case 'company':
				$label = 'Companies';
				if (count($etype) > 2 && ($s = kaosGetCountrySchema(strtoupper($etype[1].'/'.$etype[2])))){
					$label = strtoupper($etype[2]).' ('.$s->name.')';
				}
				$str[] = $label;
				break;
		}
	}
	return implode(', ', $str);
}
