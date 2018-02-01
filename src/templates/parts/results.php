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

global $smap;

$i = 0;
$last = null;
foreach ($results['items'] as $r){
	$last = $r;
	?>
	<div class="<?= ($i == $results['count']-1 ? 'last' : '') ?> result" <?= related(array('entity_id' => $r['id'])) ?>>
		<a href="<?= get_entity_url($r) ?>">
			<span class="inline-entity"><i class="result-icon fa fa-<?= get_entity_icon($r) ?>"></i><span><?= get_entity_title($r) ?></span></span>

			<?php
			if (0){
				$summary = get_entity_summary($r, 'list');

				// TODO: factorize with template/Entity.php stats! (try to grab everything at once, or precache to entity table)

				$details = array();
				
				// founded
				$date = get_var('SELECT b.date AS date
					FROM statuses AS s
					LEFT JOIN precepts AS p ON s.precept_id = p.id
					LEFT JOIN bulletins AS b ON p.bulletin_id = b.id
					WHERE related_id = %s AND type = "capital" AND action = "new"
				', $r['id']);

				if ($date)
					$details[] = '<span class="entity-line-detail"><span class="entity-line-label">'._('Founded').': </span><span class="entity-line-body">'.date_i18n('Y', strtotime($date)).'</span></span>';

				$object = get_var('SELECT note FROM statuses WHERE related_id = %s AND type = "object" AND action = "new" ORDER BY id DESC LIMIT 1', $r['id']);

				if ($object)
					$details[] = '<span class="entity-line-detail"><span class="entity-line-label">'._('Object').': </span><span class="entity-line-body"><i>"'.$object.'"</i></span></span>';
					
				if (!empty($summary['fund']))
					$details[] = '<span class="entity-line-detail"><i class="fa fa-'.$summary['fund']['icon'].'"></i> '.format_number_nice($summary['fund']['originalAmount'] / 100, false).'</span>';
				
				if (!empty($summary['location']))
					$details[] = '<span class="entity-line-detail"><i class="fa fa-map-marker"></i> '.$summary['location']['html'].'</span>';
				
				if (!empty($summary['funding']))
					$details[] = '<span class="entity-line-detail"><i class="fa fa-'.$summary['funding']['icon'].'"></i> '.format_number_nice($summary['funding']['originalAmount'] / 100, false).'</span>';

				if ($details)
					echo '<span class="entity-line-details">'.implode(' / ', $details).'</span>';
			}
			?>
		</a>
	</div>
	<?php
	$i++;
}

if (!empty($results['left'])){
	$autoload = !empty($smap['query']['loaded_count']);
	$related = array(
		'after_id' => $last['id'], 
		'loaded_count' => $results['count'] + (!empty($smap['query']['loaded_count']) ? $smap['query']['loaded_count'] : 0)
	);

	$loader = '<div class="infinite-loader infinite-autoload loading" '.related($related).'>'.get_loading().'</div>';
	
	if ($autoload){
		echo $loader;
		
	} else {
		?>
		<a class="infinite-loader" href="#" data-smap-loading="<?= esc_attr($loader) ?>" <?= related($related) ?>><?= __('Load more') ?> <i class="fa fa-angle-down"></i></a>
		<?php
	}

} else if (is_search()){
	?>
	<div class="results-outro"><a href="#"><i class="fa fa-search"></i> Edit search..</a></div>
	<?php
}
	
	
