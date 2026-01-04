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

use Runtime\Serializer\BooleanType;
use Runtime\Serializer\ObjectType;
use Runtime\Serializer\MapType;
use Runtime\Serializer\StringType;
use Runtime\Serializer\VectorType;
use Runtime\Exceptions\RuntimeException;
use BayLang\OpCodes\BaseOpCode;
use BayLang\OpCodes\OpCodeType;
use BayLang\OpCodes\OpDeclareClass;
use BayLang\OpCodes\OpNamespace;
use BayLang\OpCodes\OpUse;


class OpModule extends \BayLang\OpCodes\BaseOpCode
{
	var $op;
	var $uses;
	var $items;
	var $is_component;
	
	
	/**
	 * Serialize object
	 */
	static function serialize($rules)
	{
		parent::serialize($rules);
		$rules->addType("is_component", new \Runtime\Serializer\BooleanType());
		$rules->addType("items", new \Runtime\Serializer\VectorType(new \BayLang\OpCodes\OpCodeType()));
		$rules->addType("uses", new \Runtime\Serializer\MapType(new \Runtime\Serializer\StringType()));
	}
	
	
	/**
	 * Add module
	 */
	function addModule($class_name, $alias_name = "", $is_component = true)
	{
		if ($alias_name != "")
		{
			$this->uses->set($alias_name, $class_name);
		}
		/* Add op_code */
		$pos = $this->items->find(lib::isInstance("BayLang.OpCodes.OpNamespace"));
		$op_code = new \BayLang\OpCodes\OpUse(new \Runtime\Map([
			"alias" => $alias_name,
			"name" => $class_name,
			"is_component" => $is_component,
		]));
		if ($pos != -1)
		{
			$pos = $pos + 1;
			while ($pos < $this->items->count())
			{
				$item = $this->items->get($pos);
				if ($item == null) break;
				if (!($item instanceof \BayLang\OpCodes\OpUse)) break;
				if (\Runtime\rs::compare($class_name, $item->name) == -1) break;
				$pos = $pos + 1;
			}
			$this->items->insert($pos, $op_code);
		}
		else
		{
			$this->items->prepend($op_code);
		}
	}
	
	
	/**
	 * Has module
	 */
	function hasModule($alias_name){ return $this->uses->has($alias_name); }
	
	
	/**
	 * Find alias name
	 */
	function findModule($class_name)
	{
		$keys = $this->uses->keys();
		for ($i = 0; $i < $keys->count(); $i++)
		{
			$key_name = $keys->get($i);
			if ($this->uses->get($key_name) == $class_name) return $key_name;
		}
		return null;
	}
	
	
	/**
	 * Find class
	 */
	function findClass(){ return $this->items ? $this->items->findItem(lib::isInstance("BayLang.OpCodes.OpDeclareClass")) : null; }
	
	
	
	/**
	 * Find class by name
	 */
	function findClassByName($name)
	{
		return $this->items->findItem(function ($item) use (&$name)
		{
			if (!($item instanceof \BayLang\OpCodes\OpDeclareClass)) return false;
			if ($item->name == $name) return false;
			return true;
		});
	}
	
	
	/* ========= Class init functions ========= */
	function _init()
	{
		parent::_init();
		$this->op = "op_module";
		$this->uses = null;
		$this->items = null;
		$this->is_component = false;
	}
	static function getClassName(){ return "BayLang.OpCodes.OpModule"; }
	static function getMethodsList(){ return null; }
	static function getMethodInfoByName($field_name){ return null; }
}