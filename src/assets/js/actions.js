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
 
// data-smap-* shortcuts
$.fn.smap = function(key){
	return this.data('smap-'+(key ? key : 'related'));
};

$.fn.smapGetRelated = function(){
	var c = $(this).smap();
	//console.log(c);
	var ret = c ? c : {};
	$(this).parents('[data-smap-related]').each(function(){
		$.extend(ret, $(this).data('smap-related'));
	});
	return ret;
};

$.fn.smapSet = function(obj){
	var data = this.smap();
	if (!data)
		data = {};
	data = $.extend(true, {}, data, obj);
	return this.data('smap-related', data);
};


// action hooks (WordPress-style)
// note: we don't use SMAP.actions because SMAP is inited after smap_add_action are called
var SMAP_Actions = {};
function smap_do_action(name){
	if (SMAP_Actions[name])
		for (var i=0; i<SMAP_Actions[name].length; i++)
			SMAP_Actions[name][i]();
}

function smap_add_action(name, cb){
	if (!SMAP_Actions[name])
		SMAP_Actions[name] = [];
	SMAP_Actions[name].push(cb);
}

// filters (WordPress-style)
// note: we don't use SMAP.filters because SMAP is inited after smap_add_action are called
var SMAP_Filters = {};
function smap_apply_filters(name, v){
	if (SMAP_Filters[name]){
		var args = Array.prototype.slice.call(arguments);
		for (var i=0; i<SMAP_Filters[name].length; i++){
			args[0] = v;
			v = SMAP_Filters[name][i].apply(window, args);
		}
	}
	return v;
}

function smap_add_filter(name, cb){
	if (!SMAP_Filters[name])
		SMAP_Filters[name] = [];
	SMAP_Filters[name].push(cb);
}


// autoactions
smap_add_action('load', function(){

	$('body').on('click', '.autoaction', function(e){
		var t = $(e.target).closest('a');
		var related = t.smapGetRelated();
		if (typeof window['autoaction_'+related.action] == 'function')
			return window['autoaction_'+related.action](t, related);
		
		smapAjax('autoaction', {related: related}, function(data, success){
			debugger;
		});
		return false;
	});
	
	$('.results').on('click', '.results-outro a', function(){
		sugg.input.focus();
		return false;
	});
	
});
