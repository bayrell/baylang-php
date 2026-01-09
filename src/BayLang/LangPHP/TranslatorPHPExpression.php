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
use Runtime\BaseObject;
use BayLang\SaveOpCode;
use BayLang\LangPHP\TranslatorPHP;
use BayLang\OpCodes\BaseOpCode;
use BayLang\OpCodes\OpAttr;
use BayLang\OpCodes\OpAwait;
use BayLang\OpCodes\OpCall;
use BayLang\OpCodes\OpClassOf;
use BayLang\OpCodes\OpCollection;
use BayLang\OpCodes\OpDeclareFunction;
use BayLang\OpCodes\OpDict;
use BayLang\OpCodes\OpDictPair;
use BayLang\OpCodes\OpIdentifier;
use BayLang\OpCodes\OpMath;
use BayLang\OpCodes\OpMethod;
use BayLang\OpCodes\OpNew;
use BayLang\OpCodes\OpNumber;
use BayLang\OpCodes\OpPreprocessorIfCode;
use BayLang\OpCodes\OpPreprocessorIfDef;
use BayLang\OpCodes\OpPreprocessorSwitch;
use BayLang\OpCodes\OpString;
use BayLang\OpCodes\OpTernary;
use BayLang\OpCodes\OpTypeIdentifier;


class TranslatorPHPExpression extends \Runtime\BaseObject
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
	 * OpIdentifier
	 */
	function OpIdentifier($op_code, $result, $is_const = false)
	{
		$variables = new \Runtime\Vector("null", "static", "true", "false");
		if ($op_code->value == "print") $result->push("echo");
		else if ($op_code->value == "var_dump") $result->push("var_dump");
		else if ($variables->indexOf($op_code->value) >= 0) $result->push($op_code->value);
		else if ($op_code->value == "this")
		{
			if ($this->translator->class_function && $this->translator->class_function->isStatic())
			{
				$result->push("static");
			}
			else
			{
				$result->push("\$this");
			}
		}
		else if ($op_code->value == "parent")
		{
			$result->push("parent");
		}
		else if ($op_code->value == "@")
		{
			$result->push("\\Runtime\\rtl::getContext()");
		}
		else
		{
			if ($this->translator->uses->has($op_code->value))
			{
				$class_name = $this->translator->uses->get($op_code->value);
				if ($class_name == $this->translator->current_class_name) $result->push("static");
				else $result->push("\\" . $this->translator->getModuleName($class_name));
			}
			else $result->push(!$is_const ? "\$" . $op_code->value : $op_code->value);
		}
		$this->translator->opcode_level = 20;
	}
	
	
	/**
	 * OpNumber
	 */
	function OpNumber($op_code, $result)
	{
		$result->push($op_code->value);
		$this->translator->opcode_level = 20;
	}
	
	
	/**
	 * OpString
	 */
	function OpString($op_code, $result)
	{
		$result->push($this->translator->toString($op_code->value));
		$this->translator->opcode_level = 20;
	}
	
	
	/**
	 * OpCode generics
	 */
	function OpCodeGenerics($items, $result)
	{
		if (!$items) return;
		/* Get items count */
		$items_count = $items->count();
		if ($items_count == 0) return;
		/* Output generics */
		$result->push("<");
		for ($i = 0; $i < $items_count; $i++)
		{
			$op_code_item = $items->get($i);
			if ($op_code_item instanceof \BayLang\OpCodes\OpIdentifier)
			{
				$this->OpIdentifier($op_code_item, $result);
			}
			else if ($op_code_item instanceof \BayLang\OpCodes\OpTypeIdentifier)
			{
				$this->OpTypeIdentifier($op_code_item, $result);
			}
			if ($i < $items_count - 1) $result->push(", ");
		}
		$result->push(">");
	}
	
	
	/**
	 * OpTypeIdentifier
	 */
	function OpTypeIdentifier($op_code, $result)
	{
		$name = $op_code->entity_name->getName();
		if ($this->translator->uses->has($name))
		{
			$result->push("\\" . $this->translator->getModuleName($this->translator->uses->get($name)));
		}
		else
		{
			if (\Runtime\rs::indexOf($name, ".") >= 0)
			{
				$result->push("\\" . $this->translator->getModuleName($name));
			}
			else
			{
				$result->push($name);
			}
		}
	}
	
	
	/**
	 * OpCollection
	 */
	function OpCollection($op_code, $result)
	{
		$is_multiline = $op_code->isMultiLine();
		$result->push("new \\Runtime\\Vector(");
		if ($is_multiline)
		{
			$this->translator->levelInc();
		}
		$i = 0;
		$items_count = $op_code->items->count();
		$last_result = true;
		while ($i < $items_count)
		{
			$op_code_item = $op_code->items->get($i);
			$result1 = new \Runtime\Vector();
			/* Preprocessor */
			$is_result = false;
			$is_preprocessor = true;
			if ($op_code_item instanceof \BayLang\OpCodes\OpPreprocessorIfCode)
			{
				$is_result = $this->translator->program->OpPreprocessorIfCode($op_code_item, $result1);
			}
			else if ($op_code_item instanceof \BayLang\OpCodes\OpPreprocessorIfDef)
			{
				$is_result = $this->translator->program->OpPreprocessorIfDef($op_code_item, $result1, \BayLang\OpCodes\OpPreprocessorIfDef::KIND_COLLECTION);
			}
			else if ($op_code_item instanceof \BayLang\OpCodes\OpPreprocessorSwitch)
			{
				$is_result = $this->translator->program->OpPreprocessorSwitch($op_code_item, $result1, \BayLang\OpCodes\OpPreprocessorSwitch::KIND_COLLECTION);
			}
			else
			{
				$is_preprocessor = false;
				$this->translate($op_code_item, $result1);
			}
			$last_result = !$is_preprocessor || $is_result;
			if ($last_result && $result1->count() > 0)
			{
				if ($is_multiline) $result->push($this->translator->newLine());
				$result->appendItems($result1);
				if ($is_multiline) $result->push(",");
				else if ($i < $items_count - 1) $result->push(", ");
			}
			$i++;
		}
		if ($is_multiline)
		{
			$this->translator->levelDec();
			$result->push($this->translator->newLine());
		}
		$result->push(")");
	}
	
	
	/**
	 * OpDict
	 */
	function OpDict($op_code, $result)
	{
		$is_multiline = $op_code->isMultiLine();
		if ($op_code->items->count() == 0 && !$is_multiline)
		{
			$result->push("new \\Runtime\\Map()");
			return;
		}
		/* Begin bracket */
		$result->push("new \\Runtime\\Map([");
		if ($is_multiline)
		{
			$this->translator->levelInc();
		}
		/* Items */
		$i = 0;
		$items_count = $op_code->items->count();
		while ($i < $items_count)
		{
			$op_code_item = $op_code->items->get($i);
			/* Preprocessor */
			if ($op_code_item->condition != null)
			{
				$name = $op_code_item->condition->value;
				if (!$this->translator->preprocessor_flags->has($name))
				{
					$i++;
					continue;
				}
			}
			/* Add new line */
			if ($is_multiline)
			{
				$result->push($this->translator->newLine());
			}
			/* Translate item */
			$result->push($this->translator->toString($op_code_item->key->value));
			$result->push(" => ");
			$this->translate($op_code_item->expression, $result);
			if ($is_multiline) $result->push(",");
			else if ($i < $items_count - 1) $result->push(", ");
			$i++;
		}
		/* End bracket */
		if ($is_multiline)
		{
			$this->translator->levelDec();
			$result->push($this->translator->newLine());
		}
		$result->push("])");
	}
	
	
	/**
	 * OpAttr
	 */
	function OpAttr($op_code, $result)
	{
		$attrs = new \Runtime\Vector();
		$op_code_first = $op_code;
		while ($op_code_first instanceof \BayLang\OpCodes\OpAttr)
		{
			$attrs->push($op_code_first);
			$op_code_first = $op_code_first->prev;
		}
		$attrs->reverse();
		/* First op_code */
		$is_bracket = $op_code_first instanceof \BayLang\OpCodes\OpNew;
		if ($is_bracket) $result->push("(");
		$this->translateItem($op_code_first, $result);
		if ($is_bracket) $result->push(")");
		/* Is static function */
		$is_static = $this->translator->class_function && $this->translator->class_function->isStatic();
		/* Attrs */
		for ($i = 0; $i < $attrs->count(); $i++)
		{
			$item_attr = $attrs->get($i);
			if ($item_attr->kind == \BayLang\OpCodes\OpAttr::KIND_ATTR)
			{
				if ($is_static && $item_attr->prev instanceof \BayLang\OpCodes\OpIdentifier && $item_attr->prev->value == "this")
				{
					$result->push("::");
				}
				else $result->push("->");
				$result->push($item_attr->next->value);
			}
			else if ($item_attr->kind == \BayLang\OpCodes\OpAttr::KIND_STATIC)
			{
				$result->push("::");
				$result->push($item_attr->next->value);
			}
			else if ($item_attr->kind == \BayLang\OpCodes\OpAttr::KIND_DYNAMIC)
			{
				$result->push("[");
				for ($j = 0; $j < $item_attr->next->items->count(); $j++)
				{
					$this->translate($item_attr->next->items->get($j), $result);
					if ($j < $item_attr->next->items->count() - 1) $result->push(", ");
				}
				$result->push("]");
			}
		}
		$this->translator->opcode_level = 20;
	}
	
	
	/**
	 * OpClassOf
	 */
	function OpClassOf($op_code, $result)
	{
		if ($op_code->entity_name->items->count() == 1)
		{
			$item = $op_code->entity_name->items->last();
			$name = $item->value;
			if ($this->translator->uses->has($name))
			{
				$result->push($this->translator->toString($this->translator->uses->get($name)));
			}
			else
			{
				$result->push($this->translator->toString($name));
			}
		}
		else
		{
			$result->push($this->translator->toString($op_code->entity_name->getName()));
		}
	}
	
	
	/**
	 * OpCall
	 */
	function OpCall($op_code, $result)
	{
		if ($op_code->item instanceof \BayLang\OpCodes\OpIdentifier && $op_code->item->value == "parent")
		{
			if ($this->translator->class_function)
			{
				$name = $this->translator->class_function->name;
				if ($name == "constructor") $name = "__construct";
				$result->push("parent::" . $name);
			}
			else
			{
				$result->push("parent");
			}
		}
		else
		{
			$this->translateItem($op_code->item, $result);
		}
		$result->push("(");
		$args_count = $op_code->args->count();
		for ($i = 0; $i < $args_count; $i++)
		{
			$op_code_item = $op_code->args->get($i);
			$this->Expression($op_code_item, $result);
			if ($i < $args_count - 1) $result->push(", ");
		}
		$result->push(")");
		$this->translator->opcode_level = 20;
	}
	
	
	/**
	 * OpNew
	 */
	function OpNew($op_code, $result)
	{
		$result->push("new ");
		$this->OpTypeIdentifier($op_code->pattern, $result);
		$result->push("(");
		$args_count = $op_code->args->count();
		for ($i = 0; $i < $args_count; $i++)
		{
			$op_code_item = $op_code->args->get($i);
			$this->Expression($op_code_item, $result);
			if ($i < $args_count - 1) $result->push(", ");
		}
		$result->push(")");
		$this->translator->opcode_level = 20;
	}
	
	
	/**
	 * OpMath
	 */
	function OpMath($op_code, $result)
	{
		$result1 = new \Runtime\Vector();
		$this->Expression($op_code->value1, $result1);
		$opcode_level1 = $this->translator->opcode_level;
		$op = "";
		$opcode_level = 0;
		if ($op_code->math == "!")
		{
			$opcode_level = 16;
			$op = "!";
		}
		if ($op_code->math == ">>")
		{
			$opcode_level = 12;
			$op = ">>";
		}
		if ($op_code->math == "<<")
		{
			$opcode_level = 12;
			$op = "<<";
		}
		if ($op_code->math == "&")
		{
			$opcode_level = 9;
			$op = "&";
		}
		if ($op_code->math == "xor")
		{
			$opcode_level = 8;
			$op = "^";
		}
		if ($op_code->math == "|")
		{
			$opcode_level = 7;
			$op = "|";
		}
		if ($op_code->math == "*")
		{
			$opcode_level = 14;
			$op = "*";
		}
		if ($op_code->math == "/")
		{
			$opcode_level = 14;
			$op = "/";
		}
		if ($op_code->math == "%")
		{
			$opcode_level = 14;
			$op = "%";
		}
		if ($op_code->math == "div")
		{
			$opcode_level = 14;
			$op = "div";
		}
		if ($op_code->math == "mod")
		{
			$opcode_level = 14;
			$op = "mod";
		}
		if ($op_code->math == "+")
		{
			$opcode_level = 13;
			$op = "+";
		}
		if ($op_code->math == "-")
		{
			$opcode_level = 13;
			$op = "-";
		}
		if ($op_code->math == "~")
		{
			$opcode_level = 13;
			$op = "~";
		}
		if ($op_code->math == "===")
		{
			$opcode_level = 10;
			$op = "===";
		}
		if ($op_code->math == "!==")
		{
			$opcode_level = 10;
			$op = "!==";
		}
		if ($op_code->math == "==")
		{
			$opcode_level = 10;
			$op = "==";
		}
		if ($op_code->math == "!=")
		{
			$opcode_level = 10;
			$op = "!=";
		}
		if ($op_code->math == ">=")
		{
			$opcode_level = 10;
			$op = ">=";
		}
		if ($op_code->math == "<=")
		{
			$opcode_level = 10;
			$op = "<=";
		}
		if ($op_code->math == ">")
		{
			$opcode_level = 10;
			$op = ">";
		}
		if ($op_code->math == "<")
		{
			$opcode_level = 10;
			$op = "<";
		}
		if ($op_code->math == "is")
		{
			$opcode_level = 10;
			$op = "instanceof";
		}
		if ($op_code->math == "instanceof")
		{
			$opcode_level = 10;
			$op = "instanceof";
		}
		if ($op_code->math == "implements")
		{
			$opcode_level = 10;
			$op = "instanceof";
		}
		if ($op_code->math == "bitnot")
		{
			$opcode_level = 16;
			$op = "bitnot";
		}
		if ($op_code->math == "neg")
		{
			$opcode_level = 16;
			$op = "neg";
		}
		if ($op_code->math == "not")
		{
			$opcode_level = 16;
			$op = "!";
		}
		if ($op_code->math == "and" || $op_code->math == "&&")
		{
			$opcode_level = 6;
			$op = "&&";
		}
		if ($op_code->math == "or" || $op_code->math == "||")
		{
			$opcode_level = 5;
			$op = "||";
		}
		if ($op_code->math == "neg" || $op_code->math == "not" || $op_code->math == "bitnot")
		{
			if ($op_code->math == "bitnot") $result->push("~");
			else if ($op_code->math == "neg") $result->push("-");
			else $result->push($op);
			if ($opcode_level1 < $opcode_level)
			{
				$result->push("(");
				$result->appendItems($result1);
				$result->push(")");
			}
			else
			{
				$result->appendItems($result1);
			}
		}
		else
		{
			if ($opcode_level1 < $opcode_level)
			{
				$result->push("(");
				$result->appendItems($result1);
				$result->push(")");
			}
			else
			{
				$result->appendItems($result1);
			}
			if ($op == "~") $op = ".";
			$result->push(" " . $op . " ");
			$result2 = new \Runtime\Vector();
			$this->Expression($op_code->value2, $result2);
			$opcode_level2 = $this->translator->opcode_level;
			if ($opcode_level2 < $opcode_level)
			{
				$result->push("(");
				$result->appendItems($result2);
				$result->push(")");
			}
			else
			{
				$result->appendItems($result2);
			}
		}
		$this->translator->opcode_level = $opcode_level;
	}
	
	
	/**
	 * Translate ternary
	 */
	function OpTernary($op_code, $result)
	{
		$result1 = new \Runtime\Vector();
		$this->translate($op_code->condition, $result1);
		if ($this->translator->opcode_level < 9)
		{
			$result->push("(");
			$result->appendItems($result1);
			$result->push(")");
		}
		else
		{
			$result->appendItems($result1);
		}
		$result1 = new \Runtime\Vector();
		$result->push(" ? ");
		$this->translate($op_code->if_true, $result1);
		if ($this->translator->opcode_level < 9)
		{
			$result->push("(");
			$result->appendItems($result1);
			$result->push(")");
		}
		else
		{
			$result->appendItems($result1);
		}
		$result1 = new \Runtime\Vector();
		$result->push(" : ");
		$this->translate($op_code->if_false, $result1);
		if ($this->translator->opcode_level < 9)
		{
			$result->push("(");
			$result->appendItems($result1);
			$result->push(")");
		}
		else
		{
			$result->appendItems($result1);
		}
		$this->translator->opcode_level = 0;
	}
	
	
	/**
	 * Op await
	 */
	function OpAwait($op_code, $result)
	{
		if ($op_code->item instanceof \BayLang\OpCodes\OpCall)
		{
			$this->translateItem($op_code->item, $result);
		}
	}
	
	
	/**
	 * Op method
	 */
	function OpMethod($op_code, $result)
	{
		$result->push("new \\Runtime\\Method(");
		$this->translateItem($op_code->value1, $result);
		$result->push(", ");
		$result->push($this->translator->toString($op_code->value2));
		$result->push(")");
	}
	
	
	/**
	 * Translate item
	 */
	function translateItem($op_code, $result)
	{
		if ($op_code instanceof \BayLang\OpCodes\OpAwait)
		{
			$this->OpAwait($op_code, $result);
		}
		else if ($op_code instanceof \BayLang\OpCodes\OpNumber)
		{
			$this->OpNumber($op_code, $result);
		}
		else if ($op_code instanceof \BayLang\OpCodes\OpString)
		{
			$this->OpString($op_code, $result);
		}
		else if ($op_code instanceof \BayLang\OpCodes\OpIdentifier)
		{
			$this->OpIdentifier($op_code, $result);
		}
		else if ($op_code instanceof \BayLang\OpCodes\OpAttr)
		{
			$this->OpAttr($op_code, $result);
		}
		else if ($op_code instanceof \BayLang\OpCodes\OpCall)
		{
			$this->OpCall($op_code, $result);
		}
		else if ($op_code instanceof \BayLang\OpCodes\OpClassOf)
		{
			$this->OpClassOf($op_code, $result);
		}
		else if ($op_code instanceof \BayLang\OpCodes\OpCollection)
		{
			$this->OpCollection($op_code, $result);
		}
		else if ($op_code instanceof \BayLang\OpCodes\OpDict)
		{
			$this->OpDict($op_code, $result);
		}
		else if ($op_code instanceof \BayLang\OpCodes\OpDeclareFunction)
		{
			$this->translator->program->OpDeclareFunction($op_code, $result);
		}
		else if ($op_code instanceof \BayLang\OpCodes\OpMethod)
		{
			$this->OpMethod($op_code, $result);
		}
		else if ($op_code instanceof \BayLang\OpCodes\OpNew)
		{
			$this->OpNew($op_code, $result);
		}
		else if ($op_code instanceof \BayLang\OpCodes\OpTernary)
		{
			$this->OpTernary($op_code, $result);
		}
		else if ($op_code instanceof \BayLang\OpCodes\OpTypeIdentifier)
		{
			$this->OpTypeIdentifier($op_code, $result);
		}
	}
	
	
	/**
	 * Expression
	 */
	function Expression($op_code, $result)
	{
		if ($op_code instanceof \BayLang\OpCodes\OpMath)
		{
			$this->OpMath($op_code, $result);
		}
		else
		{
			$this->translateItem($op_code, $result);
		}
	}
	
	
	/**
	 * Translate expression
	 */
	function translate($op_code, $result)
	{
		$this->Expression($op_code, $result);
	}
	
	
	/* ========= Class init functions ========= */
	function _init()
	{
		parent::_init();
		$this->translator = null;
	}
	static function getClassName(){ return "BayLang.LangPHP.TranslatorPHPExpression"; }
	static function getMethodsList(){ return null; }
	static function getMethodInfoByName($field_name){ return null; }
}