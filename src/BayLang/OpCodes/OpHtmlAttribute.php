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
use Runtime\Serializer\BooleanType;
use Runtime\Serializer\ObjectType;
use Runtime\Serializer\StringType;
use BayLang\OpCodes\BaseOpCode;
use BayLang\OpCodes\OpCodeType;


class OpHtmlAttribute extends \BayLang\OpCodes\BaseOpCode
{
	var $op;
	var $key;
	var $is_spread;
	var $expression;
	
	
	/**
	 * Serialize object
	 */
	static function serialize($rules)
	{
		parent::serialize($rules);
		$rules->addType("is_spread", new \Runtime\Serializer\BooleanType());
		$rules->addType("key", new \Runtime\Serializer\StringType());
		$rules->addType("expression", new \BayLang\OpCodes\OpCodeType());
	}
	
	
	/* ========= Class init functions ========= */
	function _init()
	{
		parent::_init();
		$this->op = "op_html_attr";
		$this->key = "";
		$this->is_spread = false;
		$this->expression = null;
	}
	static function getClassName(){ return "BayLang.OpCodes.OpHtmlAttribute"; }
	static function getMethodsList(){ return null; }
	static function getMethodInfoByName($field_name){ return null; }
}