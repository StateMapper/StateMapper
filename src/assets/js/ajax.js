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



// ajax method to call the server
function smapAjax(action, actionData, successCb, errorCb, completeCb){
	
	return $.ajax({
		type: 'POST',
		url: SMAP.ajaxUrl,
		data: {
			action: action,
			session: SMAP.session,
			lang: SMAP.lang,
			data: actionData
		},
		complete: function(data){
			if (completeCb)
				completeCb(data);
		},
		success: function(data){
			if (data.error)
				alert(data.error);
			if (data.msg)
				alert(data.msg);

			data = smap_apply_filters('ajax_return', data, action, actionData);
			
			if (data.reload)
				location.reload();
			else {
				if (successCb)
					successCb(data, data && data.success);
				if ((!data || !data.success) && errorCb)
					errorCb(data);
			}
		},
		error: function(data){
			//alert('an error occurred');
			if (errorCb)
				errorCb(data);
		},
		dataType: 'json'
	});
}

$(document).ready(function(){
	
	// auto (dropin) ajax
	$('body').on('click', 'a.smap-ajax', function(){
		var c = $(this).data('smap-confirm');
		if (!c || confirm(c)){
			var data = $(this).data('smap-ajax-data');
			smapAjax($(this).data('smap-ajax'), data ? data : {});
		}
		return false;
	});
	
	// ajax forms
	$('body').on('submit', 'form.ajax-form', function(e){
		var f = $(this);
		var related = f.smapGetRelated();
		e.preventDefault();
		
		var fields = f.find('input').serializeArray();
		
		var btn = f.find('input[type="submit"]');
		if (!btn.data('smap-olabel'))
			btn.data('smap-olabel', btn.val());
		
		if (btn.data('smap-loading-label'))
			btn.val(btn.data('smap-loading-label'));
		
		smapAjax(related.action, {fields: fields}, function(data, success){
			if (!success)
				btn.val(btn.data('smap-olabel'));
		});
		
		return false;
	});

});
