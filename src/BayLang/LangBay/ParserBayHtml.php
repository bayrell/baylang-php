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
use BayLang\Caret;
use BayLang\TokenReader;
use BayLang\LangBay\ParserBay;
use BayLang\OpCodes\BaseOpCode;
use BayLang\OpCodes\OpAssign;
use BayLang\OpCodes\OpCall;
use BayLang\OpCodes\OpComment;
use BayLang\OpCodes\OpDeclareClass;
use BayLang\OpCodes\OpDeclareFunction;
use BayLang\OpCodes\OpDeclareFunctionArg;
use BayLang\OpCodes\OpEntityName;
use BayLang\OpCodes\OpFor;
use BayLang\OpCodes\OpHtmlAttribute;
use BayLang\OpCodes\OpHtmlContent;
use BayLang\OpCodes\OpHtmlCSS;
use BayLang\OpCodes\OpHtmlCSSAttribute;
use BayLang\OpCodes\OpHtmlItems;
use BayLang\OpCodes\OpHtmlSlot;
use BayLang\OpCodes\OpHtmlStyle;
use BayLang\OpCodes\OpHtmlTag;
use BayLang\OpCodes\OpIdentifier;
use BayLang\OpCodes\OpIf;
use BayLang\OpCodes\OpIfElse;
use BayLang\OpCodes\OpItems;
use BayLang\OpCodes\OpModule;
use BayLang\OpCodes\OpNamespace;
use BayLang\OpCodes\OpTypeIdentifier;
use BayLang\OpCodes\OpUse;


class ParserBayHtml extends \Runtime\BaseObject
{
	var $parser;
	
	
	/**
	 * Constructor
	 */
	function __construct($parser)
	{
		parent::__construct();
		$this->parser = $parser;
	}
	
	
	/**
	 * Read comment
	 */
	function readComment($reader)
	{
		$caret_start = $reader->start();
		$reader->matchToken("<");
		$reader->matchToken("!--");
		$value = new \Runtime\Vector();
		while (!$reader->main_caret->eof() && $reader->main_caret->nextString(3) != "-->")
		{
			$value->push($reader->main_caret->readChar());
		}
		$reader->init($reader->main_caret);
		$reader->matchToken("--");
		$reader->matchToken(">");
		return new \BayLang\OpCodes\OpComment(new \Runtime\Map([
			"value" => \Runtime\rs::join("", $value),
			"caret_start" => $caret_start,
			"caret_end" => $reader->caret(),
		]));
	}
	
	
	/**
	 * Read open string
	 */
	function readOpenString($reader)
	{
		if ($reader->nextToken() == "\"")
		{
			$reader->matchToken("\"");
			return "\"";
		}
		else if ($reader->nextToken() == "'")
		{
			$reader->matchToken("'");
			return "'";
		}
		else
		{
			throw $reader->expected("\"");
		}
	}
	
	
	/**
	 * Read attr expression
	 */
	function readAttrExpression($reader, $key, $kind = "")
	{
		$expression = null;
		if ($kind == "type")
		{
			$open_tag = $this->readOpenString($reader);
			$expression = $this->parser->parser_base->readTypeIdentifier($reader, false);
			$reader->matchToken($open_tag);
		}
		else if ($kind == "template" || $kind == "style")
		{
			if ($key == "name")
			{
				$open_tag = $this->readOpenString($reader);
				$expression = $this->parser->parser_base->readIdentifier($reader);
				$reader->matchToken($open_tag);
			}
			else if ($key == "args")
			{
				$open_tag = $this->readOpenString($reader);
				$expression = $this->parser->parser_function->readDeclareFunctionArgs($reader, false, $open_tag);
				$reader->matchToken($open_tag);
			}
			else
			{
				$expression = $this->parser->parser_base->readString($reader);
			}
		}
		else if ($kind == "expression")
		{
			$next_token = $reader->nextToken();
			$is_function = \Runtime\rs::substr($key, 0, 7) == "@event:";
			if (($next_token == "\"" || $next_token == "'") && !$is_function)
			{
				$expression = $this->parser->parser_base->readString($reader);
			}
			else if ($next_token == "{{" || $next_token == "{")
			{
				if ($next_token == "{{") $reader->matchToken("{{");
				else $reader->matchToken("{");
				$expression = $this->parser->parser_expression->readExpression($reader);
				if ($next_token == "{{") $reader->matchToken("}}");
				else $reader->matchToken("}");
			}
			else if ($next_token == "[")
			{
				$expression = $this->parser->parser_base->readCollection($reader);
			}
			else if ($is_function)
			{
				$open_tag = $this->readOpenString($reader);
				$save_vars = $this->parser->vars->copy();
				$this->parser->vars->set("event", true);
				$expression = $this->parser->parser_operator->parse($reader, false, $open_tag);
				$expression = new \BayLang\OpCodes\OpDeclareFunction(new \Runtime\Map([
					"args" => new \BayLang\OpCodes\OpItems(new \Runtime\Map([
						"items" => new \Runtime\Vector(
							new \BayLang\OpCodes\OpDeclareFunctionArg(new \Runtime\Map([
								"pattern" => \BayLang\OpCodes\OpTypeIdentifier::create("var"),
								"name" => "event",
							])),
						),
					])),
					"content" => $expression,
					"caret_start" => $expression->caret_start,
					"caret_end" => $expression->caret_end,
				]));
				$this->parser->vars = $save_vars;
				$reader->matchToken($open_tag);
			}
		}
		else
		{
			$expression = $this->parser->parser_base->readString($reader);
		}
		return $expression;
	}
	
	
	/**
	 * Read attr key
	 */
	function readAttrKey($reader)
	{
		$arr = "qazwsxedcrfvtgbyhnujmikolp01234567890_";
		$key = "";
		$caret = $reader->caret();
		while (!$caret->eof() && (\Runtime\rs::indexOf($arr, \Runtime\rs::lower($caret->nextChar())) >= 0 || $caret->nextChar() == "-"))
		{
			$key .= $caret->readChar();
		}
		if ($caret->nextChar() == ":")
		{
			$key .= $caret->readChar();
			while (!$caret->eof() && \Runtime\rs::indexOf($arr, \Runtime\rs::lower($caret->nextChar())) >= 0)
			{
				$key .= $caret->readChar();
			}
		}
		$reader->init($caret);
		return $key;
	}
	
	
	/**
	 * Read attrs
	 */
	function readAttrs($reader, $kind = "")
	{
		$attrs = new \Runtime\Vector();
		$reader->main_caret->skipToken();
		while (!$reader->main_caret->eof() && $reader->main_caret->nextChar() != ">" && $reader->main_caret->nextString(2) != "/>")
		{
			$caret_start = $reader->start();
			$is_system_attr = false;
			if ($reader->nextToken() == "@")
			{
				$is_system_attr = true;
				$reader->matchToken("@");
			}
			$is_spread = false;
			$key = "";
			$key_value = "";
			$expression = null;
			if ($reader->nextToken() == "...")
			{
				$is_spread = true;
				$reader->matchToken("...");
				$expression = $this->parser->parser_base->readDynamic($reader);
				if ($expression instanceof \BayLang\OpCodes\OpIdentifier)
				{
					$this->parser->useVariable($expression);
					$this->parser->findVariable($expression);
				}
			}
			else
			{
				$key = $this->readAttrKey($reader);
				$key_value = $is_system_attr ? "@" . $key : $key;
				$reader->main_caret->skipToken();
				$reader->init($reader->main_caret);
				$reader->matchToken("=");
				$expression = $this->readAttrExpression($reader, $key_value, $kind);
			}
			$reader->main_caret->skipToken();
			$attrs->push(new \BayLang\OpCodes\OpHtmlAttribute(new \Runtime\Map([
				"key" => $key_value,
				"is_spread" => $is_spread,
				"expression" => $expression,
				"caret_start" => $caret_start,
				"caret_end" => $reader->caret(),
			])));
		}
		return $attrs;
	}
	
	
	/**
	 * Read selector
	 */
	function readSelector($reader)
	{
		$selector = new \Runtime\Vector();
		$reader->main_caret->skipToken();
		while (!$reader->main_caret->eof() && $reader->main_caret->nextChar() != "{" && $reader->main_caret->nextChar() != "}")
		{
			$selector->push($reader->main_caret->readChar());
		}
		$reader->init($reader->main_caret);
		return \Runtime\rs::join("", $selector);
	}
	
	
	/**
	 * Returns true if next is selector
	 */
	function isNextSelector($reader)
	{
		$caret = $reader->main_caret->copy();
		while (!$caret->eof())
		{
			$ch = $caret->readChar();
			if ($ch == ";" || $ch == "}") return false;
			if ($ch == "&" || $ch == "{") return true;
		}
		return false;
	}
	
	
	/**
	 * Read CSS Item
	 */
	function readCSSItem($reader)
	{
		$caret = $reader->main_caret;
		$content = new \Runtime\Vector();
		while (!$caret->eof() && !($caret->isNextString(":") || $caret->isNextString(";") || $caret->isNextString("}")))
		{
			$content->push($caret->readChar());
		}
		$reader->init($caret);
		return \Runtime\rs::trim(\Runtime\rs::join("", $content));
	}
	
	
	/**
	 * Read CSS
	 */
	function readCSS($reader)
	{
		$caret_start = $reader->start();
		$selector = $this->readSelector($reader);
		$reader->matchToken("{");
		$items = new \Runtime\Vector();
		while (!$reader->eof() && $reader->nextToken() != "}")
		{
			$reader->main_caret->skipToken();
			if ($reader->main_caret->nextChar() == "}") break;
			if ($this->isNextSelector($reader))
			{
				$op_code_item = $this->readCSS($reader);
				$items->push($op_code_item);
			}
			else
			{
				$caret_start_item = $reader->start();
				$key = $this->readCSSItem($reader);
				$reader->matchToken(":");
				$value = $this->readCSSItem($reader);
				$items->push(new \BayLang\OpCodes\OpHtmlCSSAttribute(new \Runtime\Map([
					"key" => $key,
					"value" => $value,
					"caret_start" => $caret_start_item,
					"caret_end" => $reader->caret(),
				])));
				$reader->main_caret->skipToken();
				$reader->init($reader->main_caret);
				if ($reader->main_caret->nextChar() != "}") $reader->matchToken(";");
			}
		}
		$reader->main_caret->skipToken();
		$reader->main_caret->matchChar("}");
		$reader->init($reader->main_caret);
		return new \BayLang\OpCodes\OpHtmlCSS(new \Runtime\Map([
			"selector" => $selector,
			"items" => $items,
			"caret_start" => $caret_start,
			"caret_end" => $reader->caret(),
		]));
	}
	
	
	/**
	 * Read style content
	 */
	function readStyleContent($reader, $end_tag = "}")
	{
		$caret_start = $reader->start();
		$items = new \Runtime\Vector();
		while (!$reader->eof() && $reader->nextToken() != $end_tag)
		{
			$op_code_item = $this->readCSS($reader);
			$items->push($op_code_item);
		}
		return new \BayLang\OpCodes\OpItems(new \Runtime\Map([
			"items" => $items,
			"caret_start" => $caret_start,
			"caret_end" => $reader->caret(),
		]));
	}
	
	
	/**
	 * Read style
	 */
	function readStyle($reader)
	{
		$caret_start = $reader->start();
		$reader->matchToken("<");
		$reader->matchToken("style");
		$attrs = $this->readAttrs($reader, "style");
		$reader->matchToken(">");
		$content = $this->readStyleContent($reader, "</");
		$reader->matchToken("</");
		$reader->matchToken("style");
		$reader->matchToken(">");
		$global = $attrs->find(function ($attr){ return $attr->key == "global"; });
		$is_global = $global ? $global->expression->value == "true" : false;
		return new \BayLang\OpCodes\OpHtmlStyle(new \Runtime\Map([
			"content" => $content,
			"is_global" => $is_global,
			"caret_start" => $caret_start,
			"caret_end" => $reader->caret(),
		]));
	}
	
	
	/**
	 * Read html content
	 */
	function readHtmlContent($reader)
	{
		$caret_start = $reader->main_caret->copy();
		$caret = $caret_start->copy();
		$tokens = new \Runtime\Vector("</", "{{");
		$content = new \Runtime\Vector();
		while (!$caret->eof() && !$caret->isNextChar("<") && $tokens->indexOf($caret->nextString(2)) == -1)
		{
			$content->push($caret->readChar());
		}
		$reader->init($caret);
		$value = \Runtime\rs::trim(\Runtime\rs::join("", $content), "\n\t");
		$value = \Runtime\rs::decodeHtml($value);
		return new \BayLang\OpCodes\OpHtmlContent(new \Runtime\Map([
			"value" => $value,
			"caret_start" => $caret_start,
			"caret_end" => $reader->caret(),
		]));
	}
	
	
	/**
	 * Read HTML string
	 */
	function readHtmlString($reader, $end_tag)
	{
		$caret_start = $reader->main_caret->copy();
		$caret_item = $caret_start->copy();
		$caret = $caret_start->copy();
		$items = new \Runtime\Vector();
		$content = new \Runtime\Vector();
		$addItem = function () use (&$items, &$content, &$caret, &$caret_item)
		{
			if ($content->count() == 0) return;
			$value = \Runtime\rs::trim(\Runtime\rs::join("", $content));
			$content = new \Runtime\Vector();
			$items->push(new \BayLang\OpCodes\OpHtmlContent(new \Runtime\Map([
				"value" => $value,
				"caret_start" => $caret_item->copy(),
				"caret_end" => $caret->copy(),
			])));
		};
		while (!$caret->eof() && !$caret->isNextString($end_tag))
		{
			if ($caret->isNextString("{{"))
			{
				$addItem();
				$reader->init($caret);
				$op_code = $this->readHtmlExpression($reader);
				$items->push($op_code);
				$caret = $reader->main_caret->copy();
			}
			else
			{
				$content->push($caret->readChar());
			}
		}
		$addItem();
		$reader->init($caret);
		return new \BayLang\OpCodes\OpHtmlItems(new \Runtime\Map([
			"items" => $items,
			"caret_start" => $caret_start,
			"caret_end" => $reader->caret(),
		]));
	}
	
	
	/**
	 * Read html expression
	 */
	function readHtmlExpression($reader)
	{
		$reader->matchToken("{{");
		$expression = $this->parser->parser_expression->readExpression($reader);
		$reader->matchToken("}}");
		return $expression;
	}
	
	
	/**
	 * Read html render
	 */
	function readHtmlRender($reader)
	{
		$reader->matchToken("%render");
		$expression = $this->parser->parser_expression->readExpression($reader);
		$reader->matchToken(";");
		if ($expression instanceof \BayLang\OpCodes\OpCall) $expression->is_html = true;
		return $expression;
	}
	
	
	/**
	 * Returns true if tag_name is component
	 */
	static function isComponent($tag_name)
	{
		$first = \Runtime\rs::substr($tag_name, 0, 1);
		return \Runtime\rs::upper($first) == $first;
	}
	
	
	/**
	 * Read html tag
	 */
	function readHtmlTag($reader)
	{
		$caret_start = $reader->start();
		$reader->matchToken("<");
		if ($reader->nextToken() == "!--")
		{
			$reader->init($caret_start);
			return $this->readComment($reader);
		}
		$tag_name = null;
		$is_variable = false;
		if ($reader->nextToken() == "{")
		{
			$is_variable = true;
			$reader->matchToken("{");
			$tag_name = $this->parser->parser_base->readDynamic($reader);
			$reader->matchToken("}");
			$this->parser->useVariable($tag_name);
		}
		else
		{
			$tag_name = $this->parser->parser_base->readIdentifier($reader);
		}
		/* Detect component */
		$is_component = true;
		if (!$is_variable) $is_component = static::isComponent($tag_name->value);
		/* Save vars */
		$vars = new \Runtime\Vector();
		$vars_uses = $this->parser->vars_uses->copy();
		if ($tag_name->value == "slot" || $is_component)
		{
			$this->parser->function_level += 1;
			$this->parser->vars_uses = new \Runtime\Map();
		}
		$attrs = $this->readAttrs($reader, (!$is_variable && $tag_name->value == "slot") ? "template" : "expression");
		$content = null;
		if ($reader->nextToken() != "/>")
		{
			$reader->matchToken(">");
			if ($tag_name->value == "script" || $tag_name->value == "style")
			{
				$content = $this->readHtmlString($reader, "</" . $tag_name->value . ">");
			}
			else $content = $this->readHtml($reader);
			$reader->matchToken("</");
			if ($is_variable) $reader->matchToken("{");
			if ($tag_name instanceof \BayLang\OpCodes\OpIdentifier) $reader->matchToken($tag_name->value);
			if ($is_variable) $reader->matchToken("}");
			$reader->matchToken(">");
		}
		else
		{
			$reader->matchToken("/>");
		}
		/* Restore vars */
		if ($tag_name->value == "slot" || $is_component)
		{
			$this->parser->parser_function->extendVariables($vars);
			$this->parser->vars_uses = $this->parser->vars_uses->concat($vars_uses);
			/* Dec level */
			$this->parser->function_level -= 1;
		}
		if ($tag_name->value == "slot")
		{
			$name = "";
			$args = null;
			for ($i = 0; $i < $attrs->count(); $i++)
			{
				$item = $attrs->get($i);
				if ($item->key == "name") $name = $item->expression->value;
				else if ($item->key == "args") $args = $item->expression;
			}
			return new \BayLang\OpCodes\OpHtmlSlot(new \Runtime\Map([
				"args" => $args,
				"name" => $name,
				"vars" => $vars,
				"content" => $content,
				"caret_start" => $caret_start,
				"caret_end" => $reader->caret(),
			]));
		}
		return new \BayLang\OpCodes\OpHtmlTag(new \Runtime\Map([
			"attrs" => $attrs,
			"vars" => $vars,
			"content" => $content,
			"is_component" => $is_component,
			"tag_name" => $is_variable ? $tag_name : $tag_name->value,
			"caret_start" => $caret_start,
			"caret_end" => $reader->caret(),
		]));
	}
	
	
	/**
	 * Read HTML Assign
	 */
	function readHtmlAssign($reader)
	{
		if ($reader->nextToken() == "%set") $reader->matchToken("%set");
		else $reader->matchToken("%var");
		$op_code = $this->parser->parser_operator->readAssign($reader);
		$reader->matchToken(";");
		return $op_code;
	}
	
	
	/**
	 * Read HTML for
	 */
	function readHtmlFor($reader)
	{
		$caret_start = $reader->start();
		/* Read for */
		$reader->matchToken("%for");
		$reader->matchToken("(");
		/* Read assing */
		$expr1 = $this->parser->parser_operator->readAssign($reader);
		$is_foreach = $reader->nextToken() == "in";
		if ($is_foreach)
		{
			$reader->matchToken("in");
		}
		else $reader->matchToken(";");
		/* Read expression */
		$expr2 = $this->parser->parser_expression->readExpression($reader);
		if (!$is_foreach) $reader->matchToken(";");
		/* Read operator */
		$expr3 = null;
		if (!$is_foreach) $expr3 = $this->parser->parser_operator->readInc($reader);
		$reader->matchToken(")");
		/* Read content */
		$content = $this->readHtml($reader, true, "}");
		/* Returns op_code */
		return new \BayLang\OpCodes\OpFor(new \Runtime\Map([
			"expr1" => $expr1,
			"expr2" => $expr2,
			"expr3" => $expr3,
			"content" => $content,
			"caret_start" => $caret_start,
			"caret_end" => $reader->caret(),
		]));
	}
	
	
	/**
	 * Read HTML if
	 */
	function readHtmlIf($reader)
	{
		$caret_start = $reader->start();
		$if_true = null;
		$if_false = null;
		$if_else = new \Runtime\Vector();
		/* Read condition */
		$reader->matchToken("%if");
		$reader->matchToken("(");
		$condition = $this->parser->parser_expression->readExpression($reader);
		$reader->matchToken(")");
		/* Read content */
		$if_true = $this->readHtml($reader, true, "}");
		$this->parser->parser_base->skipComment($reader);
		/* Read content */
		$caret_last = null;
		$operations = new \Runtime\Vector("%else", "%elseif");
		while (!$reader->eof() && $operations->indexOf($reader->nextToken()) >= 0)
		{
			$token = $reader->readToken();
			if ($token == "%elseif" || $token == "%else" && $reader->nextToken() == "if")
			{
				/* Read condition */
				if ($reader->nextToken() == "if") $reader->readToken();
				$reader->matchToken("(");
				$if_else_condition = $this->parser->parser_expression->readExpression($reader);
				$reader->matchToken(")");
				/* Read content */
				$if_else_content = $this->readHtml($reader, true, "}");
				/* Add op_code */
				$if_else->push(new \BayLang\OpCodes\OpIfElse(new \Runtime\Map([
					"condition" => $if_else_condition,
					"content" => $if_else_content,
					"caret_start" => $caret_start,
					"caret_end" => $reader->caret(),
				])));
			}
			else if ($token == "%else")
			{
				$if_false = $this->readHtml($reader, true, "}");
			}
			$caret_last = $reader->caret();
			$this->parser->parser_base->skipComment($reader);
		}
		/* Restore caret */
		if ($caret_last) $reader->init($caret_last);
		return new \BayLang\OpCodes\OpIf(new \Runtime\Map([
			"condition" => $condition,
			"if_true" => $if_true,
			"if_false" => $if_false,
			"if_else" => $if_else,
			"caret_start" => $caret_start,
			"caret_end" => $reader->caret(),
		]));
	}
	
	
	/**
	 * Read HTML item
	 */
	function readHtmlItem($reader)
	{
		$next_token = $reader->nextToken();
		if ($next_token == "<") return $this->readHtmlTag($reader);
		else if ($next_token == "{{") return $this->readHtmlExpression($reader);
		else if ($next_token == "%render") return $this->readHtmlRender($reader);
		else if ($next_token == "%set" || $next_token == "%var") return $this->readHtmlAssign($reader);
		else if ($next_token == "%for") return $this->readHtmlFor($reader);
		else if ($next_token == "%if") return $this->readHtmlIf($reader);
		return $this->readHtmlContent($reader);
	}
	
	
	/**
	 * Read html
	 */
	function readHtml($reader, $match_brackets = false, $end_tag = "")
	{
		$caret_start = $reader->start();
		$items = new \Runtime\Vector();
		if ($match_brackets && $reader->nextToken() == "<")
		{
			$op_code_item = $this->readHtmlItem($reader);
			$items->push($op_code_item);
		}
		else
		{
			if ($match_brackets) $reader->matchToken("{");
			while (!$reader->eof() && $reader->nextToken() != "</" && $reader->nextToken() != $end_tag)
			{
				$op_code_item = $this->readHtmlItem($reader);
				$items->push($op_code_item);
			}
			if ($match_brackets) $reader->matchToken("}");
		}
		return new \BayLang\OpCodes\OpHtmlItems(new \Runtime\Map([
			"items" => $items,
			"caret_start" => $caret_start,
			"caret_end" => $reader->caret(),
		]));
	}
	
	
	/**
	 * Read use
	 */
	function readUse($reader)
	{
		$caret_start = $reader->start();
		$reader->matchToken("<");
		$reader->matchToken("use");
		$attrs = $this->readAttrs($reader);
		$reader->matchToken("/>");
		$alias = "";
		$name = "";
		$is_component = false;
		for ($i = 0; $i < $attrs->count(); $i++)
		{
			$item = $attrs->get($i);
			$value = $item->expression->value;
			if ($item->key == "name") $name = $value;
			else if ($item->key == "as") $alias = $value;
			else if ($item->key == "component")
			{
				if ($value == "true" || $value == "1") $is_component = true;
			}
		}
		/* Get alias */
		if ($alias == "")
		{
			$arr = \Runtime\rs::split(".", $name);
			$alias = $arr->last();
		}
		/* Add use */
		$this->parser->uses->set($alias, $name);
		return new \BayLang\OpCodes\OpUse(new \Runtime\Map([
			"alias" => $alias,
			"name" => $name,
			"is_component" => $is_component,
			"caret_start" => $caret_start,
			"caret_end" => $reader->caret(),
		]));
	}
	
	
	/**
	 * Read template
	 */
	function readTemplate($reader)
	{
		$caret_start = $reader->start();
		$reader->matchToken("<");
		$reader->matchToken("template");
		$attrs = $this->readAttrs($reader, "template");
		$reader->matchToken(">");
		/* Add new level */
		$this->parser->function_level += 1;
		/* Save vars */
		$vars_uses = $this->parser->vars_uses->copy();
		$vars = new \Runtime\Vector();
		/* Read html */
		$content = $this->readHtml($reader);
		/* Restore vars */
		if ($this->parser->function_level > 1)
		{
			$this->parser->parser_function->extendVariables($vars);
			$this->parser->vars_uses = $this->parser->vars_uses->concat($vars_uses);
		}
		/* Dec level */
		$this->parser->function_level -= 1;
		$reader->matchToken("</");
		$reader->matchToken("template");
		$reader->matchToken(">");
		$name = null;
		$args = null;
		for ($i = 0; $i < $attrs->count(); $i++)
		{
			$item = $attrs->get($i);
			if ($item->key == "name") $name = $item->expression->value;
			else if ($item->key == "args") $args = $item->expression;
		}
		return new \BayLang\OpCodes\OpDeclareFunction(new \Runtime\Map([
			"name" => $name ? $name : "render",
			"args" => $args ? $args : null,
			"vars" => $vars,
			"is_html" => true,
			"content" => $content,
			"caret_start" => $caret_start,
			"caret_end" => $reader->caret(),
		]));
	}
	
	
	/**
	 * Read script
	 */
	function readScript($reader)
	{
		$caret_start = $reader->start();
		$reader->matchToken("<");
		$reader->matchToken("script");
		$attrs = $this->readAttrs($reader, "script");
		$reader->matchToken(">");
		$items = $this->parser->parser_class->readBody($reader, false, "</");
		$reader->matchToken("</");
		$reader->matchToken("script");
		$reader->matchToken(">");
		return $items;
	}
	
	
	/**
	 * Read item
	 */
	function readItem($reader)
	{
		$caret_save = $reader->caret();
		$reader->matchToken("<");
		$next_token = $reader->nextToken();
		$reader->init($caret_save);
		if ($next_token == "style") return $this->readStyle($reader);
		else if ($next_token == "template") return $this->readTemplate($reader);
		else if ($next_token == "script") return $this->readScript($reader);
		else if ($next_token == "use") return $this->readUse($reader);
		else if ($next_token == "!--") return $this->readComment($reader);
		else throw $reader->next_caret->error("Unknown token " . $next_token);
		return null;
	}
	
	
	/**
	 * Read class content
	 */
	function readContent($reader)
	{
		$caret_start = $reader->start();
		$items = new \Runtime\Vector();
		while (!$reader->eof() && $reader->nextToken() != "</")
		{
			$op_code_item = $this->readItem($reader);
			if ($op_code_item instanceof \BayLang\OpCodes\OpItems) $items->appendItems($op_code_item->items);
			else $items->push($op_code_item);
		}
		return new \BayLang\OpCodes\OpItems(new \Runtime\Map([
			"items" => $items,
			"caret_start" => $caret_start,
			"caret_end" => $reader->caret(),
		]));
	}
	
	
	/**
	 * Parse HTML
	 */
	function parse($reader)
	{
		$caret_start = $reader->start();
		$items = new \Runtime\Vector();
		/* Read comment */
		if ($caret_start->skipSpace()->nextString(2) == "<!")
		{
			$items->push($this->readComment($reader));
		}
		/* Read class */
		$reader->matchToken("<");
		$reader->matchToken("class");
		/* Read attrs */
		$attrs = $this->readAttrs($reader, "type");
		$class_name = $attrs->find(function ($attr){ return $attr->key == "name"; });
		$extend_name = $attrs->find(function ($attr){ return $attr->key == "extends"; });
		$reader->matchToken(">");
		/* Read component content */
		$class_content = $this->readContent($reader);
		/*string class_name_value = class_name.entity_name.items.last();*/
		$namespace_name = \Runtime\rs::join(".", $class_name->expression->entity_name->items->slice(0, $class_name->expression->entity_name->items->count() - 1)->map(function ($item){ return $item->value; }));
		/* Change class name */
		$class_name->expression->entity_name->items = new \Runtime\Vector($class_name->expression->entity_name->items->last());
		/* Add namespace */
		$items->push(new \BayLang\OpCodes\OpNamespace(new \Runtime\Map([
			"name" => $namespace_name,
			"caret_start" => $caret_start,
			"caret_end" => $reader->caret(),
		])));
		/* Add use */
		$uses = $class_content->items->filter(function ($item){ return $item instanceof \BayLang\OpCodes\OpUse; });
		$items->appendItems($uses);
		/* Filter content */
		$class_content->items = $class_content->items->filter(function ($item){ return !($item instanceof \BayLang\OpCodes\OpUse); });
		$items->push(new \BayLang\OpCodes\OpDeclareClass(new \Runtime\Map([
			"name" => $class_name->expression,
			"kind" => \BayLang\OpCodes\OpDeclareClass::KIND_CLASS,
			"class_extends" => $extend_name ? $extend_name->expression : null,
			"is_component" => true,
			"content" => $class_content,
			"caret_start" => $caret_start,
			"caret_end" => $reader->caret(),
		])));
		return new \BayLang\OpCodes\OpModule(new \Runtime\Map([
			"items" => $items,
			"is_component" => true,
			"caret_start" => $caret_start,
			"caret_end" => $reader->caret(),
		]));
	}
	
	
	/* ========= Class init functions ========= */
	function _init()
	{
		parent::_init();
		$this->parser = null;
	}
	static function getClassName(){ return "BayLang.LangBay.ParserBayHtml"; }
	static function getMethodsList(){ return null; }
	static function getMethodInfoByName($field_name){ return null; }
}