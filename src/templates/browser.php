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

$smap['mem']['wrapPrinted'] = true;

add_js('browser');
$types = get_entity_types();

$tip = _('Lookup for a company, a person..');

ob_start();
?>
<div class="browser-title-title">
	<form action="<?= BASE_URL ?>" method="GET">
		<span class="search-icon"><i class="fa fa-search"></i></span>
		<input type="text"<?php
			if (is_home(true))
				echo ' title="'.esc_attr($tip).'" data-tippy-placement="bottom"';
			?> autocomplete="off" name="q" id="search-input" placeholder="<?php
			if (!is_home(true))
				echo esc_attr($tip);
			?>" value="<?= (!empty($smap['query']['q']) ? esc_attr($smap['query']['q']) : '') ?>" />
		<div class="search-suggs">
			<div class="search-suggs-inner">
				<div class="search-suggs-loading-msg">
					<div><i class="fa fa-circle-o-notch fa-spin"></i> <?= _('Loading') ?>..</div>
				</div>
				<div class="search-suggs-results">
					<div class="search-suggs-results-inner"></div>
					<div class="search-suggs-results-more"></div>
				</div>
			</div>
		</div>
	</form>
</div>
<?php
$searchInput = ob_get_clean();

print_template('parts/header');
?>
<div id="main-inner">
	<div class="main-header">
		<div class="main-header-inner">
			<?php 
			print_template('parts/header_logo'); 
			
			if (!is_home(true)){
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
							if (!is_home(true))
								echo $searchInput;
							if (!has_filter())
								echo '<span class="header-filter-ind" title="'.esc_attr(_('Add a filter')).'">+ '._('Filters').'</span>';
							else
								echo '<a title="'.esc_attr(_('Remove all filters')).'" class="header-filter-ind" href="'.add_lang(BASE_URL.(!empty($_GET['q']) ? '?q='.$_GET['q'] : '')).'">- '._('Filters').'</a>';
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
	<?php print_template('parts/filters'); ?>
	<div class="main-body main-body-type-<?= (!empty($smap['call']) ? $smap['call'] : 'home') ?>">
		<div class="main-body-inner">
			<div id="wrap">
				<?php 
				
				if ($smap['filters']['q'] != '' || (has_filter() && empty($smap['entity']))){

					ob_start();
					if (!empty($smap['results'])){

						$c = count($smap['results']);
						$total = number_format($smap['resultsCount'], 0);

						?>
						<div class="search-wrap">
							<div class="search-results-count"><?php
								echo get_results_count_label($c, $total);
							?></div>
							<h1><?php
								echo get_page_title().':';
							?></h1>
							<div class="search-results">
								<?php print_template('parts/results') ?>
							</div>
						</div>
						<?php
					} else {
						?>
						<div class="search-results-none"><?php
							
							$item = 'entities';
							
							if (!empty($smap['filters']['etype']) && strpos($smap['filters']['etype'], ' ') === false && isset($types[$smap['filters']['etype']]))
								$item = $types[$smap['filters']['etype']]['plural'];
								
							if ($smap['filters']['q'] == '')
								echo sprintf(_('No %s to show.'), $item);
							else
								echo sprintf(_('No %s found for "%s".'), $item, htmlentities($smap['filters']['q']));

							if (has_filter())
								echo ' '._('Check your query or try removing some filters...');

						?></div>
						<?php
					}

				} else if (!empty($entity)){

					print_template('entity', array('entity' => $entity));

				} else {
					?>
					<div class="browser-center-msg">
						<div>
							<div class="logo-root-big-wrap"><a data-tippy-placement="bottom" title="<?= esc_attr(_('Start investigating..')) ?>" href="#" onclick="jQuery('#search-input').focus().select(); return false;">
								<img src="<?= ASSETS_URL.'/images/logo/logo-square-transparent.png?v='.ASSETS_INC ?>" class="logo-root-big" />
							</a></div>
							<?php
								if (!empty($_SESSION['smap_installed'])){
									$_SESSION['smap_installed'] = false; // this will be written to session because we're inside an ob_start()
									echo '<div class="front-warning front-warning-success"><span>'._('The installation was completed successfully!').'</span></div>';
								}
							?>
							<div class="browser-directories"><div><?php 
								foreach (get_entity_types() as $type => $c){
									$count = get_entity_count($type);
									?>
									<a href="<?= url(null, $c['slug']) ?>" title="<?= esc_attr(sprintf(_('%s %s retrieved so far'), number_format($count, 0), $c['plural'])) ?>"><i class="fa fa-<?= get_entity_icon($type) ?>"></i> <?= format_number_nice($count).' '.ucfirst($c['plural']) ?></a>
									<?php
								}
							?></div></div>
							<div class="search-root-big-wrap"><?= $searchInput ?></div>
							<div class="browser-big-submit">
								<input type="button" class="browser-big-submit-button" value="<?= esc_attr(_('$tate Search')) ?>" />
							</div>
							<div class="root-slogan"><?= get_slogan(true) ?>.</div>
							<?php
								if (IS_DEBUG)
									echo '<div class="front-warning front-warning-debug"><span>'._('Debug mode enabled').'</span></div>';
								if (defined('SMAP_FRONTPAGE_MESSAGE') && SMAP_FRONTPAGE_MESSAGE)
									echo '<div class="front-warning front-warning-custom"><span>'.SMAP_FRONTPAGE_MESSAGE.'</span></div>';
							?>
						</div>
					</div>
					<?php
				} ?>
			</div>
		</div>
	</div>
</div>
<?php

print_template('parts/footer');
