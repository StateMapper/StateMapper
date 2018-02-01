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

if (IS_INSTALL)
	return;
	
$modes = get_modes();
?>
<div class="header-right">
	
	<?php // bulletin actions menu 
	
	$actions = array();
	$urlQuery = $smap['query'];
	
	//if (!defined('IS_ERROR') || !IS_ERROR){

		if (!empty($smap['query']['schema'])){
			$schema = get_schema($smap['query']['schema']);
			$urlQuery = $smap['query'];
		
		} else if (!empty($smap['filters']['loc'])){
			$schema = get_schema($smap['filters']['loc']);
			if ($schema)
				$urlQuery = array('schema' => $schema->id);
			else {
				$schema = null;
				$urlQuery = array();
//				$urlQuery = array('schema' => null, 'locs' => null); // @todo: fix providers button's URL in /?loc=es%20fr&etype=company
			}
		
		} else {
			$schema = null;
			$urlQuery = array();
		}
		
		if (in_array($smap['page'], array('bulletin', 'ambassadors')) || ($smap['page'] == 'providers' && !empty($smap['filters']['loc']))){

			$urlQuery['tr'] = array('precept', 'filter');
			
			// header date/datepicker
			if (!empty($smap['query']['date']) && in_array($smap['call'], array('fetch', 'parse', 'lint', 'extract')) && schema_has_feature($smap['query']['schema'], 'fetch')){ 
				ob_start();	
				?>
				<span class="menu menu-right header-date-menu">
					<a data-tippy-placement="bottom" href="#" title="<?= esc_attr(_('Browse bulletins by date')) ?>" class="header-date header-action-active">
						<i class="fa fa-calendar"></i>
						<span class="header-date-wrap">
							<span class="header-date-main"><?= date_i18n('M j', strtotime($smap['query']['date'])) ?></span>
							<span class="header-date-year"><?= date_i18n('Y', strtotime($smap['query']['date'])) ?></span>
						</span>
					</a>
					<span class="menu-wrap">
						<span class="menu-menu">
							<span class="menu-inner">
								<span class="smap-calendar header-calendar"><input type="date" id="date" value="<?= $smap['query']['date'] ?>" autocomplete="off" data-smap-url="<?= url(array(
									'date' => '%s',
								) + $smap['query'], $smap['call']) ?>" data-smap-oval="<?= $smap['query']['date'] ?>" /><button><i class="fa fa-arrow-right"></i></button></span>
							</span>
						</span>
					</span>
				</span>
				<?php 
				$actions['datepicker'] = array(
					'html' => ob_get_clean()
				);
			} 
			
			if (in_array($schema->type, array('country', 'continent'))){
				if (!is_browser())
					$actions['browse'] = array(
						'url' => get_filter_url(array('loc' => $smap['filters']['loc']), false, false),
					);
				$actions['providers'] = array(
					'active' => $smap['page'] == 'providers',
					'url' => get_providers_url($smap['filters']['loc']),
				);
			}
			
			if (show_mode('schema')){
				$actions['schema'] = array();
			}
			
			if ($schema->type == 'bulletin' && show_mode('fetch')){ 
				$actions['fetch'] = array(
					'disabled' => !schema_has_feature($smap['query']['schema'], 'fetch'), 
				);

				if (schema_has_feature($smap['query']['schema'], 'fetch') && show_mode('rewind'))
					$actions['lint'] = array();
				
				if (show_mode('parse'))
					$actions['parse'] = array();
				
				$class = '';
				if (!schema_has_feature($smap['query']['schema'], 'extract'))
					$class = ' header-action-disabled';
				
				if (show_mode('extract'))
					$actions['extract'] = array();
				
				if (show_mode('rewind'))
					$actions['rewind'] = array();
			}
				
			$actions['soldiers'] = array(
				'disabled' => empty($schema->soldiers)
			);
			
			if (in_array($schema->type, array('country', 'continent')))
				$actions['ambassadors'] = array(
					'disabled' => empty($schema->ambassadors),
					'active' => $smap['page'] == 'ambassadors',
				);
		
		} else if (!is_home()){
			if (!is_browser())
				$actions['browse'] = array(
					'url' => url(),
					'active' => false,
				);
			if (is_logged())
				$actions['lists'] = array(
					'url' => url(null, 'lists'),
					'active' => $smap['page'] == 'lists',
					'disabled' => false,
				);
			$actions['providers'] = array(
				'url' => get_providers_url(!empty($smap['filters']['loc']) ? $smap['filters']['loc'] : null),
				'active' => $smap['page'] == 'providers',
			);
		}
		
		if ($actions){
			echo '<div class="header-actions">';
			print_header_actions($actions, $urlQuery);
			echo '</div>';
		}
	//}
	
	// homepage's main menu
	if (is_home()){ ?>
		<div class="root-menu">
			<ul>
				<li><a data-tippy-placement="bottom" title="<?= esc_attr(_('Read our spiritual guidelines!')) ?>" href="<?= anonymize(get_repository_url('#manifest')) ?>" target="_blank" class="clean-links"><?= _('Manifest') ?></a></li>
				<li><a data-tippy-placement="bottom" title="<?= esc_attr(_('Which public data providers we integrate')) ?>" href="<?= get_providers_url() ?>" class="clean-links"><?= $modes['providers']['title'] ?></a></li>
				<li><a data-tippy-placement="bottom" title="<?= esc_attr(_('Contribute')) ?>" href="<?= get_repository_url('#contribute') ?>" class="clean-links"><?= _('Contribute') ?></a></li>
			</ul>
		</div>
	<?php } ?>
	
	<?php // main menu ?>
	<div class="header-menu menu menu-right">
		<div data-tippy-placement="bottom" class="header-menu-icon menu-button" title="<?= esc_attr(_('Main menu')) ?>"><i class="fa fa-bars"></i></div>
		<div class="header-menu-wrap menu-wrap">
			<div class="header-menu-menu menu-menu">
				<?php
					if (is_logged()){
						?>
						<div class="header-menu-current-user">Logged in as @<?= get_current_user_name() ?></div>
						<?php
					}
				?>
				<ul class="header-menu-inner menu-inner">
					<li><a href="<?= url() ?>"><i class="fa fa-search"></i> <?= _('Browse') ?></a></li>
					<li><a href="<?= get_providers_url() ?>"><i class="fa fa-<?= $modes['providers']['icon'] ?>"></i> <?= $modes['providers']['title'] ?></a></li>
					
					<?php if (is_logged()){ ?>
						<li><a href="<?= url(null, 'lists') ?>"><i class="fa fa-<?= $modes['lists']['icon'] ?>"></i> <?= $modes['lists']['title'] ?></a></li>
					<?php } ?>
					
					<li><a href="<?= url(null, 'api') ?>"><i class="fa fa-<?= $modes['api']['icon'] ?>"></i> <?= $modes['api']['title'] ?></a></li>
					
					<?php if (is_admin()){ ?>
						<li><a href="<?= add_lang(BASE_URL.'settings') ?>"><i class="fa fa-cog"></i> <?= _('Settings') ?></a></li>
					
					<?php } else { ?>
						<li><a target="_blank" href="<?= anonymize(get_repository_url('#contribute')) ?>"><i class="fa fa-thumbs-o-up"></i> <?= _('Contribute') ?></a></li>
						<li><a target="_blank" href="<?= anonymize(get_repository_url('#contact--support')) ?>"><i class="fa fa-info-circle"></i> <?= _('Help') ?></a></li>
						<li><a target="_blank" href="<?= anonymize(get_repository_url('#top')) ?>"><i class="fa fa-question-circle-o"></i> <?= _('About') ?></a></li>
					<?php } ?>
					
					<?php if (is_logged()){ ?>
						<li><a href="<?= esc_attr(add_lang(BASE_URL.'logout')) ?>"><i class="fa fa-sign-out"></i> <?= _('Logout') ?></a></li>
					<?php } ?>
				</ul>
			</div>
		</div>
	</div>
</div>
<?php
