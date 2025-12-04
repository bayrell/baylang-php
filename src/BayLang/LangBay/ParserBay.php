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

use Runtime\BaseStruct;
use Runtime\Reference;
use BayLang\Caret;
use BayLang\CoreParser;
use BayLang\TokenReader;
use BayLang\LangBay\ParserBayBase;
use BayLang\LangBay\ParserBayClass;
use BayLang\LangBay\ParserBayExpression;
use BayLang\LangBay\ParserBayFunction;
use BayLang\LangBay\ParserBayHtml;
use BayLang\LangBay\ParserBayOperator;
use BayLang\LangBay\ParserBayPreprocessor;
use BayLang\LangBay\ParserBayProgram;
use BayLang\OpCodes\BaseOpCode;
use BayLang\OpCodes\OpEntityName;
use BayLang\OpCodes\OpIdentifier;
use BayLang\OpCodes\OpTypeIdentifier;


class ParserBay extends \BayLang\CoreParser
{
	/* Parsers */
	var $parser_base;
	var $parser_class;
	var $parser_expression;
	var $parser_function;
	var $parser_html;
	var $parser_operator;
	var $parser_preprocessor;
	var $parser_program;
	
	
	/**
	 * Returns true if registered variable
	 */
	function isRegisteredVariable($name)
	{
		$variables = new \Runtime\Vector(
			"print",
			"var_dump",
			"rs",
			"rtl",
			"parent",
			"this",
			"@",
			"null",
			"true",
			"false",
			"document",
			"window",
		);
		if ($variables->indexOf($name) == -1) return false;
		return true;
	}
	
	
	/**
	 * Returns true if system type
	 */
	function isSystemType($name)
	{
		$variables = new \Runtime\Vector(
			"var",
			"void",
			"bool",
			"byte",
			"int",
			"char",
			"real",
			"double",
			"string",
			"list",
			"scalar",
			"primitive",
			"html",
			"fn",
			"Error",
			"Object",
			"DateTime",
			"Collection",
			"Dict",
			"Vector",
			"Map",
			"ArrayInterface",
		);
		if ($variables->indexOf($name) == -1) return false;
		return true;
	}
	
	
	/**
	 * Find identifier
	 */
	function findIdentifier($op_code)
	{
		$name = $op_code->value;
		if ($this->vars->has($name) || $this->isRegisteredVariable($name))
		{
			$op_code->kind = \BayLang\OpCodes\OpIdentifier::KIND_VARIABLE;
		}
		if ($this->uses->has($name) || $this->isSystemType($name))
		{
			$op_code->kind = \BayLang\OpCodes\OpIdentifier::KIND_TYPE;
		}
	}
	
	
	/**
	 * Find variable
	 */
	function findVariable($op_code)
	{
		$name = $op_code->value;
		$this->findIdentifier($op_code);
		if ($op_code->kind == \BayLang\OpCodes\OpIdentifier::KIND_VARIABLE) return;
		throw $op_code->caret_end->error("Unknown variable '" . $name . "'");
	}
	
	
	/**
	 * Find type
	 */
	function findType($op_code)
	{
		$name = $op_code->value;
		if ($op_code->kind == \BayLang\OpCodes\OpIdentifier::KIND_TYPE) return;
		throw $op_code->caret_end->error("Unknown type '" . $name . "'");
	}
	
	
	/**
	 * Find entity
	 */
	function findEntity($op_code)
	{
		/* Find name */
		if ($op_code->items->count() != 1) return;
		$op_code_item = $op_code->items->get(0);
		if ($this->uses->has($op_code_item->value)) return;
		if ($this->isSystemType($op_code_item->value)) return;
		throw $op_code->caret_end->error("Unknown identifier '" . $op_code_item->value . "'");
	}
	
	
	/**
	 * Add use
	 */
	function addGenericUse($items)
	{
		if ($items && $items->count() > 0)
		{
			for ($i = 0; $i < $items->count(); $i++)
			{
				$item = $items->get($i);
				$this->uses->set($item->entity_name->getName(), $item);
				$this->addGenericUse($item->generics);
			}
		}
	}
	
	
	/**
	 * Parse file and convert to BaseOpCode
	 */
	function parse()
	{
		$reader = $this->createReader();
		if ($reader->nextToken() == "<")
		{
			return $this->parser_html->parse($reader);
		}
		return $this->parser_program->parse($reader);
	}
	
	
	/* ========= Class init functions ========= */
	function _init()
	{
		parent::_init();
		$this->parser_base = new \BayLang\LangBay\ParserBayBase($this);
		$this->parser_class = new \BayLang\LangBay\ParserBayClass($this);
		$this->parser_expression = new \BayLang\LangBay\ParserBayExpression($this);
		$this->parser_function = new \BayLang\LangBay\ParserBayFunction($this);
		$this->parser_html = new \BayLang\LangBay\ParserBayHtml($this);
		$this->parser_operator = new \BayLang\LangBay\ParserBayOperator($this);
		$this->parser_preprocessor = new \BayLang\LangBay\ParserBayPreprocessor($this);
		$this->parser_program = new \BayLang\LangBay\ParserBayProgram($this);
	}
	static function getClassName(){ return "BayLang.LangBay.ParserBay"; }
	static function getMethodsList(){ return null; }
	static function getMethodInfoByName($field_name){ return null; }
}