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
	
	
function is_admin(){
	return is_logged();
}

function is_logged(){
	return IS_CLI || !empty($_SESSION['smap_authed']);
}

function is_dev(){
	return is_admin();
}

function get_current_user_name($reload = false){
	static $name = null;
	if ($name === null || $reload){
		if (!($id = get_my_id()))
			$name = false;
		else {
			$name = get_var('SELECT user_login FROM users WHERE id = %s', $id);
			if (!$name)
				$name = false;
		}
	}
	return $name;
}

function get_visitor_ip(){
	if (IS_CLI)
		return 'localhost';
		
    $client  = @$_SERVER['HTTP_CLIENT_IP'];
    $forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];

    if (filter_var($client, FILTER_VALIDATE_IP))
        return $client;
    if (filter_var($forward, FILTER_VALIDATE_IP))
        return $forward;
	return @$_SERVER['REMOTE_ADDR'];
}

function create_user($user = array()){
	$user += array(
		'created' => date('Y-m-d H:i:s'),
	);
	$user['id'] = insert('users', $user);
	return $user['id'] ? $user : false;
}

function logout_do($alert = true){
	unset($_SESSION['smap_authed']);
	if ($alert)
		$_SESSION['smap_just_logged_out'] = 1;
}

function get_my_id(){
	return !IS_CLI && is_logged() ? $_SESSION['smap_authed'] : null;
}

add_action('redirect', 'track_user_last_seen');
function track_user_last_seen(){
	if ($id = get_my_id())
		update_row('users', array(
			'last_seen' => date('Y-m-d H:i:s'),
		), array(
			'id' => $id,
		));
}

add_action('footer_end', 'print_login_form');
function print_login_form(){
	
	if (@IS_INSTALL)
		return;
	?>
	<form id="login-form" class="hidden ajax-form" method="POST" action="<?= url(null, 'login') ?>" <?= related(array('action' => 'login_form')) ?>>
		<h3>Authenticate with $tateMapper</h3>
		<div class="login-form-fields">
			<div class="radios">
				<label><input type="radio" name="login_form_mode" value="signin" checked="checked"> Sign in</label>
				<label><input type="radio" name="login_form_mode" value="signup"> Create an account</label>
			</div>
			<div class="login-form-mode login-form-mode-signin">
				<div>
					<label class="clean-field-label">Username:</label>
					<div class="clean-field-wrap"><input name="login_form_user" type="text" value="" autocomplete="current-user" /></div>
				</div>
				<div>
					<label class="clean-field-label">Password:</label>
					<div class="clean-field-wrap"><input name="login_form_pass" type="password" value="" autocomplete="current-password" /></div>
				</div>
				<div class="fake-hidden">
					<label class="clean-field-label">Do not fill this field:</label>
					<div class="clean-field-wrap"><input name="login_form_fake" type="text" value="" /></div>
				</div>
				<div class="login-form-buttons">
					<input type="hidden" name="redirect" value="<?= current_url() ?>" />
					<input name="login_form_submit" type="submit" value="Enter" data-smap-loading-label="Logging in.." />
				</div>
			</div>
			<div class="login-form-mode login-form-mode-signup hidden">
				We're still under development and we don't allow anyone to sign up yet. <br><br>If you wish to get a pre-release account, please <a href="<?= anonymize(get_repository_url('#contact--support')) ?>" target="_blank">send us an email</a> telling us why you think you deserve such early access.
			</div>
		</div>
	</form>
	<?php
}

add_action('footer_left', 'footer_login_button', 100);
function footer_login_button(){
	if (!is_logged() && ALLOW_LOGIN){ 
		?>
		<span class="footer-login left pointer" title="Login or create an account to save your searches and build custom lists!"><span><i class="fa fa-sign-in"></i> <?= _('Login') ?></span></span>
		<?php 
	} 
}

function smap_ajax_login_form($args){
	if (!ALLOW_LOGIN)
		return 'no login allowed';
		
	$fields = unserialize_fields($args['fields']);
	
	if (empty($fields['login_form_user']) || trim($fields['login_form_user']) === '')
		return array('success' => false, 'error' => 'Please fill in your username');
	
	if (empty($fields['login_form_pass']) || trim($fields['login_form_pass']) === '')
		return array('success' => false, 'error' => 'Please fill in your password');
		
	$user_id = get_var('SELECT id FROM users WHERE user_login = %s AND user_pass = %s AND status = "active" LIMIT 1', array($fields['login_form_user'], hash('sha256', $fields['login_form_pass'])));
	
	if ($user_id){
		// login successful
		$_SESSION['smap_authed'] = $user_id;
		$_SESSION['smap_just_authed'] = 1;
		return array('success' => true, 'reload' => true);
	}
	
	// login failed
	return array('success' => false, 'error' => 'Login failed! Please try again.');
}

function unserialize_fields($args){
	$fields = array();
	
	foreach ($args as $a)
		$fields[$a['name']] = is_numeric($a['value'])
			? intval($a['value']) 
			: ($a['value'] === 'true' || $a['value'] === 'false' ? $a['value'] === true : $a['value']);
			
	return $fields;
}

// login/logout nice alerts
add_action('body_after', function(){
	
	if (!empty($_SESSION['smap_just_authed'])){
		unset($_SESSION['smap_just_authed']);
		
		print_nice_alert(array(
			'id' => 'login_success',
			'class' => 'success',
			'icon' => 'check',
			'label' => sprintf('Hi @%s!', get_current_user_name()),
			'timeout' => 5000,
		));
	}
	
	if (!empty($_SESSION['smap_just_logged_out'])){
		unset($_SESSION['smap_just_logged_out']);
		
		print_nice_alert(array(
			'id' => 'logout_success',
			'icon' => 'check',
			'label' => 'You are now logged out',
			'timeout' => 8000,
		));
	}
	
});
