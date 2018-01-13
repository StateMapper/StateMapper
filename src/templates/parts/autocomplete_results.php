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

foreach ($results as $r){

	$title = get_entity_title($r, true);
	foreach (explode(' ', $args['query']) as $w){
		$title = preg_replace('#'.preg_quote(htmlentities($w), '#').'#ius', '<b>$0</b>', $title); // highlight search terms
		$title = preg_replace('#<b>([^<]*)<b>([^<]*)</b>([^<]*)</b>#ius', '<b>$1$2$3</b>', $title); // reduce <b><b></b></b> to outer <b></b>
	}
	?>
	<div><a href="<?= get_entity_url($r) ?>"><i class="fa fa-<?= get_entity_icon($r) ?>"></i><span><?= $title ?><?php if ($r['subtype'] || $r['country']){ ?><span class="searchSugg-sugg-metas<?php if ($r['subtype']) echo ' search-sugg-flag-more'; ?>"><?= ($r['subtype'] ? $r['subtype'] : '') ?><?= ($r['country'] ? '<img class="sugg-flag" src="'.get_flag_url($r['country']).'" />' : '') ?></span><?php } ?></span></a></div>
	<?php
}
