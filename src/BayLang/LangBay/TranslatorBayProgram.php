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
namespace BayLang\LangBay;

use Runtime\BaseObject;
use BayLang\OpCodes\BaseOpCode;
use BayLang\OpCodes\OpAnnotation;
use BayLang\OpCodes\OpAssign;
use BayLang\OpCodes\OpComment;
use BayLang\OpCodes\OpDeclareClass;
use BayLang\OpCodes\OpDeclareFunction;
use BayLang\OpCodes\OpDeclareFunctionArg;
use BayLang\OpCodes\OpItems;
use BayLang\OpCodes\OpNamespace;
use BayLang\OpCodes\OpTypeIdentifier;
use BayLang\OpCodes\OpUse;
use BayLang\LangBay\TranslatorBay;


class TranslatorBayProgram extends \Runtime\BaseObject
{
	var $translator;
	
	
	/**
	 * Constructor
	 */
	function __construct($translator)
	{
		parent::__construct();
		$this->translator = $translator;
	}
	
	
	/**
	 * OpNamespace
	 */
	function OpNamespace($op_code, $result)
	{
		$result->push("namespace ");
		$result->push($op_code->name);
		$result->push(";");
		$result->push($this->translator->newLine());
	}
	
	
	/**
	 * OpUse
	 */
	function OpUse($op_code, $result)
	{
		$items = \Runtime\rs::split(".", $op_code->name);
		$last_name = $items->last();
		$result->push("use ");
		$result->push($op_code->name);
		if ($op_code->alias != "" && $op_code->alias != $last_name)
		{
			$result->push(" as ");
			$result->push($op_code->alias);
		}
		$result->push(";");
	}
	
	
	/**
	 * OpAnnotation
	 */
	function OpAnnotation($op_code, $result)
	{
		$result->push("@");
		$this->translator->expression->OpTypeIdentifier($op_code->name, $result);
		$this->translator->expression->OpDict($op_code->params, $result);
	}
	
	
	/**
	 * OpAssign
	 */
	function OpAssign($op_code, $result)
	{
		$this->translator->operator->OpAssign($op_code, $result);
		$result->push(";");
	}
	
	
	/**
	 * OpComment
	 */
	function OpComment($op_code, $result)
	{
		$result->push("/*");
		$result->push($op_code->value);
		$result->push("*/");
	}
	
	
	/**
	 * OpDeclareFunctionArg
	 */
	function OpDeclareFunctionArg($op_code, $result)
	{
		$this->translator->expression->OpTypeIdentifier($op_code->pattern, $result);
		$result->push(" ");
		$result->push($op_code->name);
		if ($op_code->expression)
		{
			$result->push(" = ");
			$this->translator->expression->translate($op_code->expression, $result);
		}
	}
	
	
	/**
	 * OpDeclareFunctionArgs
	 */
	function OpDeclareFunctionArgs($op_code, $result)
	{
		if ($op_code->args && $op_code->args->count() > 0)
		{
			$args_count = $op_code->args->count();
			for ($i = 0; $i < $args_count; $i++)
			{
				$op_code_item = $op_code->args->get($i);
				$this->OpDeclareFunctionArg($op_code_item, $result);
				if ($i < $args_count - 1) $result->push(", ");
			}
		}
	}
	
	
	/**
	 * OpDeclareFunction
	 */
	function OpDeclareFunction($op_code, $result)
	{
		/*if (not (op_code.pattern instanceof OpTypeIdentifier)) return;*/
		/* Comments */
		if ($op_code->comments)
		{
			for ($i = 0; $i < $op_code->comments->count(); $i++)
			{
				$op_code_item = $op_code->comments->get($i);
				$this->OpComment($op_code_item, $result);
				$result->push($this->translator->newLine());
			}
		}
		/* Function flags */
		$flags = new \Runtime\Vector("async", "static", "pure", "computed");
		$flags = $flags->filter(function ($flag_name) use (&$op_code){ return $op_code->flags ? $op_code->flags->isFlag($flag_name) : false; });
		$result->push(\Runtime\rs::join(" ", $flags));
		if ($flags->count() > 0) $result->push(" ");
		/* Function result type */
		if ($op_code->pattern) $this->translator->expression->OpTypeIdentifier($op_code->pattern, $result);
		else $result->push("void");
		/* Function name */
		$result->push(" ");
		$result->push($op_code->name);
		/* Arguments */
		$result->push("(");
		$this->OpDeclareFunctionArgs($op_code, $result);
		$result->push(")");
		/* If interface */
		if ($this->translator->current_class && $this->translator->current_class->kind == \BayLang\OpCodes\OpDeclareClass::KIND_INTERFACE)
		{
			$result->push(";");
			return;
		}
		/* Expression */
		$is_expression = !($op_code->content instanceof \BayLang\OpCodes\OpItems);
		if ($is_expression)
		{
			$is_multiline = $op_code->content->isMultiLine();
			if ($is_multiline)
			{
				$result->push(" =>");
				$result->push($this->translator->newLine());
			}
			else
			{
				$result->push(" => ");
			}
			$this->translator->expression->translate($op_code->content, $result);
		}
		else
		{
			$this->translator->operator->translateItems($op_code->content, $result);
		}
	}
	
	
	/**
	 * Translate class item
	 */
	function translateClassItem($op_code, $result)
	{
		if ($op_code instanceof \BayLang\OpCodes\OpAnnotation)
		{
			$this->OpAnnotation($op_code, $result);
		}
		else if ($op_code instanceof \BayLang\OpCodes\OpAssign)
		{
			$this->OpAssign($op_code, $result);
		}
		else if ($op_code instanceof \BayLang\OpCodes\OpComment)
		{
			$this->OpComment($op_code, $result);
		}
		else if ($op_code instanceof \BayLang\OpCodes\OpDeclareFunction)
		{
			$this->OpDeclareFunction($op_code, $result);
			if (!($op_code->content instanceof \BayLang\OpCodes\OpItems) && $this->translator->current_class->kind != \BayLang\OpCodes\OpDeclareClass::KIND_INTERFACE)
			{
				$result->push(";");
			}
		}
		else
		{
			return false;
		}
		return true;
	}
	
	
	/**
	 * Translate class body
	 */
	function translateClassBody($op_code, $result)
	{
		/* Begin bracket */
		$result->push("{");
		$this->translator->levelInc();
		/* Class body items */
		$prev_op_code = null;
		$next_new_line = true;
		for ($i = 0; $i < $op_code->content->count(); $i++)
		{
			$op_code_item = $op_code->content->get($i);
			if ($next_new_line)
			{
				$lines = 1;
				if ($prev_op_code)
				{
					$lines = $op_code_item->getOffset()->get("start") - $prev_op_code->getOffset()->get("end");
				}
				for ($j = 0; $j < $lines; $j++) $result->push($this->translator->newLine());
			}
			$next_new_line = $this->translateClassItem($op_code_item, $result);
			$prev_op_code = $op_code_item;
		}
		/* End bracket */
		$this->translator->levelDec();
		$result->push($this->translator->newLine());
		$result->push("}");
	}
	
	
	/**
	 * Translate class
	 */
	function translateClass($op_code, $result)
	{
		/* Current class */
		$this->translator->current_class = $op_code;
		/* Abstract class */
		if ($op_code->is_abstract) $result->push("abstract ");
		/* Class kind */
		if ($op_code->kind == \BayLang\OpCodes\OpDeclareClass::KIND_CLASS)
		{
			$result->push("class ");
		}
		else if ($op_code->kind == \BayLang\OpCodes\OpDeclareClass::KIND_INTERFACE)
		{
			$result->push("interface ");
		}
		/* Class name */
		$this->translator->expression->OpTypeIdentifier($op_code->name, $result);
		/* Template */
		if ($op_code->template)
		{
			$this->translator->expression->OpTypeTemplate($op_code->template, $result);
		}
		/* Extends */
		if ($op_code->class_extends)
		{
			$result->push(" extends ");
			$this->translator->expression->OpTypeIdentifier($op_code->class_extends, $result);
		}
		/* Implements */
		if ($op_code->class_implements && $op_code->class_implements->count() > 0)
		{
			$result->push(" implements ");
			$items_count = $op_code->class_implements->count();
			for ($i = 0; $i < $items_count; $i++)
			{
				$op_code_item = $op_code->class_implements->get($i);
				$this->translator->expression->OpTypeIdentifier($op_code_item, $result);
				if ($i < $items_count - 1) $result->push(", ");
			}
		}
		/* Class body */
		if (!$op_code->is_abstract)
		{
			$result->push($this->translator->newLine());
			$this->translateClassBody($op_code, $result);
		}
		else $result->push(";");
		$this->translator->current_class = null;
	}
	
	
	/**
	 * Translate item
	 */
	function translateItem($op_code, $result)
	{
		if ($op_code instanceof \BayLang\OpCodes\OpDeclareClass)
		{
			$this->translateClass($op_code, $result);
		}
		else if ($op_code instanceof \BayLang\OpCodes\OpNamespace)
		{
			$this->OpNamespace($op_code, $result);
		}
		else if ($op_code instanceof \BayLang\OpCodes\OpUse)
		{
			$this->OpUse($op_code, $result);
		}
		else
		{
			$this->translator->last_semicolon = false;
			$this->translator->operator->translateItem($op_code, $result);
			$this->translator->operator->addSemicolon($op_code, $result);
		}
	}
	
	
	/**
	 * Translate items
	 */
	function translateItems($items, $result)
	{
		$op_code_use_count = 0;
		$prev_op_code_use = false;
		for ($i = 0; $i < $items->count(); $i++)
		{
			$op_code_item = $items->get($i);
			if ($op_code_item instanceof \BayLang\OpCodes\OpDeclareClass)
			{
				if ($op_code_use_count > 0)
				{
					$result->push($this->translator->newLine($op_code_use_count > 1 ? 3 : 2));
				}
				else
				{
					$result->push($this->translator->newLine(3));
				}
			}
			else if ($i > 0) $result->push($this->translator->newLine());
			if ($op_code_item instanceof \BayLang\OpCodes\OpUse) $op_code_use_count++;
			else $op_code_use_count = 0;
			$this->translateItem($items->get($i), $result);
		}
	}
	
	
	/**
	 * Translate
	 */
	function translate($op_code, $result)
	{
		$this->translateItems($op_code->items, $result);
	}
	
	
	/* ========= Class init functions ========= */
	function _init()
	{
		parent::_init();
		$this->translator = null;
	}
	static function getClassName(){ return "BayLang.LangBay.TranslatorBayProgram"; }
	static function getMethodsList(){ return null; }
	static function getMethodInfoByName($field_name){ return null; }
}