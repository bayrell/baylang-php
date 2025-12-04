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
use BayLang\OpCodes\OpTypeIdentifier;


class OpTryCatchItem extends \BayLang\OpCodes\BaseOpCode
{
	var $op;
	var $name;
	var $pattern;
	var $content;
	
	
	/**
	 * Serialize object
	 */
	function serialize($serializer, $data)
	{
		parent::serialize($serializer, $data);
		$serializer->process($this, "name", $data);
		$serializer->process($this, "pattern", $data);
		$serializer->process($this, "content", $data);
	}
	
	
	/* ========= Class init functions ========= */
	function _init()
	{
		parent::_init();
		$this->op = "op_try_catch_item";
		$this->name = null;
		$this->pattern = null;
		$this->content = null;
	}
	static function getClassName(){ return "BayLang.OpCodes.OpTryCatchItem"; }
	static function getMethodsList(){ return null; }
	static function getMethodInfoByName($field_name){ return null; }
}