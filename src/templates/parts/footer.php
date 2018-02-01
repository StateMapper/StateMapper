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

$str = array();

						?>
					
						</div>
					</div>
					<?php
					do_action('footer');

					if (empty($is_iframe) && !IS_INSTALL){

						?>
						<div class="footer"><?php
							
							do_action('footer_left');
							
							//echo '<span class="left"><span title="'.esc_attr(sprintf(_('$tateMapper is currently version %s'), preg_replace('#^(.*)a$#i', '$1', SMAP_VERSION).(IS_ALPHA ? ' alpha' : ''))).'">v'.SMAP_VERSION.'</span></span>';
										
							if (is_dev())
								print_template('parts/footer_console');
				
							if (IS_DEBUG)
								echo '<span class="footer-debug-ind" title="Debug mode is active. Please set IS_DEBUG to false in your config.php if this site is to be published!">'._('Debug ON').'</span>';
							
							echo '<span><a title="'.esc_attr(_('$tateMapper needs many thinking minds! <br>Get involved!')).'" target="_blank" href="'.esc_attr(anonymize(get_repository_url('#contribute'))).'" class="footer-contribute"><i class="fa fa-github"></i> '._('Contribute').'</a></span>';
							echo '<span><a title="'.esc_attr(_('Free and OpenSource Software! <br>Contribute to the code!')).'" target="_blank" href="'.anonymize(get_repository_url('#top')).'" target="_blank">'.sprintf(_('Licensed under %s'), get_license()).'</a></span>';
							?> 
							
							<span class="copyright">
								<span><i class="fa fa-copyright"></i> <?= date('Y') ?> StateMapper.net</span>
							</span>
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
					do_action('footer_after');
					?>
				</div>
			</div>
		</div>
		<div class="lazy-loader-ind"></div><!-- just an indicator for the lazy CSS to be fully loaded -->
		<?php do_action('footer_end'); ?>
	</body>
</html><?php 



