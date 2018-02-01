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

function fetch_json($url, $data = array(), $isFinalURL = true, $filePath = false, $opts = array()){
	return @json_decode(fetch($url, $data, $isFinalURL, $filePath, $opts));
}

function fetch($url, $data = array(), $isFinalURL = true, $filePath = false, $opts = array()){
	$opts += array(
		'countAsFetch' => true, 
		'allowTor' => true, 
		'noUserAgent' => false,
		'cache' => false, // use database cache (precise period)
		'timeout' => CURL_TIMEOUT,
		'retries' => CURL_RETRY,
	);
	//$opts['cache'] = false;
	if ($filePath)
		$opts['cache'] = false;
	
	global $smap;
	@session_start();
	$sessionFetched = isset($_SESSION['fetched']) ? $_SESSION['fetched'] : 0;
	
	$cache_url = $url;
	if ($data){
		$url .= '?'.http_encode($data);
		if ($opts['cache']){
			$cache_data = $data;
			ksort($cache_data);
			$cache_url .= '?'.http_encode($cache_data);
		}
	}
	
	// check db cache
	if ($opts['cache']){
		$content = get_cache('fetch '.$cache_url);
		if ($content !== null)
			return $content;
	}
		
	if (IS_CLI)
		print_log('downloading '.$url.($filePath ? ' to '.strip_root($filePath) : ''), array('color' => 'grey'));
	
	if (!$isFinalURL)
		$url = get_final_url($url);
	
	$smap['fetches']++;
	if ($opts['countAsFetch']){
		$sessionFetched++;
		$_SESSION['fetched'] = $sessionFetched;
		
		if (TOR_ENABLED && $sessionFetched % TOR_RENEW_EVERY == 0){ // renew Tor IP every 3 fetches (in same page request)
			
			$torProxy = explode(':', TOR_CONTROL_URL);
			$cmd = 'printf "AUTHENTICATE \"\"\r\nSIGNAL NEWNYM\r\n" | nc '.preg_replace('#([^0-9\.])#', '', $torProxy[0]).' '.intval(
			$torProxy[1]);
			//echo $cmd.PHP_EOL;
			
			if (empty($smap['simulateFetch']))
				exec($cmd);

			//echo "CHANGED IP: ";
			//echo get_tor_ip().PHP_EOL;
			
			if ($smap['call'] == 'rewind' || IS_CLI)
				print_log('Changing IP', array('color' => 'green'));
				
			$_SESSION['fetched'] = $sessionFetched = 0;
			
			if (!isset($smap['tor_ip_changes']))
				$smap['tor_ip_changes'] = array();
				
			$smap['tor_ip_changes'][] = date('Y-m-d H:i:s');
		
		} else if (empty($smap['simulateFetch']))
			wait_for_fetching();
	}
		
	if (!isset($smap['fetched_urls']))
		$smap['fetchTypes'] = $smap['fetchCodes'] = $smap['fetchDurations'] = $smap['fetched_urls'] = array();
		
	if (!empty($smap['simulateFetch'])){
		$content = 'SIMULATION';
		if ($smap['call'] == 'rewind' || IS_CLI)
			print_log('(Should be) calling '.$url, array('color' => 'grey'));
			
		$smap['fetched_urls'][] = $url;
		$smap['fetchDurations'][] = 0;
		$smap['fetchCodes'][] = 200;
	
	} else {
		// really fetch
		$ch = get_curl_channel($url, $opts['timeout'], $opts);
	
		for ($i = 0; $i < max($opts['retries'], 1); $i++){
			$smap['lastFetchBegin'] = microtime(true);
			
			//if ($smap['call'] == 'rewind')
				//echo 'Calling '.$url.'<br>';
				
			$smap['fetched_urls'][] = $url;
			$smap['fetchTypes'][] = $filePath;
				
			$content = curl_exec($ch);
			$response = curl_getinfo($ch);
			$error = curl_error($ch);
			// DEBUG: print_r($error);

			$smap['lastFetch'] = microtime(true);
			$smap['fetchDurations'][] = $smap['lastFetch'] - $smap['lastFetchBegin'];
			$smap['fetchDuration'] = (isset($smap['fetchDuration']) ? $smap['fetchDuration'] : 0) + $smap['lastFetch'] - $smap['lastFetchBegin'];
			
			$smap['fetchCodes'][] = $response['http_code'].($error ? ' ('.$error.')' : '');
			
			if (!empty($smap['simulateFetch']) || $response['http_code'] == 200)
				break;
			else
				wait_for_fetching();
		}
		
		curl_close($ch);
	}
	
	if (!empty($smap['simulateFetch'])){
		if ($filePath)
			print_log('File (should be) written to '.strip_root($filePath), array('color' => 'green'));
		return true;
	}

	if ($response['http_code'] != 200){
		if (!empty($_GET['debug']) && is_admin())
			echo 'bad HTTP response code: '.$response['http_code'].'<br>';
	
	} else if (trim($content) != ''){ // do not accept empty content as valid file
		// success
		if (!$filePath){
					
			// save to db cache
			if ($opts['cache'])
				set_cache('fetch '.$cache_url, $content, $opts['cache']);
				
			return $content;
		}
			
		if ($fp = fopen($filePath, 'w')){
				
			fwrite($fp, $content);
			fclose($fp);
			
			if (IS_CLI)
				print_log('file written to '.strip_root($filePath), array('color' => 'green'));

			return true;
		
		} else
			return new SMapError('could not write file to '.$filePath);
	}
		
	/*exec('curl --insecure --write-out "%{http_code}" "'.$url.'"'.($filePath ? ' --output "'.$filePath.'"' : ''), $output, $return_var);
	
	if (empty($return_var) && $output && $output[0] == '200'){
		return $filePath ? true : implode('', $output);
	}
	*/
	
	// error, delete downloaded file if any
	if ($filePath)
		@unlink($filePath);
	return false;
}

function get_curl_channel($url, $timeout = null, $opts = array()){
	$opts += array(
		'allowTor' => true, 
		'noUserAgent' => false,
		'accept' => false,
		'type' => null,
	);
	//$url = str_replace("&amp;", "&", urldecode(trim($url))); <- bugfix no longer needed

	$cookie = tempnam("/tmp", "CURLCOOKIE");
	
	$ch = curl_init();
	$headers = array();

	if (!$opts['accept'] && $opts['type'])
		switch ($opts['type']){
			case 'json':
				$opts['accept'] = 'application/json';
				break;
			case 'html':
				//$opts['accept'] = 'text/html';
				break;
		}
		
	if ($opts['accept'] !== false)
		$headers[] = 'Accept: '.(!empty($opts['accept']) ? $opts['accept'] : 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8');
		
	$headers[] = 'Connection: keep-alive'; 
	//$headers[] = 'Content-type: application/x-www-form-urlencoded;charset=UTF-8'; 
	$headers[] = 'Accept-Encoding: gzip, deflate, br';
	$headers[] = 'Upgrade-Insecure-Requests: 1';

	curl_setopt($ch, CURLOPT_HEADER, FALSE);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	
	curl_setopt($ch, CURLOPT_ENCODING, 'gzip');

	curl_setopt($ch, CURLOPT_COOKIESESSION, TRUE);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);

	if (!$opts['noUserAgent'])
		curl_setopt($ch, CURLOPT_USERAGENT, get_user_agent());
		
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_ENCODING, "");
	curl_setopt($ch, CURLOPT_AUTOREFERER, true);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout ? $timeout : CURL_TIMEOUT);
	curl_setopt($ch, CURLOPT_TIMEOUT, $timeout ? $timeout : CURL_TIMEOUT);
	curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	
	// disable SSL check, states are so bad at certificates..
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	
	// set Tor proxy
	if ($opts['allowTor'] && TOR_ENABLED){
		curl_setopt($ch, CURLOPT_PROXY, TOR_PROXY_URL);
		curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
	
	} else if ($proxy = get_fetch_proxy()){
		// set random proxy
		curl_setopt($ch, CURLOPT_PROXY, $proxy);
	}
		
	return $ch;
}

function get_fetch_proxy(){
	if (!FETCH_USE_PROXY)
		return false;
		
	$proxies = explode(',', FETCH_PROXY_LIST);
	return $proxies[array_rand($proxies)];
}

function get_user_agent(){
	$agents = array(
		'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Ubuntu Chromium/61.0.3163.100 Chrome/61.0.3163.100 Safari/537.36',
		'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/33.0.1750.517 Safari/537.36',
		'Mozilla/5.0 (compatible; MSIE 10.6; Windows NT 6.1; Trident/5.0; InfoPath.2; SLCC1; .NET CLR 3.0.4506.2152; .NET CLR 3.5.30729; .NET CLR 2.0.50727) 3gpp-gba UNTRUSTED/1.0',
		'Opera/9.80 (Windows NT 6.0) Presto/2.12.388 Version/12.14',
		'Opera/9.80 (Windows NT 6.0; U; pl) Presto/2.10.229 Version/11.62',
		'Mozilla/5.0 (Linux; U; Android 2.3.4; en-us; T-Mobile myTouch 3G Slide Build/GRI40) AppleWebKit/533.1 (KHTML, like Gecko) Version/4.0 Mobile Safari/533.1',
		'Mozilla/5.0 (BlackBerry; U; BlackBerry 9800; en-US) AppleWebKit/534.1+ (KHTML, like Gecko)',
		'Mozilla/5.0 (BlackBerry; U; BlackBerry 9850; en-US) AppleWebKit/534.11+ (KHTML, like Gecko) Version/7.0.0.115 Mobile Safari/534.11+',
	);

	// TODO: change user agent each time a new IP is taken, or each failure.
	
	$i = ceil(time() / 1800); // change useragent every half-hour
	
	//$i = 0; // remove!
	return $agents[$i % count($agents)];
}

function get_final_url($url, $timeout = null, $opts = array()){
	$opts += array(
		'noUserAgent' => false,
	);
	
	$ch = get_curl_channel($url, $timeout, $opts);

	$content = curl_exec($ch);
	$response = curl_getinfo($ch);
	
	curl_close($ch);

	if ($response['http_code'] == 301 || $response['http_code'] == 302){
		ini_set('user_agent', get_fetch_useragent());
		$headers = get_headers($response['url']);

		$location = "";
		foreach ($headers as $value){
			if (substr(strtolower($value), 0, 9) == 'location:')
				return get_final_url(trim(substr($value, 9, strlen($value))));
		}
	}

	if (preg_match("/window\.location\.replace\('(.*)'\)/i", $content, $value) 
		|| preg_match("/window\.location\=\"(.*)\"/i", $content, $value)){
		return get_final_url($value[1]);
	} else {
		return $response['url'];
	}
}

function serve_file($file, $mimeType, $download = false, $title = null){
	if (!($fp = fopen($file, "r")))
		die_error('cannot read file '.$file);

	header("Cache-Control: maxage=1");
	header("Pragma: public");
	header("Content-type: ".$mimeType);//."; charset=utf-8");
	header("Content-Length: ".((string) filesize($file)));
	
	header("Content-Disposition: ".($download ? 'download' : 'inline')."; filename=".str_replace('/', '-', str_replace(DATA_PATH.'/', '', $file))."");
	//header("Content-Description: PHP Generated Data");
	header("Content-Transfer-Encoding: binary");
	
	//header('Vary: x'); // was useful??
	
	// clean output
	@ob_clean();
	@flush();
	
	while (!feof($fp)) {
	   $buff = fread($fp, 1024);
	   print $buff; 
	}
	fclose($fp);
	exit();
}


function get_tor_ip(){
	$ch = get_curl_channel('http://ifconfig.me/ip', CURL_TIMEOUT, array('noUserAgent' => true));
	$ip = curl_exec($ch);
	curl_close($ch);
	return $ip;
}

// wait for a minimum (random) short period after each fetch
function wait_for_fetching(){
	global $smap;
	if (!empty($smap['lastFetch'])){
		$wait = max(CURL_WAIT_BETWEEN, 0.1) + (rand(1, CURL_RANDOM_WAIT * 1000)/1000) - (time() - $smap['lastFetch']); // CURL_WAIT_BETWEEN seconds minimum wait between calls
		if ($wait > 0){
			$smap['fetchWaitBegin'] = time();
			usleep(ceil($wait * 1000000));
			$smap['fetchWaitDuration'] = (isset($smap['fetchWaitDuration']) ? $smap['fetchWaitDuration'] : 0) + time() - $smap['fetchWaitBegin'];
		}
	}
}

function http_encode($params){
	return http_build_query($params, '', '&', PHP_QUERY_RFC3986);
}


function get_fetcher_cache($cacheType, $protocoleConfig, $query, $parent){
	$filePath = APP_PATH.'/fetcher/caches/BulletinFetcher'.ucfirst($cacheType).'Cache.php';
	if (file_exists($filePath)){
		require_once APP_PATH.'/fetcher/BulletinFetcherCache.php';
		require_once $filePath;
		$class = '\\StateMapper\\BulletinFetcher'.ucfirst($cacheType).'Cache';
		$fetcherCache = new $class();
		$fetcherCache->set_config($protocoleConfig, $query, $parent);
		return $fetcherCache;
	} 
	return null;
}

function get_format_fetcher($format, $parent = null){
	static $cache = array();
	if (isset($cache[$format]))
		return $cache[$format];

	// load format fetcher
	require_once APP_PATH.'/fetcher/BulletinFetcher.php';
	require_once APP_PATH.'/fetcher/BulletinFetcherFormat.php';
	$fetcherClass = 'BulletinFetcher'.ucfirst($format);
	$fetcherPath = APP_PATH.'/fetcher/formats/'.$fetcherClass.'.php';
	$fetcherClass = '\\StateMapper\\'.$fetcherClass;
	if (!is_file($fetcherPath))
		return new SMapError('unknown fetchProcole format '.$format);

	require_once $fetcherPath;
	$cache[$format] = new $fetcherClass($parent);
	return $cache[$format];
}

function can_fetch_by_context($context){
	return in_array($context, array('sheet', 'sheet-top', 'sheet-sidebar', 'popup')) && IS_AJAX;
}
