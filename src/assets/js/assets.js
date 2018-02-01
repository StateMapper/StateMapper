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



// load lazy CSS
function smapLazyloadCSS(done){

	if (!SMAP.lazy_stop){
		var loading = SMAP.lazy_css.length;
		if (loading)
			for (var i=0; i<SMAP.lazy_css.length; i++){
				$("head").append('<link rel="stylesheet" type="text/css" href="'+SMAP.lazy_css[i]+'" media="all" />');
				
				loading--;
				if (!loading){
					var interval = setInterval(function(){
						if ($('.lazy-loader-ind').is(':visible')){
							load();
							clearInterval(interval);
						}
					}, 300);
				}
			}
		else
			load();
	}
	
	function load(){
		SMAP.actions = [];
		
		// lazyload
		smap_do_action('load');
		done();
		$('body').removeClass('lazy-loading');
		smap_do_action('loaded');
	}
}
