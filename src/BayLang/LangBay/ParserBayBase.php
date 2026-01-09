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
use Runtime\Reference;
use BayLang\Caret;
use BayLang\TokenReader;
use BayLang\Exceptions\ParserEOF;
use BayLang\Exceptions\ParserError;
use BayLang\Exceptions\ParserExpected;
use BayLang\LangBay\ParserBay;
use BayLang\OpCodes\BaseOpCode;
use BayLang\OpCodes\OpAttr;
use BayLang\OpCodes\OpCall;
use BayLang\OpCodes\OpComment;
use BayLang\OpCodes\OpClassOf;
use BayLang\OpCodes\OpClassRef;
use BayLang\OpCodes\OpCollection;
use BayLang\OpCodes\OpCurry;
use BayLang\OpCodes\OpCurryArg;
use BayLang\OpCodes\OpDeclareFunction;
use BayLang\OpCodes\OpDeclareFunctionArg;
use BayLang\OpCodes\OpDict;
use BayLang\OpCodes\OpDictPair;
use BayLang\OpCodes\OpEntityName;
use BayLang\OpCodes\OpIdentifier;
use BayLang\OpCodes\OpMethod;
use BayLang\OpCodes\OpNew;
use BayLang\OpCodes\OpNumber;
use BayLang\OpCodes\OpNegative;
use BayLang\OpCodes\OpPreprocessorIfDef;
use BayLang\OpCodes\OpString;
use BayLang\OpCodes\OpTypeConvert;
use BayLang\OpCodes\OpTypeIdentifier;


class ParserBayBase extends \Runtime\BaseObject
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
	 * Returns true if name is identifier
	 */
	static function isIdentifier($name)
	{
		if ($name == "") return false;
		if ($name == "@") return true;
		if (\BayLang\Caret::isNumber(\Runtime\rs::charAt($name, 0))) return false;
		$sz = \Runtime\rs::strlen($name);
		for ($i = 0; $i < $sz; $i++)
		{
			$ch = \Runtime\rs::charAt($name, $i);
			if (\BayLang\Caret::isChar($ch) || \BayLang\Caret::isNumber($ch) || $ch == "_") continue;
			return false;
		}
		return true;
	}
	
	
	/**
	 * Returns true if reserved words
	 */
	static function isReserved($name)
	{
		if (\Runtime\rs::substr($name, 0, 3) == "__v") return true;
		return false;
	}
	
	
	/**
	 * Read number
	 */
	function readNumber($reader, $flag_negative = false)
	{
		$caret_start = $reader->start();
		/* Read number */
		$value = $reader->readToken();
		if ($value == "")
		{
			throw $caret_start->expected("Number");
		}
		if (!\BayLang\Caret::isNumber($value))
		{
			throw $caret_start->expected("Number");
		}
		/* Look dot */
		if ($reader->nextToken() == ".")
		{
			$value .= $reader->readToken();
			$value .= $reader->readToken();
		}
		/* Returns op_code */
		return new \BayLang\OpCodes\OpNumber(new \Runtime\Map([
			"value" => $flag_negative ? "-" . $value : $value,
			"caret_start" => $caret_start,
			"caret_end" => $reader->caret(),
		]));
	}
	
	
	/**
	 * Read string
	 */
	function readString($reader)
	{
		$caret_start = $reader->caret();
		$str_char = $reader->readToken();
		/* Read begin string char */
		if ($str_char != "'" && $str_char != "\"")
		{
			throw $caret_start->expected("String");
		}
		/* Read string value */
		$caret = $reader->caret();
		$value_str = "";
		$ch = $caret->nextChar();
		while (!$caret->eof() && $ch != $str_char)
		{
			if ($ch == "\\")
			{
				$caret->readChar();
				if ($caret->eof())
				{
					throw $caret->expected("End of string");
				}
				$ch2 = $caret->readChar();
				if ($ch2 == "n") $value_str .= "\n";
				else if ($ch2 == "r") $value_str .= "\r";
				else if ($ch2 == "t") $value_str .= "\t";
				else if ($ch2 == "s") $value_str .= " ";
				else if ($ch2 == "\\") $value_str .= "\\";
				else if ($ch2 == "'") $value_str .= "'";
				else if ($ch2 == "\"") $value_str .= "\"";
				else $value_str .= $ch . $ch2;
			}
			else
			{
				$value_str .= $caret->readChar();
			}
			if ($caret->eof())
			{
				throw $caret->expected("End of string");
			}
			$ch = $caret->nextChar();
		}
		/* Read end string char */
		$caret->matchString($str_char);
		/* Restore reader */
		$reader->init($caret);
		/* Returns op_code */
		return new \BayLang\OpCodes\OpString(new \Runtime\Map([
			"value" => $value_str,
			"caret_start" => $caret_start,
			"caret_end" => $reader->caret(),
		]));
	}
	
	
	/**
	 * Read comment
	 */
	function readComment($reader)
	{
		/* Init caret */
		$caret = $reader->caret();
		$caret->skip_comments = false;
		/* Caret start */
		$caret_start = $caret->copy()->skipSpace();
		/* Read begin coment */
		$caret_start->matchString("/*");
		$caret->seek($caret_start);
		/* Read comment value */
		$value_str = "";
		$ch2 = $caret->nextString(2);
		while (!$caret->eof() && $ch2 != "*/")
		{
			$value_str .= $caret->readChar();
			if ($caret->eof())
			{
				throw $caret->expected("End of comment");
			}
			$ch2 = $caret->nextString(2);
		}
		/* Restore reader */
		$reader->init($caret);
		/* Read end coment */
		$reader->matchToken("*");
		$reader->matchToken("/");
		/* Returns op_code */
		return new \BayLang\OpCodes\OpComment(new \Runtime\Map([
			"value" => $value_str,
			"caret_start" => $caret_start,
			"caret_end" => $reader->caret(),
		]));
	}
	
	
	/**
	 * Skip comment
	 */
	function skipComment($reader)
	{
		if ($reader->nextToken() != "/") return false;
		$this->readComment($reader);
		return true;
	}
	
	
	/**
	 * Read identifier
	 */
	function readIdentifier($reader)
	{
		$caret_start = $reader->start();
		/* Read identifier */
		$name = $reader->readToken();
		if (!static::isIdentifier($name) || static::isReserved($name))
		{
			throw $reader->expected("Identifier");
		}
		/* Returns op_code */
		return new \BayLang\OpCodes\OpIdentifier(new \Runtime\Map([
			"value" => $name,
			"caret_start" => $caret_start,
			"caret_end" => $reader->caret(),
		]));
	}
	
	
	/**
	 * Read entity name
	 */
	function readEntityName($reader)
	{
		$caret_start = $reader->start();
		$items = new \Runtime\Vector();
		/* Read name */
		$items->push($this->readIdentifier($reader));
		/* Read names */
		while ($reader->nextToken() == ".")
		{
			$reader->readToken();
			$items->push($this->readIdentifier($reader));
		}
		/* Returns op_code */
		return new \BayLang\OpCodes\OpEntityName(new \Runtime\Map([
			"items" => $items,
			"caret_start" => $caret_start,
			"caret_end" => $reader->caret(),
		]));
	}
	
	
	/**
	 * Read type identifier
	 */
	function readTypeIdentifier($reader, $find_entity = true, $read_generic = true)
	{
		$caret_start = $reader->start();
		$entity_name = $this->readEntityName($reader);
		/* Find entity */
		if ($find_entity) $this->parser->findEntity($entity_name);
		/* Read generics */
		$generics = new \Runtime\Vector();
		if ($reader->nextToken() == "<" && $read_generic)
		{
			$reader->matchToken("<");
			$reader->main_caret->skipToken();
			while (!$reader->eof() && $reader->main_caret->nextChar() != ">")
			{
				$generics->push($this->readTypeIdentifier($reader, false));
				$reader->main_caret->skipToken();
				$reader->init($reader->main_caret);
				if ($reader->main_caret->nextChar() != ">")
				{
					$reader->matchToken(",");
				}
			}
			$reader->main_caret->matchString(">");
			$reader->init($reader->main_caret);
		}
		return new \BayLang\OpCodes\OpTypeIdentifier(new \Runtime\Map([
			"entity_name" => $entity_name,
			"generics" => $generics,
			"caret_start" => $caret_start,
			"caret_end" => $reader->caret(),
		]));
	}
	
	
	/**
	 * Read collection
	 */
	function readCollection($reader)
	{
		$caret_start = $reader->start();
		$reader->matchToken("[");
		$items = new \Runtime\Vector();
		while (!$reader->eof() && $reader->nextToken() != "]")
		{
			if ($this->skipComment($reader))
			{
				continue;
			}
			$op_code_item = null;
			/* Prepocessor */
			$is_preprocessor = false;
			$next_token = $reader->nextToken();
			if ($next_token == "#switch" || $next_token == "#ifcode" || $next_token == "#ifdef")
			{
				$is_preprocessor = true;
				$op_code_item = $this->parser->parser_preprocessor->readPreprocessor($reader, \BayLang\OpCodes\OpPreprocessorIfDef::KIND_COLLECTION);
			}
			else
			{
				$op_code_item = $this->parser->parser_expression->readExpression($reader);
			}
			$items->push($op_code_item);
			if (($reader->nextToken() == "," || $reader->nextToken() != "]") && !$is_preprocessor)
			{
				$reader->matchToken(",");
			}
		}
		$reader->matchToken("]");
		return new \BayLang\OpCodes\OpCollection(new \Runtime\Map([
			"items" => $items,
			"caret_start" => $caret_start,
			"caret_end" => $reader->caret(),
		]));
	}
	
	
	/**
	 * Read collection
	 */
	function readDict($reader)
	{
		$caret_start = $reader->start();
		$reader->matchToken("{");
		$items = new \Runtime\Vector();
		while (!$reader->eof() && $reader->nextToken() != "}")
		{
			if ($reader->nextToken() == "/")
			{
				$this->readComment($reader);
				continue;
			}
			$is_preprocessor = false;
			$condition = null;
			/* Prepocessor */
			$next_token = $reader->nextToken();
			if ($next_token == "#ifdef")
			{
				$is_preprocessor = true;
				$reader->matchToken("#ifdef");
				$save_find_variable = $this->parser->find_variable;
				$this->parser->find_variable = false;
				$condition = $this->parser->parser_expression->readExpression($reader);
				$this->parser->find_variable = $save_find_variable;
				$reader->matchToken("then");
			}
			$op_code_name = $this->readString($reader);
			$reader->matchToken(":");
			$op_code_item = $this->parser->parser_expression->readExpression($reader);
			$items->push(new \BayLang\OpCodes\OpDictPair(new \Runtime\Map([
				"key" => $op_code_name,
				"expression" => $op_code_item,
				"condition" => $condition,
			])));
			if ($reader->nextToken() == "," || $reader->nextToken() != "}")
			{
				$reader->matchToken(",");
			}
			if ($is_preprocessor)
			{
				$reader->matchToken("#endif");
			}
		}
		$reader->matchToken("}");
		return new \BayLang\OpCodes\OpDict(new \Runtime\Map([
			"items" => $items,
			"caret_start" => $caret_start,
			"caret_end" => $reader->caret(),
		]));
	}
	
	
	/**
	 * Read new instance
	 */
	function readNew($reader)
	{
		$caret_start = $reader->start();
		$reader->matchToken("new");
		$pattern = $this->readTypeIdentifier($reader);
		$args = new \Runtime\Vector();
		if ($reader->nextToken() == "{")
		{
			$item = $this->readDict($reader);
			$args = new \Runtime\Vector($item);
		}
		else if ($reader->nextToken() == "(")
		{
			$args = $this->parser->parser_function->readFunctionArgs($reader);
		}
		return new \BayLang\OpCodes\OpNew(new \Runtime\Map([
			"args" => $args,
			"pattern" => $pattern,
			"caret_start" => $caret_start,
			"caret_end" => $reader->caret(),
		]));
	}
	
	
	/**
	 * Read classof
	 */
	function readClassOf($reader)
	{
		$caret_start = $reader->start();
		$reader->matchToken("classof");
		$entity_name = $this->readEntityName($reader);
		return new \BayLang\OpCodes\OpClassOf(new \Runtime\Map([
			"entity_name" => $entity_name,
			"caret_start" => $caret_start,
			"caret_end" => $reader->caret(),
		]));
	}
	
	
	/**
	 * Read item
	 */
	function readItem($reader)
	{
		$next_token = $reader->nextToken();
		if (\BayLang\Caret::isNumber($next_token))
		{
			return $this->readNumber($reader);
		}
		else if ($next_token == "'" || $next_token == "\"")
		{
			return $this->readString($reader);
		}
		else if ($next_token == "new")
		{
			return $this->readNew($reader);
		}
		else if ($next_token == "classof")
		{
			return $this->readClassOf($reader);
		}
		return $this->readIdentifier($reader);
	}
	
	
	/**
	 * Read dynamic
	 */
	function readDynamic($reader, $read_function = true)
	{
		$caret_start = $reader->start();
		/* Read await */
		$is_await = false;
		if ($reader->nextToken() == "await")
		{
			$is_await = true;
			$reader->matchToken("await");
		}
		$item = $this->readItem($reader);
		if ($reader->nextToken() == "." && $item instanceof \BayLang\OpCodes\OpIdentifier)
		{
			$this->parser->useVariable($item);
			$this->parser->findVariable($item);
		}
		$operations = new \Runtime\Vector(".", "::", "[", "(");
		while (!$reader->eof() && $operations->indexOf($reader->nextToken()) >= 0)
		{
			$next_token = $reader->nextToken();
			if ($next_token == "." || $next_token == "::")
			{
				if ($next_token == "::" && $item instanceof \BayLang\OpCodes\OpIdentifier)
				{
					if (!$this->parser->vars->has($item->value))
					{
						$item = new \BayLang\OpCodes\OpTypeIdentifier(new \Runtime\Map([
							"entity_name" => new \BayLang\OpCodes\OpEntityName(new \Runtime\Map([
								"items" => new \Runtime\Vector($item),
								"caret_start" => $item->caret_start,
								"caret_end" => $item->caret_end,
							])),
							"caret_start" => $item->caret_start,
							"caret_end" => $item->caret_end,
						]));
					}
				}
				$reader->matchToken($next_token);
				$op_code_item = $this->readIdentifier($reader);
				$item = new \BayLang\OpCodes\OpAttr(new \Runtime\Map([
					"kind" => $next_token == "." ? \BayLang\OpCodes\OpAttr::KIND_ATTR : \BayLang\OpCodes\OpAttr::KIND_STATIC,
					"prev" => $item,
					"next" => $op_code_item,
					"caret_start" => $caret_start,
					"caret_end" => $reader->caret(),
				]));
			}
			else if ($next_token == "(")
			{
				if ($read_function)
				{
					$item = $this->parser->parser_function->readCallFunction($reader, $item);
					$item->is_await = $is_await;
					$item->caret_start = $caret_start;
				}
				else
				{
					break;
				}
			}
			else if ($next_token == "[")
			{
				$op_code_item = $this->readCollection($reader);
				$item = new \BayLang\OpCodes\OpAttr(new \Runtime\Map([
					"kind" => \BayLang\OpCodes\OpAttr::KIND_DYNAMIC,
					"prev" => $item,
					"next" => $op_code_item,
					"caret_start" => $caret_start,
					"caret_end" => $reader->caret(),
				]));
			}
		}
		return $item;
	}
	
	
	/* ========= Class init functions ========= */
	function _init()
	{
		parent::_init();
		$this->parser = null;
	}
	static function getClassName(){ return "BayLang.LangBay.ParserBayBase"; }
	static function getMethodsList(){ return null; }
	static function getMethodInfoByName($field_name){ return null; }
}