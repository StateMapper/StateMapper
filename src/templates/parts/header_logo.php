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

if (is_home())
	return;

$go_to_home = is_browser() || is_api() || is_api_root() || (is_page('providers') && !has_filter());
?>
<div class="header-logo">
	<a data-tippy-placement="bottom" href="<?php
			
			if (IS_API)
				echo url(null, 'api');
			else if (IS_INSTALL)
				echo anonymize(get_repository_url('blob/master/documentation/manuals/INSTALL.md#top'));
			else if ($go_to_home)
				echo url();
			else 
				echo get_providers_url(!empty($smap['query']['schema']) ? get_country_from_schema($smap['query']['schema']) : null);
			
		?>" <?php
		
			if (IS_INSTALL)
				echo 'target="_blank" ';
				
		?>title="<?php
			
			if (IS_API)
				echo _('Go to the API Reference');
			else if (!IS_INSTALL){
				if ($go_to_home)
					echo _('Go to homepage');
				else if (!empty($smap['query']['schema']))
					echo sprintf(_('Go back to %s providers'), get_schema($smap['query']['schema'])->adjective);
				else
					echo _('Go back to the providers list');
			}

			?>"><?php
				if (IS_ALPHA)
					echo '<span class="alpha-ind">alpha</span>';
				?>
				<img src="<?= ASSETS_URL.'/images/logo/logo-transparent.png?v='.ASSETS_INC ?>" />
			</a>
</div>
