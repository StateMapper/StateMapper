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


function add_test($name, $test_cb){
	
	?>
	<div>
		<div><?= $name ?>:</div>
		<div>
			<?php 
			ob_start();
			$errors = call_user_func($test_cb);
			$output = ob_get_clean();
			
			if ($errors)
				echo '<div><i class="fa fa-warning"></i> '.$errors.' Errors</div>';
			else
				echo '<div><i class="fa fa-check"></i> Passed</div>';
				
			if ($output)
				echo '<div>'.$output.'</div>';
			?>
		</div>
	</div>
	<?php
	
	exit();
}
