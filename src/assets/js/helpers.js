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




function argsToObject(search){
    var params = decodeURIComponent(search).split('&'),
        sParameterName;
	
	var obj = {};
    for (var i = 0; i < params.length; i++){
        sParameterName = params[i].split('=');
		obj[sParameterName[0]] = sParameterName[1];
    }
    return obj;
}

function smapInc(){
	if (!SMAP.inc)
		SMAP.inc = 1;
	else
		SMAP.inc++;
	return SMAP.inc;
}

// load bad iframe in Chrome
function smapGetChromeVersion(){
	var raw = navigator.userAgent.match(/Chrom(e|ium)\/([0-9]+)\./);
	return raw ? parseInt(raw[2], 10) : false;
}

function smapRedrawElement(el,delay){
	// console.log("chrome44-bug-512827 workaround");
	var dispOrigValue = el.style.display;
	el.style.display = 'none';
	setTimeout(function(){
		var te = document.createTextNode(' ');
		el.appendChild(te);
		el.style.display = dispOrigValue;
		te.parentElement.removeChild(te);
	},delay);
}    

function convert_integer(str){
	return typeof str == 'string' ? parseInt(str, 10) : str;
}
