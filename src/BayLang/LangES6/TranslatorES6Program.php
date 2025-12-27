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
namespace BayLang\LangES6;

use Runtime\lib;
use Runtime\BaseObject;
use BayLang\SaveOpCode;
use BayLang\Exceptions\DeclaredClass;
use BayLang\LangES6\TranslatorES6;
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


class TranslatorES6Program extends \Runtime\BaseObject
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
		$arr = \Runtime\rs::split(".", $op_code->name);
		for ($i = 0; $i < $arr->count(); $i++)
		{
			$name = \Runtime\rs::join(".", $arr->slice(0, $i + 1));
			$result->push("if (typeof " . $name . " == 'undefined') " . $name . " = {};");
			if ($i < $arr->count() - 1) $result->push($this->translator->newLine());
		}
	}
	
	
	/**
	 * OpUse
	 */
	function OpUse($op_code, $result)
	{
		$this->translator->uses->set($op_code->alias, $op_code->name);
		return false;
	}
	
	
	/**
	 * OpAnnotation
	 */
	function OpAnnotation($annotations, $result)
	{
		$result->push("return new Vector(");
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
			if ($op_code->isStatic()) $result->push("static ");
			if ($op_code_item->value instanceof \BayLang\OpCodes\OpIdentifier)
			{
				$this->translator->expression->OpIdentifier($op_code_item->value, $result);
			}
			else
			{
				$last_result = false;
				continue;
			}
			if ($op_code_item->expression && $is_expression)
			{
				$result->push(" = ");
				$this->translator->expression->translate($op_code_item->expression, $result);
			}
			$result->push(";");
			$last_result = true;
		}
		return true;
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
					$this->translator->html->OpDeclareComponentDataItems($op_code->content, $result);
				}
				else if ($current_block == \BayLang\OpCodes\OpPreprocessorIfDef::KIND_COMPONENT_BODY)
				{
					return $this->translator->html->translateComponentBodyItem($op_code->content, $result, false);
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
				$result->push($op_code_item->name);
				if ($i < $args_count - 1) $result->push(", ");
			}
		}
	}
	
	
	/**
	 * OpDeclareFunctionInitArgs
	 */
	function OpDeclareFunctionInitArgs($op_code, $result, $is_multiline = true)
	{
		if ($op_code->args && $op_code->args->count() > 0)
		{
			$args_count = $op_code->args->count();
			for ($i = 0; $i < $args_count; $i++)
			{
				$op_code_item = $op_code->args->get($i);
				if ($op_code_item->expression != null)
				{
					if ($is_multiline)
					{
						$result->push($this->translator->newLine());
					}
					$result->push("if (" . $op_code_item->name . " == undefined) ");
					$result->push($op_code_item->name . " = ");
					$this->translator->expression->translate($op_code_item->expression, $result);
					$result->push(";");
				}
			}
		}
	}
	
	
	/**
	 * OpDeclareFunction
	 */
	function OpDeclareFunction($op_code, $result)
	{
		/*if (not (op_code.pattern instanceof OpTypeIdentifier)) return;*/
		if ($op_code->is_html)
		{
			$this->html->OpDeclareFunction($op_code, $result);
			return;
		}
		$is_component = $this->translator->current_class->is_component;
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
		$flags = new \Runtime\Vector();
		if ($op_code->flags)
		{
			if (!$is_component && ($op_code->flags->isFlag("static") || $op_code->flags->isFlag("pure")))
			{
				$flags->push("static");
			}
			if ($op_code->flags->isFlag("async")) $flags->push("async");
		}
		/* Add flags if class is not component */
		if (!$is_component)
		{
			$result->push(\Runtime\rs::join(" ", $flags));
			if ($flags->count() > 0) $result->push(" ");
		}
		/* Function name */
		if ($old_function == null) $result->push($op_code->name);
		/* Add flags if class is component */
		if ($is_component)
		{
			if ($old_function == null)
			{
				$flags->push("function");
				$result->push(": ");
			}
			$result->push(\Runtime\rs::join(" ", $flags));
		}
		/* Arguments */
		$result->push("(");
		$this->OpDeclareFunctionArgs($op_code, $result);
		$result->push(")");
		/* Add arrow */
		if ($this->translator->is_operator_block)
		{
			$result->push(" =>");
		}
		/* Multiline */
		$is_multiline = $this->translator->allow_multiline && ($op_code->content->isMultiLine() || $op_code->content instanceof \BayLang\OpCodes\OpItems);
		if ($is_multiline)
		{
			$result->push($this->translator->newLine());
		}
		else
		{
			if ($this->translator->is_operator_block) $result->push(" ");
		}
		$result->push("{");
		if ($is_multiline)
		{
			$this->translator->levelInc();
		}
		else
		{
			$result->push(" ");
		}
		/* Save modules */
		$save_use_modules = $this->translator->setUseModules();
		/* Expression */
		$result1 = new \Runtime\Vector();
		$is_expression = !($op_code->content instanceof \BayLang\OpCodes\OpItems);
		if ($is_expression)
		{
			$this->OpDeclareFunctionInitArgs($op_code, $result1, $is_multiline);
			if ($is_multiline) $result1->push($this->translator->newLine());
			$result1->push("return ");
			$save_operator_block = $this->translator->is_operator_block;
			$this->translator->is_operator_block = true;
			$this->translator->expression->translate($op_code->content, $result1);
			$this->translator->is_operator_block = $save_operator_block;
			$result1->push(";");
		}
		else
		{
			$this->OpDeclareFunctionInitArgs($op_code, $result1);
			$this->translator->operator->translateItems($op_code->content, $result1, false);
		}
		/* Add modules */
		$this->translator->addUseModules($result, $is_multiline);
		$result->appendItems($result1);
		/* Restore */
		$this->translator->setUseModules($save_use_modules);
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
		/* Restore function */
		$this->translator->current_function = $old_function;
	}
	
	
	/**
	 * Translate class item
	 */
	function translateClassItem($op_code, $result)
	{
		if ($op_code instanceof \BayLang\OpCodes\OpAssign)
		{
			if ($op_code->isStatic())
			{
				return $this->OpAssign($op_code, $result);
			}
			return true;
		}
		else if ($op_code instanceof \BayLang\OpCodes\OpComment)
		{
			$this->OpComment($op_code, $result);
		}
		else if ($op_code instanceof \BayLang\OpCodes\OpDeclareFunction)
		{
			$this->translator->class_function = $op_code;
			if ($op_code->is_html) $this->translator->html->OpDeclareFunction($op_code, $result);
			else $this->OpDeclareFunction($op_code, $result);
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
		$is_first = true;
		for ($i = 0; $i < $op_code->content->count(); $i++)
		{
			$item_result = new \Runtime\Vector();
			$op_code_item = $op_code->content->get($i);
			if ($op_code_item instanceof \BayLang\OpCodes\OpAssign && !$op_code_item->isStatic() || $op_code_item instanceof \BayLang\OpCodes\OpAnnotation || $op_code_item instanceof \BayLang\OpCodes\OpComment)
			{
				/*prev_op_code = null;*/
				continue;
			}
			if ($next_new_line)
			{
				$lines = !$is_first ? 3 : 1;
				if ($prev_op_code instanceof \BayLang\OpCodes\OpAssign && $op_code_item instanceof \BayLang\OpCodes\OpAssign)
				{
					$lines = 1;
					$prev_op_code = null;
				}
				for ($j = 0; $j < $lines; $j++) $item_result->push($this->translator->newLine());
			}
			$prev_op_code = $op_code_item;
			$prev_next_new_line = $next_new_line;
			$next_new_line = $this->translateClassItem($op_code_item, $item_result);
			if (\Runtime\rs::trim(\Runtime\rs::join("", $item_result)) != "")
			{
				$is_first = false;
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
	 * Translate class init item
	 */
	function translateClassInitItem($op_code, $result)
	{
		if ($op_code instanceof \BayLang\OpCodes\OpAssign && !$op_code->isStatic())
		{
			for ($j = 0; $j < $op_code->items->count(); $j++)
			{
				$assign_item = $op_code->items->get($j);
				if ($assign_item->expression)
				{
					$result->push($this->translator->newLine());
					$result->push("this." . $assign_item->value->value . " = ");
					$this->translator->expression->translate($assign_item->expression, $result);
					$result->push(";");
				}
			}
		}
	}
	
	
	/**
	 * Translate class init
	 */
	function translateClassInit($op_code, $result, $newline = true)
	{
		if (!$op_code->is_component)
		{
			$result->push($this->translator->newLine(($newline && $op_code->content->count() > 0) ? 3 : 1));
			$result->push("/* ========= Class init functions ========= */");
			$result->push($this->translator->newLine());
			$result->push("_init()");
			$result->push($this->translator->newLine());
			$result->push("{");
			$this->translator->levelInc();
			if ($this->translator->parent_class_name != "")
			{
				$result->push($this->translator->newLine());
				$result->push("super._init();");
			}
			$save_use_modules = $this->translator->setUseModules();
			$result1 = new \Runtime\Vector();
			for ($i = 0; $i < $op_code->content->items->count(); $i++)
			{
				$op_code_item = $op_code->content->items->get($i);
				$this->translateClassInitItem($op_code_item, $result1);
			}
			$this->translator->addUseModules($result);
			$this->translator->setUseModules($save_use_modules);
			$result->appendItems($result1);
			$this->translator->levelDec();
			$result->push($this->translator->newLine());
			$result->push("}");
		}
		if (!$op_code->is_component)
		{
			$result->push($this->translator->newLine());
			/* Get class name */
			$result->push("static getClassName(){ ");
			$result->push("return \"" . $this->translator->current_class_name . "\"; }");
			$result->push($this->translator->newLine());
			/* Get class annotations */
			if ($op_code->annotations->count() > 0)
			{
				$result->push("static getClassInfo()");
				$result->push($this->translator->newLine());
				$result->push("{");
				$this->translator->levelInc();
				$result1 = new \Runtime\Vector();
				$save_use_modules = $this->translator->setUseModules();
				$result1->push($this->translator->newLine());
				$this->OpAnnotation($op_code->annotations, $result1);
				$this->translator->addUseModules($result);
				$result->appendItems($result1);
				$this->translator->setUseModules($save_use_modules);
				$this->translator->levelDec();
				$result->push($this->translator->newLine());
				$result->push("}");
				$result->push($this->translator->newLine());
			}
			/* Get methods with annotations */
			$methods = new \Runtime\Vector();
			$this->translator->helper->getMethodsWithAnnotations($op_code->content, $methods);
			/* Get methods list */
			$result->push("static getMethodsList()");
			if ($methods->count() > 0)
			{
				$result->push($this->translator->newLine());
				$result->push("{");
				$this->translator->levelInc();
				$result->push($this->translator->newLine());
				$result->push("const Vector = use(\"Runtime.Vector\");");
				$result->push($this->translator->newLine());
				$result->push("return new Vector(");
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
			$result->push("static getMethodInfoByName(field_name)");
			if ($methods->count() > 0)
			{
				$result->push($this->translator->newLine());
				$result->push("{");
				$this->translator->levelInc();
				$result->push($this->translator->newLine());
				$result->push("const Vector = use(\"Runtime.Vector\");");
				for ($i = 0; $i < $methods->count(); $i++)
				{
					$op_code_item = $methods->get($i);
					$method_name = $this->translator->toString($op_code_item->name);
					$result->push($this->translator->newLine());
					$result->push("if (field_name == " . $method_name . ") ");
					$this->OpAnnotation($op_code_item->annotations, $result);
				}
				$result->push($this->translator->newLine());
				$result->push("return null;");
				$this->translator->levelDec();
				$result->push($this->translator->newLine());
				$result->push("}");
			}
			else $result->push("{ return null; }");
			/* Implements */
			if ($op_code->class_implements)
			{
				$class_implements = $op_code->class_implements->map(function ($class_name)
				{
					return $this->translator->toString($this->translator->getFullName($class_name->entity_name->getName()));
				});
				$result->push($this->translator->newLine());
				/* Get interfaces */
				$result->push("static getInterfaces(){ ");
				$result->push("return [" . \Runtime\rs::join(",", $class_implements) . "]; }");
			}
		}
	}
	
	
	/**
	 * Translate class name
	 */
	function translateClassName($class_name){ return $class_name; }
	
	
	/**
	 * Translate class
	 */
	function translateClass($op_code, $result)
	{
		if ($op_code->kind == \BayLang\OpCodes\OpDeclareClass::KIND_INTERFACE) return false;
		if ($op_code->is_component) return $this->translator->html->translateComponent($op_code, $result);
		/* Class name */
		$class_name = $op_code->name->entity_name->items->last()->value;
		$this->translator->parent_class_name = "";
		$this->translator->current_class = $op_code;
		$this->translator->current_class_name = $this->translator->current_namespace_name . "." . $op_code->name->entity_name->getName();
		/* Add use */
		$this->translator->uses->set($class_name, $this->translator->current_class_name);
		$this->translator->class_items->set($class_name, $this->translator->current_class_name);
		/* Abstract class */
		if ($op_code->is_abstract)
		{
			return false;
		}
		/* Define class */
		$result->push($this->translator->current_class_name . " = class");
		/* Extends */
		if ($op_code->class_extends)
		{
			$result->push(" extends ");
			$this->translator->parent_class_name = $this->translator->getFullName($op_code->class_extends->entity_name->getName());
			$result->push($this->translateClassName($this->translator->parent_class_name));
		}
		$result->push($this->translator->newLine());
		$this->translateClassBody($op_code, $result);
		$result->push(";");
		/* Register class */
		if ($this->translator->use_module_name)
		{
			$result->push($this->translator->newLine());
			$result->push("use.add(" . $this->translator->current_class_name . ");");
		}
		if ($this->translator->use_window)
		{
			$result->push($this->translator->newLine());
			$result->push("window[\"" . $this->translator->current_class_name . "\"] = " . $this->translator->current_class_name . ";");
		}
		return true;
	}
	
	
	/**
	 * Translate item
	 */
	function translateItem($op_code, $result)
	{
		if ($op_code instanceof \BayLang\OpCodes\OpDeclareClass)
		{
			return $this->translateClass($op_code, $result);
		}
		else if ($op_code instanceof \BayLang\OpCodes\OpNamespace)
		{
			$this->OpNamespace($op_code, $result);
		}
		else if ($op_code instanceof \BayLang\OpCodes\OpUse)
		{
			return $this->OpUse($op_code, $result);
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
		$prev_op_code = null;
		for ($i = 0; $i < $items->count(); $i++)
		{
			$op_code_item = $items->get($i);
			if ($last_result)
			{
				$result->push($this->translator->newLine());
			}
			$last_result = $this->translateItem($items->get($i), $result);
			$prev_op_code = $op_code_item;
		}
	}
	
	
	/**
	 * Add exports
	 */
	function addModuleExports($result)
	{
		$result->push($this->translator->newLine());
		$result->push("module.exports = {");
		$this->translator->levelInc();
		$keys = \Runtime\rtl::list($this->translator->class_items->keys());
		for ($i = 0; $i < $keys->count(); $i++)
		{
			$class_name = $keys->get($i);
			$result->push($this->translator->newLine());
			$result->push($this->translator->toString($class_name) . ": " . $this->translator->uses->get($class_name) . ",");
		}
		$this->translator->levelDec();
		$result->push($this->translator->newLine());
		$result->push("};");
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
	static function getClassName(){ return "BayLang.LangES6.TranslatorES6Program"; }
	static function getMethodsList(){ return null; }
	static function getMethodInfoByName($field_name){ return null; }
}