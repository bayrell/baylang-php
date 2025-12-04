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
use BayLang\CoreParser;
use BayLang\TokenReader;
use BayLang\LangBay\ParserBay;
use BayLang\OpCodes\BaseOpCode;
use BayLang\OpCodes\OpItems;
use BayLang\OpCodes\OpPreprocessorIfCode;
use BayLang\OpCodes\OpPreprocessorIfDef;
use BayLang\OpCodes\OpPreprocessorSwitch;


class ParserBayPreprocessor extends \Runtime\BaseObject
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
	 * Read preprocessor switch
	 */
	function readSwitch($reader, $current_block = "")
	{
		$caret_start = $reader->start();
		$items = new \Runtime\Vector();
		$reader->matchToken("#switch");
		while (!$reader->eof() && $reader->nextToken() != "#endswitch")
		{
			$reader->matchToken("#case");
			$op_code_item = null;
			if ($reader->nextToken() == "ifdef")
			{
				$op_code_item = $this->readIfDef($reader, $current_block);
			}
			else
			{
				$op_code_item = $this->readIfCode($reader);
			}
			$items->push($op_code_item);
		}
		$reader->matchToken("#endswitch");
		return new \BayLang\OpCodes\OpPreprocessorSwitch(new \Runtime\Map([
			"items" => $items,
			"caret_start" => $caret_start,
			"caret_end" => $reader->caret(),
		]));
	}
	
	
	/**
	 * Read preprocessor ifcode
	 */
	function readIfCode($reader)
	{
		$caret_start = $reader->start();
		$is_switch = false;
		if ($reader->nextToken() == "#ifcode") $reader->matchToken("#ifcode");
		else
		{
			$is_switch = true;
			$reader->matchToken("ifcode");
		}
		$save_find_variable = $this->parser->find_variable;
		$this->parser->find_variable = false;
		$expression = $this->parser->parser_expression->readExpression($reader);
		$this->parser->find_variable = $save_find_variable;
		$reader->matchToken("then");
		/* Read content */
		$content = new \Runtime\Vector();
		$caret = $reader->caret();
		while (!$caret->eof() && !($caret->isNextString("#endif") || $caret->isNextString("#case") || $caret->isNextString("#endswitch")))
		{
			$content->push($caret->readChar());
		}
		$reader->init($caret);
		if (!$is_switch) $reader->matchToken("#endif");
		return new \BayLang\OpCodes\OpPreprocessorIfCode(new \Runtime\Map([
			"condition" => $expression,
			"content" => \Runtime\rs::trim(\Runtime\rs::join("", $content)),
			"caret_start" => $caret_start,
			"caret_end" => $reader->caret(),
		]));
	}
	
	
	/**
	 * Read preprocessor ifdef
	 */
	function readIfDef($reader, $current_block)
	{
		$caret_start = $reader->start();
		$is_switch = false;
		if ($reader->nextToken() == "#ifdef") $reader->matchToken("#ifdef");
		else
		{
			$is_switch = true;
			$reader->matchToken("ifdef");
		}
		/* Read expression */
		$save_find_variable = $this->parser->find_variable;
		$this->parser->find_variable = false;
		$expression = $this->parser->parser_expression->readExpression($reader);
		$this->parser->find_variable = $save_find_variable;
		$reader->matchToken("then");
		/* Read content */
		$content = null;
		if ($current_block == \BayLang\OpCodes\OpPreprocessorIfDef::KIND_PROGRAM)
		{
			$content = $this->parser->parser_program->parse($reader);
		}
		else if ($current_block == \BayLang\OpCodes\OpPreprocessorIfDef::KIND_CLASS_BODY)
		{
			$content = $this->parser->parser_class->readBody($reader, false);
		}
		else if ($current_block == \BayLang\OpCodes\OpPreprocessorIfDef::KIND_OPERATOR)
		{
			$content = $this->parser->parser_operator->parse($reader, false);
		}
		else if ($current_block == \BayLang\OpCodes\OpPreprocessorIfDef::KIND_COLLECTION)
		{
			$items = new \Runtime\Vector();
			while (!$reader->eof() && $reader->nextToken() != "#endif")
			{
				$op_code_item = $this->parser->parser_expression->readExpression($reader);
				$items->push($op_code_item);
				if ($reader->nextToken() == "," || $reader->nextToken() != "#endif")
				{
					$reader->matchToken(",");
				}
			}
			$content = new \BayLang\OpCodes\OpItems(new \Runtime\Map([
				"items" => $items,
				"caret_start" => $caret_start,
				"caret_end" => $reader->caret(),
			]));
		}
		else if ($current_block == \BayLang\OpCodes\OpPreprocessorIfDef::KIND_EXPRESSION)
		{
			$content = $this->parser->parser_expression->readExpression($reader);
			if ($reader->nextToken() == ",") $reader->matchToken(",");
		}
		else
		{
			throw $reader->error("Unknown block '" . $current_block . "'");
		}
		if (!$is_switch) $reader->matchToken("#endif");
		return new \BayLang\OpCodes\OpPreprocessorIfDef(new \Runtime\Map([
			"condition" => $expression,
			"content" => $content,
			"caret_start" => $caret_start,
			"caret_end" => $reader->caret(),
		]));
	}
	
	
	/**
	 * Read namespace
	 */
	function readPreprocessor($reader, $current_block = "")
	{
		if ($reader->nextToken() == "#switch")
		{
			return $this->readSwitch($reader, $current_block);
		}
		else if ($reader->nextToken() == "#ifcode")
		{
			return $this->readIfCode($reader);
		}
		else if ($reader->nextToken() == "#ifdef")
		{
			return $this->readIfDef($reader, $current_block);
		}
		return null;
	}
	
	
	/* ========= Class init functions ========= */
	function _init()
	{
		parent::_init();
		$this->parser = null;
	}
	static function getClassName(){ return "BayLang.LangBay.ParserBayPreprocessor"; }
	static function getMethodsList(){ return null; }
	static function getMethodInfoByName($field_name){ return null; }
}