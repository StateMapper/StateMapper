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

if (is_home(true))
	return;

$backToHome = is_browser();
?>
<div class="header-logo">
	<a data-tippy-placement="bottom" href="<?php
			
			if (!IS_INSTALL){
				if ($backToHome)
					echo url();
				else 
					echo get_providers_url(!empty($smap['query']['schema']) ? get_country_from_schema($smap['query']['schema']) : null);
			}
			
		?>" title="<?php
			
			if (!IS_INSTALL){
				if ($backToHome)
					echo _('Go to homepage');
				else if (!empty($smap['query']['schema']))
					echo sprintf(_('Go back to %s providers'), get_country_schema($smap['query']['schema'])->adjective);
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
