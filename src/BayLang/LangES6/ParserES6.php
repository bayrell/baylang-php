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

use Runtime\BaseStruct;
use Runtime\Reference;
use BayLang\Caret;
use BayLang\CoreParser;
use BayLang\TokenReader;
use BayLang\LangES6\ParserES6Base;
use BayLang\LangES6\ParserES6Expression;
use BayLang\LangES6\ParserES6Function;
use BayLang\LangES6\ParserES6Html;
use BayLang\LangES6\ParserES6Operator;
use BayLang\LangES6\ParserES6Preprocessor;
use BayLang\LangES6\ParserES6Program;
use BayLang\OpCodes\BaseOpCode;
use BayLang\OpCodes\OpIdentifier;


class ParserES6 extends \BayLang\CoreParser
{
	/* Parsers */
	var $parser_base;
	var $parser_expression;
	var $parser_function;
	var $parser_operator;
	var $parser_program;
	
	
	/**
	 * Returns true if registered variable
	 */
	function isRegisteredVariable($name)
	{
		$variables = new \Runtime\Vector(
			"console",
			"document",
			"window",
			"String",
		);
		if ($variables->indexOf($name) == -1) return false;
		return true;
	}
	
	
	/**
	 * Find variable
	 */
	function findVariable($op_code)
	{
		$name = $op_code->value;
		if ($this->vars->has($name)) return;
		if ($this->isRegisteredVariable($name)) return;
		throw $op_code->caret_end->error("Unknown identifier '" . $name . "'");
	}
	
	
	/**
	 * Parse file and convert to BaseOpCode
	 */
	function parse()
	{
		$reader = $this->createReader();
		return $this->parser_program->parse($reader);
	}
	
	
	/* ========= Class init functions ========= */
	function _init()
	{
		parent::_init();
		$this->parser_base = new \BayLang\LangES6\ParserES6Base($this);
		$this->parser_expression = new \BayLang\LangES6\ParserES6Expression($this);
		$this->parser_function = new \BayLang\LangES6\ParserES6Function($this);
		$this->parser_operator = new \BayLang\LangES6\ParserES6Operator($this);
		$this->parser_program = new \BayLang\LangES6\ParserES6Program($this);
	}
	static function getClassName(){ return "BayLang.LangES6.ParserES6"; }
	static function getMethodsList(){ return null; }
	static function getMethodInfoByName($field_name){ return null; }
}