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


smap_add_filter('ajax_return', function(data, action, actionData){
	if (data && data.reauth){
		smap_auth();
		return false;
	}
	return data;
});

function smap_auth(){
	$('#login-form').clone(false).removeAttr('id').addClass('login-form').smapPopup({
		onOpen: function(popup){
			popup.find('.radios input').change(function(){
				popup.find('.login-form-mode').hide();
				popup.find('.login-form-mode-'+popup.find('.radios input:checked').val()).show();
			});
			popup.find('input[type="text"]:first').focus();
		}
	});
}

smap_add_action('load', function(){
	$('.footer-login').on('click', function(e){
		smap_auth();
	});
});

