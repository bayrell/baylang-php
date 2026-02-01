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
namespace BayLang\OpCodes;

use Runtime\BaseObject;
use Runtime\BaseStruct;
use Runtime\SerializeInterface;
use Runtime\Serializer;
use Runtime\Serializer\ObjectType;
use Runtime\Serializer\StringType;
use BayLang\Caret;


class BaseOpCode extends \Runtime\BaseObject implements \Runtime\SerializeInterface
{
	const op = "";
	var $caret_start;
	var $caret_end;
	
	
	/**
	 * Serialize object
	 */
	static function serialize($rules)
	{
		parent::serialize($rules);
		$rules->addType("op", new \Runtime\Serializer\StringType());
		$rules->addType("caret_start", new \Runtime\Serializer\ObjectType(new \Runtime\Map(["class_name" => "BayLang.Caret"])));
		$rules->addType("caret_end", new \Runtime\Serializer\ObjectType(new \Runtime\Map(["class_name" => "BayLang.Caret"])));
	}
	
	
	/**
	 * Assign rules
	 */
	function assignRules($rules){}
	
	
	/**
	 * Constructor
	 */
	function __construct($params = null)
	{
		parent::__construct();
		$this->_assign_values($params);
	}
	
	
	/**
	 * Is multiline
	 */
	function isMultiLine()
	{
		if (!$this->caret_start) return true;
		if (!$this->caret_end) return true;
		return $this->caret_start->y != $this->caret_end->y;
	}
	
	
	/**
	 * Returns offset
	 */
	function getOffset()
	{
		return new \Runtime\Map([
			"start" => $this->caret_start ? $this->caret_start->y : 0,
			"end" => $this->caret_end ? $this->caret_end->y : 0,
		]);
	}
	
	
	/**
	 * Clone this struct with new values
	 * @param Map obj = null
	 * @return BaseStruct
	 */
	function clone($obj = null)
	{
		if ($obj == null) return $this;
		$item = clone $this;
		$item->_assign_values($ctx, $obj);
		return $item;
		return $this;
	}
	
	
	/**
	 * Copy this struct with new values
	 * @param Map obj = null
	 * @return BaseStruct
	 */
	function copy($obj = null)
	{
		return $this->clone($obj);
	}
	
	
	/* ========= Class init functions ========= */
	function _init()
	{
		parent::_init();
		$this->caret_start = null;
		$this->caret_end = null;
	}
	static function getClassName(){ return "BayLang.OpCodes.BaseOpCode"; }
	static function getMethodsList(){ return null; }
	static function getMethodInfoByName($field_name){ return null; }
}