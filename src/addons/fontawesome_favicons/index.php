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
 


if (defined('BASE_PATH')) // only direct calls
	die();
	
$tempFolder = dirname(__FILE__).'/../../tmp';
	
// extract icon from url (format /theicon.ico?...)
$icon = @$_SERVER['REQUEST_URI'];
if ($has = strstr($icon, '?', true))
	$icon = $has;
$icon = explode('/', ltrim($icon, '/')); // get last URL bit
if ($icon)
	$icon = preg_replace('#^(.*)\.ico$#', '$1', array_pop($icon));
		
// find unicode equivalent
if (!$icon || !preg_match('#^[a-z0-9-]+$#i', $icon))
	die('no symbol specified in URL');

$bg = !empty($_GET['bg']) && preg_match('#^[a-f0-9]{6}$#i', $_GET['bg']) ? $_GET['bg'] : 'ffffff';
$color = !empty($_GET['color']) && preg_match('#^[a-f0-9]{6}$#i', $_GET['color']) ? $_GET['color'] : '000000';
$isTransparent = empty($_GET['bg']) || $_GET['bg'] == 'transparent';

$icoPath = $tempFolder ? $tempFolder.'/favicon-fa--'.$icon.'--'.$color.'-'.($isTransparent ? 'transparent' : $bg).'.png' : null;
if (!$tempFolder || !file_exists($icoPath)){
		
	// loads constants (only), to get 'ASSETS_PATH'
	define('LOAD_ONLY_CONFIG', true);
	require('../../../index.php'); 

	$fontawesomeFolder = ASSETS_PATH.'/lib/font-awesome-4.7.0';
		
	$conv = file_get_contents($fontawesomeFolder.'/less/variables.less');

	if (!preg_match('#^@fa-var-'.preg_quote($icon, '#').'\s*:\s*["\'](.+)["\'];.*$#ium', $conv, $m))
		die('symbol not found');

	$text = '&#x'.strtoupper(ltrim($m[1], '\\')).';';


	// create the picture
	$im = imagecreatetruecolor(16, 16);
	imagesavealpha($im, true);

	list($r, $g, $b) = sscanf('#'.$bg, "#%02x%02x%02x");
	$bg = imagecolorallocatealpha($im, $r, $g, $b, $isTransparent ? 127 : 0);
	imagefill($im, 0, 0, $bg);

	//imagealphablending($im, true)

	list($r, $g, $b) = sscanf('#'.$color, "#%02x%02x%02x");
	$color = imagecolorallocatealpha($im, $r, $g, $b, 0);

	$text = json_decode('"'.$text.'"');
	$font = $fontawesomeFolder.'/fonts/fontawesome-webfont.ttf';
	if (!is_file($font))
		die('font not found: '.$font);

	// print character
	imagettftext($im, 10, 0, 1, 12, $color, $font, $text); 

	// save image
	if ($tempFolder){
		imagepng($im, $icoPath);
		imagedestroy($im);
	} else {
		header('Content-Type: image/x-icon');
		imagepng($im);
		imagedestroy($im);
		exit();
	}
}

// print image
header('Content-Type: image/x-icon');
readfile($icoPath);
exit();
