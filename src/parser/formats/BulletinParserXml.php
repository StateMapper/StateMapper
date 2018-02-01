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

namespace StateMapper; 

if (!defined('BASE_PATH'))
	die();
	
class BulletinParserXml {
	public $parent = null;
	
	public function __construct($parent){
		$this->parent = $parent;
	}
	
	// init 
	public function load_root_node($content){
		try {
			return new \SimpleXMLElement($content);
		} catch (Exception $e){
			return new SMapError('XML Exception: '.$e->getMessage());//.print_r($e, true));
		}
	}
	/*
	public function select($childConfig, $node, $rootNode){
		$childConfigSelector = is_string($childConfig) ? $childConfig : $childConfig->selector;
		
		if (substr($childConfigSelector, 0, 2) == './'){
			if (!$node)
				return 'bad selector '.$childConfigSelector;
			return $this->get_node_child($node, substr($childConfigSelector, 2));
		} else
			return $this->get_node($rootNode, $childConfigSelector);
	}
*/
	public function is_selector($selector){
		return property_exists($selector, 'selector');
	}
	
	public function get_value_by_selector($selector, $childConfig, $node, $rootNode, $isChild = false){
		$oselector = $selector;
		
		if (is_object($selector))
			$selector = $selector->selector;

		if ($selector == '.')
			//return print_r($node, true);//
			return $this->get_node_content($node);

		if ($selector[0] == '/'){// || $selector[0] == '('){
			if (!$rootNode)
				return null;
			
			$ret = $this->get_node($rootNode, $selector);
			return $isChild && is_array($ret) ? array_shift($ret) : $ret;
		}
		
		if ($selector[0] == '@'){
			if (!$node)
				return null;
				
			return (string) $this->get_attribute($node, substr($selector, 1));
		}

		if (substr($selector, 0, 2) == './')
			return $this->get_node_child($node, substr($selector, 2));
		
		if (is_string($childConfig))
			return $childConfig;
			
		die('error in parsingProtocole, selector '.$selector);
	}

	// "@" selector (attribute)
	public function get_attribute($node, $selector){
		$attr = $node->attributes();
		return (string) $attr[$selector];
	}
	
	// "/" selector (xpath style selector)
	public function get_node($rootNode, $selector){
		$selector = explode('@', $selector);
		$fsel = preg_replace('#^(.*?)/?$#', '$1', $selector[0]);
		
		if (empty($fsel)){
			// pure attribute selector
			$fsel = '@'.$selector[1];
			$selector = array($fsel);
		}
		
		//echo $fsel.(!empty($selector[1]) ? ' / @'.$selector[1] : '').'<br>';
		$node = $rootNode->xpath($fsel);
		if (count($selector) > 1){
			if (is_array($node)){
				if (!$node)
					return null;
				$node = $node[0];
			}
			$attr = $node->attributes();
			return !empty($attr[$selector[1]]) ? ((string) $attr[$selector[1]]) : null;
		}
		return $node;
	}
	
	// "./" selector (get child node)
	public function get_node_child($node, $selector){
		return $node->{$selector};
	}
	
	// "." selector (get text value)
	public function get_node_content($node){
		$value = $node->asXML();
		
//		$value = convert_encoding($value);
		
		// html escaping
		$cleanValue = '';
		foreach (explode('<p', $value) as $i => $line){
			$line = trim(strip_tags($i ? '<p'.$line : $line));
			if ($line != '')
				$cleanValue .= $line.P_DILIMITER;//"\n\n";
		}
		return $cleanValue;
	}
}
