<?php
/*
 * StateMapper: worldwide, collaborative, public data reviewing and monitoring tool.
 * Copyright (C) 2017  StateMapper.net <statemapper@riseup.net>
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


function kaosFetch($url, $data = array(), $isFinalURL = true, $filePath = false, $opts = array()){
	$opts += array(
		'countAsFetch' => true, 
		'allowTor' => true, 
		'noUserAgent' => false,
	);
	
	global $kaosCall;
	@session_start();
	$sessionFetched = isset($_SESSION['kaosFetched']) ? $_SESSION['kaosFetched'] : 0;
	
	if ($data)
		$url .= '?'.http_build_query($data);
		
	if (KAOS_IS_CLI)
		kaosPrintLog('downloading '.$url.($filePath ? ' to '.$filePath : ''), array('color' => 'grey'));
	
	if (!$isFinalURL)
		$url = kaosGetFinalURL($url);
	
	if ($opts['countAsFetch']){
		$kaosCall['fetches']++;
		$sessionFetched++;
		$_SESSION['kaosFetched'] = $sessionFetched;
		
		if (TOR_ENABLED && $sessionFetched % TOR_RENEW_EVERY == 0){ // renew Tor IP every 3 fetches (in same page request)
			
			$torProxy = explode(':', TOR_CONTROL_URL);
			$cmd = 'printf "AUTHENTICATE \"\"\r\nSIGNAL NEWNYM\r\n" | nc '.preg_replace('#([^0-9\.])#', '', $torProxy[0]).' '.intval(
			$torProxy[1]);
			//echo $cmd.PHP_EOL;
			
			if (empty($kaosCall['sumulateFetch']))
				exec($cmd);

			//echo "CHANGED IP: ";
			//echo kaosGetTorIP().PHP_EOL;
			
			if ($kaosCall['call'] == 'rewind' || KAOS_IS_CLI)
				kaosPrintLog('Changing IP', array('color' => 'green'));
				
			$_SESSION['kaosFetched'] = $sessionFetched = 0;
			
			if (!isset($kaosCall['torIpChanges']))
				$kaosCall['torIpChanges'] = array();
				
			$kaosCall['torIpChanges'][] = date('Y-m-d H:i:s');
		
		} else if (empty($kaosCall['sumulateFetch']))
			kaosFetchWait();
	}
		
	if (!isset($kaosCall['fetchedUrls']))
		$kaosCall['fetchedUrls'] = array();
		
	if (!empty($kaosCall['sumulateFetch'])){
		$content = 'SIMULATION';
		if ($kaosCall['call'] == 'rewind' || KAOS_IS_CLI)
			kaosPrintLog('(Should be) calling '.$url, array('color' => 'grey'));
			
		$kaosCall['fetchedUrls'][] = $url;
	
	} else {
		// really fetch
		$ch = kaosGetCurlChannel($url, CURL_TIMEOUT, $opts);
	
		for ($i = 0; $i < max(CURL_RETRY, 1); $i++){
			$kaosCall['lastFetchBegin'] = time();
			
			//if ($kaosCall['call'] == 'rewind')
				//echo 'Calling '.$url.'<br>';
				
			$kaosCall['fetchedUrls'][] = $url;
				
			$content = curl_exec($ch);
			$response = curl_getinfo($ch);
			$error = curl_error($ch);
			// DEBUG: print_r($error);

			$kaosCall['lastFetch'] = time();
			$kaosCall['fetchDuration'] = (isset($kaosCall['fetchDuration']) ? $kaosCall['fetchDuration'] : 0) + $kaosCall['lastFetch'] - $kaosCall['lastFetchBegin'];
			
			if (!empty($kaosCall['sumulateFetch']) || $response['http_code'] == 200)
				break;
			else
				kaosFetchWait();
		}
		
		curl_close($ch);
	}
	
	if (!empty($kaosCall['sumulateFetch'])){
		if ($filePath)
			kaosPrintLog('File (should be) written to '.$filePath, array('color' => 'green'));
		return true;
	}

	if ($response['http_code'] != 200){
		if (!empty($_GET['debug']) && isAdmin())
			echo 'bad HTTP response code: '.$response['http_code'].'<br>';
	
	} else if (trim($content) != ''){ // do not accept empty content as valid file
		// success
		if ($filePath){
			if ($fp = fopen($filePath, 'w')){
					
				fwrite($fp, $content);
				fclose($fp);
				
				if (KAOS_IS_CLI)
					kaosPrintLog('file written to '.$filePath, array('color' => 'green'));

				return true;
			
			} else
				return new KaosError('could not write file to '.$filePath);
		} else
			return $content;
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

function kaosGetCurlChannel($url, $timeout = null, $opts = array()){
	$opts += array(
		'allowTor' => true, 
		'noUserAgent' => false,
	);
	$url = str_replace("&amp;", "&", urldecode(trim($url)));

	$cookie = tempnam("/tmp", "CURLCOOKIE");
	
	$ch = curl_init();
		
	$headers = array();
	if (!isset($opts['accept']) || $opts['accept'] !== false)
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
		curl_setopt($ch, CURLOPT_USERAGENT, kaosGetUserAgent());
		
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
	
	} else if ($proxy = kaosGetFetchProxy()){
		// set random proxy
		curl_setopt($ch, CURLOPT_PROXY, $proxy);
	}
		
	return $ch;
}

function kaosGetFetchProxy(){
	if (!KAOS_USE_PROXY)
		return false;
		
	$proxies = array(
		'200.54.108.54',
		'13.73.1.69',
		'180.250.88.165',
		'189.58.101.69',
		'165.98.137.66',
		'91.121.162.173',
	);
	return $proxies[array_rand($proxies)];
}

function kaosGetUserAgent(){
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

function kaosGetFinalURL($url, $timeout = null, $opts = array()){
	$opts += array(
		'noUserAgent' => false,
	);
	
	$ch = kaosGetCurlChannel($url, $timeout, $opts);

	$content = curl_exec($ch);
	$response = curl_getinfo($ch);
	
	curl_close($ch);

	if ($response['http_code'] == 301 || $response['http_code'] == 302){
		ini_set("user_agent", kaosGetFetchUserAgent());
		$headers = get_headers($response['url']);

		$location = "";
		foreach ($headers as $value){
			if (substr(strtolower($value), 0, 9) == "location:")
				return kaosGetFinalURL(trim(substr($value, 9, strlen($value))));
		}
	}

	if (preg_match("/window\.location\.replace\('(.*)'\)/i", $content, $value) 
		|| preg_match("/window\.location\=\"(.*)\"/i", $content, $value)){
		return kaosGetFinalURL($value[1]);
	} else {
		return $response['url'];
	}
}

function serveFile($file, $mimeType, $download = false, $title = null){
	
	$fp = fopen($file, "r") ;

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

function kaosGetTorIP(){
	$ch = kaosGetCurlChannel('http://ifconfig.me/ip', CURL_TIMEOUT, array('noUserAgent' => true));
	$ip = curl_exec($ch);
	curl_close($ch);
	return $ip;
}


function kaosFetchWait(){
	global $kaosCall;
	if (!empty($kaosCall['lastFetch'])){
		$wait = max(CURL_WAIT_BETWEEN, 0.1) + (rand(1, CURL_RANDOM_WAIT * 1000)/1000) - (time() - $kaosCall['lastFetch']); // CURL_WAIT_BETWEEN seconds minimum wait between calls
		if ($wait > 0){
			$kaosCall['fetchWaitBegin'] = time();
			usleep(ceil($wait * 1000000));
			$kaosCall['fetchWaitDuration'] = (isset($kaosCall['fetchWaitDuration']) ? $kaosCall['fetchWaitDuration'] : 0) + time() - $kaosCall['fetchWaitBegin'];
		}
	}
}

