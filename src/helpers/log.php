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


function print_log($str, $opts = array()){
	global $smap;

	if (empty($smap['debug']) && (!IS_CLI || !in_array($smap['call'], array('spide'))) && !@SMAP_FORCE_OUTPUT)
		return;
		
	// see https://unix.stackexchange.com/questions/148/colorizing-your-terminal-and-shell-environment for colors
	$color = '';
	if (!empty($opts['color']))
		switch ($opts['color']){
			case 'grey': $color = "\e[0;30m"; break;
			case 'green': $color = "\e[0;32m"; break;
			case 'lgreen': $color = "\e[1;32m"; break;
			case 'red': $color = "\e[0;31m"; break;
			case 'lred': $color = "\e[1;31m"; break;
			case 'lblue': $color = "\e[0;34m"; break;
		}
	if (defined('WORKER_ID'))
		$opts['worker_id'] = WORKER_ID;

	$o = '';
	if (IS_CLI){
		$o = $color;
		if (isset($opts['worker_id']))
			$o .= '[W'.str_pad($opts['worker_id']+1, 2, '0', STR_PAD_LEFT).']';
	}
	
	if (IS_CLI && !empty($smap['query'])){
		
		if (!isset($opts['spider_id']) && isset($smap['query']['date']))
			$o .= '['.$smap['query']['date'].']';
		else if (!empty($smap['query']['schema']))
			$o .= '['.$smap['query']['schema'].']';
	}
		
	if (isset($smap['query']['id']))
		$o .= '/'.$smap['query']['id'];
		
	if (IS_CLI)
		$o .= ' ';
	
	$o .= $str;
	
	if (IS_CLI)
		$o .= "\e[0m".PHP_EOL;
	else
		$o .= '<br>';
		
	echo $o;
}


