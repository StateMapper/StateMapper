<?php

if (!defined('BASE_PATH'))
	die();


require('entities.php');
require('spiders.php');
require('locations.php');
require('string.php');
require('currency.php');
require('fetch.php');
require('time.php');
require('db.php');
require('bulletins.php');
require('schemas.php');
require('labels.php');
require('map.php');
require('file.php');
require('names.php');

// addons
require(APP_PATH.'/addons/wikipedia.php');
require(APP_PATH.'/addons/relatives.php');
require(APP_PATH.'/addons/here_com.php');
require(APP_PATH.'/addons/company_website.php');
require(APP_PATH.'/addons/schema_links.php');


function kaosAnonymize($url){
	return 'https://anon.to/?'.$url;
}


function add_action($action, $cb = null){
	static $actions = array();

	if (!$cb)
		return isset($actions[$action]) ? $actions[$action] : array();

	if (!isset($actions[$action]))
		$actions[$action] = array();
	$actions[$action][] = $cb;
}

function do_action($action){
	$args = (array) func_get_args();
	array_splice($args, 0, 1);
	foreach (add_action($action) as $cb)
		call_user_func_array($cb, $args);
}



// TODO: implement using this order by preference
function kaosFormatPreference(){
	return array('xml', 'html', 'pdf');
}


class KaosError {

	public $msg = null;
	public $opts = array();

	public function __construct($msg, $opts = array()){
		$this->msg = $msg;
		$this->opts = $opts;
	}
}

function kaosDie($str_or_error = null, $error = null){
	global $kaosCall;
	if (!$str_or_error)
		$str_or_error = 'Operation forbidden';

	$msg = (is_string($str_or_error) ? $str_or_error : $str_or_error->msg).($error ? $error->msg : '');

	if (!empty($kaosCall) && !KAOS_IS_CLI){
		kaosAPIReturn(array(
			'success' => false,
			'query' => isset($kaosCall['query']) ? $kaosCall['query'] : null,
			'error' => $msg
		), 'Error');
	}

	// cli or not api
	echo $msg.PHP_EOL;
	exit(1);
}

function kaosIsError($obj){
	return is_object($obj) && get_class($obj) == 'KaosError';
}

function kaosAPIReturn($obj, $title = null){
	global $kaosCall;
	$human = empty($kaosCall['raw']);

	if (is_array($obj) && isset($obj['success'])){
		$obj = array('success' => $obj['success'], 'stats' => array()) + $obj;
		foreach (array('fetchOrigins', 'fetchedUrls', 'torIpChanges') as $k)
			$obj['stats'][$k] = !empty($kaosCall[$k]) ? $kaosCall[$k] : array();
	}

	if ($human && !KAOS_IS_CLI){
		echo kaosGetTemplate('APIReturn', array('obj' => $obj, 'title' => $title));
		exit();

	} else if (!KAOS_IS_CLI && !$human && !empty($_GET['human'])){
		echo kaosJSON($obj);

	} else {
		if (!KAOS_IS_CLI)
			header('Content-type: application/json');

		if (KAOS_IS_CLI && $human)
			echo kaosJSON($obj);
		else
			echo json_encode($obj, JSON_UNESCAPED_UNICODE);
	}
	exit();
}

function kaosLsdir($dir_path){
	$files = array();
	$folders = array();
	$dir = opendir($dir_path);
	while ($file = readdir($dir))
		if ($file != "." && $file != ".." && substr($file, -1) != "~"){ // avoid Unix temp files (ending in ~)
			if (is_file(rtrim($dir_path, '/').'/'.$file))
				$files[] = $file;
			else
				$folders[] = $file;
		}
	closedir($dir);
	return array_merge($folders, $files);
}


function head($title = null){
	add_js('helpers');

	global $kaosCall, $kaosPage;
	$session = array(
		'query' => isset($kaosCall['query']) ? $kaosCall['query'] : array(),
	);
	?>
	<!--
	StateMapper: an international, collaborative, public data reviewing and monitoring tool.
	Redesign of Kaos155 <https://github.com/ingobernable/Kaos155>, by the same Ingoberlab team.

    Copyright (C) <?= getCopyrightRange() ?>  StateMapper.net <statemapper@riseup.net>

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <https://www.gnu.org/licenses/>.
    -->

	<meta http-equiv="content-type" content="text/html; charset=utf-8">

	<link rel="stylesheet" type="text/css" href="<?= ASSETS_URL ?>/lib/font-awesome-4.7.0/css/font-awesome.min.css" />
	<link rel="stylesheet" type="text/css" href="<?= ASSETS_URL ?>/css/api.css?v=<?= KAOS_ASSETS_INC ?>" />

	<script type="text/javascript" src="<?= ASSETS_URL ?>/lib/jquery-3.2.1/jquery-3.2.1.min.js"></script>
	<script type="text/javascript" src="<?= ASSETS_URL ?>/lib/tippyjs-2.0.0-beta.2/dist/tippy.all.min.js"></script>

	<script type="text/javascript">
		var KAOS = {
			ajaxUrl: '<?= BASE_URL ?>',
			session: <?= json_encode($session) ?>,
			refreshMap: <?= (!empty($_GET['stop']) ? '0' : '1') ?>,
			searchUrl: '<?= add_url_arg('q', '%s', isHome() ? null : BASE_URL, false) ?>'
		};
	</script>
	<?php
	foreach (add_js() as $js)
		echo '<script type="text/javascript" src="'.ASSETS_URL.'/js/'.$js.'.js?v='.KAOS_ASSETS_INC.'"></script>';

	$fav = 'map-signs';
	if (!empty($kaosCall['entity'])){
		$types = getEntityTypes();
		$fav = $types[$kaosCall['entity']['type']]['icon'];
	} else if ($kaosPage == 'api' && (empty($kaosCall['call']) || $kaosCall['call'] == 'schema'))
		$fav = 'book';
	else if ($kaosPage == 'api' && $kaosCall['call'] == 'rewind')
		$fav = 'backward';
		
	?>
	<title><?= ($title ? $title.' - ' : '').'StateMapper' ?></title>
	<link rel="icon" href="<?= ASSETS_URL.'/images/favicons/'.$fav.'.ico' ?>" type="image/x-icon" />

	<style>
		@font-face {
			font-family: 'roboto';
			src: url('<?= ASSETS_URL ?>/font/roboto/Roboto-Light.ttf') format('truetype'); /* Chrome 4+, Firefox 3.5, Opera 10+, Safari 3—5 */
		}
	</style>
	<?php
}

function add_js($js = null){
	static $jss = array();
	if ($js)
		$jss[] = $js;
	return $jss;
}



function kaosCurrentURL($stripArgs = false){
	if (isset($_SERVER, $_SERVER['HTTP_HOST']))
		$url = (isset($_SERVER['HTTPS']) ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
	else {

		global $kaosCall;
		if (!empty($kaosCall['cliArgs']))
			$url = $kaosCall['cliArgs'][0];
		else
			return null;
	}
	if ($stripArgs && ($nurl = strstr($url, '?', true)))
		return $nurl;
	return $url;
}



function kaosPrintTableTd($v){
	if (is_object($v) || is_array($v))
		return kaosPrintTable($v, null, false);
	return $v;
}

function kaosPrintTable($trs, $th = null, $echo = true, $class = 'kaos-basic-table'){
	$ntrs = array();
	$isAssoc = is_array($trs) && array_key_exists(0, $trs);

	if ($th){
		$trTh = array();
		foreach ($th as $cth)
			$trTh[] = '<th>'.$cth.'</th>';
		$ntrs[] = '<tr>'.implode('', $trTh).'</tr>';
	}
	if ($isAssoc && !is_object($trs[0]) && !is_array($trs[0])){
		foreach ($trs as $v)
			$ntrs[] = '<tr><td>'.kaosPrintTableTd($v).'</td></tr>';

	} else
		foreach ($isAssoc ? $trs : array($trs) as $t){
			$tr = array();
			foreach ($t as $k => $v){
				$tr[] = '<td>'.kaosPrintTableTd($v).'</td>';
			}
			$ntrs[] = '<tr>'.implode('', $tr).'</tr>';
		}
	$ret = $ntrs ? '<div><table border="0" cellspacing="0" cellpadding="0" class="'.$class.'">'.implode('', $ntrs).'</table></div>' : '';
	if ($echo){
		echo $ret;
		return $ret != '';
	}
	return $ret;
}


function kaosGetCommandPath($cmd){
	exec('whereis -b '.$cmd, $output, $ret);
	if (empty($ret) && !empty($output) && !preg_match('#^[a-z0-9_]+:$#i', trim($output[0]), $m))
		return preg_match('#^[a-z0-9_]+:\s*([a-z0-9_/]+)(\s*.*)?$#i', trim($output[0]), $m) ? $m[1] : false;
	return false;
}

function kaosGetCommandRunning($cmd){
	exec('ps -aux | grep "'.$cmd.'" | grep -v "grep '.$cmd.'" | tr -s " " | cut -d" " -f2', $output, $ret);
	if (empty($ret) && !empty($output))
		return $output[0];
	return false;
}

function waitForLock($key, $timeout = 5){ // timeout in seconds
	$begin = time();
	do {
		$id = lock($key);
		if ($id !== false)
			return $id;
		usleep(500000); // half second
	} while (time() - $begin < $timeout);
	return false;
}

function lock($key){
	$id = insert('locks', array('target' => $key, 'created' => date('Y-m-d H:i:s')));
	$lock_id = get('SELECT id FROM locks WHERE target = %s ORDER BY id ASC', array($key));
	return $id === intval($lock_id) ? $key : false;
}

function unlock($key){
	if (!empty($key))
		query('DELETE FROM locks WHERE target = %s', array($key));
}

function updateOption($name, $value){
	$id = addOption($name, $value);
	query('DELETE FROM options WHERE name = %s AND id != %s ORDER BY id ASC', array($name, $id));
}

function addOption($name, $value){
	return insert('options', array('name' => $name, 'value' => is_object($value) || is_array($value) || is_bool($value) ? serialize($value) : $value));
}


function getOption($name, $default = null){
	$value = get('SELECT value FROM options WHERE name = %s ORDER BY id DESC LIMIT 1', array($name));
	if ($value === null)
		return $default;

	try {
		$unserialized = @unserialize($value);
	} catch (Exception $e){
		return $value;
	}
	return $unserialized === false ? $value : $unserialized;
}

function deleteOption($name){
	return query('DELETE FROM options WHERE name = %s', $name);
}

function addGetOption($name, $val){
	$id = get('SELECT id FROM options WHERE name = %s AND value = %s LIMIT 1', array($name, $val));
	if ($id === null)
		$id = insert('options', array(
			'name' => $name,
			'value' => $val,
		));
	return $id;
}


function kaosPrintLog($str, $opts = array()){
	global $kaosCall;

	if (empty($kaosCall['debug']) && (!KAOS_IS_CLI || !in_array($kaosCall['call'], array('spide'))) && (!defined('KAOS_FORCE_OUTPUT') || !KAOS_FORCE_OUTPUT))
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
		}
	if (defined('KAOS_WORKER_ID'))
		$opts['worker_id'] = KAOS_WORKER_ID;

	echo (KAOS_IS_CLI ? $color.(isset($opts['worker_id']) ? '[W'.str_pad($opts['worker_id']+1, 2, '0', STR_PAD_LEFT).']' : '') : '')
		.(KAOS_IS_CLI && !empty($kaosCall['query']) ? (!isset($opts['spider_id']) && isset($kaosCall['query']['date']) ? '['.$kaosCall['query']['date'].']' : '['.$kaosCall['query']['schema'].']').(isset($kaosCall['query']['id']) ? '/'.$kaosCall['query']['id'] : '') : '')
		.(KAOS_IS_CLI ? ' ' : '')
		.$str
		.(KAOS_IS_CLI ? "\e[0m".PHP_EOL : '<br>');
}



function kaosSetLocale($lang){
	putenv('LANG='.$lang);
	putenv('LANGUAGE='.$lang);
	setlocale(LC_ALL, $lang.'.UTF-8');
	bindtextdomain('kaos', APP_PATH.'/languages');
	bind_textdomain_codeset('kaos', 'UTF-8');
	textdomain('kaos');
}

function getLang(){
	return !empty($_GET['lang']) ? substr($_GET['lang'], 0, 2) : (defined('LANG') ? substr(LANG, 0, 2) : 'en');
}



/* error to exception bridge
set_error_handler(function($errno, $errstr, $errfile, $errline, array $errcontext) {
    // error was suppressed with the @-operator
    if (0 === error_reporting()) {
        return false;
    }

    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});*/

function kaosGetFormatFetcher($format, $parent = null){
	static $cache = array();
	if (isset($cache[$format]))
		return $cache[$format];

	// load format fetcher
	require_once(APP_PATH.'/fetcher/BulletinFetcher.php');
	$fetcherClass = 'BulletinFetcher'.ucfirst($format);
	$fetcherPath = APP_PATH.'/fetcher/formats/'.$fetcherClass.'.php';
	if (!is_file($fetcherPath))
		return new KaosError('unknown fetchProcole format '.$format);

	require_once $fetcherPath;
	$cache[$format] = new $fetcherClass($parent);
	return $cache[$format];
}

function logo(){
	global $kaosPage, $kaosCall;
	?>
	<div class="kaos-api-result-intro-name">
		<?php
			if (isHome(true)){
				?>
				<a href="#" onclick="jQuery('#kaosSearch').focus().select(); return false"><img src="<?= ASSETS_URL.'/images/logo/logo-transparent.png?v='.KAOS_ASSETS_INC ?>" /></a>
				<?php
			} else {
				?>
				<a href="<?= BASE_URL.($kaosPage == 'api' && !empty($kaosCall['call']) ? 'api' : '') ?>" title="<?= ($kaosPage == 'api' && !empty($kaosCall['call']) ? 'Go back to bulletins' : 'Go to homepage') ?>"><img src="<?= ASSETS_URL.'/images/logo/logo-transparent.png?v='.KAOS_ASSETS_INC ?>" /></a>
				<?php
			}
		?>
	</div>
	<?php
}

function footer(){
	global $kaosCall;

	$str = array();

	if (isAdmin()){
		$dbduration = 0;
		if (!empty($kaosCall['queries']))
			foreach ($kaosCall['queries'] as $q)
				$dbduration += $q['duration'];

		if (!empty($kaosCall['begin'])){

			$str[] = '<span><i class="fa fa-clock-o"></i> '.humanTimeDiff($kaosCall['begin']).' exec</span>';

			if (isset($kaosCall['fetches'])){

				$cstr = array();
				if (!empty($kaosCall['fetchOrigins']))
					foreach ($kaosCall['fetchOrigins'] as $f => $count)
						$cstr[] = number_format($count, 0).' fetched from '.$f;

				$cprec = array();
				if (!empty($kaosCall['fetchDuration']))
					$cprec[] = humanTimeDiff(0, $kaosCall['fetchDuration']).' fetch';
				if (!empty($kaosCall['fetchWaitDuration']))
					$cprec[] = humanTimeDiff(0, $kaosCall['fetchWaitDuration']).' wait';

				$str[] = '<span title="'.esc_attr(implode("\n", $cstr)).'"><i class="fa fa-download"></i> '.number_format($kaosCall['fetches'], 0).' fetches'.($cprec ? ' ('.implode(', ', $cprec).')' : '').'</span>';

			}
		}

		if (!empty($kaosCall['queries']))
			$str[] = '<span title="'.esc_attr('click to show all executed queries').'" class="kaos-show-queries"><i class="fa fa-database"></i> '.number_format(count($kaosCall['queries']), 0).' queries ('.humanTimeDiff(0, $dbduration).')</span>';

		if (!$str)
			return;

		if (!empty($kaosCall['queries'])){

			$slow = $kaosCall['queries'];
			usort($slow, function($a, $b){
				return $a['duration'] < $b['duration'];
			});
			array_splice($slow, 10);

			$has = 0;
			foreach ($slow as $q){
				if ($q['duration'] < 1)
					continue;
				$has++;
			}

			?>
			<div class="kaos-api-queries">
				<?php
					if ($has){
					?>
						<div class="kaos-api-queries-title">Top 10 slow queries:</div>
						<table border="0">
						<?php
							foreach ($slow as $q)
								printQueryLine($q, true);
						?>
						</table>
					<?php
				}
				?>
				<div class="kaos-api-queries-title">All <?= number_format(count($kaosCall['queries']), 0) ?> queries:</div>
				<table border="0">
				<?php
					foreach ($kaosCall['queries'] as $q)
						printQueryLine($q);
				?>
				</table>
			</div>
			<?php
		}
	}
	?>
	<div class="footer"><?php

		if ($str)
			echo implode('', $str).'<span>|</span>';
			
		if (!isAdmin())
			echo '<span><a href="'.kaosAnonymize(getRepositoryUrl('#top')).'" target="_blank">Collaborative work licensed under GNU GPLv3</a></span>';

		?><span class="copyright"><span class="menu menu-right menu-top">
			<span class="menu-button"><i class="fa fa-copyright"></i> <?= getCopyrightRange() ?> StateMapper.net</span>
			<span class="menu-wrap">
				<span class="menu-menu">
					<ul class="menu-inner">
						<?php if (!isAdmin()){ ?>
							<li><a href="<?= esc_attr(BASE_URL.'login?redirect='.urlencode(kaosCurrentURL())) ?>"><i class="fa fa-sign-in"></i> Login</a></li>
						<?php } ?>
						
						<?php if (!isHome(true)){ ?>
							<li><a href="<?= add_url_arg('human', 1, preg_replace('#^(.*?)/?([\?\#].*)?$#iu', '$1/raw$2', kaosCurrentURL())) ?>"><i class="fa fa-plug"></i> Human JSON</a></li>
							<li><a href="<?= preg_replace('#^(.*?)/?([\?\#].*)?$#iu', '$1/raw$2', kaosCurrentURL()) ?>"><i class="fa fa-plug"></i> Raw JSON</a></li>
						<?php } ?>

						<li><a target="_blank" href="<?= esc_attr(kaosAnonymize(getRepositoryUrl('#top'))) ?>"><i class="fa fa-question-circle-o"></i> About</a></li>

						<?php if (isAdmin()){ ?>
							<li><a href="<?= esc_attr(BASE_URL.'logout') ?>"><i class="fa fa-sign-out"></i> Logout</a></li>
						<?php } ?>
					</ul>
				</span>
			</span>
		</span>
	</div>
	<div class="login-popup popup">
		<div class="popup-bg"></div>
		<div class="popup-inner">
			<div>Please enter your email and password. </div>
			<div>
				<label><input type="radio" name="login_form_mode" value="login" selected /> Login</label>
				<label><input type="radio" name="login_form_mode" value="signup" /> Sign up</label>
			</div>
			<div>
				<div><input type="text" name="login_form_login" placeholder="Login.." /></div>
				<div><input type="password" name="login_form_pass" placeholder="Password.." /></div>
			</div>
		</div>
	</div>
	<?php
}

function printQueryLine($q, $onlySlow = false){
	$class = '';
	if ($q['duration'] < 1){
		if ($onlySlow)
			return;
	} else
		$class .= 'kaos-api-queries-slow';
		
	$str = array();
	if (!empty($q['explain']))
		foreach ($q['explain'] as $l){
			$type = strtolower($l['type']);
			
			// see https://www.sitepoint.com/using-explain-to-write-better-mysql-queries/ to optimize queries..
			// and https://stackoverflow.com/questions/1157970/understanding-mysql-explain
			
			$icon = 'key';
			$suffix = '';
			if (in_array($type, array('system', 'const', 'eq_ref')) || $l['rows'] <= 1){
				$status = 'best-optimized';
				$statusStr = 'Query is GREATLY OPTIMIZED';
				$icon = 'star';
			
			} else {
				$suffix = '<span>'.$l['key'].'</span>';
				if ($type == 'ref' && $l['rows'] < 2000){
					$status = 'optimized';
					$statusStr = 'Query COULD BE OPTIMIZED (though few matches)';

				} else if (!empty($l['possible_keys'])){
					$status = 'optimizable';
					$statusStr = 'Query COULD BE OPTIMIZED';

				} else {
					$status = 'not-optimized';
					$statusStr = 'Query is NOT OPTIMIZED';
				}
			}
			
			$iclass = 'query-key-icon query-key-icon-'.$type.' query-key-status-'.$status;
			$str[] = '<span class="'.$iclass.'" title="'.esc_attr('<div style="text-align: left"><strong>'.$statusStr.':</strong><br>'.debug($l, false).'</div>').'"><i class="fa fa-'.$icon.'"></i>'.$suffix.'</span>';
		}
	echo '<tr class="'.$class.'"><td class="kaos-api-queries-prefix">'.str_pad('['.humanTimeDiff(0, $q['duration']).']', 5, ' ').' </td><td class="query-key-td">'.implode('', $str).'</td><td class="kaos-api-queries-val">'.$q['query'].'</td></tr>';
}


function kaosSearchResults($args, &$count = null){
	$args += array(
		'query' => '',
		'etype' => null,
		'esubtype' => null,
		'country' => null,
		'locations' => null,
		'etypes' => array(), // array of person, institution, company or company/es/sl
		'misc' => null, // [buggy]
	);
	$where = array();

	if (!empty($args['query']) && trim($args['query']) != ''){
		$query = cleanKeywords($args['query']);

		foreach (explode(' ', $query) as $q)
			$where[] = queryPrepare('keywords LIKE %s', array('%'.$q.'%'));
	}

	if ($args['etype'])
		$where[] = queryPrepare('type = %s', $args['etype']);
	if ($args['esubtype'])
		$where[] = queryPrepare('subtype = %s', $args['esubtype']);

	if ($args['country'])
		$where[] = queryPrepare('country = %s', $args['country']);

	if ($args['locations'])
		foreach ($args['locations'] as $l){
			// TODO: join with statuses on location type and filter where
		}

	if ($args['etypes']){
		$subwhere = array();
		foreach ($args['etypes'] as $etype){
			$etype = explode('/', $etype);
			if (count($etype) < 3)
				$subwhere[] = queryPrepare('type = %s', $etype[0]);
			else
				$subwhere[] = queryPrepare('type = %s AND subtype = %s AND country = %s', array($etype[0], strtoupper($etype[2]), strtolower($etype[1])));
		}
		$where[] = '( '.implode(' OR ', $subwhere).' )';
	}

	if (!empty($args['misc']) && $args['misc'] == 'buggy')
		$where[] = '(( type = "person" AND LENGTH(name) > 40 OR LENGTH(first_name) > 30) OR LENGTH(name) > 80 )';
	
	$q = 'FROM entities WHERE '.implode(' AND ', $where);
	$res = query('SELECT id, country, type, subtype, name, first_name, slug '.$q.' ORDER BY name ASC, first_name ASC'.(!empty($args['limit']) ? ' LIMIT '.$args['limit'] : ''));
	
	if ($count !== null)
		$count = get('SELECT COUNT(id) '.$q);
	return $res;
}

function kaosIsCall($call){
	global $kaosCall;
	if (!empty($kaosCall) && !empty($kaosCall['call']))
		return is_array($call) ? in_array($kaosCall['call'], $call) : $kaosCall['call'] == $call;
	return false;
}

function headerRight(){
	global $kaosPage, $kaosCall;

	ob_start();
	?>
	<div class="kaos-api-result-header-right">
		<?php if ($kaosPage == 'api' && $kaosCall && !empty($kaosCall['call'])){
			$tr = array('precept', 'filter');
			?>
			<div class="kaos-top-actions">
				<?php if (!empty($kaosCall['query']['date']) && in_array($kaosCall['call'], array('fetch', 'parse', 'lint', 'extract')) && kaosSchemaHasFeature($kaosCall['query']['schema'], 'fetch')){ ?>
					<span class="menu menu-right kaos-top-date-menu">
						<a href="#" title="Date" class="kaos-top-date kaos-top-action-active">
							<i class="fa fa-calendar"></i>
							<span class="kaos-query-date-wrap">
								<span class="kaos-query-date-main"><?= date_i18n('M j', strtotime($kaosCall['query']['date'])) ?></span>
								<span class="kaos-query-date-year"><?= date_i18n('Y', strtotime($kaosCall['query']['date'])) ?></span>
							</span>
						</a>
						<span class="menu-wrap">
							<span class="menu-menu">
								<span class="menu-inner">
									<span class="kaos-calendar kaos-top-calendar"><input type="date" id="date" value="<?= $kaosCall['query']['date'] ?>" autocomplete="off" data-kaos-url="<?= kaosGetUrl(array(
										'date' => '%s',
									) + $kaosCall['query'], $kaosCall['call']) ?>" data-kaos-oval="<?= $kaosCall['query']['date'] ?>" /><button><i class="fa fa-arrow-right"></i></button></span>
								</span>
							</span>
						</span>
					</span>
				<?php } ?>
				<?php
					if (kaosIsCall(array('soldiers', 'ambassadors'))){
						$schema = kaosGetSchema($kaosCall['query']['schema']);
						if (in_array($schema->type, array('bulletin', 'institution'))){
							?>
							<a href="<?= kaosGetUrl($kaosCall['query'], 'soldiers') ?>" title="" class="<?php if (kaosIsCall('soldiers')) echo 'kaos-top-action-active'; ?>"><i class="fa fa-fire"></i><span>Soldiers</span></a>
							<?php
						
						} else if ($schema->type == 'country'){
							?>
							<a href="<?= kaosGetUrl($kaosCall['query'], 'ambassadors') ?>" title="" class="<?php if (kaosIsCall('ambassadors')) echo 'kaos-top-action-active'; ?>"><i class="fa fa-globe"></i><span>Ambass.</span></a>
							<?php
						}
					} else { 
						?>
						<a href="<?= kaosGetUrl($kaosCall['query'], 'schema') ?>" title="" class="<?php if (kaosIsCall('schema')) echo 'kaos-top-action-active'; ?>"><i class="fa fa-book"></i><span>Schema</span></a>
						<a href="<?= kaosGetUrl($kaosCall['query'], 'fetch') ?>" title="" class="<?php

							if (kaosIsCall('fetch'))
								echo 'kaos-top-action-active';

							$fetchClass = $class = '';
							if (!kaosSchemaHasFeature($kaosCall['query']['schema'], 'fetch'))
								$fetchClass = $class = ' kaos-top-action-disabled';
							echo $class;

							?>"><i class="fa fa-cloud-download"></i><span>Fetch</span></a>
						<?php if (in_array($kaosCall['call'], array('fetch')) && kaosSchemaHasFeature($kaosCall['query']['schema'], 'fetch')){ ?>
							<a href="<?= kaosGetUrl($kaosCall['query'], 'fetch/raw') ?>" title="" class="<?= $class ?>"><i class="fa fa-arrows-alt"></i><span>Fullscreen</span></a>
							<a href="<?= kaosGetUrl($kaosCall['query'], 'download') ?>" title="" class="<?= $class ?>"><i class="fa fa-download"></i><span>Download</span></a>
						<?php } ?>
						<?php if (in_array($kaosCall['call'], array('fetch', 'lint')) && kaosSchemaHasFeature($kaosCall['query']['schema'], 'fetch')){ ?>
							<a href="<?= kaosGetUrl($kaosCall['query'], 'lint') ?>" title="" class="<?php if (kaosIsCall('lint')) echo 'kaos-top-action-active'; echo $class; ?>"><i class="fa fa-file-text-o"></i><span>Lint</span></a>

							<?php if (in_array($kaosCall['call'], array('lint'))){ ?>
								<a href="<?= kaosGetUrl($kaosCall['query'], 'lint/raw') ?>" title="" class="<?= $class ?>"><i class="fa fa-arrows-alt"></i><span>Fullscreen</span></a>
							<?php } ?>

							<a href="<?= kaosGetUrl(array('format' => kaosGetFormatByQuery($kaosCall['query'])) + $kaosCall['query'], 'redirect') ?>" title="" target="_blank" class="<?= $class ?>"><i class="fa fa-external-link-square"></i><span>Redirect</span></a>
						<?php } ?>
						<?php
						$class = '';
						if (!kaosSchemaHasFeature($kaosCall['query']['schema'], 'parse'))
							$class = ' kaos-top-action-disabled';
						?>
						<a href="<?= kaosGetUrl($kaosCall['query'], 'parse', $tr) ?>" title="" class="<?php if (kaosIsCall('parse')) echo 'kaos-top-action-active'; echo $class; ?>"><i class="fa fa-tree"></i><span>Parse</span></a>
						<?php
						$class = '';
						if (!kaosSchemaHasFeature($kaosCall['query']['schema'], 'extract'))
							$class = ' kaos-top-action-disabled';

						?>
						<a href="<?= kaosGetUrl($kaosCall['query'], 'extract', $tr) ?>" title="" class="<?php if (kaosIsCall('extract')) echo 'kaos-top-action-active'; echo $class; ?>"><i class="fa fa-magic"></i><span>Extract</span></a>
						<a href="<?= kaosGetUrl($kaosCall['query'], 'rewind') ?>" title="" class="<?php if (kaosIsCall('rewind')) echo 'kaos-top-action-active'; echo $fetchClass; ?>"><i class="fa fa-backward"></i><span>Rewind</span></a>
					
						<?php
					}
				?>
			</div>
		<?php } ?>
		<div class="kaos-top-menu menu menu-right">
			<div class="kaos-top-menu-icon menu-button" title="Main menu"><i class="fa fa-bars"></i></div>
			<div class="kaos-top-menu-wrap menu-wrap">
				<div class="kaos-top-menu-menu menu-menu">
					<ul class="kaos-top-menu-inner menu-inner">
						<li><a href="<?= BASE_URL ?>"><i class="fa fa-search"></i> Browse</a></li>
						<li><a href="<?= BASE_URL.'api' ?>"><i class="fa fa-book"></i> Bulletins</a></li>
						<?php if (isAdmin()){ ?>
							<li><a href="<?= BASE_URL.'settings' ?>"><i class="fa fa-cog"></i> Settings</a></li>
						<?php } ?>
						<li><a target="_blank" href="<?= kaosAnonymize(getRepositoryUrl('#contact--support')) ?>"><i class="fa fa-comment-o"></i> Contact us</a></li>
						<li><a target="_blank" href="<?= kaosAnonymize(getRepositoryUrl('#contribute')) ?>"><i class="fa fa-thumbs-o-up"></i> Contribute</a></li>
						<li><a target="_blank" href="<?= kaosAnonymize(getRepositoryUrl('#contact--support')) ?>"><i class="fa fa-info-circle"></i> Help</a></li>
						<li><a target="_blank" href="<?= kaosAnonymize(getRepositoryUrl('#top')) ?>"><i class="fa fa-question-circle-o"></i> About</a></li>
						<?php if (isAdmin()){ ?>
							<li><a href="<?= esc_attr(BASE_URL.'logout') ?>"><i class="fa fa-sign-out"></i> Logout</a></li>
						<?php } ?>
					</ul>
				</div>
			</div>
		</div>
	</div>
	<?php
	return ob_get_clean();
}

function kaosGetUrl($query, $apiCall = null, $passCurrentArgs = array()){
	$url = BASE_URL.'api';

	if (is_string($query))
		$schema = strtolower($query);
	else if (!empty($query['schema']))
		$schema = strtolower($query['schema']);
	else
		$schema = null;

	if ($schema)
		$url .= '/'.$schema;

	if ($apiCall){
		if ($schema && in_array($apiCall, array('fetch', 'lint', 'parse', 'extract', 'redirect', 'download')) && is_array($query)){
			if (!empty($query['date']))
				$url .= '/'.$query['date'];
			if (!empty($query['id']))
				$url .= '/'.$query['id'];
		}
		$url .= '/'.$apiCall;
		if (in_array($apiCall, array('redirect')) && !empty($query['format']))
			$url .= '/'.$query['format'];

	} else if (!empty($query['filter']))
		$url .= '/'.strtolower($query['filter']);
	
	$has = false;
	if (!empty($query['precept'])){
		$url .= '?precept='.$query['precept'];
		$has = true;
	}
		
	foreach ($passCurrentArgs as $k)
		if (isset($_GET[$k])){
			$url .= ($has ? '&' : '?').$k.'='.urlencode($_GET[$k]);
			$has = true;
		}

	return $url;
}


function kaosGetSlug($table, $col, $title, $length = null){
	$title = sanitize_title($title, $length);
	$i = 1;
	do {
		$slug = $title.($i > 1 ? '-'.$i : '');
		$i++;
	} while (get('SELECT COUNT(*) FROM '.$table.' WHERE '.$col.' = %s', array($slug)));
	return $slug;
}

function kaosGetFlagUrl($country){
	if (is_object($country))
		$country = $country->id;
	return file_exists(APP_PATH.'/assets/images/flags/'.$country.'.png') ? ASSETS_URL.'/images/flags/'.$country.'.png' : null;
}

function kaosInlineError($error){
	echo '<div class="kaos-inline-error"><i class="fa fa-warning"></i> '.$error.'</div>';
}


function kaosCallInit(){
	global $kaosCall;
	if (empty($kaosCall))
		$kaosCall = array();

	$kaosCall += array(
		'fetches' => 0,
		'spiderConfig' => array(),
	);
	return $kaosCall;
}



function kaosReturnPrintInner($obj){
	global $kaosCall;

	$wrapped = false;
	if (empty($kaosCall['call']) || !in_array($kaosCall['call'], array('fetch', 'lint', 'schema'))){
		$wrapped = true;
		?>
		<div id="wrap">
		<?php
	}
	if (!empty($kaosCall['isIframe']) || !empty($kaosCall['outputNoFilter']))
		echo is_array($obj) || is_object($obj) ? kaosJSON($obj) : (is_string($obj) ? $obj : $obj);

	else {
		if (!empty($kaosCall['apiResultPreview']))
			$kaosCall['apiResultPreview']->preview($obj['result'], $kaosCall['query']);

		if (!empty($kaosCall['collapseAPIReturn']))
			echo '<div><a href="#" onclick="jQuery(this).parent().find(\'.kaos-unfolding\').toggle(); return false">Unfold API return <i class="fa fa-caret-down"></i></a><div class="kaos-unfolding" style="display:none">';

		if (is_string($obj))
			echo kaosPrintString($obj);
		else
			kaosJSON($obj);

		if (!empty($kaosCall['collapseAPIReturn']))
			echo '</div></div>';

	}
	if ($wrapped){
		?>
		</div>
		<?php
	}
}

function getCopyrightRange(){
	$str = '2017';
	$cur = date('Y');
	if ($cur != $str)
		$str .= '-'.$cur;
	return $str;
}


function add_url_arg($name, $val, $url = null, $encode = true){
	if (!$url)
		$url = kaosCurrentURL();
	$url = rtrim(rtrim(preg_replace('#([&\?])('.$name.'=[^&\#]*&?)#', '$1', $url), '&'), '?');
	$url .= (strpos($url, '?') !== false ? '&' : '?').$name.'='.($encode ? urlencode($val) : $val);
	return $url;
}

function remove_url_arg($name, $url = null){
	if (!$url)
		$url = kaosCurrentURL();
	$url = rtrim(rtrim(preg_replace('#([&\?])('.$name.'=[^&\#]+&?)#', '$1', $url), '&'), '?');
	return $url;
}

function hasFilter(){
	return !empty($_GET['etype']) || !empty($_GET['esubtype']) || !empty($_GET['year']) || !empty($_GET['atype']);
}

function isHome($root = false){
	global $kaosPage, $kaosCall;
	return $kaosPage == 'browser' && (!$root || (empty($kaosCall['call']) && empty($_GET['q']) && !hasFilter()));
}

function isAdmin(){
	return KAOS_IS_CLI || !empty($_SESSION['kaos_authed']);
}

function getPrintDomain($url){
	return preg_match('#^https?://(?:www\.)?([^/\?\#]+)(?:[/\?\#].*)?$#iu', $url, $m) ? $m[1] : $url;
}

function getRepositoryUrl($uri = null){
	return 'https://github.com/'.KAOS_GITHUB_REPOSITORY.($uri ? '/'.ltrim($uri, '/') : '');
}
function redirect($url){
	header('Location: '.$url);
	exit();
}


function add_filter($name, $cb = null){
	static $cbs = array();
	if ($cb){
		if (!isset($cbs[$name]))
			$cbs[$name] = array();
		$cbs[$name][] = $cb;
	
	} 
	return $cbs;
}

function apply_filters($name){
	$cbs = add_filter($name);
	if (isset($cbs[$name])){
		$vars = func_get_args();
		array_shift($vars);
		$var = $vars[0];
		foreach ($cbs[$name] as $cb){
			$cvars = $vars;
			$cvars[0] = $var;
			$var = call_user_func_array($cb, $cvars);
		}
	}
	return $var;
}

function buggyButton($type, $title){
	if (!isAdmin())
		return '';
		
	ob_start();
	?>
	<span class="status-alert status-alert-buggy"><a href="#" class="status-action" data-kaos-status-action="markAsBuggy:<?= $type ?>" title="<?= esc_attr($title) ?>"><i class="fa fa-flag"></i></a></span>
	<?php
	return ob_get_clean();
}
