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

class BulletinParserPdf {
	
	public $parent = null;
	private $varReplaceNode = null;
	
	public function __construct($parent){
		$this->parent = $parent;
	}
	
	// init 
	public function load_root_node($content){
		return $content;
	}
	
	public function is_selector($selector){
		return property_exists($selector, 'regexpAttr') 
			|| property_exists($selector, 'match') 
			|| (property_exists($selector, 'regexp') && !preg_match('#^(\$[0-9]+)$#', $selector->regexp) && $selector->regexp != ".");
	}
		
	public function get_value_by_selector($selector, $childConfig, $node, $rootNode, $isChild = false, $parent = null){
		if (is_object($selector) && $this->is_selector($selector)){// && (!empty($selector->regexpAttr) || empty($selector->match))){
			//echo 'FIRST SELECTOR: '.print_r($selector->regexp, true).'<br>';

			if (!empty($selector->regexpAttr)){
				
				// regexpMatch as first transform rule
				if (!empty($selector->transform) && $selector->transform[0]->type == 'regexpMatch')
					return preg_replace($selector->regexpAttr, $selector->transform[0]->match, $rootNode);

				if (!empty($selector->match)) 
					return preg_replace($selector->regexpAttr, $selector->match, $node); // TODO: not working, TO CONTINUE!!
					
				if ($selector->regexpAttr == ".")
					return trim($rootNode);

				return preg_match($selector->regexpAttr, $rootNode, $matches) ? $matches : null; // pass matches to following operations (*)
			
			} else if (!empty($selector->regexp)){
				
				$selector = $selector->regexp;
				if ($parent)
					$selector = str_replace('{legalEntityPattern}', $this->parent->get_entity_pattern(), $selector);
				
				if (!($blocks = preg_split($selector, $rootNode, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY)))
					return null;
				
				array_shift($blocks); // remove before delimiter
				
				$value = array();
				$cblock = null;
				foreach ($blocks as $b){
					
					if (preg_match($selector, $b)){ // is a delimiter
						if ($cblock !== null)
							$value[] = $cblock;
						$cblock = array();
					}
					
					$cblock[] = $b;
				}
				if ($cblock !== null)
					$value[] = $cblock;
					
				return $value;
			}
		}
		
		if (is_object($selector)){
			//if (!empty($selector->regexpAttr))!preg_match($selector->regexpAttr, $rootNode, $matches)) // is a delimiter
			//	return null;
			
			if (!empty($selector->match))
				$selector = $selector->match;
			
			else if (!empty($selector->regexp))
				$selector = $selector->regexp;
				
			//else if (!empty($selector->regexpAttr)){
				//$selector = $selector->regexpAttr;
				/*
				var_dump($selector); die();
				return preg_replace($selector->regexpAttr, $selector->match, $node);
				*/
				
			else 
				return $childConfig;
		}
		
		//echo 'END SELECTOR: '.$selector.'<br>';
		if (!is_string($selector))
			return $selector;
			
		if ($selector == ".")
			return $rootNode;

		//return preg_replace($childConfig->regexpAttr, $selector, $value);
		
		// convert to preg_replace
		$this->varReplaceNode = (array) $node; // passed from previous regexpAttr (*)
		$ret = preg_replace_callback('#(\$([0-9]+))#', array(&$this, 'inject_variables'), $selector);
		return trim($ret);
	}
	
	function inject_variables($matches){
		$i = intval($matches[2]) - 1;
		return isset($this->varReplaceNode[$i]) ? $this->varReplaceNode[$i] : '';
	}
	
	public function get_node_content($node){
		if (!is_string($node)){
			var_dump($node);
			die('ERROR IN PDF PARSER');
		}
		return $node;
	}
}
