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

print_header('page');

if (empty($ambassadors)){
	echo 'No Country Ambassadors are currently defined for this schema. Please, help this project <a href="'.anonymize(get_repository_url('blob/master/documentation/manuals/AMBASSADORS.md#top')).'" target="_blank">enrolling as an Ambassador now</a>!';

} else {
	?>
	<?= number_format(count($ambassadors)) ?> Ambassadors defined for <?= $schema->name ?>:
	<table class="table">
		<?php
		foreach ($ambassadors as $s){
			?><tr><td>
				<div><?= $s->name ?></div>
				<?php if (!empty($s->users)){ ?>
					<div>
						<?php foreach ($s->users as $u){ ?>
							<a href="https://github.com/<?= $u ?>" target="_blank"><i class="fa fa-github"></i> <?= $u ?></a>
						<?php } ?>
					</div>
				<?php } ?>
				<?php if (!empty($s->nodes)){ ?>
					<div>
						<?php foreach ($s->nodes as $u){ ?>
							<a href="https://ipfs.io/<?= $u ?>" target="_blank"><i class="fa fa-globe"></i> <?= $u ?></a>
						<?php } ?>
					</div>
				<?php } ?>
			</td></tr><?php
		}
	?>
	</table>
	<?php
}

print_footer();
