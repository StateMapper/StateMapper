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

$str = array();

	if (empty($is_iframe) && !IS_INSTALL){

		?>
		<div class="footer"><?php

			if (is_admin())
				print_template('parts/footer_console');

			if (!is_admin()){
				echo '<span><strong><a title="'.esc_attr(_('$tateMapper needs many thinking minds! <br>Get involved!')).'" target="_blank" href="'.esc_attr(anonymize(get_repository_url('#contribute'))).'" class="footer-contribute"><i class="fa fa-github"></i> '._('Contribute').'</a></strong></span>';
				echo '<span>|</span><span><a title="'.esc_attr(_('Free and OpenSource Software! <br>Contribute to the code!')).'" target="_blank" href="'.anonymize(get_repository_url('#top')).'" target="_blank">'.sprintf(_('Licensed under %s'), get_license()).'</a></span>';
				echo '<span>|</span>';
			}
			echo '<span><a href="'.esc_attr(anonymize(get_repository_url())).'" target="_blank" title="'.esc_attr(sprintf(_('$tateMapper is version %s. Click to see the full source code!'), SMAP_VERSION.(IS_ALPHA ? ' (alpha)' : ''))).'">v'.SMAP_VERSION.'</a></span><span>|</span>';

			?>
			<span class="copyright"><span class="menu menu-right menu-top">
				<span class="menu-button" title="<?= esc_attr(_('Advanced menu')) ?>"><i class="fa fa-copyright"></i> <?= date('Y') ?> StateMapper.net</span>
				<span class="menu-wrap">
					<span class="menu-menu">
						<ul class="menu-inner">
							<?php if (!is_admin() && ALLOW_LOGIN){ ?>
								<li><a href="<?= esc_attr(add_lang(BASE_URL.'login?redirect='.urlencode(current_url()))) ?>"><i class="fa fa-sign-in"></i> <?= _('Login') ?></a></li>
							<?php } 
							?>
							
							<?php if (!is_home(true) && ($type = get_api_type())){ 
								
								if ($type == 'json'){
									?>
									<li><a href="<?= add_url_arg('human', 1, get_api_url()) ?>"><i class="fa fa-plug"></i> <?= _('Human JSON') ?></a></li>
									<li><a href="<?= get_api_url() ?>"><i class="fa fa-plug"></i> <?= _('Raw JSON') ?></a></li>
									<?php 
									
								} else if ($type == 'document'){
									?>
									<li><a href="<?= get_api_url() ?>"><i class="fa fa-plug"></i> <?= _('Raw document') ?></a></li>
									<?php 
								} 
							}
							?>

							<li><a target="_blank" href="<?= esc_attr(anonymize(get_repository_url('#top'))) ?>"><i class="fa fa-question-circle-o"></i> <?= _('About') ?></a></li>

							<?php if (is_admin()){ ?>
								<li><a href="<?= esc_attr(add_lang(BASE_URL.'logout?redirect='.urlencode(current_url()))) ?>"><i class="fa fa-sign-out"></i> <?= _('Logout') ?></a></li>
							<?php } ?>
						</ul>
					</span>
				</span>
			</span></span>
			<?php do_action('footer_copyright_after') ?>
		</div>
		<?php

		// allow extra footer tags (do not put addons/extra_footer.php on github!!)
		if (is_file(APP_PATH.'/addons/extra_footer.php'))
			include(APP_PATH.'/addons/extra_footer.php');
			
		/* TODO: login form and process
		 * 
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
		 */
	}
	?>
		</div>
	</body>
</html><?php 



