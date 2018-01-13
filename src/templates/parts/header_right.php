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

global $smap;

if (IS_INSTALL)
	return;
	
$modes = get_modes();
?>
<div class="header-right">
	
	<?php // bulletin actions menu 
	
	if (!defined('IS_ERROR') || !IS_ERROR){
		if (in_array($smap['page'], array('bulletin', 'ambassadors')) || ($smap['page'] == 'providers' && !empty($smap['filters']['loc']))){
			$tr = array('precept', 'filter');
			?>
			<div class="header-actions">
				<?php 
				
				// header date/datepicker
				if (!empty($smap['query']['date']) && in_array($smap['call'], array('fetch', 'parse', 'lint', 'extract')) && schema_has_feature($smap['query']['schema'], 'fetch')){ ?>
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
					} 
					
					if (!empty($smap['query']['schema'])){
						$schema = get_schema($smap['query']['schema']);
						$urlQuery = $smap['query'];
					} else {
						$schema = get_schema($smap['filters']['loc']);
						$urlQuery = array('schema' => $schema->id);
					}
				
					if (in_array($schema->type, array('country', 'continent'))){
						?>
						<a href="<?= get_filter_url(array('loc' => $smap['filters']['loc']), false, false) ?>" title="<?= esc_attr($modes['browse']['headerTip']) ?>" class=""><i class="fa fa-<?= esc_attr($modes['browse']['icon']) ?>"></i><span><?= $modes['browse']['title'] ?></span></a>
						<a href="<?= url($urlQuery, 'providers') ?>" title="<?= esc_attr($modes['providers']['headerTip']) ?>" class="<?php if ($smap['page'] == 'providers') echo 'header-action-active'; ?>"><i class="fa fa-<?= $modes['providers']['icon'] ?>"></i><span><?= (!empty($modes['providers']['shortTitle']) ? $modes['providers']['shortTitle'] : $modes['providers']['title']) ?></span></a>
						<?php 
					}
					
					if (show_mode('schema')){
						?>
						<a href="<?= url($urlQuery, 'schema') ?>" title="<?= esc_attr($modes['schema']['headerTip']) ?>" class="<?php if (is_call('schema')) echo 'header-action-active'; ?>"><i class="fa fa-<?= $modes['schema']['icon'] ?>"></i><span><?= (!empty($modes['schema']['shortTitle']) ? $modes['schema']['shortTitle'] : $modes['schema']['title']) ?></span></a>
						<?php 
					}
					
					if ($schema->type == 'bulletin' && show_mode('fetch')){ ?>
						<a href="<?= url($urlQuery, 'fetch') ?>" title="<?= esc_attr($modes['fetch']['headerTip']) ?>" class="<?php

							if (is_call('fetch'))
								echo 'header-action-active';

							$fetchClass = $class = '';
							if (!schema_has_feature($smap['query']['schema'], 'fetch'))
								$fetchClass = $class = ' header-action-disabled';
							echo $class;

							?>"><i class="fa fa-<?= $modes['fetch']['icon'] ?>"></i><span><?= (!empty($modes['fetch']['shortTitle']) ? $modes['fetch']['shortTitle'] : $modes['fetch']['title']) ?></span></a>
						
						<?php if (schema_has_feature($smap['query']['schema'], 'fetch') && show_mode('rewind')){ ?>
							<a href="<?= url($urlQuery, 'lint') ?>" title="<?= esc_attr($modes['lint']['headerTip']) ?>" class="<?php if (is_call('lint')) echo 'header-action-active'; echo $class; ?>"><i class="fa fa-<?= $modes['lint']['icon'] ?>"></i><span><?= (!empty($modes['lint']['shortTitle']) ? $modes['lint']['shortTitle'] : $modes['lint']['title']) ?></span></a>
						<?php } 
						
						$class = '';
						if (!schema_has_feature($smap['query']['schema'], 'parse'))
							$class = ' header-action-disabled';
							
						if (show_mode('parse')){
							?>
							<a href="<?= url($urlQuery, 'parse', $tr) ?>" title="<?= esc_attr($modes['parse']['headerTip']) ?>" class="<?php if (is_call('parse')) echo 'header-action-active'; echo $class; ?>"><i class="fa fa-<?= $modes['parse']['icon'] ?>"></i><span><?= (!empty($modes['parse']['shortTitle']) ? $modes['parse']['shortTitle'] : $modes['parse']['title']) ?></span></a>
							<?php
						}
						
						$class = '';
						if (!schema_has_feature($smap['query']['schema'], 'extract'))
							$class = ' header-action-disabled';
						
						if (show_mode('extract')){
							?>
							<a href="<?= url($urlQuery, 'extract', $tr) ?>" title="<?= esc_attr($modes['extract']['headerTip']) ?>" class="<?php if (is_call('extract')) echo 'header-action-active'; echo $class; ?>"><i class="fa fa-<?= $modes['extract']['icon'] ?>"></i><span><?= (!empty($modes['extract']['shortTitle']) ? $modes['extract']['shortTitle'] : $modes['extract']['title']) ?></span></a>
							<?php
						}
						if (show_mode('rewind')){
							?>
							<a href="<?= url($urlQuery, 'rewind') ?>" title="<?= esc_attr($modes['rewind']['headerTip']) ?>" class="<?php if (is_call('rewind')) echo 'header-action-active'; echo $fetchClass; ?>"><i class="fa fa-<?= $modes['rewind']['icon'] ?>"></i><span><?= (!empty($modes['rewind']['shortTitle']) ? $modes['rewind']['shortTitle'] : $modes['rewind']['title']) ?></span></a>
							<?php
						}
					}
						
					//if (in_array($schema->type, array('bulletin', 'institution'))){
						?>
						<a href="<?= url($urlQuery, 'soldiers') ?>" title="<?= esc_attr($modes['soldiers']['headerTip']) ?>" class="<?php if (empty($schema->soldiers)) echo ' header-action-disabled'; else if (is_call('soldiers')) echo 'header-action-active'; ?>"><i class="fa fa-<?= $modes['soldiers']['icon'] ?>"></i><span><?= (!empty($modes['soldiers']['shortTitle']) ? $modes['soldiers']['shortTitle'] : $modes['soldiers']['title']) ?></span></a>
						<?php
					//}
					
					if (in_array($schema->type, array('country', 'continent'))){
						?>
						<a href="<?= url($urlQuery, 'ambassadors') ?>" title="<?= esc_attr($modes['ambassadors']['headerTip']) ?>" class="<?php if (empty($schema->ambassadors)) echo ' header-action-disabled'; else if ($smap['page'] == 'ambassadors') echo 'header-action-active'; ?>"><i class="fa fa-<?= $modes['ambassadors']['icon'] ?>"></i><span><?= (!empty($modes['ambassadors']['shortTitle']) ? $modes['ambassadors']['shortTitle'] : $modes['ambassadors']['title']) ?></span></a>
						<?php
					}
				?>
			</div>
		
		<?php } else if ($smap['page'] == 'providers'){
			?>
			<div class="header-actions">
				<a href="<?= url() ?>" title="<?= esc_attr($modes['browse']['headerTip']) ?>" class=""><i class="fa fa-<?= $modes['browse']['icon'] ?>"></i><span><?= $modes['browse']['title'] ?></span></a>
				<a href="<?= get_providers_url() ?>" title="<?= esc_attr($modes['providers']['headerTip']) ?>" class="header-action-active"><i class="fa fa-<?= $modes['providers']['icon'] ?>"></i><span><?= (!empty($modes['providers']['shortTitle']) ? $modes['providers']['shortTitle'] : $modes['providers']['title']) ?></span></a>
			</div>
			<?php
		
		} else if (!is_home(true)){
			?>
			<div class="header-actions">
				<a href="<?= get_providers_url(!empty($smap['filters']['loc']) ? $smap['filters']['loc'] : null) ?>" title="<?= esc_attr($modes['providers']['headerTip']) ?>" class=""><i class="fa fa-<?= $modes['providers']['icon'] ?>"></i><span><?= (!empty($modes['providers']['shortTitle']) ? $modes['providers']['shortTitle'] : $modes['providers']['title']) ?></span></a>
			</div>
			<?php
		}
	}
	
	// home menu
	
	if (is_home(true)){ ?>
		<div class="root-menu">
			<ul>
				<li><a data-tippy-placement="bottom" title="<?= esc_attr(_('Read our spiritual guidelines!')) ?>" href="<?= anonymize(get_repository_url('#manifest')) ?>" target="_blank" class="clean-links"><?= _('Manifest') ?></a></li>
				<li><a data-tippy-placement="bottom" title="<?= esc_attr(_('Which public data providers we integrate')) ?>" href="<?= get_providers_url() ?>" class="clean-links"><?= $modes['providers']['title'] ?></a></li>
				<li><a data-tippy-placement="bottom" title="<?= esc_attr(_('Build your app on top of our stack!')) ?>" href="<?= add_lang(BASE_URL.'api') ?>" class="clean-links"><?= _('API') ?></a></li>
			</ul>
		</div>
	<?php } ?>
	
	<?php // main menu ?>
	<div class="header-menu menu menu-right">
		<div data-tippy-placement="bottom" class="header-menu-icon menu-button" title="<?= esc_attr(_('Main menu')) ?>"><i class="fa fa-bars"></i></div>
		<div class="header-menu-wrap menu-wrap">
			<div class="header-menu-menu menu-menu">
				<ul class="header-menu-inner menu-inner">
					<li><a href="<?= url() ?>"><i class="fa fa-search"></i> <?= _('Browse') ?></a></li>
					<li><a href="<?= get_providers_url() ?>"><i class="fa fa-<?= $modes['providers']['icon'] ?>"></i> <?= $modes['providers']['title'] ?></a></li>
					<?php if (is_admin()){ ?>
						<li><a href="<?= add_lang(BASE_URL.'settings') ?>"><i class="fa fa-cog"></i> <?= _('Settings') ?></a></li>
					<?php } else { ?>
						<li><a target="_blank" href="<?= anonymize(get_repository_url('#contribute')) ?>"><i class="fa fa-thumbs-o-up"></i> <?= _('Contribute') ?></a></li>
						<li><a target="_blank" href="<?= anonymize(get_repository_url('#contact--support')) ?>"><i class="fa fa-info-circle"></i> <?= _('Help') ?></a></li>
						<li><a target="_blank" href="<?= anonymize(get_repository_url('#top')) ?>"><i class="fa fa-question-circle-o"></i> <?= _('About') ?></a></li>
					<?php } ?>
					<?php if (is_admin()){ ?>
						<li><a href="<?= esc_attr(add_lang(BASE_URL.'logout')) ?>"><i class="fa fa-sign-out"></i> <?= _('Logout') ?></a></li>
					<?php } ?>
				</ul>
			</div>
		</div>
	</div>
</div>
<?php
