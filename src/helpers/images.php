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
 

// still under developement
	
if (!defined('BASE_PATH'))
	die();


use Spatie\ImageOptimizer\OptimizerChainFactory;

function optimize_image($path){
	if (!file_exists($path))
		die('bad path: '.$path);
	
	if (!preg_match('#^(.*)\.([a-z]+)$#i', $path, $m))
		die('bad file: '.$path);
		
	$dest = $m[1].'.min.'.$m[2];
	if ($dest == $path)
		die('error in optimize_image');
	@unlink($dest);
	
	require_once APP_PATH.'/assets/lib/image-optimizer/src/Optimizer.php';
	require_once APP_PATH.'/assets/lib/image-optimizer/src/OptimizerChain.php';
	require_once APP_PATH.'/assets/lib/image-optimizer/src/OptimizerChainFactory.php';
	require_once APP_PATH.'/assets/lib/image-optimizer/src/DummyLogger.php';
	require_once APP_PATH.'/assets/lib/image-optimizer/src/Image.php';
	
	$optimizerChain = OptimizerChainFactory::create();
	$optimizerChain->optimize($path, $dest);
	if (!file_exists($dest))
		die('error optimizing image: no destination file');
	die("OK");
}

optimize_image(APP_PATH.'/assets/images/bg/space.jpg');
die("IN");
