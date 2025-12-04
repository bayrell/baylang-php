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
use BayLang\Exceptions\ParserError;
use BayLang\LangBay\ParserBay;
use BayLang\OpCodes\BaseOpCode;
use BayLang\OpCodes\OpAnnotation;
use BayLang\OpCodes\OpAssign;
use BayLang\OpCodes\OpAssignValue;
use BayLang\OpCodes\OpComment;
use BayLang\OpCodes\OpDeclareClass;
use BayLang\OpCodes\OpDeclareFunction;
use BayLang\OpCodes\OpDict;
use BayLang\OpCodes\OpEntityName;
use BayLang\OpCodes\OpFlags;
use BayLang\OpCodes\OpItems;
use BayLang\OpCodes\OpModule;
use BayLang\OpCodes\OpNamespace;
use BayLang\OpCodes\OpPreprocessorIfDef;
use BayLang\OpCodes\OpTypeIdentifier;
use BayLang\OpCodes\OpUse;


class ParserBayProgram extends \Runtime\BaseObject
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
	 * Read namespace
	 */
	function readNamespace($reader)
	{
		$caret_start = $reader->start();
		/* Read module name */
		$reader->matchToken("namespace");
		$entity_name = $this->parser->parser_base->readEntityName($reader);
		$module_name = $entity_name->getName();
		/* Create op_code */
		$op_code = new \BayLang\OpCodes\OpNamespace(new \Runtime\Map([
			"caret_start" => $caret_start,
			"caret_end" => $reader->caret(),
			"name" => $module_name,
		]));
		/* Set current namespace */
		$this->parser->current_namespace = $op_code;
		$this->parser->current_namespace_name = $module_name;
		/* Returns op_code */
		return $op_code;
	}
	
	
	/**
	 * Read use
	 */
	function readUse($reader)
	{
		$look = null;
		$name = null;
		$caret_start = $reader->start();
		$alias = "";
		/* Read module name */
		$reader->matchToken("use");
		$module_name = $this->parser->parser_base->readEntityName($reader);
		/* Read alias */
		if ($reader->nextToken() == "as")
		{
			$reader->readToken();
			$alias = $reader->readToken();
		}
		else
		{
			$alias = $module_name->items->last()->value;
		}
		/* Add use */
		$this->parser->uses->set($alias, $module_name->getName());
		return new \BayLang\OpCodes\OpUse(new \Runtime\Map([
			"name" => $module_name->getName(),
			"alias" => $alias,
			"caret_start" => $caret_start,
			"caret_end" => $reader->caret(),
		]));
	}
	
	
	/**
	 * Annotation
	 */
	function readAnnotation($reader)
	{
		$caret_start = $reader->start();
		$reader->matchToken("@");
		$name = $this->parser->parser_base->readTypeIdentifier($reader);
		$params = null;
		if ($reader->nextToken() == "{")
		{
			$params = $this->parser->parser_base->readDict($reader);
		}
		return new \BayLang\OpCodes\OpAnnotation(new \Runtime\Map([
			"name" => $name,
			"params" => $params,
			"caret_start" => $caret_start,
			"caret_end" => $reader->caret(),
		]));
	}
	
	
	/**
	 * Read module
	 */
	function readModuleItem($reader)
	{
		$next_token = $reader->nextTokenComments();
		/* Namespace */
		if ($next_token == "namespace")
		{
			return $this->readNamespace($reader);
		}
		else if ($next_token == "use")
		{
			return $this->readUse($reader);
		}
		else if ($next_token == "@")
		{
			return $this->readAnnotation($reader);
		}
		else if ($next_token == "abstract" || $next_token == "class" || $next_token == "interface")
		{
			return $this->parser->parser_class->readClass($reader);
		}
		else if ($next_token == "#switch" || $next_token == "#ifcode" || $next_token == "#ifdef")
		{
			return $this->parser->parser_preprocessor->readPreprocessor($reader, \BayLang\OpCodes\OpPreprocessorIfDef::KIND_PROGRAM);
		}
		else if ($next_token != "")
		{
			return $this->parser->parser_operator->readOperator($reader);
		}
		return null;
	}
	
	
	/**
	 * Parse program
	 */
	function parse($reader)
	{
		$annotations = new \Runtime\Vector();
		$items = new \Runtime\Vector();
		$caret_start = $reader->start();
		/* Read module */
		while (!$reader->eof() && $reader->nextToken() != "" && $reader->nextToken() != "#endswitch" && $reader->nextToken() != "#case" && $reader->nextToken() != "#endif")
		{
			$next_token = $reader->nextToken();
			/* Read module item */
			$op_code = $this->readModuleItem($reader);
			if ($op_code instanceof \BayLang\OpCodes\OpAnnotation)
			{
				$annotations->push($op_code);
			}
			else if ($op_code)
			{
				if ($op_code instanceof \BayLang\OpCodes\OpDeclareClass)
				{
					$op_code->annotations = $annotations;
					$annotations = new \Runtime\Vector();
				}
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
	static function getClassName(){ return "BayLang.LangBay.ParserBayProgram"; }
	static function getMethodsList(){ return null; }
	static function getMethodInfoByName($field_name){ return null; }
}