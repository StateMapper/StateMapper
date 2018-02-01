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

	/* toggle entry statuses */
	$('.entity-statuses').on('click', '.entity-stat-group', function(e){
		var t = $(this);
		var related = $(this).smapGetRelated();
		var w = t.closest('.entity-stat-wrap');
		var h = w.find('.entity-stat-children-holder');
		if (w.hasClass('entity-stat-children-filled')){
			h.stop().toggle();
			w.toggleClass('entity-stat-children-open');
		} else {
			w.addClass('entity-stat-children-open');
			w.addClass('entity-stat-children-filled');
			w.addClass('entity-stat-children-loading');
			smapAjax('load_statuses', {related: related}, function(data){
				if (data.success){
					var html = $(data.html).appendTo(h);
					h.stop().show();
					smapUpdate(html);
				}
			}, function(){
				// error
				w.removeClass('entity-stat-children-open');
				w.removeClass('entity-stat-children-filled');
			}, function(){
				// complete
				w.removeClass('entity-stat-children-loading');
			});
		}
	});
	
	/* extracts from title click */
	$('.entity-statuses').on('click', '.status-title, .extract-link', function(e){
		if (!$(e.target).closest('a, .entity-stat-group').length){
			$(e.target).closest('.status-body').find('.folding').first().toggleClass('visible');
			$(e.target).closest('.entity-stat').toggleClass('folding-visible-extract');
		}
	});
	
});
