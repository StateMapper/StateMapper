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

print_header('page');


if (!empty($smap['is_iframe']))
	echo is_array($obj) || is_object($obj) ? print_json($obj) : (is_string($obj) ? $obj : $obj);

else {
	if (!empty($smap['preview_api_result']))
		$smap['preview_api_result']->preview($obj['result'], $smap['query']);

	if (!empty($smap['collapse_api_return']))
		echo '<div><a href="#" onclick="jQuery(this).parent().find(\'.unfolding\').toggle(); return false">Unfold API return <i class="fa fa-caret-down"></i></a><div class="unfolding" style="display:none">';
	if (is_string($obj))
		echo convert_code($obj);
	else
		print_json($obj);

	if (!empty($smap['collapse_api_return']))
		echo '</div></div>';

}

// ?rewind=1 mode (jumping backward, day after day, in extract mode)
if (!empty($smap['call']) && $smap['call'] == 'extract' && !empty($_GET['rewind'])){
	$args = array('date' => date('Y-m-d', strtotime('-1 day', strtotime($smap['query']['date'])))) + $smap['query'];
	?>
	<script>
		setTimeout(function(){
			window.location = "<?= url($args, 'extract') ?>";
		}, 2000); // 2s
	</script>
	<?php
}

print_footer();

