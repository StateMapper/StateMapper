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
 

$.fn.smapPopup = function(opts){
	if (!opts)
		opts = {};
		
	var p = $('<div class="popup"><div class="popup-overlay"></div><div class="popup-inner"></div></div>');
	var t = this.appendTo(p.find('.popup-inner'));
	
	p.find('.popup-overlay').click(function(){
		hide_popup();
	});
	
	// open popup
	p.hide().prependTo($('body'));
	
	if (opts.onBeforeOpen)
		opts.onBeforeOpen.call(t, p);
	
	p.fadeIn(function(){
		if (opts.onComplete)
			opts.onComplete.call(t, p);
	});
	
	if (opts.onOpen)
		opts.onOpen.call(t, p);
			
	function hide_popup(){
		p.stop().fadeOut(function(){
			p.remove();
		});
	}
};

