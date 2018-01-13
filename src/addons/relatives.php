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



add_action('entity_stats_after', 'relatives_entity_suggs');
function relatives_entity_suggs($entity){
	
	if ($entity['type'] != 'person')
		return;
		
	$likes = $order = array();
	foreach (explode(' ', $entity['name']) as $name){
		$nameInner = mb_strtolower(remove_accents($name));
		
		$likes[] = 'e.name LIKE '.esc_like($nameInner.' ', 'right');
		$likes[] = 'e.name LIKE '.esc_like(' '.$nameInner, 'left');
		$likes[] = 'e.name LIKE '.esc_like(' '.$nameInner.' ', 'both');
		
		$order[] = 'e.name LIKE '.esc_like($nameInner.' ', 'right').' DESC';
		$order[] = 'e.name LIKE '.esc_like(' '.$nameInner, 'left').' DESC';
	}
	
	if (!$likes)
		return;
	$relatives = query('SELECT e.id, e.country, e.slug, e.type, e.name, e.first_name FROM entities AS e WHERE e.type = "person" AND ( '.implode(' OR ', $likes).' ) AND e.id != %s ORDER BY '.implode(', ', $order).', e.name ASC, e.first_name ASC', $entity['id']);
				
	if ($relatives){ 
		?>
		<div class="entity-sheet-detail entity-relatives">
			<div class="entity-sheet-label">Possible relatives: </div>
			<div class="entity-sheet-body">
				<div class="entity-relatives-inner">
					<ul><?php

						$i = 0;
						foreach ($relatives as $e){
							$title = get_entity_title($e);
							foreach (explode(' ', $entity['name']) as $name)
								$title = preg_replace('#'.preg_quote($name, '#').'#ius', '<strong>'.mb_strtoupper($name).'</strong>', $title);
							echo '<li><a href="'.get_entity_url($e).'">'.$title.'</a>';
							
							$common = array();
							foreach (get_entities_commons($entity, $e) as $c)
								$common[] = '<a href="'.get_entity_url($c).'">'.get_entity_title($c).'</a>';
							if ($common)
								echo '<div class="entity-relatives-common"><i class="fa fa-angle-right"></i> '.count($common).' linking companies: '.implode(', ', $common).'</div>';
							
							echo '</li>';
							$i++;
						}
					?></ul>
				</div>
			</div>
		</div>
		<?php
	}
}


function get_entities_commons($e1, $e2){
	return query('
		SELECT c.id, c.type, c.subtype, c.name, c.slug, c.country
		FROM entities AS e1
		LEFT JOIN statuses AS s1 ON e1.id = s1.target_id OR e1.id = s1.related_id
		LEFT JOIN entities AS c ON s1.related_id = c.id OR s1.target_id = c.id
		LEFT JOIN statuses AS s2 ON c.id = s2.target_id OR c.id = s2.related_id
		LEFT JOIN entities AS e2 ON s2.related_id = e2.id OR s2.target_id = e2.id
		WHERE e1.id = %s AND e2.id = %s AND c.id != e1.id AND c.id != e2.id
		GROUP BY c.id
	', array($e1['id'], $e2['id']));
}
