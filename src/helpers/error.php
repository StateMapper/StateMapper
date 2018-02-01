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
require 'error-class.php';
	
if (!defined('BASE_PATH'))
	die();
	

function die_error($str_or_error = null, $error = null){
	global $smap;
	if (!$str_or_error)
		$str_or_error = 'Operation forbidden';

	$msg = (is_string($str_or_error) ? $str_or_error : $str_or_error->msg).($error ? $error->msg : '');
	
	define('IS_ERROR', true);	
	if (!empty($smap) && (!IS_CLI || empty($smap['human']))){
		if (!is_dev())
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
	return is_object($obj) && get_class($obj) == 'StateMapper\\SMapError';
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
	<span class="status-alert status-alert-buggy"><a href="#" class="autoaction" title="<?= esc_attr($title) ?>"<?= related(array(
		'action' => 'mark_as_buggy',
		'type' => $type,
	)) ?>><i class="fa fa-flag"></i></a></span>
	<?php
	return ob_get_clean();
}

add_filter('autoaction_mark_as_buggy', 'autoaction_mark_as_buggy');
function autoaction_mark_as_buggy($ret, $args){
	$bug = null;
	switch ($args['type']){
		
		case 'status':
			if (empty($args['status_id']) || !($status = get_row('SELECT * FROM statuses WHERE id = %s', $args['status_id'])))
				return 'Bad status id';
			
			$a = $status['amount'] ? get_amount($status['amount']) : null;
			
			$arg3 = null;
			if (!empty($status['target_id'])){
				$arg3 = get_entity_by_id($status['target_id']);
				$arg3 = $arg3 ? get_entity_title($arg3, true) : $arg3;
			}
			
			$bug = array(
				'type' => 'status',
				'related_id' => $status['id'],
				'arg1' => $status['type'],
				'arg2' => $status['action'],
				'arg3' => $arg3,
				'arg4' => $a && (is_object($a) || is_array($a)) ? serialize($a) : $a, // TODO: get real amount
				'arg5' => $status['note'],
			);
			break;
		
		case 'entity_name':	
			if (empty($args['entity_id']) || !($entity = get_entity_by_id($args['entity_id'])))
				return 'Bad entity id';
				
			$bug = array(
				'type' => 'entity_name',
				'related_id' => $entity['id'],
				'arg1' => $entity['type'],
				'arg2' => $entity['subtype'],
				'arg3' => $entity['name'],
				'arg4' => $entity['first_name'],
			);
			break;
	}
	if ($bug){
		// TODO: implement a bug tracking table and web UX. it must be very flexible, and not related to auto_incremeneted ids (because it's gonna remain through re-parsings!)
		
		debug($bug); die();
		insert('bugs', $bug);
		return array('success' => true);
	}
	return false;
}


add_filter('entity_actions', 'entity_actions_report_buggy', 30, 2);
function entity_actions_report_buggy($entity, $context){
	if (!is_logged())
		return $entity;
		
	$modes = get_modes();
	$entity['actions']['report_buggy'] = array(
		'label' => 'Report the entity as buggy',
		'icon' => 'flag',
		'advanced' => true,
	);
	return $entity;
}

function has_error(){
	return defined('IS_ERROR') && IS_ERROR;
}
