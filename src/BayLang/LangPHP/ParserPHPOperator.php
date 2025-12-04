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
use Runtime\Reference;
use BayLang\Caret;
use BayLang\TokenReader;
use BayLang\Exceptions\ParserError;
use BayLang\Exceptions\ParserExpected;
use BayLang\LangPHP\ParserPHP;
use BayLang\OpCodes\BaseOpCode;
use BayLang\OpCodes\OpAnnotation;
use BayLang\OpCodes\OpAssign;
use BayLang\OpCodes\OpAssignStruct;
use BayLang\OpCodes\OpAssignValue;
use BayLang\OpCodes\OpAttr;
use BayLang\OpCodes\OpBreak;
use BayLang\OpCodes\OpCall;
use BayLang\OpCodes\OpComment;
use BayLang\OpCodes\OpContinue;
use BayLang\OpCodes\OpDeclareFunction;
use BayLang\OpCodes\OpDeclareFunctionArg;
use BayLang\OpCodes\OpDelete;
use BayLang\OpCodes\OpEntityName;
use BayLang\OpCodes\OpFlags;
use BayLang\OpCodes\OpFor;
use BayLang\OpCodes\OpIdentifier;
use BayLang\OpCodes\OpIf;
use BayLang\OpCodes\OpIfElse;
use BayLang\OpCodes\OpInc;
use BayLang\OpCodes\OpItems;
use BayLang\OpCodes\OpPipe;
use BayLang\OpCodes\OpPreprocessorIfDef;
use BayLang\OpCodes\OpReturn;
use BayLang\OpCodes\OpThrow;
use BayLang\OpCodes\OpTryCatch;
use BayLang\OpCodes\OpTryCatchItem;
use BayLang\OpCodes\OpTypeIdentifier;
use BayLang\OpCodes\OpWhile;


class ParserPHPOperator extends \Runtime\BaseObject
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
	 * Read return
	 */
	function readReturn($reader){}
	
	
	/**
	 * Read delete
	 */
	function readDelete($reader){}
	
	
	/**
	 * Read throw
	 */
	function readThrow($reader){}
	
	
	/**
	 * Read try
	 */
	function readTry($reader){}
	
	
	/**
	 * Read if
	 */
	function readIf($reader)
	{
		$caret_start = $reader->start();
		$if_true = null;
		$if_false = null;
		$if_else = new \Runtime\Vector();
		/* Read condition */
		$reader->matchToken("if");
		$reader->matchToken("(");
		$condition = $this->parser->parser_expression->readExpression($reader);
		$reader->matchToken(")");
		/* Read content */
		$if_true = $this->readContent($reader);
		/* Read content */
		$operations = new \Runtime\Vector("else", "elseif");
		while (!$reader->eof() && $operations->indexOf($reader->nextToken()) >= 0)
		{
			$token = $reader->readToken();
			if ($token == "elseif" || $token == "else" && $reader->nextToken() == "if")
			{
				/* Read condition */
				if ($reader->nextToken() == "if") $reader->readToken();
				$reader->matchToken("(");
				$if_else_condition = $this->parser->parser_expression->readExpression($reader);
				$reader->matchToken(")");
				/* Read content */
				$if_else_content = $this->readContent($reader);
				/* Add op_code */
				$if_else->push(new \BayLang\OpCodes\OpIfElse(new \Runtime\Map([
					"condition" => $if_else_condition,
					"content" => $if_else_content,
					"caret_start" => $caret_start,
					"caret_end" => $reader->caret(),
				])));
			}
			else if ($token == "else")
			{
				$if_false = $this->readContent($reader);
			}
		}
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
	 * Read For
	 */
	function readFor($reader)
	{
		$caret_start = $reader->start();
		/* Read for */
		$reader->matchToken("for");
		$reader->matchToken("(");
		/* Read assing */
		$expr1 = $this->readAssign($reader);
		$reader->matchToken(";");
		/* Read expression */
		$expr2 = $this->parser->parser_expression->readExpression($reader);
		$reader->matchToken(";");
		/* Read operator */
		$expr3 = $this->readInc($reader);
		$reader->matchToken(")");
		/* Read content */
		$content = $this->readContent($reader);
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
	 * Read While
	 */
	function readWhile($reader)
	{
		$caret_start = $reader->start();
		/* Read condition */
		$reader->matchToken("while");
		$reader->matchToken("(");
		$condition = $this->parser->parser_expression->readExpression($reader);
		$reader->matchToken(")");
		/* Read items */
		$content = null;
		if ($reader->nextToken() == "{")
		{
			$content = $this->parse($reader);
		}
		else
		{
			$content = $this->readOperator($reader);
		}
		/* Returns op_code */
		return new \BayLang\OpCodes\OpWhile(new \Runtime\Map([
			"content" => $content,
			"condition" => $condition,
			"caret_start" => $caret_start,
			"caret_end" => $reader->caret(),
		]));
	}
	
	
	/**
	 * Read assign
	 */
	function readAssign($reader, $pattern = null)
	{
		$caret_start = $reader->start();
		$items = new \Runtime\Vector();
		/* Read pattern */
		if ($pattern == null)
		{
			$pattern = $this->parser->parser_base->readTypeIdentifier($reader);
		}
		/* Read increment */
		if ($reader->nextToken() == "++" || $reader->nextToken() == "--")
		{
			$kind = "";
			$operation = $reader->readToken();
			if ($operation == "++") $kind = \BayLang\OpCodes\OpInc::KIND_INC;
			else if ($operation == "--") $kind = \BayLang\OpCodes\OpInc::KIND_DEC;
			/* Find identifier */
			if ($pattern instanceof \BayLang\OpCodes\OpTypeIdentifier)
			{
				$pattern = $pattern->entity_name->items->last();
			}
			if (!($pattern instanceof \BayLang\OpCodes\OpIdentifier))
			{
				throw $pattern->caret_end->error("Wrong type identifier");
			}
			$this->parser->findVariable($pattern);
			/* Returns op_code */
			return new \BayLang\OpCodes\OpInc(new \Runtime\Map([
				"kind" => $kind,
				"item" => $pattern,
				"caret_start" => $caret_start,
				"caret_end" => $reader->caret(),
			]));
		}
		/* Read items */
		if ($reader->nextToken() != "=")
		{
			while (!$reader->eof())
			{
				$caret_value_start = $reader->start();
				/* Read assign value */
				$value = $this->parser->parser_base->readIdentifier($reader);
				/* Register variable */
				$this->parser->addVariable($value, $pattern);
				/* Read expression */
				$expression = null;
				if ($reader->nextToken() == "=")
				{
					$reader->matchToken("=");
					$expression = $this->parser->parser_expression->readExpression($reader);
				}
				/* Add op_code */
				$items->push(new \BayLang\OpCodes\OpAssignValue(new \Runtime\Map([
					"value" => $value,
					"expression" => $expression,
					"caret_start" => $caret_value_start,
					"caret_end" => $reader->caret(),
				])));
				/* Read next token */
				if ($reader->nextToken() != ",") break;
				$reader->readToken();
			}
		}
		else
		{
			/* Get value */
			$value = $pattern;
			$pattern = null;
			/* Find identifier */
			if ($value instanceof \BayLang\OpCodes\OpTypeIdentifier)
			{
				$value = $value->entity_name->items->last();
			}
			if (!($value instanceof \BayLang\OpCodes\OpIdentifier))
			{
				throw $value->caret_end->error("Wrong type identifier");
			}
			$find = $this->parser->findVariable($value);
			if (!$find)
			{
				$pattern = new \BayLang\OpCodes\OpTypeIdentifier(new \Runtime\Map([
					"entity_name" => new \BayLang\OpCodes\OpEntityName(new \Runtime\Map([
						"items" => new \Runtime\Vector(
							new \BayLang\OpCodes\OpIdentifier(new \Runtime\Map([
								"value" => "var",
							])),
						),
					])),
				]));
				$this->parser->addVariable($value, $pattern);
			}
			/* Read expression */
			$reader->matchToken("=");
			$expression = $this->parser->parser_expression->readExpression($reader);
			$items->push(new \BayLang\OpCodes\OpAssignValue(new \Runtime\Map([
				"value" => $value,
				"expression" => $expression,
				"caret_start" => $caret_start,
				"caret_end" => $reader->caret(),
			])));
		}
		/* Returns op_code */
		return new \BayLang\OpCodes\OpAssign(new \Runtime\Map([
			"flags" => new \BayLang\OpCodes\OpFlags(),
			"pattern" => $pattern,
			"items" => $items,
			"caret_start" => $caret_start,
			"caret_end" => $reader->caret(),
		]));
	}
	
	
	/**
	 * Read operator
	 */
	function readInc($reader)
	{
		$caret_start = $reader->start();
		/* Read identifier */
		$item = $this->parser->parser_base->readIdentifier($reader);
		/* Read kind */
		$kind = $reader->readToken();
		if ($kind == "++") $kind = \BayLang\OpCodes\OpInc::KIND_INC;
		else if ($kind == "--") $kind = \BayLang\OpCodes\OpInc::KIND_DEC;
		else throw $reader->expected("++ or --");
		/* Returns op_code */
		return new \BayLang\OpCodes\OpInc(new \Runtime\Map([
			"kind" => $kind,
			"item" => $item,
			"caret_start" => $caret_start,
			"caret_end" => $reader->caret(),
		]));
	}
	
	
	/**
	 * Read operator
	 */
	function readOperator($reader)
	{
		$next_token = $reader->nextToken();
		$caret_start = $reader->start();
		/* Comment */
		if ($next_token == "/")
		{
			return $this->parser->parser_base->readComment($reader);
		}
		else if ($next_token == "#switch" || $next_token == "#ifcode")
		{
			return $this->parser->parser_preprocessor->readPreprocessor($reader);
		}
		else if ($next_token == "#ifdef")
		{
			return $this->parser->parser_preprocessor->readPreprocessorIfDef($reader, \BayLang\OpCodes\OpPreprocessorIfDef::KIND_OPERATOR);
		}
		else if ($next_token == "break")
		{
			return new \BayLang\OpCodes\OpBreak(new \Runtime\Map([
				"caret_start" => $caret_start,
				"caret_end" => $reader->caret(),
			]));
		}
		else if ($next_token == "continue")
		{
			return new \BayLang\OpCodes\OpContinue(new \Runtime\Map([
				"caret_start" => $caret_start,
				"caret_end" => $reader->caret(),
			]));
		}
		else if ($next_token == "delete")
		{
			return $this->readDelete($reader);
		}
		else if ($next_token == "return")
		{
			return $this->readReturn($reader);
		}
		else if ($next_token == "throw")
		{
			return $this->readThrow($reader);
		}
		else if ($next_token == "try")
		{
			return $this->readTry($reader);
		}
		else if ($next_token == "if")
		{
			return $this->readIf($reader);
		}
		else if ($next_token == "for")
		{
			return $this->readFor($reader);
		}
		else if ($next_token == "while")
		{
			return $this->readWhile($reader);
		}
		/* Save caret */
		$save_caret = $reader->caret();
		/* Try to read call function */
		$op_code = $this->parser->parser_function->readCallFunction($reader);
		if ($op_code) return $op_code;
		/* Restore reader */
		$reader->init($save_caret);
		/* Assign operator */
		return $this->readAssign($reader);
	}
	
	
	/**
	 * Read content
	 */
	function readContent($reader)
	{
		if ($reader->nextToken() == "{")
		{
			return $this->parse($reader);
		}
		$content = $this->readOperator($reader);
		$reader->matchToken(";");
		return $content;
	}
	
	
	/**
	 * Read operators
	 */
	function parse($reader)
	{
		$caret_start = $reader->start();
		$items = new \Runtime\Vector();
		/* Read begin tag */
		$reader->matchToken("{");
		/* Read operators */
		while (!$reader->eof() && $reader->nextToken() != "}")
		{
			$op_code = $this->readOperator($reader);
			if ($op_code)
			{
				$items->push($op_code);
			}
			else
			{
				break;
			}
			/* Match semicolon */
			if ($reader->nextToken() == ";")
			{
				$reader->matchToken(";");
			}
		}
		/* Read end tag */
		$reader->matchToken("}");
		/* Returns value */
		return new \BayLang\OpCodes\OpItems(new \Runtime\Map([
			"items" => $items,
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
	static function getClassName(){ return "BayLang.LangPHP.ParserPHPOperator"; }
	static function getMethodsList(){ return null; }
	static function getMethodInfoByName($field_name){ return null; }
}