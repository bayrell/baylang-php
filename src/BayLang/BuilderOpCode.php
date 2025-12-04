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
namespace BayLang;

use Runtime\BaseObject;
use BayLang\OpCodes\OpHtmlAttribute;
use BayLang\OpCodes\OpHtmlItems;
use BayLang\OpCodes\OpHtmlSlot;
use BayLang\OpCodes\OpHtmlTag;


class BuilderOpCode extends \Runtime\BaseObject
{
	/**
	 * Add slot
	 */
	function addSlot($op_code, $name)
	{
		$slot = new \BayLang\OpCodes\OpHtmlSlot(new \Runtime\Map([
			"name" => $name,
			"items" => new \BayLang\OpCodes\OpHtmlItems(),
		]));
		$op_code->items->items->push($slot);
		return $slot;
	}
	
	
	/**
	 * Add tag
	 */
	function addTag($op_code, $name)
	{
		$tag = new \BayLang\OpCodes\OpHtmlTag(new \Runtime\Map([
			"attrs" => new \Runtime\Vector(),
			"items" => new \BayLang\OpCodes\OpHtmlItems(),
			"tag_name" => $name,
		]));
		$op_code->items->items->push($tag);
		return $tag;
	}
	
	
	/* ========= Class init functions ========= */
	function _init()
	{
		parent::_init();
	}
	static function getClassName(){ return "BayLang.BuilderOpCode"; }
	static function getMethodsList(){ return null; }
	static function getMethodInfoByName($field_name){ return null; }
}