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
use Patchwork\JSqueeze;
 
if (!defined('BASE_PATH'))
	die();


function get_tmp_folder(){
	return APP_PATH.'/assets/tmp'; // set to false for no caching
}
	
function get_tmp_url(){
	return APP_URL.'/assets/tmp'; // set to false for no caching
}

function add_scss($scss = null){
	static $scsss = array();
	if ($scss)
		$scsss[] = $scss;
	return $scsss;
}

function print_scss_tags(){
	global $smap;
	$smap['lazy_css'] = array();
	
	require_once APP_PATH.'/assets/lib/scssphp/scss.inc.php';

	$formatter = IS_DEBUG ? 'expanded' : 'compressed';

	$scss = new \Leafo\ScssPhp\Compiler(APP_PATH.'/assets/scss');
	$scss->setFormatter('Leafo\\ScssPhp\\Formatter\\'.ucfirst($formatter));
	
	$is_writable = is_writable(get_tmp_folder());
	
	foreach (array(
		array(
			'id' => 'BOOT',
			'inline' => true,
			'libs' => array(
			),
			'scss' => array('variables', 'mixins', 'reset', 'font', 'boot'),
		),
		array(
			'id' => 'MAIN',
			'lazy' => is_home(),
			'libs' => array(
				'fa' => 'lib/font-awesome-4.7.0/css/font-awesome.min.css',
				'scroll' => 'lib/simplebar/dist/simplebar.css',
			),
			'scss' => array_merge(array('variables', 'mixins', 'main', 'popup', 'access', 'code', 'rewind', 'menu', 'filters', 'lang', 'debug', 'footer', 'lists', 'home', 'api', 'sheets'), add_scss()),
		)
	) as $scss_config){
		
		$libs = isset($scss_config['libs']) ? $scss_config['libs'] : array();
		$scss_ids = $scss_config['scss'];

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
		
		$is_inline = !$is_writable || (!IS_INSTALL && !empty($scss_config['inline']));
		
		$scssName = 'smap-'.hash('sha256', implode('+', array_keys($path))).'-'.($is_inline ? 'inline' : $formatter).'-'.$date.'-'.ASSETS_INC.'.css';
		$dest = get_tmp_folder().'/'.$scssName;
		$css = false;

		// generate css if dest missing or dest's modification time is earlier than max of scss's modification times.
		if (!file_exists($dest)){
			$str = $before = '';
			foreach ($path as $id => $scss_path){
				$cstr = file_get_contents(ASSETS_PATH.'/'.$scss_path)." \n\n";
				$scss_base_path = preg_replace('#^(.*)(/[^/]+)(\?.*)?$#', '$1/', $scss_path);
				
				// replace url paths to match (it'll be read from src/assets/tmp)
				$cstr = preg_replace_callback('#\burl\s*\(\s*([\'"])([^\'"]+?)(\1)#ius', function($m) use ($scss_base_path, $is_inline, $is_writable){
					
					$ret = ASSETS_URL.'/'.$scss_base_path.$m[2];
					
					// simplify path ('X/../' => '')
					$ret = preg_replace('#(([^/]+)/\.\./)#', '', $ret);
					
					
					//echo '<b>'.$m[2].'</b> (IN '.$scss_base_path.')<br> >> '.$ret.' (ASSETS_URL: '.ASSETS_URL.')<br>';
					return 'url('.$m[1].$ret.$m[3];
					
				}, trim_any($cstr));
				
				if (isset($libs[$id]) && $str == '')
					$before .= '

/************************************************
 * File ID: '.strtoupper($id).'
 * Original file: '.ASSETS_URL.'/'.$scss_path.'
 */

'.$cstr; // convert URLs from /lib to /tmp
					
				else {
					$str .= strip_comments(scss_inject_mixins($cstr, $id), 'css');
				}
			}
			
			$css = get_disclaimer('css').'

/* 
 * $tateMapper\'s main CSS file.
 * Different licenses may apply.
 *
 ************************************************/

'.$before.'/************************************************ 
 * File ID: '.$scss_config['id'].'
 * Original files: '.implode(', ', $labels).'
 * See: '.get_repository_url('tree/master/src/assets/scss').'
 * License: '.get_license().'
 */

'.$scss->compile($str);
			
			if ($is_writable){
				file_put_contents(get_tmp_folder().'/'.$scssName, $css);
				$css = false;
			
			} else if (IS_INSTALL || is_dev() || is_admin()){
				
				if (!defined('TMP_WRITABLE_WARNED')){
					define('TMP_WRITABLE_WARNED', 1);
					print_nice_alert(array(
						'id' => 'warning',
						'class' => 'warning',
						'icon' => 'warning',
						'label' => 'Please make src/assets/tmp writable for fast loading!',
					));
				}
			}
					
		} 
		
		if ($css){
			echo '<style>'.$css.'</style>';
			
		} else if (!IS_INSTALL && !empty($scss_config['lazy']))
			$smap['lazy_css'][] = get_tmp_url().'/'.$scssName;
			
		else if (!IS_INSTALL && !empty($scss_config['inline'])){
		
			echo '<style>';
			readfile(get_tmp_folder().'/'.$scssName);
			echo '</style>';
		
		} else {
			?>
			<link rel="stylesheet" type="text/css" href="<?= get_tmp_url().'/'.$scssName ?>" media="all" />
			<?php
		}
	}
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
	
	$vars = array(
		'ajaxUrl' => REAL_BASE_URL,
		'session' => $session,
		'refreshMap' => !empty($_GET['stop']) ? 0 : 1,
		'searchUrl' => BASE_URL.'?q=%s'.($filters ? '&'.http_encode($filters) : ''),
		'lang' => get_lang(true),
		'loading' => get_loading(),
		'lazy_css' => $smap['lazy_css'],
		'lazy_stop' => is_dev() && !empty($_GET['lazy_stop']),
		'nice_alerts' => array(),
	);
	?>
	<script type="text/javascript">
		var SMAP = <?= json_encode($vars) ?>;
	</script>
	<?php
	
	print_js_tags();
}

add_filter('body_classes', 'body_classes_lazy_css');
function body_classes_lazy_css($classes){
	global $smap;
	if (!empty($smap['lazy_css']))
		$classes[] = 'lazy-loading';
	return $classes;
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
		'scroll' => 'lib/simplebar/dist/simplebar.js',
	);
	
	$date = null;
	$js_ids = $libs; // include libraries first, in order
	
	foreach (array(
		'helpers',
		'ajax',
		'actions',
		'law',
		'access',
		'autocomplete',
		'live',
		'statuses',
		'infinite',
		'multiblock',
		'tips',
		'assets',
		'popup',
		'debug',
		'date',
		'home',
		'lists',
		'menu',
		'boot',
	) as $js)
		$js_ids[$js] = 'js/'.$js.'.js';
	
	foreach (add_js() as $js) // then custom javascripts
		$js_ids[$js] = 'js/'.$js.'.js';
		
	if (IS_DEBUG || is_dev() || IS_INSTALL){
		foreach ($js_ids as $js){
			if (!($time = filemtime(ASSETS_PATH.'/'.$js)))
				die('ERROR: can\'t read file '.$js);
				
			echo '<script type="text/javascript" src="'.ASSETS_URL.'/'.$js.'?t='.$time.'"></script>';
		}
		return;
	}
		
	foreach ($js_ids as $js_id => $js){
		$path = ASSETS_PATH.'/'.$js;
		$date = $date ? max(filemtime($path), $date) : filemtime($path);
	}
	
	$jsName = 'smap-'.hash('sha256', implode('+', array_keys($js_ids))).'-'.$date.'-'.ASSETS_INC.'.js';
	$dest = get_tmp_folder().'/'.$jsName;

	// generate css if dest missing or dest's modification time is earlier than max of scss's modification times.
	if (!file_exists($dest)){

		require_once ASSETS_PATH.'/lib/jsqueeze/src/JSqueeze.php';
		$jz = new JSqueeze();
		
		$str = '';
		foreach ($js_ids as $js_id => $js){
			$cstr = file_get_contents(ASSETS_PATH.'/'.$js);
			if (!isset($libs[$js_id])){
				$cstr = strip_comments($cstr, 'js');
				if (MINIFY_JS){

					$cstr = $jz->squeeze(
						$cstr,
						true,   // $singleLine
						false,   // $keepImportantComments
						false   // $specialVarRx
					);
				}
			} else
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
	
	define('SMAP_JS_PRINTED', true);
}

function scss_inject_mixins($str, $id){
	if (in_array($id, array('variable', 'mixins')))
		return $str;
	
	// properties presents as a mixin (in src/assets/scss/_mixins.scss)
	$properties = array(
		'box-shadow',
		'transition',
		'opacity',
		'transition',
		'transition-delay',
	);
	$str = preg_replace('#('.implode('|', $properties).')\s*:\s*([^;]+)#i', '@include $1($2)', $str);
	
	return $str;
}
