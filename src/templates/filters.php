<?php
/*
 * StateMapper: worldwide, collaborative, public data reviewing and monitoring tool.
 * Copyright (C) 2017  StateMapper.net <statemapper@riseup.net>
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
	
if (isHome(true))
	return;
	
global $kaosCall, $kaosPage;
if ($kaosPage == 'browser' || !empty($kaosCall['entity'])){
	$cbs = '<i class="fa fa-square-o cb-off multisel-cb"></i><i class="fa fa-check-square-o cb-on multisel-cb"></i>';

	?>
	<div class="header-filters"<?php if (hasFilter()) echo ' style="display: block"'; ?>>
		<ul>
			<?php if (empty($kaosCall['entity'])){ ?>
				<li id="top-filter-location" class="menu<?php if (!empty($_GET['etype'])) echo ' top-filter-active'; ?>"><span class="menu-button">Location<?php

					$loc = !empty($_GET['loc']) ? explode(' ', $_GET['loc']) : array(); 
					if ($loc){
						$str = array();
						foreach ($loc as $l){
							$l = explode(':', $l);
							if ($c = kaosGetCountrySchema(array_shift($l))){
								if (!$l)
									$str[] = $c->name; // country
								else if ($s = getStateName(array_shift($l))){
									if (!$l)
										$str[] = $s.' ('.$c->id.')';
									else if ($s2 = getCountyName(array_shift($l))){
										if (!$l)
											$str[] = $s2.' ('.$c->id.')';
										else if ($s3 = getCityName(array_shift($l))){
											if (!$l)
												$str[] = $s3.' ('.$s2.', '.$c->id.')';
										}
									}
								}
									
									
							}
						}
						if ($str)
							echo ': '.implode(', ', $str);
					}

					?> <i class="fa fa-angle-down"></i></span><?php
					?>
					<div class="top-filter-menu menu-wrap multisel">
						<div class="menu-menu">
							<ul class="menu-inner">
								<li class="menu-item-blank <?php if (!$loc) echo 'menu-item-active'; ?>"><a href="<?= remove_url_arg('loc') ?>">Any</a></li>
								<?php
									foreach (kaosAPIGetSchemas() as $l){
										$l = kaosGetSchema($l);
										if (!in_array($l->type, array('continent', 'country')))
											continue;
											
										?>
										<li class="<?php if ($l->type == 'country') echo 'menu-item-level-2 '; if (in_array($l->id, $loc)) echo 'menu-item-active'; ?>"><a href="<?= add_url_arg('loc', $l->id) ?>"><?= $cbs ?> <?= htmlentities($l->name) ?></a></li>
										<?php
										
										foreach (query('SELECT id, name FROM location_states WHERE country = %s', $l->id) as $s){
											$cid = $l->id.':'.$s['id'];
											?>
											<li class="menu-item-level-3 <?php if (in_array($cid, $loc)) echo 'menu-item-active'; ?>"><a href="<?= add_url_arg('loc', $cid) ?>"><?= $cbs ?> <?= htmlentities($s['name']) ?></a></li>
											<?php
											
											foreach (query('SELECT id, name FROM location_counties WHERE country = %s AND state_id = %s', array($l->id, $s['id'])) as $c){
												$cid = $l->id.':'.$s['id'].':'.$c['id'];
												?>
												<li class="menu-item-level-4 <?php if (in_array($cid, $loc)) echo 'menu-item-active'; ?>"><a href="<?= add_url_arg('loc', $cid) ?>"><?= $cbs ?> <?= htmlentities($c['name']) ?></a></li>
												<?php
												
												foreach (query('SELECT id, name FROM location_cities WHERE country = %s AND county_id', array($l->id, $c['id'])) as $cc){
													$cid = $l->id.':'.$s['id'].':'.$c['id'].':'.$cc['id'];
													?>
													<li class="menu-item-level-5 <?php if (in_array($cid, $loc)) echo 'menu-item-active'; ?>"><a href="<?= add_url_arg('loc', $cid) ?>"><?= $cbs ?> <?= htmlentities($cc['name']) ?></a></li>
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
				<li id="top-filter-entity-type" class="menu<?php if (!empty($_GET['etype'])) echo ' top-filter-active'; ?>"><span class="menu-button">Entity type<?php
					if (!empty($_GET['etype'])){
						echo ': ';
						echo kaosGetCompanyFilter();				
					}
						
				?> <i class="fa fa-angle-down"></i></span><?php
					?>
					<div class="top-filter-menu menu-wrap multisel">
						<div class="menu-menu">
							<ul class="menu-inner">
								<?php 
									$etype = !empty($_GET['etype']) ? explode(' ', $_GET['etype']) : array(); 
								?>
								<li class="menu-item-blank <?php if (!$etype) echo 'menu-item-active'; ?>"><a href="<?= remove_url_arg('etype') ?>">Any</a></li>
								<li class="<?php if (in_array('person', $etype)) echo 'menu-item-active'; ?>"><a href="<?= add_url_arg('etype', 'person') ?>"><?= $cbs ?> People</a></li>
								<li class="<?php if (in_array('institution', $etype)) echo 'menu-item-active'; ?>"><a href="<?= add_url_arg('etype', 'institution') ?>"><?= $cbs ?> Institutions</a></li>
								<li class="<?php if (in_array('company', $etype)) echo 'menu-item-active'; ?>"><a href="<?= add_url_arg('etype', 'company') ?>"><?= $cbs ?> Companies</a></li>
								<?php
									$types = query('SELECT country, subtype FROM entities WHERE type = "company" AND subtype IS NOT NULL GROUP BY country, subtype ORDER BY country ASC, subtype ASC');
									
									$ccountry = null;
									foreach ($types as $t){
										$s = kaosGetCountrySchema($t['country']);
										$name = $t['subtype'];
										
										if (!$ccountry || $ccountry != $t['country']){
											$ccountry = $t['country'];
											echo '<li class="menu-item-level-2 menu-item-cat">From '.$s->name.':</li>';
										}
										
										if ($s && !empty($s->vocabulary->legalEntityTypes->{$t['subtype']}))
											$name = $s->vocabulary->legalEntityTypes->{$t['subtype']}->name.' ('.$name.')';
											
										echo '<li class="menu-item-level-2'.(in_array('company/'.strtolower($t['country'].'/'.$t['subtype']), $etype) ? ' menu-item-active' : '').'"><a href="'.add_url_arg('etype', 'company/'.strtolower($t['country'].'/'.$t['subtype'])).'">'.$cbs.' '.$name.'</a></li>';
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
									$atype = !empty($_GET['atype']) ? $_GET['atype'] : null; 
									$aaction = !empty($_GET['aaction']) ? $_GET['aaction'] : null; 
								?>
								<li class="menu-item-blank <?php if (!$atype) echo 'menu-item-active'; ?>"><a href="<?= remove_url_arg('atype', remove_url_arg('aaction')) ?>">Any</a></li>
								<?php
									foreach (kaosGetStatusLabels() as $type => $c)
										foreach ($c as $action => $cc)
											if (isset($cc->name))
												echo '<li class="'.($atype == $type && $aaction == $action ? 'menu-item-active' : '').'"><a href="'.add_url_arg('atype', $type, add_url_arg('aaction', $action)).'">'.$cc->name.'</a></li>';
								?>
							</ul>
						</div>
					</div>
					<?php
				?><span class="menu-button">Activity type <i class="fa fa-angle-down"></i></span></li>
				<li id="top-filter-period" class="menu<?php if (!empty($_GET['year'])) echo ' top-filter-active'; ?>"><?php
					?>
					<div class="top-filter-menu menu-wrap">
						<div class="menu-menu">
							<ul class="menu-inner">
								<?php
									$year = !empty($_GET['year']) ? intval($_GET['year']) : null;
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
					if (!empty($_GET['year']))
						echo ': '.$_GET['year'];
				?> <i class="fa fa-angle-down"></i></span></li>
				<?php
			}
			
			if (empty($kaosCall['entity']) && isAdmin()){ 
				$miscs = array(
					'buggy' => 'Buggy'
				);
				?>
				<li id="top-filter-misc" class="menu<?php if (!empty($_GET['misc'])) echo ' top-filter-active'; ?>"><?php
					?>
					<div class="top-filter-menu menu-wrap">
						<div class="menu-menu">
							<ul class="menu-inner">
								<?php
									$misc = !empty($_GET['misc']) ? $_GET['misc'] : null;
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
					if (!empty($_GET['misc']))
						echo ': '.$miscs[$_GET['misc']];
				?> <i class="fa fa-angle-down"></i></span></li>
			<?php } ?>
			<li id="top-filter-advanced" class="menu<?php if (!empty($_GET['advanced'])) echo ' top-filter-active'; ?>">
				<span class="menu-button">+ Advanced<?php
			?></span></li>
		</ul>
	</div>
	<div class="header-filters-advanced"<?php if (!empty($_GET['advanced'])) echo ' style="display: block"'; ?>>
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
								<li><a href="#">Had a funding..</a><?php printAdvancedFilter('fund', true) ?></li>
								<li><a href="#">Was created..</a><?php printAdvancedFilter('created', true) ?></li>
								<li><a href="#">Was dissolved..</a><?php printAdvancedFilter('dissolved', true) ?></li>
								<li><a href="#">Has a connexion with..</a><?php printAdvancedFilter('connexion', true) ?></li>
								<li><a href="#">Has/had an object with words..</a><?php printAdvancedFilter('object', true) ?></li>
							</ul>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php
}

function printAdvancedFilter($filter, $blank = false){
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
