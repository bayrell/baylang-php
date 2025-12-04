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
use BayLang\Exceptions\ParserEOF;
use BayLang\Exceptions\ParserError;
use BayLang\Exceptions\ParserExpected;
use BayLang\LangES6\ParserES6;
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


class ParserES6Base extends \Runtime\BaseObject
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
		$caret_start = $reader->start();
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
		$caret_start = $reader->start();
		$str_char = $reader->readToken();
		/* Read begin coment */
		$reader->matchToken("/");
		$reader->matchToken("*");
		/* Read comment value */
		$caret = $reader->caret();
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
	function readTypeIdentifier($reader, $read_generic = true)
	{
		$caret_start = $reader->start();
		/* Read var */
		if ($reader->nextToken() == "var")
		{
			$reader->readToken();
			$caret_end = $reader->caret();
			return new \BayLang\OpCodes\OpTypeIdentifier(new \Runtime\Map([
				"entity_name" => new \BayLang\OpCodes\OpEntityName(new \Runtime\Map([
					"items" => new \Runtime\Vector(
						new \BayLang\OpCodes\OpIdentifier(new \Runtime\Map([
							"value" => "var",
							"caret_start" => $caret_start,
							"caret_end" => $caret_end,
						])),
					),
					"caret_start" => $caret_start,
					"caret_end" => $caret_end,
				])),
				"caret_start" => $caret_start,
				"caret_end" => $caret_end,
			]));
		}
		/* Read entity name */
		$entity_name = $this->readEntityName($reader);
		return new \BayLang\OpCodes\OpTypeIdentifier(new \Runtime\Map([
			"entity_name" => $entity_name,
			"caret_start" => $caret_start,
			"caret_end" => $reader->caret(),
		]));
	}
	
	
	/**
	 * Read collection
	 */
	function readCollection($reader){}
	
	
	/**
	 * Read collection
	 */
	function readDict($reader){}
	
	
	/**
	 * Read new instance
	 */
	function readNew($reader){}
	
	
	/**
	 * Read item
	 */
	function readItem($reader)
	{
		if (\BayLang\Caret::isNumber($reader->nextToken()))
		{
			return $this->readNumber($reader);
		}
		else if ($reader->nextToken() == "'" || $reader->nextToken() == "\"")
		{
			return $this->readString($reader);
		}
		return $this->readIdentifier($reader);
	}
	
	
	/* ========= Class init functions ========= */
	function _init()
	{
		parent::_init();
		$this->parser = null;
	}
	static function getClassName(){ return "BayLang.LangES6.ParserES6Base"; }
	static function getMethodsList(){ return null; }
	static function getMethodInfoByName($field_name){ return null; }
}