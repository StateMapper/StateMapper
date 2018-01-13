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

global $smap;

$count = count($smap['results']);
$i = 0;
$last = null;
foreach ($smap['results'] as $r){
	$last = $r;
	?>
	<div class="<?= ($i == $count-1 ? 'last' : '') ?> result">
		<a href="<?= get_entity_url($r) ?>">
			<span><i class="result-icon fa fa-<?= get_entity_icon($r) ?>"></i><span><?= get_entity_title($r) ?></span></span>

			<?php

				// TODO: factorize with template/Entity.php stats! (try to grab everything at once, or precache to entity table)

				$details = array();

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

				if ($details)
					echo '<span class="entity-line-details">'.implode(' / ', $details).'</span>';
			?>
		</a>
	</div>
	<?php
	$i++;
}

if ($smap['resultsLeft']){
	?>
	<div class="infinite-loader" <?= related(array('after_id' => $last['id'], 'loaded_count' => count($smap['results']) + (!empty($smap['query']['loaded_count']) ? $smap['query']['loaded_count'] : 0))) ?>><i class="fa fa-circle-o-notch fa-spin"></i> Loading..</div>
	<?php

} else {
	?>
	<div class="results-outro"><a href="#"><i class="fa fa-search"></i> Edit search..</a></div>
	<?php
}
	
	
