<?php
/*!
 *  BayLang Technology
 *
 *  (c) Copyright 2016-2025 "Ildar Bikmamatov" <support@bayrell.org>
 *
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 *  You may obtain a copy of the License at
 *
 *      https://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *  limitations under the License.
 */
namespace BayLang\LangStyle;

use Runtime\BaseObject;

class Selector extends \Runtime\BaseObject
{
	var $path;
	var $media;
	var $css_hash;
	
	
	/**
	 * Copy selector
	 */
	function copy()
	{
		$item = new \BayLang\LangStyle\Selector();
		$item->path = $this->path->slice();
		$item->media = $this->media->slice();
		$item->css_hash = $this->css_hash;
		return $item;
	}
	
	
	/**
	 * Returns media
	 */
	function getMedia(){ return \Runtime\rs::join(" ", $this->media); }
	
	
	/**
	 * Returns selector
	 */
	function getSelector(){ return \Runtime\rs::join(" ", $this->path); }
	
	
	/**
	 * Add hash
	 */
	static function addHash($selector, $css_hash)
	{
		$selectors = \Runtime\rs::split(",", $selector);
		for ($i = 0; $i < $selectors->count(); $i++)
		{
			$selector_item = $selectors->get($i);
			$items = \Runtime\rs::split(" ", \Runtime\rs::trim($selector_item));
			$item = $items->get(0);
			$arr = \Runtime\rs::split(":", $item);
			$prefix = $arr->get(0);
			$postfix = \Runtime\rs::join(":", $arr->slice(1));
			if ($postfix != "") $postfix = ":" . $postfix;
			$items->set(0, $prefix . ".h-" . $css_hash . $postfix);
			$selectors->set($i, \Runtime\rs::join(" ", $items));
		}
		return \Runtime\rs::join(", ", $selectors);
	}
	
	
	/**
	 * Concat selector
	 */
	function concat($last_item, $selector)
	{
		$result = new \Runtime\Vector();
		$last_items = \Runtime\rs::split(",", $last_item);
		for ($i = 0; $i < $last_items->count(); $i++)
		{
			$last_item = \Runtime\rs::trim($last_items->get($i));
			$arr = \Runtime\rs::split(",", $selector);
			for ($j = 0; $j < $arr->count(); $j++)
			{
				$selector_item = \Runtime\rs::trim($arr->get($j));
				$index = \Runtime\rs::indexOf($selector_item, "&");
				if ($index == -1)
				{
					if ($last_item)
					{
						$result->push($last_item . " " . $selector_item);
					}
					else
					{
						$result->push($selector_item);
					}
				}
				else
				{
					$prefix = \Runtime\rs::substr($selector_item, 0, $index);
					$postfix = \Runtime\rs::substr($selector_item, $index + 1);
					$css_hash = ".h-" . $this->css_hash;
					$last_item = \Runtime\rs::trim($last_items->get($i));
					if (\Runtime\rs::indexOf($last_item, $css_hash) >= 0)
					{
						$last_item = \Runtime\rs::replace($css_hash, "", $last_item);
						$last_item = static::addHash($last_item . $postfix, $this->css_hash);
					}
					else
					{
						$last_item = $last_item . $postfix;
					}
					$result->push($prefix . $last_item);
				}
			}
		}
		return \Runtime\rs::join(", ", $result);
	}
	
	
	/**
	 * Combine selector
	 */
	function add($selector)
	{
		if (\Runtime\rs::charAt($selector, 0) == "@")
		{
			$this->media->push($selector);
		}
		else
		{
			$last_item = \Runtime\rs::join(" ", $this->path);
			$last_item = $this->concat($last_item, $selector);
			$this->path = new \Runtime\Vector($last_item);
		}
	}
	
	
	/* ========= Class init functions ========= */
	function _init()
	{
		parent::_init();
		$this->path = new \Runtime\Vector();
		$this->media = new \Runtime\Vector();
		$this->css_hash = "";
	}
	static function getClassName(){ return "BayLang.LangStyle.Selector"; }
	static function getMethodsList(){ return null; }
	static function getMethodInfoByName($field_name){ return null; }
}