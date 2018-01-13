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


function get_license($short = false){
	return $short ? 'AGPLv3' : 'GNU AGPLv3';
}

function get_disclaimer($comment_style = null){
	$d = 'StateMapper: '.get_slogan().'.
Redesign of Kaos155 <https://github.com/ingobernable/Kaos155>, by the same Ingoberlab team.

Copyright (C) '.get_copyright_range().'  StateMapper.net <statemapper@riseup.net>

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program.  If not, see <https://www.gnu.org/licenses/>.';
	switch ($comment_style){
		case 'html':
			return '<!--
'.$d.'
-->';
/*
 * 
 */
		case 'css':
		case 'js':
		case 'php':
			return '/*
 * '.implode("\n * ", explode("\n", $d)).'
 */';
	}
	return $d;
}

function get_copyright_range(){
	$str = '2017';
	$cur = date('Y');
	if ($cur != $str)
		$str .= '-'.$cur;
	return $str;
}
