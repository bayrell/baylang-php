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
use Runtime\Reference;
use BayLang\Caret;
use BayLang\TokenReader;
use BayLang\Exceptions\ParserError;
use BayLang\Exceptions\ParserExpected;
use BayLang\LangES6\ParserES6;
use BayLang\OpCodes\BaseOpCode;
use BayLang\OpCodes\OpCall;
use BayLang\OpCodes\OpDeclareFunction;
use BayLang\OpCodes\OpDeclareFunctionArg;
use BayLang\OpCodes\OpEntityName;
use BayLang\OpCodes\OpFlags;
use BayLang\OpCodes\OpIdentifier;
use BayLang\OpCodes\OpPipe;
use BayLang\OpCodes\OpTypeIdentifier;


class ParserES6Function extends \Runtime\BaseObject
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
	 * Returns pattern
	 */
	function getPattern($pattern)
	{
		if ($pattern instanceof \BayLang\OpCodes\OpEntityName)
		{
			if ($pattern->items->count() == 2 && $pattern->items->get(0)->value == "console" && $pattern->items->get(1)->value == "log")
			{
				return new \BayLang\OpCodes\OpIdentifier(new \Runtime\Map([
					"value" => "print",
					"caret_start" => $pattern->caret_start,
					"caret_end" => $pattern->caret_end,
				]));
			}
			else
			{
				return $pattern->items->first();
			}
		}
		return $pattern;
	}
	
	
	/**
	 * Read call function
	 */
	function readCallFunction($reader, $pattern = null)
	{
		$caret_start = $reader->start();
		/* Read identifier */
		if ($pattern == null)
		{
			if (!$this->parser->parser_base::isIdentifier($reader->nextToken())) return null;
			$pattern = $this->parser->parser_base->readEntityName($reader);
		}
		/* Next token should be bracket */
		if ($reader->nextToken() != "(") return null;
		/* Update pattern */
		$pattern = $this->getPattern($pattern);
		/* Read arguments */
		$reader->matchToken("(");
		$args = new \Runtime\Vector();
		while (!$reader->eof() && $reader->nextToken() != ")")
		{
			$expression = $this->parser->parser_expression->readExpression($reader);
			$args->push($expression);
			if ($reader->nextToken() == ",")
			{
				$reader->matchToken(",");
			}
		}
		$reader->matchToken(")");
		return new \BayLang\OpCodes\OpCall(new \Runtime\Map([
			"args" => $args,
			"item" => $pattern,
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
	static function getClassName(){ return "BayLang.LangES6.ParserES6Function"; }
	static function getMethodsList(){ return null; }
	static function getMethodInfoByName($field_name){ return null; }
}