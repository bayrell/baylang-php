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
use BayLang\OpCodes\OpAssign;
use BayLang\OpCodes\OpAssignValue;
use BayLang\OpCodes\OpCodeType;
use BayLang\OpCodes\OpComment;
use BayLang\OpCodes\OpDeclareFunction;
use BayLang\OpCodes\OpFlags;
use BayLang\OpCodes\OpTypeIdentifier;


class OpDeclareClass extends \BayLang\OpCodes\BaseOpCode
{
	const KIND_CLASS = "class";
	const KIND_STRUCT = "struct";
	const KIND_INTERFACE = "interface";
	
	var $op;
	var $kind;
	var $annotations;
	var $comments;
	var $template;
	var $flags;
	var $fn_create;
	var $fn_destroy;
	var $name;
	var $class_extends;
	var $class_implements;
	var $content;
	var $is_abstract;
	var $is_static;
	var $is_declare;
	var $is_component;
	var $is_model;
	
	
	/**
	 * Serialize object
	 */
	static function serialize($rules)
	{
		parent::serialize($rules);
		$rules->addType("annotations", new \Runtime\Serializer\VectorType(new \Runtime\Serializer\ObjectType(new \Runtime\Map([
			"class_name" => "BayLang.OpCodes.OpAnnotation",
		]))));
		$rules->addType("class_extends", new \Runtime\Serializer\ObjectType(new \Runtime\Map([
			"class_name" => "BayLang.OpCodes.OpTypeIdentifier",
		])));
		$rules->addType("class_implements", new \Runtime\Serializer\VectorType(new \Runtime\Serializer\ObjectType(new \Runtime\Map([
			"class_name" => "BayLang.OpCodes.OpTypeIdentifier",
		]))));
		$rules->addType("comments", new \Runtime\Serializer\ObjectType(new \Runtime\Map([
			"class_name" => "BayLang.OpCodes.OpComment",
		])));
		$rules->addType("flags", new \Runtime\Serializer\ObjectType(new \Runtime\Map([
			"class_name" => "BayLang.OpCodes.OpFlags",
		])));
		$rules->addType("fn_create", new \Runtime\Serializer\ObjectType(new \Runtime\Map([
			"class_name" => "BayLang.OpCodes.OpDeclareFunction",
		])));
		$rules->addType("fn_destroy", new \Runtime\Serializer\ObjectType(new \Runtime\Map([
			"class_name" => "BayLang.OpCodes.OpDeclareFunction",
		])));
		$rules->addType("is_abstract", new \Runtime\Serializer\BooleanType());
		$rules->addType("is_component", new \Runtime\Serializer\BooleanType());
		$rules->addType("is_declare", new \Runtime\Serializer\BooleanType());
		$rules->addType("is_model", new \Runtime\Serializer\BooleanType());
		$rules->addType("content", new \BayLang\OpCodes\OpCodeType());
		$rules->addType("kind", new \Runtime\Serializer\StringType());
		$rules->addType("name", new \Runtime\Serializer\ObjectType(new \Runtime\Map([
			"class_name" => "BayLang.OpCodes.OpTypeIdentifier",
		])));
		$rules->addType("template", new \Runtime\Serializer\VectorType(new \Runtime\Serializer\ObjectType(new \Runtime\Map([
			"class_name" => "BayLang.OpCodes.OpTypeIdentifier",
		]))));
	}
	
	
	/**
	 * Returns offset
	 */
	function getOffset()
	{
		$res = parent::getOffset();
		$op_comment = $this->comments->first();
		if ($op_comment)
		{
			$res->set("start", $op_comment->caret_start->y);
		}
		return $res;
	}
	
	
	/**
	 * Find function
	 */
	function findFunction($name)
	{
		return $this->content ? $this->content->items->find(function ($op_code) use (&$name){ return $op_code instanceof \BayLang\OpCodes\OpDeclareFunction && $op_code->name == $name; }) : null;
	}
	
	
	
	/**
	 * Find variable
	 */
	function findVariable($name)
	{
		for ($i = 0; $i < $this->content->count(); $i++)
		{
			$item = $this->content->get($i);
			if (!($item instanceof \BayLang\OpCodes\OpAssign)) continue;
			$value = $item->findVariable($name);
			if ($value) return $value;
		}
		return null;
	}
	
	
	/* ========= Class init functions ========= */
	function _init()
	{
		parent::_init();
		$this->op = "op_class";
		$this->kind = "";
		$this->annotations = null;
		$this->comments = null;
		$this->template = null;
		$this->flags = null;
		$this->fn_create = null;
		$this->fn_destroy = null;
		$this->name = null;
		$this->class_extends = null;
		$this->class_implements = null;
		$this->content = null;
		$this->is_abstract = false;
		$this->is_static = false;
		$this->is_declare = false;
		$this->is_component = false;
		$this->is_model = false;
	}
	static function getClassName(){ return "BayLang.OpCodes.OpDeclareClass"; }
	static function getMethodsList(){ return null; }
	static function getMethodInfoByName($field_name){ return null; }
}