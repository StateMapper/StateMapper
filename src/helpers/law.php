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
	
add_action('footer', function(){
	if (is_logged() || !empty($_SESSION['agreed_lawd']) || IS_INSTALL)
		return;
	?>
	<div class="footer-law-disclaimer" title="Click to agree and hide this notice" data-tippy-placement="top"><div>Hey! We use session <a href="<?= anonymize('https://en.wikipedia.org/wiki/HTTP_cookie') ?>" target="_blank">cookies</a> to give you consistent user experience and to improve it over time. We won't give or sell information about your visit on this website to no-one, ever. The information displayed in this website is automatically generated from public data and contains (lots of) errors. Please close this window if you disagree with any of these conditions. <i class="fa fa-check"></i></div></div>
	<?php
});

function smap_ajax_agree_law_disclaimer(){
	$_SESSION['agreed_lawd'] = true;
	return array('success' => true);
}
