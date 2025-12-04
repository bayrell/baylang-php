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

use Runtime\Serializer;
use BayLang\OpCodes\BaseOpCode;
use BayLang\OpCodes\OpEntityName;
use BayLang\OpCodes\OpIdentifier;


class OpTypeIdentifier extends \BayLang\OpCodes\BaseOpCode
{
	var $op;
	var $entity_name;
	var $generics;
	
	
	/**
	 * Serialize object
	 */
	function serialize($serializer, $data)
	{
		parent::serialize($serializer, $data);
		$serializer->process($this, "entity_name", $data);
		$serializer->process($this, "generics", $data);
	}
	
	
	/**
	 * Create Type Identifier
	 */
	static function create($name)
	{
		return new \BayLang\OpCodes\OpTypeIdentifier(new \Runtime\Map([
			"entity_name" => new \BayLang\OpCodes\OpEntityName(new \Runtime\Map([
				"items" => new \Runtime\Vector(
					new \BayLang\OpCodes\OpIdentifier(new \Runtime\Map([
						"value" => $name,
					])),
				),
			])),
		]));
	}
	
	
	/* ========= Class init functions ========= */
	function _init()
	{
		parent::_init();
		$this->op = "op_type_identifier";
		$this->entity_name = null;
		$this->generics = null;
	}
	static function getClassName(){ return "BayLang.OpCodes.OpTypeIdentifier"; }
	static function getMethodsList(){ return null; }
	static function getMethodInfoByName($field_name){ return null; }
}