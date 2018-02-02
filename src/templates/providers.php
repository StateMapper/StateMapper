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

print_header();
?>
	<table class="table table-bulletins">
		<?php 
		$last = null;
		$country = null;
		
		$is_filtered = !empty($smap['filters']['loc']) ? 1 : 0;
		if ($is_filtered && ($s = get_country_schema($smap['filters']['loc'])) && $s->type == 'country')
			$is_filtered++;
		
		foreach ($smap['schemas'] as $path){ 
			$schema = get_schema($path);
			if ($schema->type == 'country')
				$country = $schema;

			$level = preg_match_all('#/#', $path) + 1;
			if ($schema->type != 'continent' && $country)
				$level++;
				
			$innerLevel = 0;
			
			if ($schema->type == 'bulletin' 
				&& $last 
				&& !empty($schema->providerId) 
				&& $schema->providerId == $last->id){
				
				$level++;
				$innerLevel++;
			}
			
			if (!empty($smap['filters']['loc']) && strtoupper($smap['filters']['loc']) == $schema->id)
				continue;
			
			?>
			<tr data-smap="<?= $path ?>" class="schema-type-<?= htmlentities($schema->type) ?> schema-level-<?= ($level - $is_filtered) ?>">
				<td><?php
				if (empty($schema))
					echo '<i class="fa fa-warning" style="color: red"></i>';
				else if ($schema->type == 'bulletin'){
					if (!empty($schema->onProgress))
						echo '<i class="fa fa-warning" style="color: orange"></i>';
					else
						echo '<i class="fa fa-check" style="color: #09d91b"></i>';
				}
				?></td>
				<td>
					<div class="schema-title">
						<div class="schema-picture">
							<?php 

							if ($url = get_schema_avatar_url($schema))
								echo '<img src="'.$url.'" />';

							?>
						</div>
						<div class="schema-intro"><?= ucfirst($schema->type) ?></div>
						<div class="schema-name">
							<?php 
							$name = $schema->name;
							if (!empty($schema->shortName) && !preg_match('#\b'.preg_quote($schema->shortName, '#').'\b#ius', $schema->name))
								$name .= ' ('.$schema->shortName.')';
								
							if (!empty($schema->searchUrl))
								echo '<a title="'.esc_attr('Go to the bulletin\'s original search form: '.$schema->searchUrl).'" href="'.anonymize($schema->searchUrl).'" target="blank">'.$name.'</a>';
							else if (!empty($schema->siteUrl))
								echo '<a title="'.esc_attr('Go to the provider\'s website: '.$schema->siteUrl).'" href="'.anonymize($schema->siteUrl).'" target="blank">'.$name.'</a>';
							else if (in_array($schema->type, array('continent', 'country')) && (empty($smap['filters']['loc']) || $schema->id != $smap['filters']['loc']))
								echo '<a title="'.esc_attr('Show only '.get_country_schema($schema)->adjective.' providers').'" href="'.get_providers_url($schema->id).'">'.$name.'</a>';
							else
								echo $name;
							?>
						</div>
					</div>
				</td>
				<td class="schema-table-actions-td">
					<div class="schema-table-actions">
						<?php if (show_mode('schema')){ ?>
							<a href="<?= url($schema->id, 'schema') ?>"title="Show the bulletin's definition <br>(<?= $path ?>)" class="action-no-date"><i class="fa fa-book"></i><span>Schema</span></a>
						<?php } ?>
						
						<?php if ($schema->type == 'bulletin'){ ?>
							
							<?php if (show_mode('fetch')){ ?>
								<a href="<?= url($schema->id, 'fetch') ?>" title="Download and display bulletins" class="<?php if (empty($schema->fetchProtocoles)) echo ' header-action-disabled'; ?>"><i class="fa fa-<?= get_mode_icon('fetch') ?>"></i><span><?= get_mode_title('fetch', true) ?></span></a>
							<?php } ?>
							
							<?php if (show_mode('parse')){ ?>
								<a href="<?= url($schema->id, 'parse') ?>" title="Display parsed information from the bulletin" class="<?php if (empty($schema->parsingProtocoles)) echo ' header-action-disabled'; ?>"><i class="fa fa-<?= get_mode_icon('parse') ?>"></i><span><?= get_mode_title('parse', true) ?></span></a>
							<?php } ?>
							
							<?php if (show_mode('extract')){ ?>
								<a href="<?= url($schema->id, 'extract') ?>" title="Extract important parts from the parsed bulletin" class="<?php if (empty($schema->extractProtocoles)) echo ' header-action-disabled'; ?>"><i class="fa fa-<?= get_mode_icon('extract') ?>"></i><span><?= get_mode_title('extract', true) ?></span></a>
							<?php } ?>
							
							<?php if (show_mode('rewind')){ ?>
								<a href="<?= url($schema->id, 'rewind') ?>" title="Fetch, parse and extract many years from a single page" class="<?php if (empty($schema->fetchProtocoles)) echo ' header-action-disabled'; ?>"><i class="fa fa-<?= get_mode_icon('rewind') ?>"></i><span><?= get_mode_title('rewind', true) ?></span></a>
								<?php
							}
							
							if (is_admin()){
								$status = get_spider_status($schema->id, 'stopped');
								if ($status != 'stopped'){
									$count = 0;
									get_workers_stats($count, $schema->id);
									?>
									<a href="<?= url($schema->id, 'rewind') ?>" class="bulletin-spider-ind" title="<?= $schema->id ?> spider is active with <?= $count ?> workers"><i class="fa fa-bug"></i><span><?= $count ?></span></a>
									<?php
								}
							}
							?>
							
						<?php } else if (in_array($schema->type, array('continent', 'country')) && strcasecmp($smap['filters']['loc'], $schema->id)){ ?>
								<a href="<?= get_providers_url($schema->id) ?>" title="Show only <?= get_country_schema($schema)->adjective ?> providers"><i class="fa fa-filter"></i><span>Filter</span></a>
						<?php } ?>
					</div>
				</td>
				<td>
					<div class="schema-table-actions">
					<?php if (show_mode('soldiers')){ // && in_array($schema->type, array('institution', 'bulletin'))){ ?>
						<a href="<?= url($schema->id, 'soldiers') ?>" title="" class="<?php if (empty($schema->soldiers)) echo ' header-action-disabled'; ?>"><i class="fa fa-<?= get_mode_icon('soldiers') ?>"></i><span><?= get_mode_title('soldiers', true) ?></span></a>
					<?php } else { ?>
						<span>&nbsp;</span>
					<?php } ?>
					<?php if (show_mode('ambassadors') && in_array($schema->type, array('country', 'continent'))){ ?>
						<a href="<?= url($schema->id, 'ambassadors') ?>" title="" class="<?php if (empty($schema->ambassadors)) echo ' header-action-disabled'; ?>"><i class="fa fa-<?= get_mode_icon('ambassadors') ?>"></i><span><?= get_mode_title('ambassadors', true) ?></span></a>
					<?php } else { ?>
						<span>&nbsp;</span>
					<?php } ?>
					</div>
				</td>
							
				<?php /*
				<!--
				<td class="schema-table-files">
					<?php
					$str = array();
					if (in_array($schema->type, array('bulletin', 'country'))){ 
						$output = array();
						
						// print amount of files
						$output = array();
						//exec('find "'.BASE_PATH.'/data/'.$path.'" -type f | grep "/byDate/" | grep -v ".pdf."  | grep -v ".xml." | wc -l', $output, $returnVar);
						if (0 && empty($returnVar) && $output && is_numeric($output[0])){
							$cstr = '<i class="fa fa-files-o"></i> '.number_format(intval($output[0]));
							/*
							$output = array();
							exec('find "'.BASE_PATH.'/data/'.$path.'" -type f | grep -v ".pdf."  | grep -v ".xml." | wc -l', $output, $returnVar);
							if (empty($returnVar) && $output && is_numeric($output[0])){
								$cstr .= ' <span class="schema-sub-bulletins">('.number_format(intval($output[0])).')</span>';
							}
							$str[] = $cstr.$rewind;
							$rewind = '';
							*
							// print folder size on disk
							if (0){
								$output = array();
								exec('du -hs "'.BASE_PATH.'/data/'.$path.'"', $output, $returnVar);
								if (empty($returnVar) && $output)
									$str[] = '<i class="fa fa-hdd-o"></i> '.preg_replace('#^(\S+)(.*?)$#', '$1', $output[0]);
							}
						} 
					} 
					if ($str) 
						echo implode('</td><td class="schema-table-size">', $str);
					if (count($str) < 2)
						echo '</td><td class="schema-table-size">';
					?>
				</td>
				*/ ?>
			</tr>
			<?php 
			if (!$innerLevel)
				$last = $schema;
		} ?>
	</table>
	
	<div>Missing a bulletin? Adding bulletin providers is easy and well documented, so you if you're a JSON/Regexp nurd, don't hesitate to participate as a Schema Soldier!</div>
<?php 
print_footer();
