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
use BayLang\OpCodes\BaseOpCode;
use BayLang\OpCodes\OpCodeType;


class OpTernary extends \BayLang\OpCodes\BaseOpCode
{
	var $op;
	var $condition;
	var $if_true;
	var $if_false;
	
	
	/**
	 * Serialize object
	 */
	static function serialize($rules)
	{
		parent::serialize($rules);
		$rules->addType("condition", new \BayLang\OpCodes\OpCodeType());
		$rules->addType("if_false", new \BayLang\OpCodes\OpCodeType());
		$rules->addType("if_true", new \BayLang\OpCodes\OpCodeType());
	}
	
	
	/* ========= Class init functions ========= */
	function _init()
	{
		parent::_init();
		$this->op = "op_ternary";
		$this->condition = null;
		$this->if_true = null;
		$this->if_false = null;
	}
	static function getClassName(){ return "BayLang.OpCodes.OpTernary"; }
	static function getMethodsList(){ return null; }
	static function getMethodInfoByName($field_name){ return null; }
}