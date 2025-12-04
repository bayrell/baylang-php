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

use Runtime\BaseObject;
use BayLang\LangBay\ParserBayHtml;
use BayLang\LangPHP\TranslatorPHP;
use BayLang\OpCodes\BaseOpCode;
use BayLang\OpCodes\OpAssign;
use BayLang\OpCodes\OpComment;
use BayLang\OpCodes\OpDeclareClass;
use BayLang\OpCodes\OpDeclareFunction;
use BayLang\OpCodes\OpFor;
use BayLang\OpCodes\OpHtmlAttribute;
use BayLang\OpCodes\OpHtmlContent;
use BayLang\OpCodes\OpHtmlItems;
use BayLang\OpCodes\OpHtmlSlot;
use BayLang\OpCodes\OpHtmlStyle;
use BayLang\OpCodes\OpHtmlTag;
use BayLang\OpCodes\OpIdentifier;
use BayLang\OpCodes\OpIf;
use BayLang\OpCodes\OpItems;
use BayLang\OpCodes\OpUse;


class TranslatorPHPHtml extends \Runtime\BaseObject
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
		$result->push($var_name . "->push(" . $this->translator->toString($op_code->value) . ");");
	}
	
	
	/**
	 * OpHtmlExpression
	 */
	function OpHtmlExpression($op_code, $result)
	{
		$var_name = $this->translator->html_var_names->last();
		$result->push($var_name . "->push(");
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
		$result->push($var_name . "->slot(");
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
			if (\Runtime\rs::charAt($key, 0) == "@" && $key != "@raw") continue;
			$item_value = new \Runtime\Vector();
			$this->translator->expression->translate($item->expression, $item_value);
			$value = \Runtime\rs::join("", $item_value);
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
			$class_name->push("\$componentHash");
		}
		/* Get attrs */
		$new_attrs = $result->transition(function ($value, $key)
		{
			if ($key == "class")
			{
				return $this->translator->toString($key) . " => \\Runtime\\rs::className(new \\Runtime\\Vector(" . \Runtime\rs::join(", ", $value) . "))";
			}
			return $this->translator->toString($key) . " => " . $value;
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
		$var_name = $this->translator->html_var_names->last();
		$current_var_name = "";
		$res = $this->OpHtmlAttrs($op_code);
		$attrs = $res->get(0);
		$spread = $res->get(1);
		$attrs_str = "";
		if ($attrs->count() > 0 || $spread->count() > 0)
		{
			$attrs_str = ", (new \\Runtime\\Map([" . \Runtime\rs::join(", ", $attrs) . "]))";
			for ($i = 0; $i < $spread->count(); $i++)
			{
				$attrs_str = $attrs_str . "->concat(" . $spread->get($i) . ")";
			}
		}
		$tag_name = $this->getTagName($op_code->tag_name);
		$result->push("/* Element " . $tag_name->get(0) . " */");
		$result->push($this->translator->newLine());
		if ($op_code->content && $op_code->content->count() > 0)
		{
			$current_var_name = "\$" . $this->translator->varInc();
			$result->push($current_var_name . " = ");
		}
		$result->push($var_name . "->element(" . $tag_name->get(1) . $attrs_str . ");");
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
				$result->push($current_var_name . "->slot(\"default\", ");
				$this->translator->program->OpDeclareFunction($op_code_item, $result);
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
	 * Translate OpItems
	 */
	function translateItems($op_code, $result, $match_brackets = true)
	{
		$result->push($this->translator->newLine());
		$result->push("{");
		$this->translator->levelInc();
		/* Add component hash */
		$result->push($this->translator->newLine());
		$result->push("\$componentHash = \\Runtime\\rs::getComponentHash(static::getClassName());");
		/* Create Virtual Dom */
		$result->push($this->translator->newLine());
		$result->push("\$__v = new \\Runtime\\VirtualDom(\$this);");
		$result->push($this->translator->newLine());
		/* Add is render */
		if ($this->translator->current_function->name == "render")
		{
			$result->push("\$__v->is_render = true;");
			$result->push($this->translator->newLine());
		}
		/* Save old var names */
		$old_var_inc = $this->translator->var_inc;
		$this->translator->var_inc = 0;
		$old_var_names = $this->translator->html_var_names->slice();
		$this->translator->html_var_names = new \Runtime\Vector();
		$this->translator->html_var_names->push("\$__v");
		/* Translate HTML items */
		if ($op_code->items->count() == 1 && $op_code->get(0) instanceof \BayLang\OpCodes\OpHtmlContent)
		{
			$this->OpHtmlContent($op_code->get(0), $result);
		}
		else if ($op_code->items->count() > 0)
		{
			$this->OpHtmlItems($op_code, $result);
			$result->push($this->translator->newLine());
		}
		/* Restore */
		$this->translator->var_inc = $old_var_inc;
		$this->translator->html_var_names = $old_var_names;
		/* Return Virtual Dom */
		if ($op_code->items->count() > 0)
		{
			$result->push($this->translator->newLine());
		}
		$result->push("return \$__v;");
		$this->translator->levelDec();
		$result->push($this->translator->newLine());
		$result->push("}");
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
		$result->push("static function getComponentStyle(){ ");
		$result->push("return " . $this->translator->toString(\Runtime\rs::join("", $css_content)) . "; ");
		$result->push("}");
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
		$result->push("static function getRequiredComponents(){ ");
		$result->push("return new \\Runtime\\Vector(" . \Runtime\rs::join(", ", $components) . "); ");
		$result->push("}");
	}
	
	
	/* ========= Class init functions ========= */
	function _init()
	{
		parent::_init();
		$this->translator = null;
	}
	static function getClassName(){ return "BayLang.LangPHP.TranslatorPHPHtml"; }
	static function getMethodsList(){ return null; }
	static function getMethodInfoByName($field_name){ return null; }
}