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
use BayLang\Exceptions\ParserError;
use BayLang\Exceptions\ParserExpected;
use BayLang\LangBay\ParserBay;
use BayLang\OpCodes\BaseOpCode;
use BayLang\OpCodes\OpCall;
use BayLang\OpCodes\OpDeclareClass;
use BayLang\OpCodes\OpDeclareFunction;
use BayLang\OpCodes\OpDeclareFunctionArg;
use BayLang\OpCodes\OpEntityName;
use BayLang\OpCodes\OpFlags;
use BayLang\OpCodes\OpIdentifier;
use BayLang\OpCodes\OpItems;
use BayLang\OpCodes\OpPipe;
use BayLang\OpCodes\OpTypeIdentifier;


class ParserBayFunction extends \Runtime\BaseObject
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
	 * Read function arguments
	 */
	function readFunctionArgs($reader, $match_brackets = true)
	{
		if ($match_brackets) $reader->matchToken("(");
		$args = new \Runtime\Vector();
		while (!$reader->eof() && $reader->nextToken() != ")")
		{
			$expression = $this->parser->parser_expression->readExpression($reader);
			$args->push($expression);
			if ($reader->nextToken() != ")")
			{
				$reader->matchToken(",");
			}
		}
		if ($match_brackets) $reader->matchToken(")");
		return $args;
	}
	
	
	/**
	 * Read call function
	 */
	function readCallFunction($reader, $pattern = null)
	{
		$caret_start = $reader->start();
		/* Read identifier */
		$is_await = false;
		if ($pattern == null)
		{
			if ($reader->nextToken() == "await")
			{
				$is_await = true;
				$reader->matchToken("await");
			}
			$pattern = $this->parser->parser_base->readDynamic($reader, false);
		}
		/* Next token should be bracket */
		if ($reader->nextToken() != "(") return null;
		/* Find identifier */
		if ($pattern instanceof \BayLang\OpCodes\OpTypeIdentifier) $pattern = $pattern->entity_name->items->last();
		else if ($pattern instanceof \BayLang\OpCodes\OpIdentifier)
		{
			$this->parser->findVariable($pattern);
		}
		/* Read arguments */
		$args = $this->readFunctionArgs($reader);
		return new \BayLang\OpCodes\OpCall(new \Runtime\Map([
			"args" => $args,
			"item" => $pattern,
			"is_await" => $is_await,
			"caret_start" => $caret_start,
			"caret_end" => $reader->caret(),
		]));
	}
	
	
	/**
	 * Read function args
	 */
	function readDeclareFunctionArgs($reader, $match_brackets = true, $end_tag = "")
	{
		$caret_start = $reader->start();
		$items = new \Runtime\Vector();
		if ($match_brackets) $reader->matchToken("(");
		while (!$reader->eof() && $reader->nextToken() != ")" && $reader->nextToken() != $end_tag)
		{
			$caret_start_item = $reader->start();
			/* Read argument */
			$pattern = $this->parser->parser_base->readTypeIdentifier($reader);
			$name = $this->parser->parser_base->readIdentifier($reader);
			$expression = null;
			/* Read expression */
			if ($reader->nextToken() == "=")
			{
				$reader->matchToken("=");
				$expression = $this->parser->parser_expression->readExpression($reader);
			}
			/* Add item */
			$this->parser->addVariable($name, $pattern);
			$items->push(new \BayLang\OpCodes\OpDeclareFunctionArg(new \Runtime\Map([
				"pattern" => $pattern,
				"name" => $name->value,
				"expression" => $expression,
				"caret_start" => $caret_start,
				"caret_end" => $reader->caret(),
			])));
			if ($reader->nextToken() != ")" && $reader->nextToken() != $end_tag)
			{
				$reader->matchToken(",");
			}
		}
		if ($match_brackets) $reader->matchToken(")");
		return new \BayLang\OpCodes\OpItems(new \Runtime\Map([
			"items" => $items,
			"caret_start" => $caret_start,
			"caret_end" => $reader->caret(),
		]));
	}
	
	
	/**
	 * Read function variables
	 */
	function readDeclareFunctionUse($reader)
	{
		if ($reader->nextToken() != "use") return new \Runtime\Vector();
		$items = new \Runtime\Vector();
		$reader->matchToken("use");
		$reader->matchToken("(");
		while (!$reader->eof() && $reader->nextToken() != ")")
		{
			$item = $this->parser->parser_base->readIdentifier($reader);
			$items->push($item);
			if ($reader->nextToken() != ")") $reader->matchToken(",");
		}
		$reader->matchToken(")");
		return $items;
	}
	
	
	/**
	 * Read function
	 */
	function readDeclareFunction($reader, $read_name = true)
	{
		$caret_start = $reader->start();
		/* Read async */
		$is_async = false;
		if ($reader->nextToken() == "async")
		{
			$is_async = true;
			$reader->matchToken("async");
		}
		$flags = new \BayLang\OpCodes\OpFlags(new \Runtime\Map([
			"items" => new \Runtime\Map([
				"async" => $is_async,
			]),
			"caret_start" => $caret_start,
			"caret_end" => $reader->caret(),
		]));
		/* Read function name */
		$name = "";
		$content = null;
		$pattern = $this->parser->parser_base->readTypeIdentifier($reader);
		if ($read_name) $name = $this->parser->parser_base->readIdentifier($reader)->value;
		$args = $this->readDeclareFunctionArgs($reader);
		$vars = $this->readDeclareFunctionUse($reader);
		/* Read content */
		if ($this->parser->current_class != null && $this->parser->current_class->kind == \BayLang\OpCodes\OpDeclareClass::KIND_INTERFACE)
		{
			if ($reader->nextToken() == "{")
			{
				$reader->matchToken("{");
				$reader->matchToken("}");
			}
			else
			{
				$reader->matchToken(";");
			}
		}
		else if ($reader->nextToken() == "{") $content = $this->parser->parser_operator->parse($reader);
		else
		{
			$reader->matchToken("=>");
			$content = $this->parser->parser_expression->readExpression($reader);
		}
		return new \BayLang\OpCodes\OpDeclareFunction(new \Runtime\Map([
			"args" => $args,
			"flags" => $flags,
			"name" => $name,
			"vars" => $vars,
			"pattern" => $pattern,
			"content" => $content,
			"caret_start" => $caret_start,
			"caret_end" => $reader->caret(),
		]));
	}
	
	
	/**
	 * Try to read function
	 */
	function tryReadFunction($reader, $read_name = true)
	{
		$save_caret = $reader->caret();
		$error = false;
		try
		{
			if ($reader->nextToken() == "async") $reader->matchToken("async");
			$pattern = $this->parser->parser_base->readTypeIdentifier($reader);
			if ($read_name) $this->parser->parser_base->readIdentifier($reader);
			/* Read function args */
			$reader->matchToken("(");
			while (!$reader->eof() && $reader->nextToken() != ")")
			{
				$this->parser->parser_base->readTypeIdentifier($reader, false);
				$this->parser->parser_base->readIdentifier($reader);
				if ($reader->nextToken() == "=")
				{
					$reader->matchToken("=");
					$this->parser->parser_expression->readExpression($reader);
				}
				if ($reader->nextToken() != ")") $reader->matchToken(",");
			}
			$reader->matchToken(")");
			/* Read use */
			if ($reader->nextToken() == "use")
			{
				$reader->matchToken("use");
				$reader->matchToken("(");
				while (!$reader->eof() && $reader->nextToken() != ")")
				{
					$this->parser->parser_base->readIdentifier($reader);
					if ($reader->nextToken() != ")") $reader->matchToken(",");
				}
				$reader->matchToken(")");
			}
			if ($this->parser->current_class != null && $this->parser->current_class->kind == \BayLang\OpCodes\OpDeclareClass::KIND_INTERFACE && $reader->nextToken() == ";")
			{
				$reader->matchToken(";");
			}
			else if ($reader->nextToken() != "=>" && $reader->nextToken() != "use")
			{
				$reader->matchToken("{");
			}
		}
		catch (\BayLang\Exceptions\ParserError $e)
		{
			$error = true;
		}
		$reader->init($save_caret);
		if ($error) return null;
		return $this->readDeclareFunction($reader, $read_name);
	}
	
	
	/* ========= Class init functions ========= */
	function _init()
	{
		parent::_init();
		$this->parser = null;
	}
	static function getClassName(){ return "BayLang.LangBay.ParserBayFunction"; }
	static function getMethodsList(){ return null; }
	static function getMethodInfoByName($field_name){ return null; }
}