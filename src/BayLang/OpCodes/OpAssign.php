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

use Runtime\Serializer\ObjectType;
use Runtime\Serializer\VectorType;
use BayLang\OpCodes\BaseOpCode;
use BayLang\OpCodes\OpAnnotation;
use BayLang\OpCodes\OpAssignValue;
use BayLang\OpCodes\OpComment;
use BayLang\OpCodes\OpFlags;
use BayLang\OpCodes\OpTypeIdentifier;
use BayLang\OpCodes\OpIdentifier;


class OpAssign extends \BayLang\OpCodes\BaseOpCode
{
	const KIND_ASSIGN = "assign";
	const KIND_DECLARE = "declare";
	const KIND_STRUCT = "struct";
	
	var $op;
	var $flags;
	var $pattern;
	var $items;
	
	
	/**
	 * Serialize object
	 */
	static function serialize($rules)
	{
		parent::serialize($rules);
		$rules->addType("flags", new \Runtime\Serializer\ObjectType(new \Runtime\Map(["class_name" => "BayLang.OpCodes.OpFlags"])));
		$rules->addType("pattern", new \Runtime\Serializer\ObjectType(new \Runtime\Map(["class_name" => "BayLang.OpCodes.OpTypeIdentifier"])));
		$rules->addType("items", new \Runtime\Serializer\VectorType(new \Runtime\Serializer\ObjectType(new \Runtime\Map([
			"class_name" => "BayLang.OpCodes.OpAssignValue",
		]))));
	}
	
	
	/**
	 * Returns true if static
	 */
	function isStatic(){ return $this->flags && ($this->flags->isFlag("static") || $this->flags->isFlag("const")); }
	
	
	/**
	 * Find variable
	 */
	function findVariable($name)
	{
		return $this->items->find(function ($item) use (&$name){ return $item->value instanceof \BayLang\OpCodes\OpIdentifier && $item->value->value == $name; });
	}
	
	
	/* ========= Class init functions ========= */
	function _init()
	{
		parent::_init();
		$this->op = "op_assign";
		$this->flags = null;
		$this->pattern = null;
		$this->items = null;
	}
	static function getClassName(){ return "BayLang.OpCodes.OpAssign"; }
	static function getMethodsList(){ return null; }
	static function getMethodInfoByName($field_name){ return null; }
}