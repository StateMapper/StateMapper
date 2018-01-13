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

$title = get_page_title();

print_template('parts/header');
?>
<div class="main-header<?php echo ' header-avatar-'.($avatar ? 'has' : 'none'); ?>">
	<div class="main-header-inner">
		<?php print_template('parts/header_logo') ?>
		<div class="header-center-wrap">
			<div class="header-center">
				<div class="header-center-inner">
					<div class="header-center-title header-title">
						API Preview: <?= $title ?>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<div class="api-human-content">
	<?php echo print_json($obj); ?>
</div>
<?php
print_template('parts/footer');
