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
	
if (!has_filter_bar())
	return;
	
$cbs = get_multisel_cbs();

?>
<div class="header-filters"<?php if (has_filter()) echo ' style="display: block"'; ?>>
	<ul>
		<?php if (empty($smap['entity'])){ 
			$loc = !empty($smap['filters']['loc']) ? explode(' ', $smap['filters']['loc']) : array(); 
			?>
			<li id="top-filter-location" class="menu<?php if ($loc) echo ' top-filter-active'; ?>"><span class="menu-button"><?php
				
				if ($loc)
					echo implode(', ', get_locations_label($loc, true));
				else
					echo 'Location';

				?> <i class="fa fa-angle-down"></i></span><?php
				?>
				<div class="top-filter-menu menu-wrap multisel">
					<div class="menu-menu">
						<ul class="menu-inner">
							<li class="menu-item-blank <?php if (!$loc) echo 'menu-item-active'; ?>"><a href="<?= get_filter_url(array('loc' => false)) ?>">Any</a></li>
							<?php
							
								foreach (get_schemas() as $l){
									$l = get_schema($l);
									if (!in_array($l->type, array('continent', 'country')))
										continue;
									
									$cid = strtolower($l->id);
									?>
									<li class="<?php if ($l->type == 'country') echo 'menu-item-level-2 '; if (in_array($cid, $loc)) echo 'menu-item-active'; ?>"><a href="<?= get_filter_url(array(
										'loc' => $cid
									)) ?>"><?= $cbs ?> <?= htmlentities($l->name) ?></a></li>
									<?php
									
									foreach (query('SELECT id, slug, name FROM location_states WHERE country = %s', $l->id) as $s){
										$cid = strtolower($l->id).'/'.$s['slug'];
										?>
										<li class="menu-item-level-3 <?php if (in_array($cid, $loc)) echo 'menu-item-active'; ?>"><a href="<?= get_filter_url(array(
											'loc' => $cid
										)) ?>"><?= $cbs ?> <?= htmlentities($s['name']) ?></a></li>
										<?php
										
										foreach (query('SELECT id, slug, name FROM location_counties WHERE country = %s AND state_id = %s', array($l->id, $s['id'])) as $c){
											$cid = $cid.'/'.$c['slug'];
											?>
											<li class="menu-item-level-4 <?php if (in_array($cid, $loc)) echo 'menu-item-active'; ?>"><a href="<?= get_filter_url(array(
											'loc' => $cid
										)) ?>"><?= $cbs ?> <?= htmlentities($c['name']) ?></a></li>
											<?php
											
											foreach (query('SELECT id, slug, name FROM location_cities WHERE country = %s AND state_id = %s AND county_id = %s', array($l->id, $s['id'], $c['id'])) as $cc){
												
												$cid = $cid.'/'.$cc['slug'];
												?>
												<li class="menu-item-level-5 <?php if (in_array($cid, $loc)) echo 'menu-item-active'; ?>"><a href="<?= get_filter_url(array(
													'loc' => $cid
												)) ?>"><?= $cbs ?> <?= htmlentities($cc['name']) ?></a></li>
												<?php
											}
										}
									}
								}
							?>
						</ul>
					</div>
				</div>
				<?php
			?></li>
			<?php
				$etype = !empty($smap['filters']['etype']) ? explode(' ', strtolower($smap['filters']['etype'])) : array();
				$types = get_entity_types(); 
			?>
			<li id="top-filter-entity-type" class="menu<?php if (!empty($smap['filters']['etype'])) echo ' top-filter-active'; ?>"><span class="menu-button"><?php
				
				if (!empty($smap['filters']['etype']))
					echo get_company_label(true);
				else
					echo 'Entity type';
					
			?> <i class="fa fa-angle-down"></i></span><?php
				?>
				<div class="top-filter-menu menu-wrap multisel">
					<div class="menu-menu">
						<ul class="menu-inner">
							<li class="menu-item-blank <?php if (!$etype) echo 'menu-item-active'; ?>"><a href="<?= get_filter_url(array('etype' => false)) ?>">Any</a></li>
							<?php 
							foreach ($types as $type => $c){ 
								$count = query_entities(array(
									'etype' => $type,
									'esubtype' => null,
									'country' => !empty($smap['filters']['loc']) ? $smap['filters']['loc'] : null,
									'count' => true
								));
								?>
								<li class="<?php if (in_array($type, $etype)) echo 'menu-item-active'; ?>"><a href="<?= get_filter_url(array(
									'etype' => $type,
								)) ?>"><span><?= $cbs ?> <?= ucfirst($c['plural']) ?> <span class="menu-item-right"><?= format_number_nice($count) ?></span></span></a></li>
								<?php 

								if ($type == 'company'){
									$subtypes = query('SELECT country, subtype, COUNT(*) AS count FROM entities WHERE type = "company" AND subtype IS NOT NULL GROUP BY country, subtype '.(!empty($smap['filters']['loc']) ? prepare('AND country = %s ', strtoupper($smap['filters']['loc'])) : '').'ORDER BY country ASC, subtype ASC');
									
									$ccountry = null;
									foreach ($subtypes as $t){
										$s = get_country_schema($t['country']);
										$name = $t['subtype'];
										
										$count = $t['count'];/*query_entities(array(
											'etype' => $type,
											'esubtype' => $t['subtype'],
											'country' => !empty($smap['filters']['loc']) ? $smap['filters']['loc'] : null,
											'count' => true
										));*/
										
										if (!$ccountry || $ccountry != $t['country']){
											$ccountry = $t['country'];
											echo '<li class="menu-item-level-2 menu-item-cat">From '.$s->name.':</li>';
										}
										
										if ($s && !empty($s->vocabulary->legalEntityTypes->{$t['subtype']}))
											$name = $s->vocabulary->legalEntityTypes->{$t['subtype']}->name.' ('.$s->vocabulary->legalEntityTypes->{$t['subtype']}->shortName.')';
												
										?>
										<li class="menu-item-level-2<?= (in_array('company/'.strtolower($t['country'].'/'.$t['subtype']), $etype) ? ' menu-item-active' : '') ?>"><a href="<?= get_filter_url(array(
											'etype' => $type.'/'.$t['country'].'/'.$t['subtype'],
										)) ?>"><span><?= $cbs ?> <?= $name ?> <span class="menu-item-right"><?= format_number_nice($count) ?></span></span></a></li>
										<?php
									}
								}
							}
							?>
						</ul>
					</div>
				</div>
				<?php
			?></li>
			
			<?php } else { ?>
			<li id="top-filter-activity-type" class="menu"><?php
				?>
				<div class="top-filter-menu menu-wrap">
					<div class="menu-menu">
						<ul class="menu-inner">
							<?php 
								$atype = !empty($smap['filters']['atype']) ? $smap['filters']['atype'] : null; 
								$aaction = !empty($smap['filters']['aaction']) ? $smap['filters']['aaction'] : null; 
							?>
							<li class="menu-item-blank <?php if (!$atype) echo 'menu-item-active'; ?>"><a href="<?= remove_url_arg('atype', remove_url_arg('aaction')) ?>">Any</a></li>
							<?php
								foreach (get_status_labels() as $type => $c)
									foreach ($c as $action => $cc)
										if (isset($cc->name))
											echo '<li class="'.($atype == $type && $aaction == $action ? 'menu-item-active' : '').'"><a href="'.add_url_arg('atype', $type, add_url_arg('aaction', $action)).'">'.$cc->name.'</a></li>';
							?>
						</ul>
					</div>
				</div>
				<?php
			?><span class="menu-button">Activity type <i class="fa fa-angle-down"></i></span></li>
			<li id="top-filter-period" class="menu<?php if (!empty($smap['filters']['year'])) echo ' top-filter-active'; ?>"><?php
				?>
				<div class="top-filter-menu menu-wrap">
					<div class="menu-menu">
						<ul class="menu-inner">
							<?php
								$year = !empty($smap['filters']['year']) ? intval($smap['filters']['year']) : null;
							?>
							<li class="menu-item-blank <?php if (!$year) echo 'menu-item-active'; ?>"><a href="<?= remove_url_arg('year') ?>">Any</a></li>
							<?php
								for ($i=intval(date('Y')); $i>=2000; $i--)
									echo '<li class="'.($year == $i ? 'menu-item-active' : '').'"><a href="'.add_url_arg('year', $i).'">'.$i.'</a></li>';
							?>
						</ul>
					</div>
				</div>
				<?php
			?><span class="menu-button">Period<?php
				if (!empty($smap['filters']['year']))
					echo ': '.$smap['filters']['year'];
			?> <i class="fa fa-angle-down"></i></span></li>
			<?php
		}
		
		if (empty($smap['entity']) && is_admin()){ 
			$miscs = array(
				'buggy' => 'Buggy'
			);
			?>
			<li id="top-filter-misc" class="menu<?php if (!empty($smap['filters']['misc'])) echo ' top-filter-active'; ?>"><?php
				?>
				<div class="top-filter-menu menu-wrap">
					<div class="menu-menu">
						<ul class="menu-inner">
							<?php
								$misc = !empty($smap['filters']['misc']) ? $smap['filters']['misc'] : null;
							?>
							<li class="menu-item-blank <?php if (!$misc) echo 'menu-item-active'; ?>"><a href="<?= remove_url_arg('misc') ?>">None</a></li>
							<?php
								foreach ($miscs as $k => $label)
									echo '<li class="'.($misc == $k ? 'menu-item-active' : '').'"><a href="'.add_url_arg('misc', $k).'">'.$label.'</a></li>';
							?>
						</ul>
					</div>
				</div>
				<?php
			?><span class="menu-button">Misc<?php
				if (!empty($smap['filters']['misc']))
					echo ': '.$miscs[$smap['filters']['misc']];
			?> <i class="fa fa-angle-down"></i></span></li>
		<?php } ?>
		<li id="top-filter-advanced" class="menu<?php if (!empty($smap['filters']['advanced'])) echo ' top-filter-active'; ?>">
			<span class="menu-button">+ Advanced<?php
		?></span></li>
	</ul>
</div>
<div class="header-filters-advanced"<?php if (!empty($smap['filters']['advanced'])) echo ' style="display: block"'; ?>>
	<div class="multiblock">
		<div class="multiblock-items">
		</div>
		<div class="multiblock-bar">
			<div class="menu">
				<div class="multibar-buttons">
					<span class="menu-button">
						<input type="button" class="header-filters-advanced-add" value="+ Add a filter" />
					</span>
					<input type="button" class="header-filters-advanced-apply" value="Apply filters" />
				</div>
				<div class="menu-wrap">
					<div class="menu-menu">
						<ul class="menu-inner">
							<li><a href="#">Had a funding..</a><?php print_advanced_filter('fund', true) ?></li>
							<li><a href="#">Was created..</a><?php print_advanced_filter('created', true) ?></li>
							<li><a href="#">Was dissolved..</a><?php print_advanced_filter('dissolved', true) ?></li>
							<li><a href="#">Has a connexion with..</a><?php print_advanced_filter('connexion', true) ?></li>
							<li><a href="#">Has/had an object with words..</a><?php print_advanced_filter('object', true) ?></li>
						</ul>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<?php

function print_advanced_filter($filter, $blank = false){
	?>
	<div class="multiblock-<?= ($blank ? 'blank' : 'item') ?>">
		<i class="fa fa-filter"></i> 
		<?php
			switch ($filter){
				
				case 'fund':
					?>
					Had a funding <select>
						<option>greater than</option>
						<option>fewer than</option>
					</select> <input type="text" />
					<?php
					break;
				
				case 'created':
					?>
					Was created <select>
						<option>between</option>
						<option>later than</option>
						<option>earlier than</option>
					</select> <input type="date" /> and <input type="date" />
					<?php
					break;
				
				case 'dissolved':
					?>
					Was dissolved <select>
						<option>between</option>
						<option>later than</option>
						<option>earlier than</option>
					</select> <input type="date" /> and <input type="date" />
					<?php
					break;
				
				case 'connexion':
					?>
					Has/had a connexion with <input type="text" />
					<?php
					break;
				
				case 'object':
					?>
					Has/had an object with words <input type="text" />
					<?php
					break;
			}
		?>
		<a href="#" class="multiblock-delete"><i class="fa fa-times"></i></a>
	</div>
	<?php
}
