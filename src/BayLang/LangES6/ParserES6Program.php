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
use BayLang\LangES6\ParserES6;
use BayLang\OpCodes\BaseOpCode;
use BayLang\OpCodes\OpModule;


class ParserES6Program extends \Runtime\BaseObject
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
	 * Read module
	 */
	function readModuleItem($reader)
	{
		return $this->parser->parser_operator->readOperator($reader);
	}
	
	
	/**
	 * Parse program
	 */
	function parse($reader)
	{
		$items = new \Runtime\Vector();
		$caret_start = $reader->start();
		/* Read module */
		while (!$reader->eof() && $reader->nextToken() != "")
		{
			$next_token = $reader->nextToken();
			/* Read module item */
			$op_code = $this->readModuleItem($reader);
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
		/* Returns op_code */
		return new \BayLang\OpCodes\OpModule(new \Runtime\Map([
			"caret_start" => $caret_start,
			"caret_end" => $reader->caret(),
			"items" => $items,
		]));
	}
	
	
	/* ========= Class init functions ========= */
	function _init()
	{
		parent::_init();
		$this->parser = null;
	}
	static function getClassName(){ return "BayLang.LangES6.ParserES6Program"; }
	static function getMethodsList(){ return null; }
	static function getMethodInfoByName($field_name){ return null; }
}