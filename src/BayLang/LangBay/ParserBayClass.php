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
use BayLang\TokenReader;
use BayLang\LangBay\ParserBay;
use BayLang\OpCodes\BaseOpCode;
use BayLang\OpCodes\OpAnnotation;
use BayLang\OpCodes\OpAssign;
use BayLang\OpCodes\OpCall;
use BayLang\OpCodes\OpComment;
use BayLang\OpCodes\OpDeclareClass;
use BayLang\OpCodes\OpDeclareFunction;
use BayLang\OpCodes\OpEntityName;
use BayLang\OpCodes\OpFlags;
use BayLang\OpCodes\OpIdentifier;
use BayLang\OpCodes\OpItems;
use BayLang\OpCodes\OpPreprocessorIfDef;
use BayLang\OpCodes\OpTypeIdentifier;


class ParserBayClass extends \Runtime\BaseObject
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
	 * Read flags
	 */
	function readFlags($reader)
	{
		$caret_start = $reader->start();
		$items = new \Runtime\Map();
		$current_flags = \BayLang\OpCodes\OpFlags::getFlags();
		while (!$reader->eof() && $current_flags->indexOf($reader->nextToken()) >= 0)
		{
			$flag = $reader->readToken();
			$items->set($flag, true);
		}
		return new \BayLang\OpCodes\OpFlags(new \Runtime\Map([
			"items" => $items,
			"caret_start" => $caret_start,
			"caret_end" => $reader->caret(),
		]));
	}
	
	
	/**
	 * Read class item
	 */
	function readItem($reader)
	{
		$next_token = $reader->nextTokenComments();
		/* Comment */
		if ($next_token == "/")
		{
			return $this->parser->parser_base->readComment($reader);
		}
		else if ($next_token == "@")
		{
			return $this->parser->parser_program->readAnnotation($reader);
		}
		else if ($next_token == "#switch" || $next_token == "#ifcode" || $next_token == "#ifdef")
		{
			return $this->parser->parser_preprocessor->readPreprocessor($reader, \BayLang\OpCodes\OpPreprocessorIfDef::KIND_CLASS_BODY);
		}
		/* Read flags */
		$flags = $this->readFlags($reader);
		/* Try to read call function */
		$op_code = $this->parser->parser_function->tryReadFunction($reader);
		/* Assign operator */
		if (!$op_code)
		{
			$op_code = $this->parser->parser_operator->readAssign($reader);
		}
		$op_code->flags = $flags;
		$op_code->caret_start = $flags->caret_start;
		return $op_code;
	}
	
	
	/**
	 * Process items
	 */
	function processItems($items)
	{
		$annotations = new \Runtime\Vector();
		$result = new \Runtime\Vector();
		$comments = new \Runtime\Vector();
		for ($i = 0; $i < $items->count(); $i++)
		{
			$item = $items->get($i);
			if ($item instanceof \BayLang\OpCodes\OpAnnotation)
			{
				$annotations->push($item);
			}
			else if ($item instanceof \BayLang\OpCodes\OpAssign)
			{
				$result->appendItems($comments);
				$result->push($item);
				$annotations = new \Runtime\Vector();
				$comments = new \Runtime\Vector();
			}
			else if ($item instanceof \BayLang\OpCodes\OpComment)
			{
				$comments->push($item);
			}
			else if ($item instanceof \BayLang\OpCodes\OpDeclareFunction)
			{
				$item->annotations = $annotations;
				$item->comments = $comments;
				/*
				int line = item.caret_start.y - 1;
				for (int j=comments.count() - 1; j>=0; j--)
				{
				OpComment op_code = comments.get(j);
				if (op_code.caret_end.y == line)
				{
				item.comments.push(op_code);
				comments.remove(j);
				line = op_code.caret_start.y - 1;
				}
				}
				item.comments.reverse();
				*/
				$result->push($item);
				$annotations = new \Runtime\Vector();
				$comments = new \Runtime\Vector();
			}
			else
			{
				$result->push($item);
			}
		}
		return $result;
	}
	
	
	/**
	 * Read class body
	 */
	function readBody($reader, $match_brackets = true, $end_tag = "")
	{
		$caret_start = $reader->start();
		$items = new \Runtime\Vector();
		if ($match_brackets) $reader->matchToken("{");
		/* Read class */
		while (!$reader->eof() && $reader->nextToken() != "}" && $reader->nextToken() != "#endswitch" && $reader->nextToken() != "#case" && $reader->nextToken() != "#endif" && $reader->nextToken() != $end_tag)
		{
			$op_code = $this->readItem($reader);
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
		if ($match_brackets) $reader->matchToken("}");
		/* Process items */
		$items = $this->processItems($items);
		return new \BayLang\OpCodes\OpItems(new \Runtime\Map([
			"items" => $items,
			"caret_start" => $caret_start,
			"caret_end" => $reader->caret(),
		]));
	}
	
	
	/**
	 * Read class
	 */
	function readClass($reader)
	{
		$caret_start = $reader->start();
		/* Read abstract */
		$is_abstract = false;
		if ($reader->nextToken() == "abstract")
		{
			$is_abstract = true;
			$reader->matchToken("abstract");
		}
		/* Read class or interface */
		$is_interface = false;
		if ($reader->nextToken() == "interface")
		{
			$is_interface = true;
			$reader->matchToken("interface");
		}
		else $reader->matchToken("class");
		$class_extends = null;
		$class_implements = new \Runtime\Vector();
		/* Read class name */
		$class_name = $this->parser->parser_base->readTypeIdentifier($reader, false);
		$this->parser->uses->set($class_name->entity_name->getName(), $this->parser->current_namespace_name . "." . $class_name->entity_name->getName());
		/* Add generics */
		$save_uses = $this->parser->uses->copy();
		$this->parser->addGenericUse($class_name->generics);
		/* Read extends */
		if ($reader->nextToken() == "extends")
		{
			$reader->readToken();
			$class_extends = $this->parser->parser_base->readTypeIdentifier($reader);
		}
		if ($reader->nextToken() == "implements")
		{
			$reader->readToken();
			while (!$reader->eof() && $reader->nextToken() != "{" && $reader->nextToken() != ";")
			{
				$op_code_item = $this->parser->parser_base->readTypeIdentifier($reader);
				$class_implements->push($op_code_item);
				if ($reader->nextToken() != "{" && $reader->nextToken() != ";")
				{
					$reader->matchToken(",");
				}
			}
		}
		/*
		if (class_extends == null)
		{
		class_extends = new OpTypeIdentifier
		{
		"entity_name": new OpEntityName
		{
		"items":
		[
		new OpIdentifier
		{
		"value": "BaseObject",
		}
		]
		}
		};
		}
		*/
		$this->parser->current_class = new \BayLang\OpCodes\OpDeclareClass(new \Runtime\Map([
			"kind" => $is_interface ? \BayLang\OpCodes\OpDeclareClass::KIND_INTERFACE : \BayLang\OpCodes\OpDeclareClass::KIND_CLASS,
			"name" => $class_name,
			"is_abstract" => $is_abstract,
			"class_extends" => $class_extends,
			"class_implements" => $class_implements,
			"caret_start" => $caret_start,
			"caret_end" => $reader->caret(),
		]));
		if (!$is_abstract)
		{
			$this->parser->current_class->content = $this->readBody($reader);
		}
		else
		{
			if ($reader->nextToken() == "{")
			{
				$reader->matchToken("{");
				$reader->matchToken("}");
			}
			else
			{
				$reader->matchToken(";");
			}
		}
		$this->parser->uses = $save_uses;
		return $this->parser->current_class;
	}
	
	
	/* ========= Class init functions ========= */
	function _init()
	{
		parent::_init();
		$this->parser = null;
	}
	static function getClassName(){ return "BayLang.LangBay.ParserBayClass"; }
	static function getMethodsList(){ return null; }
	static function getMethodInfoByName($field_name){ return null; }
}