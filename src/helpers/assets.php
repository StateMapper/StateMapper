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
	
function get_tmp_folder(){
	return APP_PATH.'/assets/tmp'; // set to false for no caching
}
	
function get_tmp_url(){
	return APP_URL.'/assets/tmp'; // set to false for no caching
}

function print_scss_tags(){
	// libraries to include
	$libs = array(
		'fontawesome' => 'lib/font-awesome-4.7.0/css/font-awesome.min.css',
	);
	
	// custom scss to include
	$scss_ids = array('variables', 'mixins', 'reset', 'font', 'main', 'rewind', 'menu', 'debug', 'footer', 'home'); 
	
	require_once APP_PATH.'/assets/lib/scssphp/scss.inc.php';
	
	$formatter = IS_DEBUG ? 'expanded' : 'compressed';
	
	$scss = new \Leafo\ScssPhp\Compiler(APP_PATH.'/assets/scss');
	$scss->setFormatter('Leafo\\ScssPhp\\Formatter\\'.ucfirst($formatter));

	$path = $libs; // libraries first

	$labels = array();
	foreach ($scss_ids as $scss_id){
		$labels[] = '_'.$scss_id.'.scss';
		$path[$scss_id] = 'scss/_'.$scss_id.'.scss';
	}
	
	$date = null;
	foreach ($path as $p){
		$p = ASSETS_PATH.'/'.$p;
		$date = $date ? max(filemtime($p), $date) : filemtime($p);
	}
	
	$scssName = 'smap-'.implode('+', array_keys($path)).'-'.$formatter.'-'.$date.'-'.ASSETS_INC.'.css';
	$dest = get_tmp_folder().'/'.$scssName;

	// generate css if dest missing or dest's modification time is earlier than max of scss's modification times.
	if (!file_exists($dest)){
		$str = $before = '';
		foreach ($path as $id => $scss_path){
			$cstr = file_get_contents(ASSETS_PATH.'/'.$scss_path)." \n\n";
			if (isset($libs[$id]) && $str == '')
				$before .= '
			

/************************************************
 * File ID: '.strtoupper($id).'
 * Original file: '.ASSETS_URL.'/'.$scss_path.'
 */

'.preg_replace('#\burl\s*\(([\'"])#ius', '$0../'.dirname($scss_path).'/', trim_any($cstr)); // convert URLs from /lib to /tmp
				
			else 
				$str .= strip_comments($cstr, 'css');;
		}
		file_put_contents(get_tmp_folder().'/'.$scssName, get_disclaimer('css').'

/* 
 * $tateMapper\'s main CSS file.
 * Different licenses may apply.
 *
 ************************************************/

'.$before.'
			

/************************************************ 
 * File ID: MAIN
 * Original files: '.implode(', ', $labels).'
 * See: '.get_repository_url('tree/master/src/assets/scss').'
 * License: '.get_license().'
 */

'.$scss->compile($str));
	} 
	?>
	<link rel="stylesheet" type="text/css" href="<?= get_tmp_url().'/'.$scssName ?>" media="all" />
	<?php
}

add_action('head', 'head_print_assets');
function head_print_assets(){
	global $smap;
	$session = array(
		'query' => isset($smap['query']) ? $smap['query'] : array(),
		'filters' => isset($smap['filters']) ? $smap['filters'] : array(),
	);
	
	print_scss_tags();

	$filters = !empty($smap['filters']) ? $smap['filters'] : array();
	unset($filters['q']);
	
	// pass variables from PHP to JS
	?>
	<script type="text/javascript">
		var SMAP = <?= json_encode(array(
			'ajaxUrl' => REAL_BASE_URL,
			'session' => $session,
			'refreshMap' => !empty($_GET['stop']) ? 0 : 1,
			'searchUrl' => BASE_URL.'?q=%s'.($filters ? '&'.http_encode($filters) : ''),
			'lang' => get_lang(true),
		)) ?>;
	</script>
	<?php
	
	print_js_tags();
}

function add_js($js = null){
	static $jss = array();
	if ($js)
		$jss[] = $js;
	return $jss;
}

function print_js_tags(){
	
	// libraries to include
	$libs = array(
		'jquery' => 'lib/jquery-3.2.1/jquery-3.2.1.min.js',
		'tippy' => 'lib/tippyjs-2.0.0-beta.2/dist/tippy.all.min.js',
	);
	
	$date = null;
	$js_ids = $libs; // include libraries first, in order
	
	$js_ids['boot'] = 'js/boot.js'; // boot.js first after frameworks!!
	foreach (add_js() as $js) // then custom javascripts
		$js_ids[$js] = 'js/'.$js.'.js';
		
	if (IS_DEBUG || is_admin()){
		foreach ($js_ids as $js)
			echo '<script type="text/javascript" src="'.ASSETS_URL.'/'.$js.'"></script>';
		return;
	}
		
	foreach ($js_ids as $js_id => $js){
		$path = ASSETS_PATH.'/'.$js;
		$date = $date ? max(filemtime($path), $date) : filemtime($path);
	}
	
	$jsName = 'smap-'.implode('+', array_keys($js_ids)).'-'.$date.'-'.ASSETS_INC.'.js';
	$dest = get_tmp_folder().'/'.$jsName;

	// generate css if dest missing or dest's modification time is earlier than max of scss's modification times.
	if (!file_exists($dest)){

		$str = '';
		foreach ($js_ids as $js_id => $js){
			$cstr = file_get_contents(ASSETS_PATH.'/'.$js);
			if (!isset($libs[$js_id]))
				$cstr = strip_comments($cstr, 'js');
			else
				$cstr = trim_any($cstr);
			$str .= '
			

/************************************************ 
 * File ID: '.strtoupper($js_id).'
 * Original file: '.ASSETS_URL.'/'.$js.(isset($libs[$js_id]) ? '' : '
 * License: '.get_license()).'
 */

'.$cstr." \n\n";
		}
			
		file_put_contents($dest, get_disclaimer('js').'

/* 
 * $tateMapper\'s main JS file.
 * Different licenses may apply.
 *
 ************************************************/

'.$str);
	} 
	
	echo '<script type="text/javascript" async src="'.get_tmp_url().'/'.$jsName.'"></script>';
}
