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


smap_add_action('loaded', function(){
	
	// top date menu
	var dateInput = $('.header-calendar input');
	dateInput.on('keypress keydown blur', function(e){
		if (e.type == 'blur'){
			if (!$(e.target).closest('.header-calendar').length)
				dateReset();
		} else if (e.which == 13) // enter
			dateChange();
	});
	$('.header-calendar button').click(function(){
		dateChange();
	});
	
	function dateChange(){
		var date = dateInput.val();
		if (smapDateIsValid(date)){
			window.location = dateInput.data('smap-url').replace('%s', date);
			return true;
		}
		return false;
	}
	function dateReset(){
		dateInput.val(dateInput.data('smap-oval'));
	}
	
	$('.header-date').on('click', function(){
		$('body').off('click.header-top-date');
		if ($('body').toggleClass('header-date-menu-on').hasClass('header-date-menu-on'))
			setTimeout(function(){
				$('body').off('click.header-top-date').on('click.header-top-date', function(e){
					if (!$(e.target).closest('.header-date-menu').length){
						
						dateReset();
						$('body').removeClass('header-date-menu-on');
						$('body').off('click.header-top-date');
					}
				});
				dateInput.focus();
		}, 100);
		return false;
	});
});

function smapDateIsValid(input){ // mysql format
	var bits = input.split('-');
	var d = new Date(bits[0], bits[1] - 1, bits[2]);
	return d.getFullYear() == bits[0] && (d.getMonth() + 1) == bits[1] && d.getDate() == Number(bits[2]);
}
