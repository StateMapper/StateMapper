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


$types = get_entity_types();
$modes = get_modes();

?>
<div class="main-header">
	<div class="main-header-inner">
		<?php 
		print_template('parts/header_logo'); 
		
		if (!is_home()){
			?>
			<div class="header-center-wrap">
				<div class="header-center"><?php
					if (!empty($smap['query']['schema']) && (!defined('IS_ERROR') || !IS_ERROR)){
						if (file_exists(SCHEMAS_PATH.'/'.$smap['query']['schema'].'.png')){
							?>
							<img class="header-center-bulletin-avatar" src="<?= BASE_URL.'schemas/'.$smap['query']['schema'].'.png' ?>" />
							<?php
						}
					}
					
					?>
					<div class="main-title-inner">
						<?php
						if (!is_home())
							print_template('parts/search_input');
							
						if (has_filter_bar()){
							if (!has_filter())
								echo '<span class="header-filter-ind" title="'.esc_attr(_('Add a filter')).'">+ '._('Filters').'</span>';
							else
								echo '<a title="'.esc_attr(_('Remove all filters')).'" class="header-filter-ind" href="'.add_lang(BASE_URL.(!empty($_GET['q']) ? '?q='.$_GET['q'] : '')).'">- '._('Filters').'</a>';
						}
						?>
					</div>
				</div>
			</div>
			<?php 
		}
		print_template('parts/header_right') 
		?>
	</div>
</div>
<?php

print_template('parts/filters'); 
