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
use Runtime\Serializer\StringType;
use Runtime\Serializer\VectorType;
use BayLang\OpCodes\BaseOpCode;
use BayLang\OpCodes\OpAnnotation;
use BayLang\OpCodes\OpCodeType;
use BayLang\OpCodes\OpComment;
use BayLang\OpCodes\OpDeclareFunctionArg;
use BayLang\OpCodes\OpFlags;
use BayLang\OpCodes\OpItems;
use BayLang\OpCodes\OpReturn;
use BayLang\OpCodes\OpTypeIdentifier;


class OpDeclareFunction extends \BayLang\OpCodes\BaseOpCode
{
	var $op;
	var $name;
	var $args;
	var $annotations;
	var $comments;
	var $vars;
	var $pattern;
	var $content;
	var $flags;
	var $is_context;
	var $is_html;
	var $is_html_default_args;
	
	
	/**
	 * Serialize object
	 */
	static function serialize($rules)
	{
		parent::serialize($rules);
		$rules->addType("annotations", new \Runtime\Serializer\VectorType(new \Runtime\Serializer\ObjectType(new \Runtime\Map([
			"class_name" => "BayLang.OpCodes.OpAnnotation",
		]))));
		$rules->addType("args", new \Runtime\Serializer\ObjectType(new \Runtime\Map([
			"class_name" => "BayLang.OpCodes.OpItems",
			"item" => "BayLang.OpCodes.OpDeclareFunctionArg",
		])));
		$rules->addType("comments", new \Runtime\Serializer\VectorType(new \Runtime\Serializer\ObjectType(new \Runtime\Map([
			"class_name" => "BayLang.OpCodes.OpComment",
		]))));
		$rules->addType("flags", new \Runtime\Serializer\ObjectType(new \Runtime\Map([
			"class_name" => "BayLang.OpCodes.OpFlags",
		])));
		$rules->addType("is_context", new \Runtime\Serializer\BooleanType());
		$rules->addType("is_html", new \Runtime\Serializer\BooleanType());
		$rules->addType("is_html_default_args", new \Runtime\Serializer\BooleanType());
		$rules->addType("name", new \Runtime\Serializer\StringType());
		$rules->addType("content", new \BayLang\OpCodes\OpCodeType());
		$rules->addType("pattern", new \Runtime\Serializer\ObjectType(new \Runtime\Map([
			"class_name" => "BayLang.OpCodes.OpTypeIdentifier",
		])));
		$rules->addType("vars", new \Runtime\Serializer\VectorType(new \Runtime\Serializer\StringType()));
	}
	
	
	/**
	 * Returns true if static function
	 */
	function isStatic()
	{
		return $this->flags != null && ($this->flags->isFlag("static") || $this->flags->isFlag("lambda") || $this->flags->isFlag("pure"));
	}
	
	
	
	/**
	 * Returns true if is flag
	 */
	function isFlag($flag_name){ return $this->flags != null && $this->flags->isFlag($flag_name); }
	
	
	
	/**
	 * Returns offset
	 */
	function getOffset()
	{
		$res = parent::getOffset();
		$op_comment = $this->comments ? $this->comments->first() : null;
		if ($op_comment)
		{
			$res->set("start", $op_comment->caret_start->y);
		}
		return $res;
	}
	
	
	/**
	 * Returns function expression
	 */
	function getExpression()
	{
		if ($this->expression != null)
		{
			return $this->expression;
		}
		if (!($this->items instanceof \BayLang\OpCodes\OpItems)) return null;
		$op_code_item = $this->items->items->get(0);
		if (!($op_code_item instanceof \BayLang\OpCodes\OpReturn)) return null;
		return $op_code_item->expression;
	}
	
	
	/* ========= Class init functions ========= */
	function _init()
	{
		parent::_init();
		$this->op = "op_function";
		$this->name = "";
		$this->args = null;
		$this->annotations = null;
		$this->comments = null;
		$this->vars = null;
		$this->pattern = null;
		$this->content = null;
		$this->flags = null;
		$this->is_context = true;
		$this->is_html = false;
		$this->is_html_default_args = false;
	}
	static function getClassName(){ return "BayLang.OpCodes.OpDeclareFunction"; }
	static function getMethodsList(){ return null; }
	static function getMethodInfoByName($field_name){ return null; }
}