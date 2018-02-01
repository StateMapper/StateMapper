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

$modes = get_modes();
?>
<div class="list" <?= related(array('list_id' => $list['id'], 'list_title' => $list['name'])) ?>>
	<div class="list-header">
		<h2 class="list-title"><i class="fa fa-<?= $modes['lists']['icon'] ?> icon"></i> <span class="seemless"><?= $list['name'] ?></span></h2>
		<div class="title-icons">
			<a class="list-add" href="#" title="Add an entity to this list"><i class="fa fa-plus"></i> Entity</a>
			<a class="list-rename" href="#" title="Rename this list" data-smap-prompt="<?= esc_attr(_('Please enter a new name for the list')) ?>"><i class="fa fa-pencil"></i></a>
			<a class="list-delete delete" title="Delete this list" href="#" data-smap-prompt="<?= esc_attr(_('Are you SURE you want to DELETE THIS LIST? This CANNOT be undone!')) ?>"><i class="fa fa-trash"></i></a>
		</div>
	</div>
	
	<div class="list-content">
		<?php 
		if ($list['entities']){ 
			?>
			<div class="list-entities entities-list">
				<?php
				print_template('parts/results', array('results' => array(
					'items' => $list['entities'],
					'count' => count($list['entities']),
					'total' => count($list['entities']),
					'left' => 0,
				)));
				?>
			</div>
			<?php
		}
		?>
	</div>
</div>
<?php
