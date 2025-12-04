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
use BayLang\Caret;
use BayLang\TokenReader;
use BayLang\Exceptions\ParserError;
use BayLang\Exceptions\ParserExpected;
use BayLang\LangES6\ParserES6;
use BayLang\OpCodes\BaseOpCode;
use BayLang\OpCodes\OpAttr;
use BayLang\OpCodes\OpCall;
use BayLang\OpCodes\OpIdentifier;
use BayLang\OpCodes\OpInc;
use BayLang\OpCodes\OpMath;
use BayLang\OpCodes\OpMethod;
use BayLang\OpCodes\OpNegative;
use BayLang\OpCodes\OpPipe;
use BayLang\OpCodes\OpPreprocessorIfDef;
use BayLang\OpCodes\OpString;
use BayLang\OpCodes\OpTernary;


class ParserES6Expression extends \Runtime\BaseObject
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
	 * Read function
	 */
	function readFunction($reader)
	{
		/* Save caret */
		$save_caret = $reader->caret();
		/* Read expression */
		if ($reader->nextToken() == "(")
		{
			$reader->matchToken("(");
			$op_code = $this->readExpression($reader);
			$reader->matchToken(")");
			return $op_code;
		}
		/* Try to read function */
		$op_code = $this->parser->parser_function->readCallFunction($reader);
		if ($op_code) return $op_code;
		/* Restore reader */
		$reader->init($save_caret);
		/* Read op_code */
		return $this->parser->parser_base->readItem($reader);
	}
	
	
	/**
	 * Read negative
	 */
	function readNegative($reader)
	{
		$caret_start = $reader->start();
		if ($reader->nextToken() == "-")
		{
			$reader->readToken();
			$op_code = $this->readFunction($reader);
			return new \BayLang\OpCodes\OpMath(new \Runtime\Map([
				"value1" => $op_code,
				"math" => "!",
				"caret_start" => $caret_start,
				"caret_end" => $reader->caret(),
			]));
		}
		return $this->readFunction($reader);
	}
	
	
	/**
	 * Read bit not
	 */
	function readBitNot($reader)
	{
		$caret_start = $reader->start();
		$operations = new \Runtime\Vector("~", "!");
		if ($operations->indexOf($reader->nextToken()) >= 0)
		{
			$op = $reader->readToken();
			if ($op == "!") $op = "not";
			else if ($op == "~") $op = "bitnot";
			$op_code = $this->readNegative($reader);
			return new \BayLang\OpCodes\OpMath(new \Runtime\Map([
				"value1" => $op_code,
				"math" => $op,
				"caret_start" => $caret_start,
				"caret_end" => $reader->caret(),
			]));
		}
		return $this->readNegative($reader);
	}
	
	
	/**
	 * Read bit shift
	 */
	function readBitShift($reader)
	{
		$caret_start = $reader->start();
		$operations = new \Runtime\Vector("<<", ">>");
		/* Read operators */
		$op_code = $this->readBitNot($reader);
		while (!$reader->eof() && $operations->indexOf($reader->nextToken()) >= 0)
		{
			$math = $reader->readToken();
			$value = $this->readBitNot($reader);
			$op_code = new \BayLang\OpCodes\OpMath(new \Runtime\Map([
				"value1" => $op_code,
				"value2" => $value,
				"math" => $math,
				"caret_start" => $caret_start,
				"caret_end" => $reader->caret(),
			]));
		}
		return $op_code;
	}
	
	
	/**
	 * Read bit and
	 */
	function readBitAnd($reader)
	{
		$caret_start = $reader->start();
		$operations = new \Runtime\Vector("&");
		/* Read operators */
		$op_code = $this->readBitShift($reader);
		while (!$reader->eof() && $operations->indexOf($reader->nextToken()) >= 0)
		{
			$math = $reader->readToken();
			$value = $this->readBitShift($reader);
			$op_code = new \BayLang\OpCodes\OpMath(new \Runtime\Map([
				"value1" => $op_code,
				"value2" => $value,
				"math" => $math,
				"caret_start" => $caret_start,
				"caret_end" => $reader->caret(),
			]));
		}
		return $op_code;
	}
	
	
	/**
	 * Read bit or
	 */
	function readBitOr($reader)
	{
		$caret_start = $reader->start();
		$operations = new \Runtime\Vector("|", "xor");
		/* Read operators */
		$op_code = $this->readBitAnd($reader);
		while (!$reader->eof() && $operations->indexOf($reader->nextToken()) >= 0)
		{
			$math = $reader->readToken();
			$value = $this->readBitAnd($reader);
			$op_code = new \BayLang\OpCodes\OpMath(new \Runtime\Map([
				"value1" => $op_code,
				"value2" => $value,
				"math" => $math,
				"caret_start" => $caret_start,
				"caret_end" => $reader->caret(),
			]));
		}
		return $op_code;
	}
	
	
	/**
	 * Read factor
	 */
	function readFactor($reader)
	{
		$caret_start = $reader->start();
		$operations = new \Runtime\Vector("*", "/", "%", "div", "mod");
		/* Read operators */
		$op_code = $this->readBitOr($reader);
		while (!$reader->eof() && $operations->indexOf($reader->nextToken()) >= 0)
		{
			$math = $reader->readToken();
			$value = $this->readBitOr($reader);
			$op_code = new \BayLang\OpCodes\OpMath(new \Runtime\Map([
				"value1" => $op_code,
				"value2" => $value,
				"math" => $math,
				"caret_start" => $caret_start,
				"caret_end" => $reader->caret(),
			]));
		}
		return $op_code;
	}
	
	
	/**
	 * Read arithmetic
	 */
	function readArithmetic($reader)
	{
		$caret_start = $reader->start();
		$operations = new \Runtime\Vector("+", "-");
		/* Read operators */
		$op_code = $this->readFactor($reader);
		while (!$reader->eof() && $operations->indexOf($reader->nextToken()) >= 0)
		{
			$math = $reader->readToken();
			$value = $this->readFactor($reader);
			if ($value instanceof \BayLang\OpCodes\OpCall && $math == "+" && $value->args->count() == 1 && $value->item instanceof \BayLang\OpCodes\OpIdentifier && $value->item->value == "String")
			{
				$math = "~";
				$value = $value->args->get(0);
			}
			else if ($value instanceof \BayLang\OpCodes\OpString && $math == "+")
			{
				$math = "~";
			}
			$op_code = new \BayLang\OpCodes\OpMath(new \Runtime\Map([
				"value1" => $op_code,
				"value2" => $value,
				"math" => $math,
				"caret_start" => $caret_start,
				"caret_end" => $reader->caret(),
			]));
		}
		return $op_code;
	}
	
	
	/**
	 * Read concat
	 */
	function readConcat($reader)
	{
		$caret_start = $reader->start();
		$operations = new \Runtime\Vector("~");
		/* Read operators */
		$op_code = $this->readArithmetic($reader);
		while (!$reader->eof() && $operations->indexOf($reader->nextToken()) >= 0)
		{
			$math = $reader->readToken();
			$value = $this->readArithmetic($reader);
			$op_code = new \BayLang\OpCodes\OpMath(new \Runtime\Map([
				"value1" => $op_code,
				"value2" => $value,
				"math" => $math,
				"caret_start" => $caret_start,
				"caret_end" => $reader->caret(),
			]));
		}
		return $op_code;
	}
	
	
	/**
	 * Read compare
	 */
	function readCompare($reader)
	{
		$caret_start = $reader->start();
		$op_code = $this->readConcat($reader);
		$operations1 = new \Runtime\Vector("===", "!==", "==", "!=", ">=", "<=", ">", "<");
		$operations2 = new \Runtime\Vector("is", "implements", "instanceof");
		/* Read operators */
		if ($operations1->indexOf($reader->nextToken()) >= 0)
		{
			$math = $reader->readToken();
			$value = $this->readConcat($reader);
			$op_code = new \BayLang\OpCodes\OpMath(new \Runtime\Map([
				"value1" => $op_code,
				"value2" => $value,
				"math" => $math,
				"caret_start" => $caret_start,
				"caret_end" => $reader->caret(),
			]));
		}
		else if ($operations2->indexOf($reader->nextToken()) >= 0)
		{
			$math = $reader->readToken();
			$value = $this->parser->parser_base->readTypeIdentifier($reader, false);
			$op_code = new \BayLang\OpCodes\OpMath(new \Runtime\Map([
				"value1" => $op_code,
				"value2" => $value,
				"math" => $math,
				"caret_start" => $caret_start,
				"caret_end" => $reader->caret(),
			]));
		}
		return $op_code;
	}
	
	
	/**
	 * Read and
	 */
	function readAnd($reader)
	{
		$caret_start = $reader->start();
		$operations = new \Runtime\Vector("and", "&&");
		/* Read operators */
		$op_code = $this->readCompare($reader);
		while (!$reader->eof() && $operations->indexOf($reader->nextToken()) >= 0)
		{
			$math = $reader->readToken();
			$value = $this->readCompare($reader);
			$op_code = new \BayLang\OpCodes\OpMath(new \Runtime\Map([
				"value1" => $op_code,
				"value2" => $value,
				"math" => $math,
				"caret_start" => $caret_start,
				"caret_end" => $reader->caret(),
			]));
		}
		return $op_code;
	}
	
	
	/**
	 * Read or
	 */
	function readOr($reader)
	{
		$caret_start = $reader->start();
		$operations = new \Runtime\Vector("or", "||");
		/* Read operators */
		$op_code = $this->readAnd($reader);
		while (!$reader->eof() && $operations->indexOf($reader->nextToken()) >= 0)
		{
			$math = $reader->readToken();
			$value = $this->readAnd($reader);
			$op_code = new \BayLang\OpCodes\OpMath(new \Runtime\Map([
				"value1" => $op_code,
				"value2" => $value,
				"math" => $math,
				"caret_start" => $caret_start,
				"caret_end" => $reader->caret(),
			]));
		}
		return $op_code;
	}
	
	
	/**
	 * Read element
	 */
	function readElement($reader)
	{
		/* Try to read function */
		/*
		OpDeclareFunction op_code = this.parser.parser_function.tryReadFunction(reader);
		if (op_code) return op_code;
		*/
		return $this->readOr($reader);
	}
	
	
	/**
	 * Read ternary operation
	 */
	function readTernary($reader)
	{
		$caret_start = $reader->start();
		/* Detect ternary operation */
		$op_code = $this->readElement($reader);
		if ($reader->nextToken() != "?") return $op_code;
		/* Read expression */
		$reader->matchToken("?");
		$if_true = $this->readElement($reader);
		$if_false = null;
		if ($reader->nextToken() == ":")
		{
			$reader->matchToken("?");
			$if_false = $this->readElement($reader);
		}
		return new \BayLang\OpCodes\OpTernary(new \Runtime\Map([
			"condition" => $op_code,
			"if_true" => $if_true,
			"if_false" => $if_false,
			"caret_start" => $caret_start,
			"caret_end" => $reader->caret(),
		]));
	}
	
	
	/**
	 * Read expression
	 */
	function readExpression($reader)
	{
		return $this->readTernary($reader);
	}
	
	
	/* ========= Class init functions ========= */
	function _init()
	{
		parent::_init();
		$this->parser = null;
	}
	static function getClassName(){ return "BayLang.LangES6.ParserES6Expression"; }
	static function getMethodsList(){ return null; }
	static function getMethodInfoByName($field_name){ return null; }
}