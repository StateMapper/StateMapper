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


$(document).ready(function(){
	
	// resize home's bg
	if ($('body').hasClass('root')){
		resize_home_bg();
		$(window).resize(function(){
			resize_home_bg();
		});
		
		function resize_home_bg(){
			var w = $('.bg-triangle-bg').first().width();
			var h = $('.main-header').height();
			var diff = w + (2*h/3);
			
			$('.bg-triangle').css({
				height: diff
			});
			$('.bg-triangle-bg').css({
				top: diff
			});
		}
	}
});
