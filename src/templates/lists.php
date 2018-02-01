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

?>
<h1>My lists:</h1>

<div class="page-content">
	<div class="lists">
		<?php 

		if ($lists){
			foreach ($lists as $list)
				print_template('parts/list', array('list' => $list));

		} else {
			?>
			<div class="search-results-none">You don't have any list saved.</div>
			<?php
		}
		?>
		<div class="list-new-wrap"><button class="list-new-button" data-smap-prompt="<?= esc_attr(_('Please give the new list a name:')) ?>">New list</button></div>
	</div>
</div>
<?php

print_footer();

