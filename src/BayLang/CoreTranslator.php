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

use Runtime\lib;
use Runtime\BaseObject;
use BayLang\SaveOpCode;
use BayLang\LangBay\TranslatorBay;
use BayLang\OpCodes\BaseOpCode;
use BayLang\OpCodes\OpDeclareClass;
use BayLang\OpCodes\OpDeclareFunction;
use BayLang\OpCodes\OpModule;


class CoreTranslator extends \Runtime\BaseObject
{
	/* State */
	var $opcode_level;
	var $indent_level;
	var $last_semicolon;
	var $indent;
	var $crlf;
	var $preprocessor_flags;
	var $vars;
	var $uses;
	var $class_items;
	var $current_class;
	var $current_function;
	var $class_function;
	var $current_module;
	var $html_var_names;
	var $save_var;
	var $var_inc;
	var $component_hash_inc;
	var $current_block;
	var $current_class_name;
	var $current_namespace_name;
	var $html_kind;
	var $parent_class_name;
	var $allow_multiline;
	var $is_html_props;
	var $is_operator_block;
	
	
	/**
	 * Constructor
	 */
	function __construct()
	{
		parent::__construct();
		$this->uses->set("Collection", "Runtime.Collection");
		$this->uses->set("Dict", "Runtime.Dict");
	}
	
	
	/**
	 * Use module
	 */
	function useModule($module_name)
	{
		return $module_name;
	}
	
	
	/**
	 * Returns module name
	 */
	function getUseModule($module_name)
	{
		if ($this->uses->get($module_name))
		{
			return $this->useModule($this->uses->get($module_name));
		}
		return $this->useModule($module_name);
	}
	
	
	/**
	 * Get full entity name
	 */
	function getFullName($class_name)
	{
		if ($this->uses->has($class_name))
		{
			return $this->uses->get($class_name);
		}
		else
		{
			if (\Runtime\rs::indexOf($class_name, ".") >= 0) return $class_name;
			return $this->current_namespace_name . "." . $class_name;
		}
	}
	
	
	/**
	 * Set flag
	 */
	function setFlag($flag_name, $value)
	{
		$this->preprocessor_flags->set($flag_name, $value);
		return $this;
	}
	
	
	/**
	 * Increment indent level
	 */
	function levelInc()
	{
		$this->indent_level = $this->indent_level + 1;
	}
	
	
	/**
	 * Decrease indent level
	 */
	function levelDec()
	{
		$this->indent_level = $this->indent_level - 1;
	}
	
	
	/**
	 * Increment component hash
	 */
	function componentHashInc()
	{
		$this->component_hash_inc = $this->component_hash_inc + 1;
		return $this->component_hash_inc - 1;
	}
	
	
	/**
	 * Increment variable
	 */
	function varInc()
	{
		$this->var_inc = $this->var_inc + 1;
		return "__v" . $this->var_inc - 1;
	}
	
	
	/**
	 * Decrement variable
	 */
	function varDec()
	{
		$this->var_inc = $this->var_inc - 1;
	}
	
	
	/**
	 * Save var
	 */
	function saveVar($content)
	{
		$var_name = $this->varInc();
		$this->save_var->push(new \Runtime\Map(["name" => $var_name, "content" => $content]));
		return $var_name;
	}
	
	
	/**
	 * Returns new line with indent
	 */
	function newLine($count = 1)
	{
		if (!$this->allow_multiline) return "";
		if ($count == 1) return $this->crlf . \Runtime\rs::str_repeat($this->indent, $this->indent_level);
		$arr = new \Runtime\Vector();
		for ($i = 0; $i < $count; $i++)
		{
			$arr->push($this->crlf . \Runtime\rs::str_repeat($this->indent, $this->indent_level));
		}
		return \Runtime\rs::join("", $arr);
	}
	
	
	/**
	 * Returns string
	 */
	function toString($s)
	{
		return $s;
	}
	
	
	/**
	 * Translate BaseOpCode
	 */
	function translate($op_code)
	{
		return "";
	}
	
	
	/* ========= Class init functions ========= */
	function _init()
	{
		parent::_init();
		$this->opcode_level = 0;
		$this->indent_level = 0;
		$this->last_semicolon = false;
		$this->indent = "\t";
		$this->crlf = "\n";
		$this->preprocessor_flags = new \Runtime\Map();
		$this->vars = new \Runtime\Map();
		$this->uses = new \Runtime\Map();
		$this->class_items = new \Runtime\Map();
		$this->current_class = null;
		$this->current_function = null;
		$this->class_function = null;
		$this->current_module = null;
		$this->html_var_names = new \Runtime\Vector();
		$this->save_var = new \Runtime\Vector();
		$this->var_inc = 0;
		$this->component_hash_inc = 0;
		$this->current_block = "";
		$this->current_class_name = "";
		$this->current_namespace_name = "";
		$this->html_kind = "";
		$this->parent_class_name = "";
		$this->allow_multiline = true;
		$this->is_html_props = false;
		$this->is_operator_block = false;
	}
	static function getClassName(){ return "BayLang.CoreTranslator"; }
	static function getMethodsList(){ return null; }
	static function getMethodInfoByName($field_name){ return null; }
}