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
	
	
class SMapError {

	public $msg = null;
	public $opts = array();

	public function __construct($msg, $opts = array()){
		$this->msg = $msg;
		$this->opts = $opts;
	}
}

function die_error($str_or_error = null, $error = null){
	global $smap;
	if (!$str_or_error)
		$str_or_error = 'Operation forbidden';

	$msg = (is_string($str_or_error) ? $str_or_error : $str_or_error->msg).($error ? $error->msg : '');
	
	define('IS_ERROR', true);	
	if (!empty($smap) && !IS_CLI){
		if (!is_admin())
			$msg = 'An error occurred'; // hide errors to no-admins
			
		$obj = array(
			'success' => false,
			'query' => isset($smap['query']) ? $smap['query'] : null,
			'error' => $msg
		);
		if (defined('IS_AJAX') && IS_AJAX){
			header('Content-type: application/json');
			echo json_encode($obj, JSON_UNESCAPED_UNICODE);
			exit();
		}
		
		return_wrap($obj, 'Error');
	}

	// cli or not api
	echo $msg.PHP_EOL;
	exit(1);
}

function is_error($obj){
	return is_object($obj) && get_class($obj) == 'SMapError';
}

function print_inline_error($error){
	echo '<span class="inline-error"><i class="fa fa-warning"></i> '.$error.'</span>';
}

// pipe PHP errors?
/*
set_error_handler(function($errno, $errstr, $errfile, $errline, array $errcontext) {
    // error was suppressed with the @-operator
    if (0 === error_reporting()) {
        return false;
    }

    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});
*/

function get_buggy_button($type, $title){
	if (!is_admin())
		return '';
		
	ob_start();
	?>
	<span class="status-alert status-alert-buggy"><a href="#" class="status-action" data-status-action="markAsBuggy:<?= $type ?>" title="<?= esc_attr($title) ?>"><i class="fa fa-flag"></i></a></span>
	<?php
	return ob_get_clean();
}

