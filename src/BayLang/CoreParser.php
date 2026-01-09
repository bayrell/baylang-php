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
namespace BayLang;

use Runtime\BaseObject;
use Runtime\lib;
use Runtime\Reference;
use Runtime\Interfaces\ContextInterface;
use BayLang\Caret;
use BayLang\SaveOpCode;
use BayLang\TokenReader;
use BayLang\Exceptions\ParserExpected;
use BayLang\OpCodes\BaseOpCode;
use BayLang\OpCodes\OpDeclareClass;
use BayLang\OpCodes\OpIdentifier;
use BayLang\OpCodes\OpNamespace;
use BayLang\OpCodes\OpTypeIdentifier;


class CoreParser extends \Runtime\BaseObject
{
	var $file_name;
	var $content;
	var $content_size;
	var $tab_size;
	var $function_level;
	var $find_variable;
	var $vars;
	var $vars_uses;
	var $uses;
	var $current_namespace;
	var $current_class;
	var $current_namespace_name;
	
	
	/**
	 * Save vars
	 */
	function saveVars(){ return $this->vars->copy(); }
	
	
	/**
	 * Restore vars
	 */
	function restoreVars($vars)
	{
		$this->vars = $vars;
	}
	
	
	/**
	 * Add variable
	 */
	function addVariable($op_code, $pattern)
	{
		$name = $op_code->value;
		$this->vars->set($name, new \Runtime\Map([
			"pattern" => $pattern,
			"function_level" => $this->function_level,
		]));
	}
	
	
	/**
	 * Use variable
	 */
	function useVariable($op_code)
	{
		$variable = $this->vars->get($op_code->value);
		if (!($variable instanceof \Runtime\Map) || $variable->get("function_level") >= $this->function_level)
		{
			return;
		}
		if (!$this->vars_uses->has($op_code->value))
		{
			$this->vars_uses->set($op_code->value, $op_code);
		}
	}
	
	
	/**
	 * Set content
	 */
	function setContent($content)
	{
		$this->content = $content;
		$this->content_size = \Runtime\rs::strlen($content);
		return $this;
	}
	
	
	/**
	 * Create reader
	 */
	function createReader()
	{
		$reader = new \BayLang\TokenReader();
		$reader->init(new \BayLang\Caret(new \Runtime\Map([
			"content" => new \Runtime\Reference($this->content),
			"tab_size" => $this->tab_size,
		])));
		return $reader;
	}
	
	
	/**
	 * Parse file and convert to BaseOpCode
	 */
	function parse()
	{
		return null;
	}
	
	
	/* ========= Class init functions ========= */
	function _init()
	{
		parent::_init();
		$this->file_name = "";
		$this->content = "";
		$this->content_size = 0;
		$this->tab_size = 4;
		$this->function_level = 0;
		$this->find_variable = true;
		$this->vars = new \Runtime\Map();
		$this->vars_uses = new \Runtime\Map();
		$this->uses = new \Runtime\Map();
		$this->current_namespace = null;
		$this->current_class = null;
		$this->current_namespace_name = "";
	}
	static function getClassName(){ return "BayLang.CoreParser"; }
	static function getMethodsList(){ return null; }
	static function getMethodInfoByName($field_name){ return null; }
}