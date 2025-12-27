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
namespace BayLang\LangPHP;

use Runtime\re;
use Runtime\lib;
use Runtime\BaseObject;
use BayLang\SaveOpCode;
use BayLang\Exceptions\DeclaredClass;
use BayLang\LangPHP\TranslatorPHP;
use BayLang\OpCodes\BaseOpCode;
use BayLang\OpCodes\OpAnnotation;
use BayLang\OpCodes\OpAssign;
use BayLang\OpCodes\OpAssignValue;
use BayLang\OpCodes\OpComment;
use BayLang\OpCodes\OpDeclareClass;
use BayLang\OpCodes\OpDeclareFunction;
use BayLang\OpCodes\OpDeclareFunctionArg;
use BayLang\OpCodes\OpIdentifier;
use BayLang\OpCodes\OpItems;
use BayLang\OpCodes\OpModule;
use BayLang\OpCodes\OpNamespace;
use BayLang\OpCodes\OpPreprocessorIfCode;
use BayLang\OpCodes\OpPreprocessorIfDef;
use BayLang\OpCodes\OpPreprocessorSwitch;
use BayLang\OpCodes\OpTypeIdentifier;
use BayLang\OpCodes\OpUse;


class TranslatorPHPProgram extends \Runtime\BaseObject
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
		$this->translator->current_namespace_name = $op_code->name;
		$result->push("namespace ");
		$result->push($this->translator->getModuleName($op_code->name));
		$result->push(";");
	}
	
	
	/**
	 * OpUse
	 */
	function OpUse($op_code, $result)
	{
		$this->translator->uses->set($op_code->alias, $op_code->name);
		$items = \Runtime\rs::split(".", $op_code->name);
		$last_name = $items->last();
		$result->push("use ");
		$result->push($this->translator->getModuleName($op_code->name));
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
	function OpAnnotation($annotations, $result)
	{
		$result->push("return new \\Runtime\\Vector(");
		$this->translator->levelInc();
		for ($j = 0; $j < $annotations->count(); $j++)
		{
			$annotation = $annotations->get($j);
			$result->push($this->translator->newLine());
			$result->push("new ");
			$this->translator->expression->OpTypeIdentifier($annotation->name, $result);
			$result->push("(");
			if ($annotation->params != null)
			{
				$this->translator->expression->OpDict($annotation->params, $result);
			}
			$result->push(")");
			if ($j < $annotations->count() - 1)
			{
				$result->push(",");
				$result->push($this->translator->newLine());
			}
		}
		$this->translator->levelDec();
		$result->push($this->translator->newLine());
		$result->push(");");
	}
	
	
	/**
	 * OpAssign
	 */
	function OpAssign($op_code, $result, $is_expression = true)
	{
		$last_result = false;
		for ($i = 0; $i < $op_code->items->count(); $i++)
		{
			$op_code_item = $op_code->items->get($i);
			if ($last_result)
			{
				$result->push($this->translator->newLine());
			}
			if ($op_code_item->value instanceof \BayLang\OpCodes\OpIdentifier)
			{
				if ($op_code->flags->isFlag("const"))
				{
					$result->push("const ");
				}
				else if ($op_code->flags->isFlag("static"))
				{
					$result->push("static ");
				}
				else
				{
					$result->push("var ");
				}
				$this->translator->expression->OpIdentifier($op_code_item->value, $result, $op_code->flags->isFlag("const"));
			}
			else
			{
				$last_result = false;
				continue;
			}
			if ($op_code_item->expression && ($is_expression || $op_code->isStatic()))
			{
				$result->push(" = ");
				$this->translator->expression->translate($op_code_item->expression, $result);
			}
			$result->push(";");
			$last_result = true;
		}
	}
	
	
	/**
	 * OpComment
	 */
	function OpComment($op_code, $result)
	{
		$lines = \Runtime\rs::split("\n", $op_code->value);
		if ($lines->count() == 1)
		{
			$result->push("/*");
			$result->push($op_code->value);
			$result->push("*/");
			return;
		}
		$first_line = \Runtime\rs::trim($lines->get(0));
		$is_comment_function = $first_line == "*";
		if ($first_line == "" || $first_line == "*") $lines = $lines->slice(1);
		if (\Runtime\rs::trim($lines->get($lines->count() - 1)) == "" && $lines->count() > 1)
		{
			$lines = $lines->slice(0, $lines->count() - 1);
		}
		if ($is_comment_function) $result->push("/**");
		else $result->push("/*");
		for ($i = 0; $i < $lines->count(); $i++)
		{
			$line = $lines->get($i);
			$start = 0;
			$len = \Runtime\rs::strlen($line);
			while ($start < $len && (\Runtime\rs::charAt($line, $start) == " " || \Runtime\rs::charAt($line, $start) == "\t")) $start++;
			if ($start < $len && \Runtime\rs::charAt($line, $start) == "*") $start++;
			$result->push($this->translator->newLine());
			if ($is_comment_function) $result->push(" *");
			$result->push(\Runtime\rs::substr($line, $start));
		}
		$result->push($this->translator->newLine());
		if ($is_comment_function) $result->push(" */");
		else $result->push("*/");
	}
	
	
	/**
	 * OpPreprocessorIfCode
	 */
	function OpPreprocessorIfCode($op_code, $result)
	{
		if ($op_code->condition instanceof \BayLang\OpCodes\OpIdentifier)
		{
			$name = $op_code->condition->value;
			if ($this->translator->preprocessor_flags->get($name))
			{
				$result->push($op_code->content);
				return true;
			}
		}
		return false;
	}
	
	
	/**
	 * OpPreprocessorIfDef
	 */
	function OpPreprocessorIfDef($op_code, $result, $current_block = "")
	{
		if ($op_code->condition instanceof \BayLang\OpCodes\OpIdentifier)
		{
			$name = $op_code->condition->value;
			if ($this->translator->preprocessor_flags->get($name))
			{
				if ($current_block == \BayLang\OpCodes\OpPreprocessorIfDef::KIND_PROGRAM)
				{
					$this->translate($op_code->content, $result);
				}
				else if ($current_block == \BayLang\OpCodes\OpPreprocessorIfDef::KIND_CLASS_BODY)
				{
					$this->translateClassBody($op_code, $result, false);
				}
				else if ($current_block == \BayLang\OpCodes\OpPreprocessorIfDef::KIND_CLASS_INIT)
				{
					$this->translateClassInitItems($op_code->content, $result);
				}
				else if ($current_block == \BayLang\OpCodes\OpPreprocessorIfDef::KIND_OPERATOR)
				{
					$this->translator->operator->translateItems($op_code->content, $result, false);
				}
				else if ($current_block == \BayLang\OpCodes\OpPreprocessorIfDef::KIND_COLLECTION)
				{
					for ($i = 0; $i < $op_code->content->items->count(); $i++)
					{
						$op_code_item = $op_code->content->items->get($i);
						if ($i > 0) $result->push($this->translator->newLine());
						$this->translator->expression->translate($op_code_item, $result);
						if ($i < $op_code->content->items->count() - 1) $result->push(",");
					}
				}
				else if ($current_block == \BayLang\OpCodes\OpPreprocessorIfDef::KIND_EXPRESSION)
				{
					$this->translator->expression->translate($op_code->content, $result);
				}
				return true;
			}
		}
		return false;
	}
	
	
	/**
	 * OpPreprocessorSwitch
	 */
	function OpPreprocessorSwitch($op_code, $result, $current_block = "")
	{
		$last_result = false;
		for ($i = 0; $i < $op_code->items->count(); $i++)
		{
			$item = $op_code->items->get($i);
			if ($item instanceof \BayLang\OpCodes\OpPreprocessorIfCode)
			{
				$result1 = new \Runtime\Vector();
				if ($this->OpPreprocessorIfCode($item, $result1))
				{
					$result->push($this->translator->newLine());
					$result->appendItems($result1);
					$last_result = true;
				}
			}
			else if ($item instanceof \BayLang\OpCodes\OpPreprocessorIfDef)
			{
				if ($this->OpPreprocessorIfDef($item, $result, $current_block))
				{
					$last_result = true;
				}
			}
		}
		return $last_result;
	}
	
	
	/**
	 * OpDeclareFunctionArg
	 */
	function OpDeclareFunctionArg($op_code, $result)
	{
		$result->push("\$" . $op_code->name);
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
	function OpDeclareFunction($op_code, $result, $use_name = true)
	{
		/*if (not (op_code.pattern instanceof OpTypeIdentifier)) return;*/
		/* Setup current function */
		$old_function = $this->translator->current_function;
		$this->translator->current_function = $op_code;
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
		if ($op_code->flags && ($op_code->flags->isFlag("static") || $op_code->flags->isFlag("pure")))
		{
			$result->push("static ");
		}
		/* Function name */
		$result->push("function ");
		if ($op_code->name == "constructor") $result->push("__construct");
		else if ($use_name) $result->push($op_code->name);
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
		/* Variables */
		if ($op_code->vars && $op_code->vars->count() > 0)
		{
			$result->push(" use (");
			$vars = $op_code->vars->map(function ($item){ return "&\$" . $item->value; });
			$result->push(\Runtime\rs::join(", ", $vars));
			$result->push(")");
		}
		/* If content is empty */
		if ($op_code->content == null)
		{
			$result->push("{");
			$result->push("}");
			return;
		}
		/* Expression */
		$is_expression = !($op_code->content instanceof \BayLang\OpCodes\OpItems);
		if ($is_expression)
		{
			$is_multiline = $op_code->content->isMultiLine();
			if ($is_multiline)
			{
				$result->push($this->translator->newLine());
			}
			$result->push("{");
			if ($is_multiline)
			{
				$this->translator->levelInc();
				$result->push($this->translator->newLine());
			}
			else
			{
				$result->push(" ");
			}
			$result->push("return ");
			$this->translator->expression->translate($op_code->content, $result);
			$result->push(";");
			if ($is_multiline)
			{
				$this->translator->levelDec();
				$result->push($this->translator->newLine());
			}
			else
			{
				$result->push(" ");
			}
			$result->push("}");
		}
		else
		{
			if ($op_code->is_html)
			{
				$this->translator->html->translateItems($op_code->content, $result);
			}
			else
			{
				$this->translator->operator->translateItems($op_code->content, $result);
			}
		}
	}
	
	
	/**
	 * Translate class item
	 */
	function translateClassItem($op_code, $result)
	{
		if ($op_code instanceof \BayLang\OpCodes\OpAssign)
		{
			$this->OpAssign($op_code, $result, false);
		}
		else if ($op_code instanceof \BayLang\OpCodes\OpComment)
		{
			$this->OpComment($op_code, $result);
		}
		else if ($op_code instanceof \BayLang\OpCodes\OpDeclareFunction)
		{
			$this->translator->class_function = $op_code;
			$this->OpDeclareFunction($op_code, $result);
			$this->translator->class_function = null;
		}
		else if ($op_code instanceof \BayLang\OpCodes\OpPreprocessorIfCode)
		{
			return $this->OpPreprocessorIfCode($op_code, $result);
		}
		else if ($op_code instanceof \BayLang\OpCodes\OpPreprocessorIfDef)
		{
			return $this->OpPreprocessorIfDef($op_code, $result, \BayLang\OpCodes\OpPreprocessorIfDef::KIND_CLASS_BODY);
		}
		else if ($op_code instanceof \BayLang\OpCodes\OpPreprocessorSwitch)
		{
			return $this->OpPreprocessorSwitch($op_code, $result, \BayLang\OpCodes\OpPreprocessorIfDef::KIND_CLASS_BODY);
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
	function translateClassBody($op_code, $result, $match_brackets = true)
	{
		/* Begin bracket */
		if ($match_brackets)
		{
			$result->push("{");
			$this->translator->levelInc();
		}
		/* Class body items */
		$prev_op_code = null;
		$result_count = $result->count();
		$next_new_line = true;
		$prev_next_new_line = true;
		for ($i = 0; $i < $op_code->content->count(); $i++)
		{
			$item_result = new \Runtime\Vector();
			$op_code_item = $op_code->content->get($i);
			if ($next_new_line && !($op_code_item instanceof \BayLang\OpCodes\OpPreprocessorIfDef || $op_code_item instanceof \BayLang\OpCodes\OpPreprocessorSwitch))
			{
				$lines = 1;
				if ($prev_op_code && !$this->translator->current_class->is_component)
				{
					$lines = $op_code_item->getOffset()->get("start") - $prev_op_code->getOffset()->get("end");
				}
				for ($j = 0; $j < $lines; $j++) $item_result->push($this->translator->newLine());
			}
			$prev_next_new_line = $next_new_line;
			$next_new_line = $this->translateClassItem($op_code_item, $item_result);
			if (\Runtime\rs::trim(\Runtime\rs::join("", $item_result)) != "")
			{
				$prev_op_code = $op_code_item;
				$result->appendItems($item_result);
			}
			else $next_new_line = $prev_next_new_line;
		}
		/* Class init */
		if ($op_code instanceof \BayLang\OpCodes\OpDeclareClass)
		{
			$this->translateClassInit($op_code, $result, $result_count != $result->count());
		}
		/* End bracket */
		if ($match_brackets)
		{
			$this->translator->levelDec();
			$result->push($this->translator->newLine());
			$result->push("}");
		}
	}
	
	
	/**
	 * Returns class count items
	 */
	static function getClassCountItems($op_code)
	{
		$count = 0;
		for ($i = 0; $i < $op_code->content->count(); $i++)
		{
			$op_code_item = $op_code->content->get($i);
			if ($op_code_item instanceof \BayLang\OpCodes\OpDeclareFunction) $count++;
		}
		return $count;
	}
	
	
	/**
	 * Translate class init items
	 */
	function translateClassInitItems($op_code, $result)
	{
		for ($i = 0; $i < $op_code->count(); $i++)
		{
			$op_code_item = $op_code->get($i);
			if ($op_code_item instanceof \BayLang\OpCodes\OpAssign && !$op_code_item->isStatic())
			{
				for ($j = 0; $j < $op_code_item->items->count(); $j++)
				{
					$assign_item = $op_code_item->items->get($j);
					if ($assign_item->expression)
					{
						$result->push($this->translator->newLine());
						$result->push("\$this->" . $assign_item->value->value . " = ");
						$this->translator->expression->translate($assign_item->expression, $result);
						$result->push(";");
					}
				}
			}
			else if ($op_code_item instanceof \BayLang\OpCodes\OpPreprocessorIfDef)
			{
				return $this->OpPreprocessorIfDef($op_code_item, $result, \BayLang\OpCodes\OpPreprocessorIfDef::KIND_CLASS_INIT);
			}
			else if ($op_code_item instanceof \BayLang\OpCodes\OpPreprocessorSwitch)
			{
				return $this->OpPreprocessorSwitch($op_code_item, $result, \BayLang\OpCodes\OpPreprocessorIfDef::KIND_CLASS_INIT);
			}
		}
	}
	
	
	/**
	 * Translate class init
	 */
	function translateClassInit($op_code, $result, $newline = true)
	{
		if ($op_code->kind == \BayLang\OpCodes\OpDeclareClass::KIND_INTERFACE) return;
		$lines = ($newline && $op_code->content->count() > 0) ? 3 : 1;
		if ($this->translator->current_class->is_component && $lines == 3) $lines = 2;
		$result->push($this->translator->newLine($lines));
		$result->push("/* ========= Class init functions ========= */");
		if ($this->translator->current_class_name != "Runtime.BaseObject")
		{
			$result->push($this->translator->newLine());
			$result->push("function _init()");
			$result->push($this->translator->newLine());
			$result->push("{");
			$this->translator->levelInc();
			if ($this->translator->parent_class_name != "")
			{
				$result->push($this->translator->newLine());
				$result->push("parent::_init();");
			}
			$this->translateClassInitItems($op_code->content, $result);
			$this->translator->levelDec();
			$result->push($this->translator->newLine());
			$result->push("}");
		}
		if ($op_code->is_component)
		{
			$this->translator->html->translateComponentStyle($op_code, $result);
			$this->translator->html->translateModuleComponents($op_code, $result);
		}
		$result->push($this->translator->newLine());
		$result->push("static function getClassName(){ " . "return \"" . $this->translator->current_class_name . "\"; }");
		if (!$op_code->is_component)
		{
			/* Get class annotations */
			if ($op_code->annotations->count() > 0)
			{
				$result->push($this->translator->newLine());
				$result->push("static function getClassInfo()");
				$result->push($this->translator->newLine());
				$result->push("{");
				$this->translator->levelInc();
				$result->push($this->translator->newLine());
				$this->OpAnnotation($op_code->annotations, $result);
				$this->translator->levelDec();
				$result->push($this->translator->newLine());
				$result->push("}");
			}
			/* Get methods with annotations */
			$methods = new \Runtime\Vector();
			$this->translator->helper->getMethodsWithAnnotations($op_code->content, $methods);
			/* Get methods list */
			$result->push($this->translator->newLine());
			$result->push("static function getMethodsList()");
			if ($methods->count() > 0)
			{
				$result->push($this->translator->newLine());
				$result->push("{");
				$this->translator->levelInc();
				$result->push($this->translator->newLine());
				$result->push("return new \\Runtime\\Vector(");
				for ($i = 0; $i < $methods->count(); $i++)
				{
					$op_code_item = $methods->get($i);
					$result->push($this->translator->toString($op_code_item->name));
					if ($i < $methods->count() - 1) $result->push(", ");
				}
				$result->push(");");
				$this->translator->levelDec();
				$result->push($this->translator->newLine());
				$result->push("}");
			}
			else $result->push("{ return null; }");
			$result->push($this->translator->newLine());
			/* Get method info by name */
			$result->push("static function getMethodInfoByName(\$field_name)");
			if ($methods->count() > 0)
			{
				$result->push($this->translator->newLine());
				$result->push("{");
				$this->translator->levelInc();
				for ($i = 0; $i < $methods->count(); $i++)
				{
					$op_code_item = $methods->get($i);
					$method_name = $this->translator->toString($op_code_item->name);
					$result->push($this->translator->newLine());
					$result->push("if (\$field_name == " . $method_name . ") ");
					$this->OpAnnotation($op_code_item->annotations, $result);
				}
				$result->push($this->translator->newLine());
				$result->push("return null;");
				$this->translator->levelDec();
				$result->push($this->translator->newLine());
				$result->push("}");
			}
			else $result->push("{ return null; }");
		}
	}
	
	
	/**
	 * Translate class
	 */
	function translateClass($op_code, $result)
	{
		/* Current class */
		$this->translator->current_class = $op_code;
		/* Class name */
		$class_name = $op_code->name->entity_name->items->last()->value;
		$this->translator->current_class_name = $this->translator->current_namespace_name . "." . $op_code->name->entity_name->getName();
		$this->translator->uses->set($class_name, $this->translator->current_class_name);
		/* Abstract class */
		if ($op_code->is_abstract) return false;
		/* Class kind */
		if ($op_code->kind == \BayLang\OpCodes\OpDeclareClass::KIND_CLASS)
		{
			$result->push("class ");
		}
		else if ($op_code->kind == \BayLang\OpCodes\OpDeclareClass::KIND_INTERFACE)
		{
			$result->push("interface ");
		}
		$result->push($class_name);
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
			$this->translator->parent_class_name = $this->translator->getFullName($op_code->class_extends->entity_name->getName());
		}
		else if ($op_code->is_component)
		{
			if ($this->translator->current_class_name == "Runtime.Component")
			{
				$this->translator->parent_class_name = "Runtime.BaseObject";
				$result->push(" extends \\Runtime\\BaseObject");
			}
			else
			{
				$this->translator->parent_class_name = "Runtime.Component";
				$result->push(" extends \\Runtime\\Component");
			}
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
		$result->push($this->translator->newLine());
		$this->translateClassBody($op_code, $result);
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
		else if ($op_code instanceof \BayLang\OpCodes\OpPreprocessorIfCode)
		{
			return $this->OpPreprocessorIfCode($op_code, $result);
		}
		else if ($op_code instanceof \BayLang\OpCodes\OpPreprocessorIfDef)
		{
			return $this->OpPreprocessorIfDef($op_code, $result, \BayLang\OpCodes\OpPreprocessorIfDef::KIND_PROGRAM);
		}
		else if ($op_code instanceof \BayLang\OpCodes\OpPreprocessorSwitch)
		{
			return $this->OpPreprocessorSwitch($op_code, $result, \BayLang\OpCodes\OpPreprocessorIfDef::KIND_PROGRAM);
		}
		else
		{
			$this->translator->last_semicolon = false;
			$res = $this->translator->operator->translateItem($op_code, $result);
			$this->translator->operator->addSemicolon($op_code, $result);
			return $res;
		}
		return true;
	}
	
	
	/**
	 * Translate items
	 */
	function translateItems($items, $result)
	{
		$op_code_use_count = 0;
		$prev_op_code_use = false;
		$last_result = false;
		for ($i = 0; $i < $items->count(); $i++)
		{
			$op_code_item = $items->get($i);
			if ($op_code_item instanceof \BayLang\OpCodes\OpDeclareClass)
			{
				if (!$op_code_item->is_abstract)
				{
					if ($op_code_use_count > 0)
					{
						$result->push($this->translator->newLine($op_code_use_count > 1 ? 3 : 2));
					}
					else
					{
						$result->push($this->translator->newLine($last_result ? 3 : 2));
					}
				}
			}
			else if ($last_result) $result->push($this->translator->newLine());
			if ($op_code_item instanceof \BayLang\OpCodes\OpUse)
			{
				if ($op_code_use_count == 0) $result->push($this->translator->newLine());
				$op_code_use_count++;
			}
			else $op_code_use_count = 0;
			$last_result = $this->translateItem($items->get($i), $result);
		}
	}
	
	
	/**
	 * Translate
	 */
	function translate($op_code, $result)
	{
		$this->translator->current_module = $op_code;
		$this->translateItems($op_code->items, $result);
	}
	
	
	/* ========= Class init functions ========= */
	function _init()
	{
		parent::_init();
		$this->translator = null;
	}
	static function getClassName(){ return "BayLang.LangPHP.TranslatorPHPProgram"; }
	static function getMethodsList(){ return null; }
	static function getMethodInfoByName($field_name){ return null; }
}