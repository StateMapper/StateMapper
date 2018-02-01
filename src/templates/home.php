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

print_header('browser');

?>
<div class="browser-center-msg">
	<div>
		<div class="logo-root-big-wrap"><a data-tippy-placement="bottom" title="<?= esc_attr(_('Start investigating..')) ?>" href="#" onclick="jQuery('#search-input').focus().select(); return false;">
			<img src="<?= ASSETS_URL.'/images/logo/logo-square-transparent.png?v='.ASSETS_INC ?>" class="logo-root-big" />
		</a></div>
		<?php
			if (!empty($_SESSION['smap_installed'])){
				$_SESSION['smap_installed'] = false; // this will be written to session because we're inside an ob_start()
				print_nice_alert(array(
					'icon' => 'check',
					'id' => 'install_success',
					'class' => 'success',
					'label' => __('The installation was successful!'),
				));
			}
		?>
		<div class="browser-loading"><?= get_loading() ?></div>
		<div class="browser-directories">
			<div><?php 
				foreach (get_entity_types() as $type => $c){
					$count = get_entity_count($type);
					?>
					<a href="<?= url(null, $c['slug']) ?>" title="<?= esc_attr(sprintf(_('%s %s retrieved so far'), number_format($count, 0), $c['plural'])) ?>"><i class="fa fa-<?= get_entity_icon($type) ?>"></i> <?= format_number_nice($count).' '.ucfirst($c['plural']) ?></a>
					<?php
				}
			?></div>
		</div>
		<div class="search-root-big-wrap"><?php print_template('parts/search_input'); ?></div>
		<div class="browser-big-submit">
			<input type="button" class="browser-big-submit-button" value="<?= esc_attr(_('$tate Search')) ?>" />
		</div>
		<div class="root-slogan"><?= get_slogan(true) ?>.</div>
		
		<?php 
		/*
		<div class="root-by pointer" title="<?= esc_attr('The Ingoberlab is the Hackers Laboratory of the Ingobernable, an okupied, self-organized space in Madrid, Spain. <br><br>More info on <a href="'.anonymize('https://ingobernable.net').'" target="_blank">ingobernable.net</a> & <a href="'.anonymize('https://hacklab.ingobernable.net').'" target="_blank">hacklab.ingobernable.net</a>.') ?>" data-tippy-interactive="true">by @<span>Ingoberlab</span></div>

		if (is_dev()){ ?>
			<div class="root-stats">
				<a href="<?= get_providers_url() ?>"><i class="fa fa-<?= $modes['providers']['icon'] ?>"></i> <?= _n('%d Source', '%d Sources', get_providers_count(), 'format_number_nice') ?></a>
				<a href="<?= get_providers_url() ?>"><i class="fa fa-<?= $modes['schema']['icon'] ?>"></i> <?= _n('%d Bulletin', '%d Bulletins', get_bulletins_count(), 'format_number_nice') ?></a>
				<a href="<?= get_providers_url() ?>"><i class="fa fa-<?= $modes['document']['icon'] ?>"></i> <?= _n('%d Document', '%d Documents', get_bulletins_count(true), 'format_number_nice') ?></a>
			</div>
			<?php
		}
		*/

		if (defined('SMAP_FRONTPAGE_MESSAGE') && SMAP_FRONTPAGE_MESSAGE)
			echo '<div class="front-warning front-warning-custom"><span>'.SMAP_FRONTPAGE_MESSAGE.'</span></div>';
		?>
	</div>
</div>
<?php

print_footer();
