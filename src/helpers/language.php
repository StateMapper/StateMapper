<?php
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
 
if (!defined('BASE_PATH'))
	die();
	
define('DEFAULT_LANG', 'en_US');

if (!empty($_REQUEST['lang']))
	smap_set_locale($_REQUEST['lang']);
else if (defined('LANG'))
	smap_set_locale(LANG);
else
	define('LANG', DEFAULT_LANG);

function smap_set_locale($lang){
	putenv('LANG='.$lang);
	putenv('LANGUAGE='.$lang); // '.UTF-8'
	setlocale(LC_ALL, $lang.'.UTF-8');
	bindtextdomain('smap', APP_PATH.'/languages');
	bind_textdomain_codeset('smap', 'UTF-8');
	textdomain('smap');
}
	
function get_lang($full = false){ 
	$lang = !empty($_REQUEST['lang']) ? $_REQUEST['lang'] : (defined('LANG') ? LANG : DEFAULT_LANG);
	if (!$full)
		$lang = substr($lang, 0, 2);
	return $lang;
}

function is_default_lang(){
	return get_lang(true) == DEFAULT_LANG;
}

function add_lang($url, $lang = null){
	if ($lang){
		if ($lang == DEFAULT_LANG)
			return remove_url_arg('lang', $url);
		return add_url_arg('lang', $lang, $url);
	}
	
	if (!is_default_lang())
		return add_url_arg('lang', get_lang(true), $url);
		
	return $url;
}

function get_country_from_lang($lang){
	$country = strtoupper(strlen($lang) == 5 ? substr($lang, 3) : $lang);
	switch ($country){
		case 'EN':
			$country = 'UK';
			break;
	}
	return $country;
}

function get_lang_label($lang){

	switch ($lang){
		case 'en_US':
			return 'English (US)';
		case 'es_ES':
			return 'Español (España)';
	}

	$lang = substr($lang, 0, 2);
	switch ($lang){
		case 'en':
			return 'English';
		case 'es':
			return 'Español';
	}
	return strtoupper($lang);
}

add_action('html_attributes', 'print_lang_html_attributes');
function print_lang_html_attributes(){
	echo ' lang="'.str_replace('_', '-', get_lang(true)).'"';
}

add_action('head', 'print_lang_metatags');
function print_lang_metatags(){
	echo '<meta property="og:locale" content="'.get_lang(true).'">';

	foreach (get_langs(false) as $lang)
		echo '<link rel="alternate" hreflang="'.substr($lang, 0, 2).'" href="'.add_lang(get_canonical_url(), $lang).'">';
}

function get_langs($include_current = false){
	$langs = array();
	if (!is_default_lang())
		$langs[] = DEFAULT_LANG;
	$cur_lang = get_lang(true);
	foreach (ls_dir(APP_PATH.'/languages') as $lang)
		if (preg_match('#^[a-z]{2}_[A-Z]{2}$#', $lang) && ($lang != $cur_lang || $include_current))
			$langs[] = $lang;
	return $langs;
}

add_action('footer_copyright_after', 'print_lang_footer');
function print_lang_footer(){
	
	$cur_lang = get_lang(true);
	$langs = get_langs(false);
	if ($langs){
		?>
		<span> | </span>
		<span class="lang-menu">
			<span class="menu menu-right menu-top">
				<span class="menu-button" title="<?= esc_attr('Switch to another language') ?>"><img src="<?= get_flag_url(get_country_from_lang($cur_lang)) ?>" /> <?= strtoupper(substr($cur_lang, 0, 2)) ?></span>
				<span class="menu-wrap">
					<span class="menu-menu">
						<ul class="menu-inner">
							<?php foreach ($langs as $lang){ ?>
								<li><a href="<?= ($lang == DEFAULT_LANG ? remove_url_arg('lang') : add_url_arg('lang', $lang)) ?>" class="<?php 
									if ($lang == $cur_lang)
										echo 'menu-item-active';
								?>"><img src="<?= get_flag_url(get_country_from_lang($lang)) ?>" /> <?= get_lang_label($lang) ?></a></li>
							<?php } ?>
						</ul>
					</span>
				</span>
			</span>
		</span>
		<?php
	}
}

/* 
 * translate methods:
 *   __('my label');
 *   __('my label', 'my context'); // for homonyms
 */
 
function __($msgid, $context = null){
	if (!$context)
		return gettext($msgid);
	$contextString = "{$context}\004{$msgid}";
	$translation = dcgettext('smap', $contextString, LC_MESSAGES);
	if ($translation == $contextString)  return $msgid;
	else  return $translation;
}

