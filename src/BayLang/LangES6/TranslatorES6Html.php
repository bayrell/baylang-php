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

use Runtime\BaseObject;
use BayLang\LangBay\ParserBayHtml;
use BayLang\LangES6\TranslatorES6;
use BayLang\OpCodes\BaseOpCode;
use BayLang\OpCodes\OpAssign;
use BayLang\OpCodes\OpAssignValue;
use BayLang\OpCodes\OpComment;
use BayLang\OpCodes\OpDeclareClass;
use BayLang\OpCodes\OpDeclareFunction;
use BayLang\OpCodes\OpFor;
use BayLang\OpCodes\OpIf;
use BayLang\OpCodes\OpItems;
use BayLang\OpCodes\OpHtmlAttribute;
use BayLang\OpCodes\OpHtmlContent;
use BayLang\OpCodes\OpHtmlCSS;
use BayLang\OpCodes\OpHtmlCSSAttribute;
use BayLang\OpCodes\OpHtmlItems;
use BayLang\OpCodes\OpHtmlSlot;
use BayLang\OpCodes\OpHtmlStyle;
use BayLang\OpCodes\OpHtmlTag;
use BayLang\OpCodes\OpIdentifier;
use BayLang\OpCodes\OpPreprocessorIfCode;
use BayLang\OpCodes\OpPreprocessorIfDef;
use BayLang\OpCodes\OpPreprocessorSwitch;
use BayLang\OpCodes\OpTypeIdentifier;
use BayLang\OpCodes\OpUse;


class TranslatorES6Html extends \Runtime\BaseObject
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
	 * OpHtmlContent
	 */
	function OpHtmlContent($op_code, $result)
	{
		$var_name = $this->translator->html_var_names->last();
		$result->push($var_name . ".push(" . $this->translator->toString($op_code->value) . ");");
	}
	
	
	/**
	 * OpHtmlExpression
	 */
	function OpHtmlExpression($op_code, $result)
	{
		$var_name = $this->translator->html_var_names->last();
		$result->push($var_name . ".push(");
		$this->translator->expression->translate($op_code, $result);
		$result->push(");");
	}
	
	
	/**
	 * OpHtmlSlot
	 */
	function OpHtmlSlot($op_code, $result)
	{
		$var_name = $this->translator->html_var_names->last();
		$result->push("/* Slot " . $op_code->name . " */");
		$result->push($this->translator->newLine());
		$result->push($var_name . ".slot(");
		$result->push($this->translator->toString($op_code->name) . ", ");
		$this->translator->expression->translate($op_code, $result);
		$result->push(");");
	}
	
	
	/**
	 * OpHtmlAttrs
	 */
	function OpHtmlAttrs($op_code)
	{
		$attrs = $op_code->attrs;
		$spread = new \Runtime\Vector();
		$result = new \Runtime\Map();
		for ($i = 0; $i < $attrs->count(); $i++)
		{
			$item = $attrs->get($i);
			if ($item->is_spread)
			{
				$item_result = new \Runtime\Vector();
				$this->translator->expression->translate($item->expression, $item_result);
				$spread->push(\Runtime\rs::join("", $item_result));
				continue;
			}
			$key = $item->key;
			if ($key == "@key") $key = "key";
			else if (\Runtime\rs::substr($key, 0, 7) == "@event:")
			{
				$key = "on" . \Runtime\rs::upper(\Runtime\rs::charAt($key, 7)) . \Runtime\rs::substr($key, 8);
			}
			$item_value = new \Runtime\Vector();
			$is_function = $item->expression instanceof \BayLang\OpCodes\OpDeclareFunction;
			if ($is_function)
			{
				$hash_value = $this->translator->componentHashInc();
				$item_value->push("this.hash(" . $hash_value . ") ? this.hash(" . $hash_value . ") : this.hash(" . $hash_value . ", ");
			}
			$this->translator->expression->translate($item->expression, $item_value);
			$value = \Runtime\rs::join("", $item_value) . ($is_function ? ")" : "");
			if ($result->has($key))
			{
				$arr = $result->get($key);
				if ($key == "class") $arr->push($value);
				$result->set($key, $arr);
			}
			else
			{
				if ($key == "class") $value = new \Runtime\Vector($value);
				$result->set($key, $value);
			}
		}
		/* Add class name */
		$class_name = $result->get("class");
		if ($class_name)
		{
			$class_name->push("componentHash");
		}
		/* Get attrs */
		$new_attrs = $result->transition(function ($value, $key)
		{
			if ($key == "class")
			{
				return $this->translator->toString($key) . ": rs.className([" . \Runtime\rs::join(", ", $value) . "])";
			}
			return $this->translator->toString($key) . ": " . $value;
		});
		return new \Runtime\Vector($new_attrs, $spread);
	}
	
	
	/**
	 * Returns tag name
	 */
	function getTagName($tag_name)
	{
		if ($tag_name instanceof \BayLang\OpCodes\BaseOpCode)
		{
			$item_result = new \Runtime\Vector();
			$this->translator->expression->translate($tag_name, $item_result);
			$value = \Runtime\rs::join("", $item_result);
			return new \Runtime\Vector($value, $value);
		}
		if (\BayLang\LangBay\ParserBayHtml::isComponent($tag_name))
		{
			$module_name = $this->translator->getUseModule($tag_name);
			return new \Runtime\Vector($module_name, $this->translator->toString($module_name));
		}
		return new \Runtime\Vector($tag_name, $this->translator->toString($tag_name));
	}
	
	
	/**
	 * OpHtmlTag
	 */
	function OpHtmlTag($op_code, $result)
	{
		$attrs_str = "";
		$var_name = $this->translator->html_var_names->last();
		$current_var_name = "";
		$res = $this->OpHtmlAttrs($op_code);
		$attrs = $res->get(0);
		$spread = $res->get(1);
		if ($attrs->count() > 0 || $spread->count() > 0)
		{
			$attrs_str = ", new Runtime.Map({" . \Runtime\rs::join(", ", $attrs) . "})";
			for ($i = 0; $i < $spread->count(); $i++)
			{
				$attrs_str = $attrs_str . ".concat(" . $spread->get($i) . ")";
			}
		}
		$tag_name = $this->getTagName($op_code->tag_name);
		$result->push("/* Element " . $tag_name->get(0) . " */");
		$result->push($this->translator->newLine());
		if ($op_code->content && $op_code->content->count() > 0)
		{
			$current_var_name = $this->translator->varInc();
			$result->push("let " . $current_var_name . " = ");
		}
		$result->push($var_name . ".element(" . $tag_name->get(1) . $attrs_str . ");");
		if ($op_code->content && $op_code->content->count() > 0)
		{
			$this->translator->html_var_names->push($current_var_name);
			$is_slot = $op_code->content->items->filter(function ($item){ return $item instanceof \BayLang\OpCodes\OpHtmlSlot; })->count() == $op_code->content->count();
			if (\BayLang\LangBay\ParserBayHtml::isComponent($op_code->tag_name) && !$is_slot)
			{
				$op_code_item = new \BayLang\OpCodes\OpDeclareFunction(new \Runtime\Map([
					"is_html" => true,
					"content" => $op_code->content,
				]));
				$result->push($this->translator->newLine());
				$result->push($this->translator->newLine());
				$result->push("/* Content */");
				$result->push($this->translator->newLine());
				$result->push($current_var_name . ".slot(\"default\", ");
				$this->OpDeclareFunction($op_code_item, $result);
				$result->push(");");
			}
			else
			{
				$this->OpHtmlItems($op_code->content, $result, true);
			}
			$this->translator->html_var_names->pop();
		}
	}
	
	
	/**
	 * Translate item
	 */
	function translateItem($op_code, $result, $new_line = false)
	{
		if ($op_code instanceof \BayLang\OpCodes\OpHtmlContent)
		{
			$this->OpHtmlContent($op_code, $result);
		}
		else if ($op_code instanceof \BayLang\OpCodes\OpHtmlSlot)
		{
			if ($new_line) $result->push($this->translator->newLine());
			$this->OpHtmlSlot($op_code, $result);
		}
		else if ($op_code instanceof \BayLang\OpCodes\OpHtmlTag)
		{
			if ($new_line) $result->push($this->translator->newLine());
			$this->OpHtmlTag($op_code, $result);
		}
		else if ($op_code instanceof \BayLang\OpCodes\OpAssign)
		{
			if ($new_line) $result->push($this->translator->newLine());
			$this->translator->operator->OpAssign($op_code, $result);
			$result->push(";");
		}
		else if ($op_code instanceof \BayLang\OpCodes\OpFor)
		{
			if ($new_line) $result->push($this->translator->newLine());
			$this->translator->operator->OpFor($op_code, $result);
		}
		else if ($op_code instanceof \BayLang\OpCodes\OpIf)
		{
			if ($new_line) $result->push($this->translator->newLine());
			$this->translator->operator->OpIf($op_code, $result);
		}
		else if ($op_code instanceof \BayLang\OpCodes\OpComment)
		{
			$this->translator->operator->OpComment($op_code, $result);
		}
		else
		{
			$this->OpHtmlExpression($op_code, $result);
		}
	}
	
	
	/**
	 * OpHtmlItems
	 */
	function OpHtmlItems($op_code, $result, $new_line = false)
	{
		if (!$op_code) return;
		$prev_op_code = null;
		for ($i = 0; $i < $op_code->count(); $i++)
		{
			$op_code_item = $op_code->get($i);
			$result->push($this->translator->newLine());
			$next_line = true;
			if ($prev_op_code == null) $next_line = $new_line;
			else if ($prev_op_code instanceof \BayLang\OpCodes\OpAssign && ($op_code_item instanceof \BayLang\OpCodes\OpAssign || $op_code_item instanceof \BayLang\OpCodes\OpIf || $op_code_item instanceof \BayLang\OpCodes\OpFor) || $prev_op_code instanceof \BayLang\OpCodes\OpComment)
			{
				$next_line = false;
			}
			$this->translateItem($op_code_item, $result, $next_line);
			$prev_op_code = $op_code_item;
		}
	}
	
	
	/**
	 * OpDeclareFunction
	 */
	function OpDeclareFunction($op_code, $result)
	{
		/* Setup current function */
		$old_function = $this->translator->current_function;
		$this->translator->current_function = $op_code;
		/* Comments */
		if ($op_code->comments)
		{
			for ($i = 0; $i < $op_code->comments->count(); $i++)
			{
				$op_code_item = $op_code->comments->get($i);
				$this->translator->program->OpComment($op_code_item, $result);
				$result->push($this->translator->newLine());
			}
		}
		/* Function name */
		if ($old_function == null)
		{
			$result->push($op_code->name);
			$result->push(": ");
		}
		/* Function flags */
		if ($op_code->flags)
		{
			$flags = new \Runtime\Vector();
			if ($op_code->flags->isFlag("async")) $flags->push("async");
			$result->push(\Runtime\rs::join(" ", $flags));
			if ($flags->count() > 0) $result->push(" ");
		}
		if ($old_function == null) $result->push("function");
		$result->push("(");
		$this->translator->program->OpDeclareFunctionArgs($op_code, $result);
		$result->push(")");
		/* Add arrow */
		if ($this->translator->is_operator_block)
		{
			$result->push(" =>");
		}
		$result->push($this->translator->newLine());
		$result->push("{");
		$this->translator->levelInc();
		/* Add rs */
		$result->push($this->translator->newLine());
		$result->push("const rs = use(\"Runtime.rs\");");
		$result->push($this->translator->newLine());
		$result->push("const componentHash = rs.getComponentHash(this.getClassName());");
		/* Create Virtual Dom */
		$result->push($this->translator->newLine());
		$result->push("let __v = new Runtime.VirtualDom(this);");
		$result->push($this->translator->newLine());
		/* Save old var names */
		$old_var_inc = $this->translator->var_inc;
		$this->translator->var_inc = 0;
		$old_var_names = $this->translator->html_var_names->slice();
		$this->translator->html_var_names = new \Runtime\Vector();
		$this->translator->html_var_names->push("__v");
		/* Save modules */
		$save_use_modules = $this->translator->setUseModules();
		/* Function content */
		$item_result = new \Runtime\Vector();
		$save_operator_block = $this->translator->is_operator_block;
		$this->translator->is_operator_block = true;
		/* Translate HTML items */
		if ($op_code->content->items->count() == 1 && $op_code->content->get(0) instanceof \BayLang\OpCodes\OpHtmlContent)
		{
			$this->OpHtmlContent($op_code->content->get(0), $result);
		}
		else if ($op_code->content->items->count() > 0)
		{
			$this->OpHtmlItems($op_code->content, $item_result);
			$item_result->push($this->translator->newLine());
		}
		$this->translator->is_operator_block = $save_operator_block;
		/* Add modules */
		$this->translator->addUseModules($result);
		$result->appendItems($item_result);
		/* Restore */
		$this->translator->var_inc = $old_var_inc;
		$this->translator->html_var_names = $old_var_names;
		$this->translator->setUseModules($save_use_modules);
		/* Return Virtual Dom */
		if ($op_code->content->items->count() > 0)
		{
			$result->push($this->translator->newLine());
		}
		$result->push("return __v;");
		$this->translator->levelDec();
		$result->push($this->translator->newLine());
		$result->push("}");
		/* Restore old function */
		$this->translator->current_function = $old_function;
		return true;
	}
	
	
	/**
	 * Translate component body item
	 */
	function translateComponentBodyItem($op_code, $result)
	{
		$kind = $this->translator->html_kind;
		if ($op_code instanceof \BayLang\OpCodes\OpDeclareFunction)
		{
			/* Check if static */
			$is_static = $op_code->flags != null && ($op_code->flags->isFlag("static") || $op_code->flags->isFlag("pure"));
			$static_methods = new \Runtime\Vector(
				"beforeMount",
				"mounted",
				"beforeUpdate",
				"updated",
				"beforeUnmount",
				"unmounted",
			);
			if ($static_methods->indexOf($op_code->name) >= 0) $is_static = true;
			if (($kind == "methods" || $kind == "computed") && $is_static || $kind == "static" && !$is_static)
			{
				return false;
			}
			if ($kind == "methods" && $op_code->flags && $op_code->flags->isFlag("computed"))
			{
				return false;
			}
			if ($kind == "computed")
			{
				if (!$op_code->flags) return false;
				else if (!$op_code->flags->isFlag("computed")) return false;
			}
			/* Check if html function */
			if (!$op_code->is_html)
			{
				$result->push($this->translator->newLine());
				$this->translator->class_function = $op_code;
				$this->translator->program->OpDeclareFunction($op_code, $result);
				$this->translator->class_function = null;
				$result->push(",");
				return true;
			}
			$result->push($this->translator->newLine());
			$this->translator->class_function = $op_code;
			$this->OpDeclareFunction($op_code, $result, false);
			$this->translator->class_function = null;
			$result->push(",");
			return true;
		}
		else if ($op_code instanceof \BayLang\OpCodes\OpItems)
		{
			return $this->translateComponentBody($op_code, $result, $kind);
		}
		else if ($op_code instanceof \BayLang\OpCodes\OpPreprocessorIfCode)
		{
			return $this->translator->program->OpPreprocessorIfCode($op_code, $result);
		}
		else if ($op_code instanceof \BayLang\OpCodes\OpPreprocessorIfDef)
		{
			return $this->translator->program->OpPreprocessorIfDef($op_code, $result, \BayLang\OpCodes\OpPreprocessorIfDef::KIND_COMPONENT_BODY);
		}
		else if ($op_code instanceof \BayLang\OpCodes\OpPreprocessorSwitch)
		{
			return $this->translator->program->OpPreprocessorSwitch($op_code, $result, \BayLang\OpCodes\OpPreprocessorIfDef::KIND_COMPONENT_BODY);
		}
	}
	
	
	/**
	 * Translate component body
	 */
	function translateComponentBody($op_code, $result)
	{
		if ($op_code instanceof \BayLang\OpCodes\OpDeclareClass) $op_code = $op_code->content;
		for ($i = 0; $i < $op_code->count(); $i++)
		{
			$op_code_item = $op_code->get($i);
			$this->translateComponentBodyItem($op_code_item, $result);
		}
	}
	
	
	/**
	 * Translate component style
	 */
	function translateComponentStyle($op_code, $result)
	{
		$items = $op_code->content->items->filter(function ($item){ return $item instanceof \BayLang\OpCodes\OpHtmlStyle; });
		$css_content = new \Runtime\Vector();
		for ($i = 0; $i < $items->count(); $i++)
		{
			$op_code_item = $items->get($i);
			$this->translator->style->OpHtmlStyle($op_code_item, $css_content);
		}
		$result->push($this->translator->newLine());
		$result->push("getComponentStyle: function(){ ");
		$result->push("return " . $this->translator->toString(\Runtime\rs::join("", $css_content)) . "; ");
		$result->push("},");
	}
	
	
	/**
	 * Translate module components
	 */
	function translateModuleComponents($op_code, $result)
	{
		$components = new \Runtime\Vector();
		for ($i = 0; $i < $this->translator->current_module->items->count(); $i++)
		{
			$op_code_item = $this->translator->current_module->items->get($i);
			if ($op_code_item instanceof \BayLang\OpCodes\OpUse && $op_code_item->is_component)
			{
				$components->push($op_code_item->name);
			}
		}
		$components = $components->map(function ($name){ return $this->translator->toString($name); });
		$result->push($this->translator->newLine());
		$result->push("getRequiredComponents: function(){ ");
		$result->push("return new Runtime.Vector(" . \Runtime\rs::join(", ", $components) . "); ");
		$result->push("},");
	}
	
	
	/**
	 * Translate component data items
	 */
	function OpDeclareComponentDataItems($op_code, $result)
	{
		for ($i = 0; $i < $op_code->count(); $i++)
		{
			$op_code_item = $op_code->get($i);
			if ($op_code_item instanceof \BayLang\OpCodes\OpAssign && ($this->translator->is_html_props && $op_code_item->flags->isFlag("props") || !$this->translator->is_html_props && !$op_code_item->flags->isFlag("props")))
			{
				for ($j = 0; $j < $op_code_item->items->count(); $j++)
				{
					$op_code_assign = $op_code_item->items->get($j);
					$item = $op_code_assign->value;
					$result->push($this->translator->newLine());
					if ($this->translator->is_html_props) $result->push($item->value . ": {default: ");
					else $result->push($item->value . ": ");
					$this->translator->expression->translate($op_code_assign->expression, $result);
					if ($this->translator->is_html_props) $result->push("},");
					else $result->push(",");
				}
			}
			else if ($op_code_item instanceof \BayLang\OpCodes\OpPreprocessorIfCode)
			{
				return $this->translator->program->OpPreprocessorIfCode($op_code_item, $result);
			}
			else if ($op_code_item instanceof \BayLang\OpCodes\OpPreprocessorIfDef)
			{
				return $this->translator->program->OpPreprocessorIfDef($op_code_item, $result, \BayLang\OpCodes\OpPreprocessorIfDef::KIND_CLASS_INIT);
			}
			else if ($op_code_item instanceof \BayLang\OpCodes\OpPreprocessorSwitch)
			{
				return $this->translator->program->OpPreprocessorSwitch($op_code_item, $result, \BayLang\OpCodes\OpPreprocessorIfDef::KIND_CLASS_INIT);
			}
		}
	}
	
	
	/**
	 * Translate component data
	 */
	function OpDeclareComponentData($op_code, $result, $is_props)
	{
		$item_result = new \Runtime\Vector();
		$item_result->push($this->translator->newLine());
		if ($is_props) $item_result->push("props: {");
		else $item_result->push("data: function()");
		if (!$is_props)
		{
			$item_result->push($this->translator->newLine());
			$item_result->push("{");
			$this->translator->levelInc();
			$item_result->push($this->translator->newLine());
			$item_result->push("return {");
		}
		$this->translator->levelInc();
		$this->translator->is_html_props = $is_props;
		$item_content = new \Runtime\Vector();
		$this->OpDeclareComponentDataItems($op_code->content, $item_content);
		$item_result->appendItems($item_content);
		$this->translator->levelDec();
		if (!$is_props)
		{
			$item_result->push($this->translator->newLine());
			$item_result->push("};");
			$this->translator->levelDec();
		}
		$item_result->push($this->translator->newLine());
		$item_result->push("},");
		if ($item_content->count() > 0)
		{
			$result->appendItems($item_result);
		}
	}
	
	
	/**
	 * Translate component
	 */
	function translateComponent($op_code, $result)
	{
		/* Class name */
		$class_name = $op_code->name->entity_name->items->last()->value;
		$this->translator->parent_class_name = "";
		$this->translator->current_class = $op_code;
		$this->translator->current_class_name = $this->translator->current_namespace_name . "." . $op_code->name->entity_name->getName();
		/* Add use */
		$this->translator->uses->set($class_name, $this->translator->current_class_name);
		$this->translator->class_items->set($class_name, $this->translator->current_class_name);
		/* Extends */
		if ($op_code->class_extends)
		{
			$this->translator->parent_class_name = $this->translator->getFullName($op_code->class_extends->entity_name->getName());
		}
		else
		{
			if ($this->translator->current_class_name != "Runtime.Component")
			{
				$this->translator->parent_class_name = "Runtime.Component";
			}
		}
		/* Define class */
		$result->push($this->translator->current_class_name . " = {");
		$this->translator->levelInc();
		$result->push($this->translator->newLine());
		/* Component name */
		$result->push("name: " . $this->translator->toString($this->translator->current_class_name) . ",");
		if ($this->translator->parent_class_name)
		{
			$result->push($this->translator->newLine());
			$result->push("extends: " . $this->translator->parent_class_name . ",");
		}
		/* Component data */
		$this->OpDeclareComponentData($op_code, $result, true);
		$this->OpDeclareComponentData($op_code, $result, false);
		/* Component methods */
		$result->push($this->translator->newLine());
		$result->push("methods:");
		$result->push($this->translator->newLine());
		$result->push("{");
		$this->translator->levelInc();
		/* Component methods */
		$this->translator->html_kind = "methods";
		$this->translateComponentBody($op_code, $result);
		if ($this->translator->html_kind == "methods")
		{
			$result->push($this->translator->newLine());
			$result->push("getClassName: function(){ ");
			$result->push("return \"" . $this->translator->current_class_name . "\"; },");
		}
		$this->translator->levelDec();
		$result->push($this->translator->newLine());
		$result->push("},");
		/* Component computed */
		$computed_result = new \Runtime\Vector();
		$computed_result->push($this->translator->newLine());
		$computed_result->push("computed:");
		$computed_result->push($this->translator->newLine());
		$computed_result->push("{");
		$this->translator->levelInc();
		/* Translate items */
		$item_result = new \Runtime\Vector();
		$this->translator->html_kind = "computed";
		$this->translateComponentBody($op_code, $item_result);
		$computed_result->appendItems($item_result);
		$this->translator->levelDec();
		$computed_result->push($this->translator->newLine());
		$computed_result->push("},");
		if ($item_result->count() > 0) $result->appendItems($computed_result);
		/* Static functions */
		$this->translator->html_kind = "static";
		$this->translateComponentBody($op_code, $result);
		$this->translateComponentStyle($op_code, $result);
		$this->translateModuleComponents($op_code, $result);
		$this->translator->program->translateClassInit($op_code, $result, false);
		$this->translator->levelDec();
		$result->push($this->translator->newLine());
		$result->push("};");
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
	
	
	/* ========= Class init functions ========= */
	function _init()
	{
		parent::_init();
		$this->translator = null;
	}
	static function getClassName(){ return "BayLang.LangES6.TranslatorES6Html"; }
	static function getMethodsList(){ return null; }
	static function getMethodInfoByName($field_name){ return null; }
}