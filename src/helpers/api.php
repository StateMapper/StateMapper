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

function get_api_type(){
	global $smap;
	if (is_home())
		return false;
	if (in_array($smap['page'], array('api', 'settings', 'login', 'logout')))
		return false;
	if (!empty($smap['call']) && in_array($smap['call'], array('fetch', 'lint')))
		return 'document';
	return 'json';
}

function get_api_uri($url = null, $type = null){
	return get_uri_from_url(get_api_url($url, $type));
}

function get_api_url($url = null, $type = null){
	if (!$type && !($type = get_api_type()))
		return null;
		
	$uri = get_uri_from_url($url ? $url : get_canonical_url());
	switch ($type){
		case 'document': 
			$end = '/raw';
			break;
		case 'json':
			$end = '.json';
			$uri = 'api/'.$uri;
			break;
	}
	return add_lang(BASE_URL.preg_replace('#^([^\#\?]*?)/?(\?[^\#]*)?(\#.*)?$#iu', '$1'.$end.'$2$3', $uri));
}

function is_rate_limited(){
	return defined('IS_API') && IS_API && API_RATE_LIMIT && IS_CLI && ($ip = get_visitor_ip()) && !is_admin() && (!IS_DEBUG || !empty($_GET['force_rate_limited'])) ? $ip : false;
}

function is_api(){
	return defined('IS_API') && IS_API;
}

function is_api_root(){
	global $smap;
	return is_page('api') && empty($smap['query']['schema']);
}

add_action('clean_tables', 'clean_api_rates');
function clean_api_rates($all){
		
	// clear api rates
	if ($all)
		query('DELETE FROM api_rates');
	else
		query('DELETE FROM api_rates WHERE date < %s', date('Y-m-d H:i:s', strtotime('-'.API_RATE_PERIOD)));
}

add_action('footer_left', function(){
	$modes = get_modes();

	?>
	<span class="api-footer-link left"><span class="menu menu-top">
		<span class="menu-button" title="<?= esc_attr(_('$tateMapper has full JSON and document API\'s. <br>Build apps and widgets on top of our stack!')) ?>"><i class="fa fa-<?= $modes['api']['icon'] ?>"></i> <?= __('API') ?> <i class="fa fa-angle-down tick"></i></span>
		<span class="menu-wrap">
			<span class="menu-menu">
				<ul class="menu-inner">
					<li><a href="<?= add_lang(BASE_URL.'api') ?>"><i class="fa fa-info-circle"></i> API Reference</a></li>
					
					<?php if (!is_home() && ($type = get_api_type())){ 
						
						if ($type == 'json'){
							?>
							<li><a href="<?= add_url_arg('human', 1, get_api_url()) ?>"><i class="fa fa-<?= $modes['api']['icon'] ?>"></i> <?= _('Human JSON') ?></a></li>
							<li><a href="<?= get_api_url() ?>"><i class="fa fa-<?= $modes['api']['icon'] ?>"></i> <?= _('Raw JSON') ?></a></li>
							<?php 
							
						} else if ($type == 'document'){
							?>
							<li><a href="<?= get_api_url() ?>"><i class="fa fa-<?= $modes['document']['icon'] ?>"></i> <?= _('Raw document') ?></a></li>
							<?php 
						} 
					}
					?>
				</ul>
			</span>
		</span>
	</span></span>
	<?php
});
