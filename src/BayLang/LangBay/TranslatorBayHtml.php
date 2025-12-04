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

use Runtime\lib;
use Runtime\BaseObject;
use BayLang\OpCodes\BaseOpCode;
use BayLang\OpCodes\OpAnnotation;
use BayLang\OpCodes\OpAssign;
use BayLang\OpCodes\OpCall;
use BayLang\OpCodes\OpDeclareClass;
use BayLang\OpCodes\OpDeclareFunction;
use BayLang\OpCodes\OpDeclareFunctionArg;
use BayLang\OpCodes\OpHtmlAttribute;
use BayLang\OpCodes\OpHtmlContent;
use BayLang\OpCodes\OpHtmlItems;
use BayLang\OpCodes\OpHtmlSlot;
use BayLang\OpCodes\OpHtmlStyle;
use BayLang\OpCodes\OpHtmlTag;
use BayLang\OpCodes\OpHtmlValue;
use BayLang\OpCodes\OpIdentifier;
use BayLang\OpCodes\OpNamespace;
use BayLang\OpCodes\OpString;
use BayLang\OpCodes\OpUse;
use BayLang\LangBay\TranslatorBay;


class TranslatorBayHtml extends \Runtime\BaseObject
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
	 * OpAssign
	 */
	function OpAssign($op_code, $result)
	{
		$result->push("%set ");
		$this->translator->operator->OpAssign($op_code, $result);
		$result->push(";");
	}
	
	
	/**
	 * OpUse
	 */
	function OpUse($op_code, $result)
	{
		$items = \Runtime\rs::split(".", $op_code->name);
		$last_name = $items->last();
		/* Get attrs */
		$attrs = new \Runtime\Vector(
			"name=\"" . $op_code->name . "\"",
		);
		/* Add alias name */
		if ($op_code->alias != "" && $op_code->alias != $last_name)
		{
			$attrs->push("as=\"" . $op_code->alias . "\"");
		}
		/* Add component */
		if ($op_code->is_component)
		{
			$attrs->push("component=\"true\"");
		}
		/* Add result */
		$result->push("<use " . \Runtime\rs::join(" ", $attrs) . " />");
	}
	
	
	/**
	 * Translate html content
	 */
	function OpHtmlContent($op_code, $result)
	{
		$result->push($op_code->value);
	}
	
	
	/**
	 * Translate attrs
	 */
	function OpHtmlAttrs($op_code_attrs, $result, $is_multiline)
	{
		/* Filter attrs */
		$op_code_attrs = $op_code_attrs->filter(function ($op_code_attr)
		{
			/* Skip @key_debug attr */
			if (!$this->translator->preprocessor_flags->get("DEBUG_COMPONENT") && $op_code_attr->key == "@key_debug")
			{
				return false;
			}
			return true;
		});
		if ($is_multiline) $this->translator->levelInc();
		$attrs_count = $op_code_attrs->count();
		for ($i = 0; $i < $attrs_count; $i++)
		{
			$op_code_attr = $op_code_attrs->get($i);
			if ($is_multiline) $result->push($this->translator->newLine());
			$result->push($op_code_attr->key);
			$result->push("=");
			/* Value */
			if ($op_code_attr->value instanceof \BayLang\OpCodes\OpString)
			{
				$this->translator->expression->translate($op_code_attr->value, $result);
			}
			else if ($op_code_attr->value instanceof \BayLang\OpCodes\OpDeclareFunction)
			{
				$result->push("{{");
				$this->translator->levelInc();
				$result->push($this->translator->newLine());
				$this->translator->expression->translate($op_code_attr->value, $result);
				$this->translator->levelDec();
				$result->push($this->translator->newLine());
				$result->push("}}");
			}
			else
			{
				$result->push("{{ ");
				$this->translator->expression->translate($op_code_attr->value, $result);
				$result->push(" }}");
			}
			if ($i < $attrs_count - 1 && !$is_multiline) $result->push(" ");
		}
		if ($is_multiline)
		{
			$this->translator->levelDec();
			$result->push($this->translator->newLine());
		}
	}
	
	
	/**
	 * Html code multiline
	 */
	function isOpHtmlTagMultiline($op_code)
	{
		$attrs_count = 0;
		for ($i = 0; $i < $op_code->attrs->count(); $i++)
		{
			$op_code_attr = $op_code->attrs->get($i);
			if ($op_code_attr->key != "@key_debug") $attrs_count++;
			if ($op_code_attr->caret_start && $op_code_attr->caret_start->y > 0)
			{
				if ($op_code_attr->isMultiLine()) return true;
				if ($op_code->caret_start->y > 0 && $op_code_attr->caret_start->y != $op_code->caret_start->y)
				{
					return true;
				}
			}
			if ($op_code_attr->value instanceof \BayLang\OpCodes\OpDeclareFunction) return true;
		}
		if ($attrs_count > 3) return true;
		return false;
	}
	
	
	/**
	 * Translate html tag
	 */
	function OpHtmlTag($op_code, $result)
	{
		$is_multiline = $op_code->isMultiLine();
		$is_multiline_attrs = $this->isOpHtmlTagMultiline($op_code);
		/* Component attrs */
		$args_content = new \Runtime\Vector();
		$this->OpHtmlAttrs($op_code->attrs, $args_content, $is_multiline_attrs);
		$args = \Runtime\rs::join("", $args_content);
		if ($args != "" && !$is_multiline_attrs) $args = " " . $args;
		if ($op_code->items == null)
		{
			$result->push("<" . $op_code->tag_name . $args . " />");
		}
		else
		{
			/* Begin tag */
			$result->push("<" . $op_code->tag_name . $args . ">");
			if ($is_multiline) $this->translator->levelInc();
			/* Items */
			$this->OpHtmlItems($op_code->items, $result, $is_multiline);
			/* End tag */
			if ($is_multiline)
			{
				$this->translator->levelDec();
				$result->push($this->translator->newLine());
			}
			$result->push("</" . $op_code->tag_name . ">");
		}
	}
	
	
	/**
	 * OpHtmlSlot
	 */
	function OpHtmlSlot($op_code, $result)
	{
		/* Slot attrs */
		$args_content = new \Runtime\Vector();
		$this->OpHtmlAttrs($op_code->attrs, $args_content);
		/* Add slot args */
		if ($op_code->args)
		{
			$args = $op_code->args->map(function ($item)
			{
				$res = new \Runtime\Vector();
				$this->translator->expression->OpTypeIdentifier($item->pattern, $res);
				$res->push(" ");
				$res->push($item->name);
				return \Runtime\rs::join("", $res);
			});
			if ($args_content->count() > 0) $args_content->push(" ");
			$args_content->push("args=\"" . \Runtime\rs::join(",", $args) . "\"");
		}
		/* Add slot vars */
		if ($op_code->vars)
		{
			$vars = $op_code->vars->map(function ($item){ return $item->value; });
			if ($args_content->count() > 0) $args_content->push(" ");
			$args_content->push("use=\"" . \Runtime\rs::join(",", $vars) . "\"");
		}
		/* Slot args */
		$args = \Runtime\rs::join("", $args_content);
		if ($args != "") $args = " " . $args;
		/* Begin slot */
		$result->push("<slot name=\"" . $op_code->name . "\"" . $args . ">");
		/* Items */
		$this->translator->levelInc();
		$this->OpHtmlItems($op_code->items, $result);
		$this->translator->levelDec();
		/* End slot */
		$result->push($this->translator->newLine());
		$result->push("</slot>");
	}
	
	
	/**
	 * Translate html item
	 */
	function OpHtmlItem($op_code, $result)
	{
		if ($op_code instanceof \BayLang\OpCodes\OpAssign)
		{
			$this->OpAssign($op_code, $result);
		}
		else if ($op_code instanceof \BayLang\OpCodes\OpHtmlTag)
		{
			$this->OpHtmlTag($op_code, $result);
		}
		else if ($op_code instanceof \BayLang\OpCodes\OpHtmlContent)
		{
			$this->OpHtmlContent($op_code, $result);
		}
		else if ($op_code instanceof \BayLang\OpCodes\OpHtmlSlot)
		{
			$this->OpHtmlSlot($op_code, $result);
		}
		else if ($op_code instanceof \BayLang\OpCodes\OpCall && $op_code->is_html)
		{
			$result->push("%render ");
			$this->translator->expression->translate($op_code, $result);
			$result->push(";");
		}
		else if ($op_code instanceof \BayLang\OpCodes\OpHtmlValue)
		{
			if ($op_code->kind == "raw")
			{
				$result->push("@raw{{ ");
				$this->translator->expression->translate($op_code->value, $result);
				$result->push(" }}");
			}
			else
			{
				$result->push("{{ ");
				$this->translator->expression->translate($op_code->value, $result);
				$result->push(" }}");
			}
		}
		else
		{
			$result->push("{{ ");
			$this->translator->expression->translate($op_code, $result);
			$result->push(" }}");
		}
	}
	
	
	/**
	 * Translate html items
	 */
	function OpHtmlItems($op_code, $result, $is_multiline = true)
	{
		$items_count = $op_code->items->count();
		for ($i = 0; $i < $items_count; $i++)
		{
			if ($is_multiline) $result->push($this->translator->newLine());
			$this->OpHtmlItem($op_code->items->get($i), $result);
		}
	}
	
	
	/**
	 * Translate template
	 */
	function translateTemplate($op_code, $result)
	{
		if (!$op_code->is_html) return;
		/* Begin template */
		if ($op_code->name == "render")
		{
			$result->push("<template>");
		}
		else
		{
			$args_content = new \Runtime\Vector();
			if ($op_code->args && $op_code->args->count() > 0)
			{
				$this->translator->program->OpDeclareFunctionArgs($op_code, $args_content);
			}
			$args = \Runtime\rs::join("", $args_content);
			if ($args != "") $args = " args=\"" . $args . "\"";
			$result->push("<template name=\"" . $op_code->name . "\"" . $args . ">");
		}
		/* Items */
		$this->translator->levelInc();
		$this->OpHtmlItems($op_code->expression, $result);
		$this->translator->levelDec();
		/* End template */
		$result->push($this->translator->newLine());
		$result->push("</template>");
		$result->push($this->translator->newLine());
	}
	
	
	/**
	 * Translate class item
	 */
	function translateClassItem($op_code, $result)
	{
		if ($op_code instanceof \BayLang\OpCodes\OpDeclareFunction)
		{
			$this->translateTemplate($op_code, $result);
		}
	}
	
	
	/**
	 * Translate style
	 */
	function translateStyle($op_code, $result)
	{
		if ($op_code->is_global)
		{
			$result->push("<style global=\"true\">");
		}
		else
		{
			$result->push("<style>");
		}
		$result->push($this->translator->newLine());
		if ($op_code->content)
		{
			$result->push($op_code->content);
			$result->push($this->translator->newLine());
		}
		$result->push("</style>");
		$result->push($this->translator->newLine());
	}
	
	
	/**
	 * Translate class
	 */
	function translateClassBody($op_code, $result)
	{
		if ($op_code->items->count() == 0) return;
		/* Get styles */
		$styles = $op_code->items->filter(function ($op_code){ return $op_code instanceof \BayLang\OpCodes\OpHtmlStyle; });
		/* Translate styles */
		for ($i = 0; $i < $styles->count(); $i++)
		{
			$result->push($this->translator->newLine());
			$op_code_item = $styles->get($i);
			$this->translateStyle($op_code_item, $result);
		}
		/* Get templates */
		$templates = $op_code->items->filter(function ($op_code){ return $op_code instanceof \BayLang\OpCodes\OpDeclareFunction && $op_code->is_html; });
		/* Translate template */
		for ($i = 0; $i < $templates->count(); $i++)
		{
			$result->push($this->translator->newLine());
			$op_code_item = $templates->get($i);
			$this->translateClassItem($op_code_item, $result);
		}
		/* Get scripts */
		$scripts = $op_code->items->filter(function ($op_code)
		{
			return $op_code instanceof \BayLang\OpCodes\OpAnnotation || $op_code instanceof \BayLang\OpCodes\OpAssign || $op_code instanceof \BayLang\OpCodes\OpDeclareFunction && !$op_code->is_html && !$op_code->name == "components";
		});
		/* Translate scripts */
		if ($scripts->count() > 0)
		{
			$result->push($this->translator->newLine());
			$result->push("<script>");
			$result->push($this->translator->newLine());
			$result->push($this->translator->newLine());
			for ($i = 0; $i < $scripts->count(); $i++)
			{
				$op_code_item = $scripts->get($i);
				$this->translator->program->translateClassItem($op_code_item, $result);
				$result->push($this->translator->newLine());
			}
			$result->push($this->translator->newLine());
			$result->push("</script>");
			$result->push($this->translator->newLine());
		}
	}
	
	
	/**
	 * Translate
	 */
	function translate($op_code, $result)
	{
		$space = $op_code->items->findItem(\Runtime\lib::isInstance("BayLang.OpCodes.OpNamespace"));
		$component = $op_code->items->findItem(\Runtime\lib::isInstance("BayLang.OpCodes.OpDeclareClass"));
		$uses = $op_code->items->filter(\Runtime\lib::isInstance("BayLang.OpCodes.OpUse"));
		if (!$component) return;
		/* Get component name */
		$component_names = new \Runtime\Vector();
		if ($space) $component_names->push($space->name);
		$component_names->push($component->name);
		$component_name = \Runtime\rs::join(".", $component_names);
		$result->push("<class name=\"" . $component_name . "\">");
		$result->push($this->translator->newLine());
		/* Add uses */
		if ($uses->count() > 0)
		{
			$result->push($this->translator->newLine());
			for ($i = 0; $i < $uses->count(); $i++)
			{
				$use_item = $uses->get($i);
				$this->OpUse($use_item, $result);
				$result->push($this->translator->newLine());
			}
		}
		/* Declare class */
		$this->translateClassBody($component, $result);
		if ($component->items->count() > 0 || $uses->count() > 0)
		{
			$result->push($this->translator->newLine());
		}
		$result->push("</class>");
	}
	
	
	/* ========= Class init functions ========= */
	function _init()
	{
		parent::_init();
		$this->translator = null;
	}
	static function getClassName(){ return "BayLang.LangBay.TranslatorBayHtml"; }
	static function getMethodsList(){ return null; }
	static function getMethodInfoByName($field_name){ return null; }
}