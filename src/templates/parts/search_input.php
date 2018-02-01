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
	
$tip = _('Lookup for a company, a person..');

?>
<div class="browser-title-title">
	<form action="<?= BASE_URL ?>" method="GET">
		<span class="search-icon"><i class="fa fa-search"></i></span>
		<input type="text"<?php
			
			echo ' '.related(array('loading' => get_search_loading()));
			
			if (is_home())
				echo ' title="'.esc_attr($tip).'" data-tippy-placement="right"';
			
			?> autocomplete="off" name="q" id="search-input" placeholder="<?php
			
			if (!is_home())
				echo esc_attr($tip);
			
			?>" value="<?= (!empty($smap['query']['q']) ? esc_attr($smap['query']['q']) : '') ?>" />
		<div class="search-suggs">
			<div class="search-suggs-inner">
				<div class="search-suggs-loading-msg">
					<div><?= get_loading() ?></div>
				</div>
				<div class="search-suggs-results">
					<div class="search-suggs-results-inner"></div>
					<div class="search-suggs-results-more"></div>
				</div>
			</div>
		</div>
	</form>
</div>
