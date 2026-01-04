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
use Runtime\Serializer\StringType;
use BayLang\OpCodes\BaseOpCode;
use BayLang\OpCodes\OpAnnotation;
use BayLang\OpCodes\OpCodeType;
use BayLang\OpCodes\OpComment;
use BayLang\OpCodes\OpEntityName;
use BayLang\OpCodes\OpFlags;


class OpAssignValue extends \BayLang\OpCodes\BaseOpCode
{
	var $op;
	var $value;
	var $expression;
	
	
	/**
	 * Serialize object
	 */
	static function serialize($rules)
	{
		parent::serialize($rules);
		$rules->addType("op", new \Runtime\Serializer\StringType());
		$rules->addType("value", new \BayLang\OpCodes\OpCodeType());
		$rules->addType("expression", new \BayLang\OpCodes\OpCodeType());
	}
	
	
	/* ========= Class init functions ========= */
	function _init()
	{
		parent::_init();
		$this->op = "";
		$this->value = null;
		$this->expression = null;
	}
	static function getClassName(){ return "BayLang.OpCodes.OpAssignValue"; }
	static function getMethodsList(){ return null; }
	static function getMethodInfoByName($field_name){ return null; }
}