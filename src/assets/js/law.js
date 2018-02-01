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
 
 
smap_add_action('load', function(){
	var lawDisclaimer = $('.footer-law-disclaimer');
	lawDisclaimer.click(function(e){
		if (!$(e.target).closest('a').length)
			closeLawDisclaimer();
	});
	
	$(window).on('scroll.smap_law_disclaimer', function(){
		if ($(window).scrollTop() > 400){
			$(window).off('scroll.smap_law_disclaimer');
			closeLawDisclaimer();
		}
	});

	function closeLawDisclaimer(){
		lawDisclaimer.slideUp();
		smapAjax('agree_law_disclaimer');
	}
});
