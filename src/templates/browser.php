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

print_header('browser');

if (!empty($results) && !empty($results['count'])){

	?>
	<div class="search-wrap">
		<div class="results-count"><?php
			echo get_results_count_label($results['count'], $results['total']);
		?></div>
		<h1><?php
			echo get_page_title().':';
		?></h1>
		
		<div class="page-content">
			<div class="results entities-list">
				<?php print_template('parts/results', array('results' => $results)) ?>
			</div>
		</div>
	</div>
	<?php

} else {
	?>
	<h1><?php

		$item = 'entities';
		
		if (!empty($smap['filters']['etype']) && strpos($smap['filters']['etype'], ' ') === false && isset($types[$smap['filters']['etype']]))
			$item = $types[$smap['filters']['etype']]['plural'];
			
		if ($smap['filters']['q'] == '')
			echo sprintf(_('No %s to show.'), $item);
		else
			echo sprintf(_('No %s found for "%s".'), $item, htmlentities($smap['filters']['q']));
	
	?></h1>
	
	<div class="page-content">
		<div class="search-results-none"><?php
			
			if (has_filter())
				echo _('Check your query or try removing some filters...');
			else
				echo _('Check your query...');

		?></div>
	</div>
	<?php
}

print_footer();
