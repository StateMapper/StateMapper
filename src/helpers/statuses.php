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
	
	
function query_statuses($query){
	$statuses = array();

	$query += array(
		'type' => null,
		'action' => null,
		'entity_id' => null,
		'ids' => array(), // entity IDs
		'date' => null,
		'include_other_names' => true,
		'target' => null,
		'related' => null,
		'issuing' => null,
	);

	if ((empty($query['entity_id']) && empty($query['ids'])) || ($query['entity_id'] && !is_numeric($query['entity_id'])))
		return array();

	if (!empty($query['ids']))
		foreach ($query['ids'] as $id)
			if (!is_numeric($id))
				return array();

	if ($query['include_other_names'])
		$query['ids'] = array_merge($query['ids'], get_other_entities($query['entity_id']));
	else if (!empty($query['entity_id']))
		$query['ids'][] = $query['entity_id'];
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

			WHERE s.target_id IN ( '.implode(', ', $query['ids']).' ) '.$where.'
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
	return array_values($statuses);
}

function get_entity_activity($entity, $fill_statuses = false){
	$ids = get_other_entities($entity['id']);
	
	// statuses where the entity is the target entity
	$activity = query('
		SELECT "target" AS rel, YEAR(b.date) AS date, s.type AS _type, s.action AS _action,
		SUM(a.original_value) AS amount, a.original_unit AS unit, COUNT(s.id) AS count, COUNT(s.related_id) AS related

		FROM precepts AS p
		LEFT JOIN statuses AS s ON p.id = s.precept_id
		LEFT JOIN bulletins AS b ON p.bulletin_id = b.id
		LEFT JOIN amounts AS a ON s.amount = a.id

		WHERE s.target_id IN ( '.implode(', ', $ids).' )
			AND s.type IS NOT NULL
			AND s.action IS NOT NULL
		GROUP BY YEAR(b.date), s.type, s.action
		ORDER BY b.date DESC, s.type = "name" ASC
	');
	
	// statuses where the entity is the related entity
	$activity = array_merge($activity, query('
		SELECT "related" AS rel, YEAR(b.date) AS date, s.type AS _type, s.action AS _action,
		SUM(a.original_value) AS amount, a.original_unit AS unit, COUNT(s.id) AS count, COUNT(s.target_id) AS target

		FROM precepts AS p
		LEFT JOIN statuses AS s ON p.id = s.precept_id
		LEFT JOIN bulletins AS b ON p.bulletin_id = b.id
		LEFT JOIN amounts AS a ON s.amount = a.id

		WHERE s.related_id IN ( '.implode(', ', $ids).' )
			AND s.type IS NOT NULL
			AND s.action IS NOT NULL
		GROUP BY YEAR(b.date), s.type, s.action
		ORDER BY b.date DESC, s.type = "name" ASC

	'));
	
	// statuses where the entity is the issuing entity
	$activity = array_merge($activity, query('
		SELECT "issuing" AS rel, YEAR(b.date) AS date, s.type AS _type, s.action AS _action,
		SUM(a.original_value) AS amount, a.original_unit AS unit, COUNT(s.id) AS count, COUNT(s.target_id) AS target

		FROM precepts AS p
		LEFT JOIN statuses AS s ON p.id = s.precept_id
		LEFT JOIN bulletins AS b ON p.bulletin_id = b.id
		LEFT JOIN amounts AS a ON s.amount = a.id

		WHERE p.issuing_id IN ( '.implode(', ', $ids).' )
			AND s.type IS NOT NULL
			AND s.action IS NOT NULL
		GROUP BY YEAR(b.date), s.type, s.action
		ORDER BY b.date DESC, s.type = "name" ASC

	'));

	if ($fill_statuses){
		foreach ($activity as &$a){
			
			// add detailed statuses
			$a['statuses'] = query_statuses(array(
				'ids' => get_other_entities($entity['id']),
				'date' => $a['date'],
				'type' => $a['_type'],
				'action' => $a['_action'],
			));
			
		}
		unset($a);
	}
	
	return $activity;
}



function get_entity_summary($entity){
	$ids = get_other_entities($entity['id']);
	
	// if numeric, calc (last "update" + SUM("increase") - SUM("decrease"))
	// if a string, take the last "update"
	// if entities, ...
	
	$summary = array();
	
	// query the foundation status
	$summary['founded'] = get_row('
		SELECT b.date, SUM(a.original_value) AS amount, a.original_unit AS unit
		
		FROM precepts AS p
		LEFT JOIN statuses AS s ON p.id = s.precept_id
		LEFT JOIN bulletins AS b ON p.bulletin_id = b.id
		LEFT JOIN amounts AS a ON s.amount = a.id
		
		WHERE s.related_id = %s AND s.type = "capital" AND s.action = "new" ORDER BY b.date ASC LIMIT 1', 
		$entity['id']);
	
	foreach (array(
		's.related_id' => 'related',
		's.target_id' => 'target',
		//'p.issuing_id' => 'issuing',
	) as $db_key => $key){
			
		// query the last "update" or "new" of each _type
		$statuses = query('
			SELECT b.date, s.type AS _type, s.action AS _action,
			a.original_value AS amount, a.original_unit AS unit, s.note, s.location_id, s.target_id, s.related_id

			FROM precepts AS p
			LEFT JOIN statuses AS s ON p.id = s.precept_id
			LEFT JOIN bulletins AS b ON p.bulletin_id = b.id
			LEFT JOIN amounts AS a ON s.amount = a.id

			WHERE '.$db_key.' IN ( '.implode(', ', $ids).' )
				AND s.action IN ( "update", "new", "increase", "decrease" )
			GROUP BY s.type, s.action, s.target_id, s.related_id, p.issuing_id
			ORDER BY b.date DESC, s.type = "name" ASC
		');
		
		foreach ($statuses as $s){
			if ($s['amount'] === null){
				if (!in_array($s['_action'], array('update', 'new')))
					continue;

				if ($s['target_id'] && $s['target_id'] != $entity['id']){
					$summary[$s['_type']][$key]['current'][] = get_entity_by_id($s['target_id']);
					continue;
				}
					
				if ($s['related_id'] && $s['related_id'] != $entity['id']){
					$summary[$s['_type']][$key]['current'][] = get_entity_by_id($s['related_id']);
					continue;
				}
					
				$current = array(
					'date' => $s['date'],
					'note' => $s['note'],
				);
				if ($s['_type'] == 'location')
					$current['location_id'] = $s['location_id'];
				
				$summary[$s['_type']][$key] = array(
					'current' => $current,
				);
				continue;
			}
			
			if (in_array($s['_action'], array('update', 'new'))){
				$summary[$s['_type']][$key] = array(
					'last' => array(
						'date' => $s['date'],
						'amount' => $s['amount'],
						'unit' => $s['unit'],
					)
				);
			
			} else 
				$summary[$s['_type']][$key] = array();
		
			// calculate the sum of "new", "update", "increase" and "decrease" after the last "new" or "update", for this type
			$summary[$s['_type']][$key]['current'] = get_row('
				SELECT SUM(a.original_value) AS amount, a.original_unit AS unit

				FROM precepts AS p
				LEFT JOIN statuses AS s ON p.id = s.precept_id
				LEFT JOIN bulletins AS b ON p.bulletin_id = b.id
				LEFT JOIN amounts AS a ON s.amount = a.id

				WHERE '.$db_key.' IN ( '.implode(', ', $ids).' )
					AND s.type = %s
					AND s.action IN ( "new", "update", "increase", "decrease" )
					AND b.date >= %s
				GROUP BY s.type
				ORDER BY b.date DESC, s.type = "name" ASC
			', array($s['_type'], $s['date']));
			
		}
		
		$statuses = query('
			SELECT b.date, s.type AS _type, s.action AS _action,
			a.original_value AS amount, a.original_unit AS unit, s.note, s.location_id, s.target_id, s.related_id

			FROM precepts AS p
			LEFT JOIN statuses AS s ON p.id = s.precept_id
			LEFT JOIN bulletins AS b ON p.bulletin_id = b.id
			LEFT JOIN amounts AS a ON s.amount = a.id

			WHERE '.$db_key.' IN ( '.implode(', ', $ids).' )
				AND s.action IN ( "start", "keep", "end" )
			GROUP BY s.type, s.action, s.target_id, s.related_id
			ORDER BY b.date DESC, s.type = "name" ASC
		');
		
		foreach ($statuses as $s){
			if ($e = get_entity_by_id($s['related_id'] == $entity['id'] ? $s['target_id'] : $s['related_id']))
				$summary[$s['_type']][$key][$s['_action'] == 'end' ? 'past' : 'current'][] = $e + array(
					'rel' => $s['related_id'] == $entity['id'] ? 'target' : 'related',
				);
			// otherwise, it's an error..
				
		}
	}
	
	return apply_filters('entity_summary', $summary, $entity, 'sheet-top');
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
					$countrySchema = get_schema($schema);
					
					$locationObj = apply_filters('location_lint', null, $p['note'], $countrySchema);
					$note = $locationObj ? get_location_label($locationObj, 'status') : $note;
				}
				if ($note != '')
					$note = '<span class="status-note">"'.$note.'"</span>';
			}

			$label = strtr($label, array(
				'[target]' => get_entity_title_html($p['target_id'], array(
					'icon' => true,
					'class' => 'status-title-tag status-target',
				)),
				'[related]' => get_entity_title_html($p['related_id'], array(
					'icon' => true,
					'class' => 'status-title-tag status-related',
				)),
				'[issuing]' => get_entity_title_html($p['issuing_id'], array(
					'icon' => true,
					'class' => 'status-title-tag status-issuing',
				)),
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

			$isAmount = $smap['mem']['amount']['original_value']
				&& $val == $smap['mem']['amount']['original_value'];

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

		if ($otherEntities = parse_entities(array('strict' => true), $text, $p['bulletin_schema'], array('_type' => 'other', 'country' => get_schema($p['bulletin_schema'])->id)))
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

		$countEntities = count($entities);

		$docQuery = array(
			'schema' => $schema->id,
			'date' => $p['date'],
			'id' => $p['external_id'],
		);

		$topDocQuery = $docQuery;
		if (!empty($docQuery['id']))
			unset($topDocQuery['id']);

		?>
		<div class="entity-stat" data-smap-related="<?= esc_json(array('type' => $p['_type'], 'action' => $p['_action'])) ?>">
			<div class="status-body" <?= related(array('status_id' => $p['status_id'])) ?>>

				<div class="status-title">
					<?php if ($printAsTopLevel){ ?>
						<i class="status-icon fa fa-<?= ($icon ? $icon : 'info-circle') ?>"></i>
					<?php } ?>
					<span class="status-type"><?= $label ?></span> 
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
					<span class="statuses-date" title="<?= esc_attr(date_i18n('M jS, Y', strtotime($date))) ?>"><i class="fa fa-clock-o"></i> <?= time_ago($date) ?></span>
					<?= get_buggy_button('status', 'Mark this status as buggy') ?>
					<span class="status-bulletin"><a title="<?= esc_attr('Published in '.$schema->name.' from '.date_i18n('M j, Y', strtotime($date)).'. <br>Click to show the original bulletin document.') ?>" href="<?= url($docQuery, 'fetch') ?>" target="_blank"><i class="fa fa-book"></i><?= $schema->shortName ?></a><span title="Click to unfold the original text" class="extract-link"><i class="fa fa-angle-down"></i></span></span><?php echo implode('', $icons); ?>
				</div>
				<div class="folding">
					<div class="extract">
						<div class="extract-header">
							<?php
							if (!empty($p['issuing_id']) && ($issuing = get_entity_by_id($p['issuing_id'])))
								$pat = 'Published by '.get_entity_title_html($issuing, array(
									'append_country' => true,
									'limit' => 50,
								)).' as part of the %s';
							else if (!empty($docQuery['id']))
								$pat = 'Original text from %s';
							else
								$pat = 'Below is the %s';

							if (!empty($docQuery['id'])){
								$inner = get_format_label($docQuery, 'document').' <a href="'.url($docQuery, 'fetch').'" target="_blank">'.$p['external_id'].'</a> from <a href="'.url($topDocQuery, 'fetch').'" target="_blank">'.date_i18n('M j, Y', strtotime($docQuery['date'])).'</a>.';
								
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
		</div>
		<?php
	}
}

function print_entity_statuses($statuses, $target, $query){
	$labels = get_status_labels();
	$date = null;
	$items = array();
	$count = 0;
	$otherIds = get_other_entities($target['id']);

	foreach ($statuses as $s){
		if (!$date || $s['date'] != $date){
			if ($date)
				print_entity_statuses_for_date($date, $items, $count, $target, $query);
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
			
			$statuses = array();
			$cquery = array(
				'type' => $s['_type'], 
				'action' => $s['_action'],
				'date' => $s['date'],
			
			) + ($s['rel'] == 'target' ? array(
				'target' => true
			
			) : array(
				'related' => true
			
			)) + $query;
					
			if ($s['count'] < 2){
				$statuses = $s['statuses'];

				if (empty($statuses))
					print_inline_error('no status returned ( < 2)');
				if (count($statuses) >= 2)
					print_inline_error('bad status count');
			}
			
			if ($s['count'] < 2 && $s['statuses']){
				ob_start();
				print_statuses($s['statuses'], $target, $query['id'], array('date' => $date), true);
				$item .= ob_get_clean();
				
			} else {
				
				$item .= '<div class="entity-stat entity-stat-group" data-smap-related="'.esc_json(array('type' => $s['_type'], 'action' => $s['_action'])).'">';
			
				$item .= '<div class="status-title"><i class="status-icon fa fa-'.$icon.'"></i> '.strtr($config->stats, array(
					'[count]' => '<span class="status-count">'.number_format($s['count'], 0).'</span>',
					'[amount]' => '<span class="status-amount">'.number_format($s['amount']/100, 2).' '.$s['unit'].'</span>', // could be calculated better....
				)).' <i class="fa fa-angle-right entity-stat-children-filled-ind" title="Unfold statuses"></i><i class="fa fa-spinner fa-pulse entity-stat-children-loading-ind"></i></div>';
				
				$item .= '</div>'; // close the entity-stat div

				if ($s['count'] <= 5){
					$statuses = $s['statuses'];//query_statuses($cquery);
					
					if (empty($statuses))
						print_inline_error('no status returned ( <= 5)');
						
					ob_start();
					print_statuses($statuses, $target, $query['id'], array('date' => $date));
					$item .= '<div class="entity-stat-children-holder"><div class="entity-stat-children">'.ob_get_clean().'</div></div>';
				} else
					$item .= '<div class="entity-stat-children-holder"></div>';
			}

			$item .= '</div>'; // close entity-stat-wrap div
			
			$items[] = $s + array('html' => $item);
		} else
			echo 'missing label for '.print_json($s, false).'<br>';
	}
	if ($date)
		print_entity_statuses_for_date($date, $items, $count, $target, $query);
}

function print_entity_statuses_for_date($date, $items, $count, $target, $query){
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
	echo '<div class="entity-stat-date" '.related(array('date' => $date)).'><span class="entity-stat-date-ind entity-stat-left">'.($date == date('Y') ? 'Last activity' : sprintf(__('Activity in %s'), $date)).'</span><div class="entity-stat-right">';
	foreach ($items as $s)
		echo $s['html'];
	echo '</div></div>';
}


function smap_ajax_search($args){
	$count = 0;
	$results = query_any(array(
		'q' => $args['query'],
		'limit' => 30,
		'include' => array('entity', 'bulletin', 'provider', 'location'),
	), $left);
	
	ob_start();
	if ($results){

		$vars = array('results' => $results, 'args' => $args, 'count' => count($results) + $left);
		$html = get_template('parts/autocomplete_results', $vars);
		$more = get_template('parts/autocomplete_footer', $vars);
		
	} else {
		$html = '<div class="sugg-none">'.__('Nothing found').'</div>';
		$more = false;
	}
	
	return array('success' => true, 'results' => $html, 'resultsMore' => $more);
}

function smap_ajax_load_statuses($args){
	
	if (empty($args['related']) || empty($args['related']['entity_id']) || !($target = get_entity_by_id($args['related']['entity_id'])))
		return 'Bad id';
	
	$statuses = query_statuses($args['related']);

	ob_start();
	echo '<div class="entity-stat-children">';
	print_statuses($statuses, $target, $args['related']['entity_id'], isset($args['related']['date']) ? array('date' => $args['related']['date']) : array());
	echo '</div>';

	return array('success' => true, 'html' => ob_get_clean());
}


function smap_ajax_load_more_results($args){
	global $smap;
	$smap['query']['loaded_count'] = $args['loaded_count'];
	$smap['query']['after_id'] = $args['after_id'];
	$smap['page'] = 'browser';
	
	$res = get_results();
	
	ob_start();
	print_template('parts/results', array('results' => $res));
	$html = ob_get_clean();
	
	return array('success' => true, 'results' => $html, 'resultsLabel' => get_results_count_label($res['count'] + $args['loaded_count'], $res['total']));
}

