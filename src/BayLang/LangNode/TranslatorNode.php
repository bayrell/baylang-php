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
namespace BayLang\LangNode;

use Runtime\BaseStruct;
use BayLang\CoreTranslator;
use BayLang\SaveOpCode;
use BayLang\LangES6\TranslatorES6;
use BayLang\LangNode\TranslatorNodeExpression;
use BayLang\LangNode\TranslatorNodeProgram;
use BayLang\OpCodes\BaseOpCode;


class TranslatorNode extends \BayLang\LangES6\TranslatorES6
{
	var $expression;
	var $program;
	
	/* Flags */
	var $use_modules;
	var $use_module_name;
	var $use_window;
	
	
	/**
	 * Constructor
	 */
	function __construct()
	{
		parent::__construct();
		$this->preprocessor_flags->set("BACKEND", true);
		$this->preprocessor_flags->set("ES6", false);
		$this->preprocessor_flags->set("NODEJS", true);
	}
	
	
	/**
	 * Set use modules
	 */
	function setUseModules($use_modules = null)
	{
		if ($use_modules == null) $use_modules = new \Runtime\Map();
		$save_use_modules = $this->use_modules->get("local")->copy();
		$this->use_modules->set("local", $use_modules);
		return $save_use_modules;
	}
	
	
	/**
	 * Use module
	 */
	function useModule($module_name)
	{
		$is_global = false;
		if ($module_name == $this->current_class_name) return $module_name;
		if ($module_name == "Runtime.rtl" || $module_name == "Runtime.rs")
		{
			$is_global = true;
		}
		$arr = \Runtime\rs::split(".", $module_name);
		$alias_name = $arr->last();
		if ($this->uses->has($alias_name))
		{
			$modules = $this->use_modules->get($is_global ? "global" : "local");
			$modules->set($alias_name, $module_name);
			return $alias_name;
		}
		return $module_name;
	}
	
	
	/**
	 * Add use modules
	 */
	function addUseModules($result, $is_multiline = true, $use_modules = null)
	{
		if ($use_modules == null) $use_modules = $this->use_modules->get("local");
		$keys = \Runtime\rtl::list($use_modules->keys());
		for ($i = 0; $i < $keys->count(); $i++)
		{
			$alias_name = $keys->get($i);
			$module_name = $use_modules->get($alias_name);
			if ($is_multiline) $result->push($this->newLine());
			$result->push("const " . $alias_name . " = use(" . $this->toString($module_name) . ");");
		}
	}
	
	
	/**
	 * Translate BaseOpCode
	 */
	function translate($op_code)
	{
		$result = new \Runtime\Vector();
		$result->push("\"use strict;\"");
		$result->push($this->newLine());
		$result->push("const use = require('bay-lang').use;");
		/*
		result.push(this.newLine());
		result.push("const {rtl, rs} = use.rtl();");
		*/
		/* Translate program */
		$result1 = new \Runtime\Vector();
		$result1->push($this->newLine());
		$this->program->translate($op_code, $result1);
		/* Add use */
		$this->addUseModules($result, true, $this->use_modules->get("global"));
		$this->addUseModules($result);
		$result->appendItems($result1);
		/* Add export */
		$this->program->addModuleExports($result);
		return \Runtime\rs::join("", $result);
	}
	
	
	/* ========= Class init functions ========= */
	function _init()
	{
		parent::_init();
		$this->expression = new \BayLang\LangNode\TranslatorNodeExpression($this);
		$this->program = new \BayLang\LangNode\TranslatorNodeProgram($this);
		$this->use_modules = new \Runtime\Map([
			"global" => new \Runtime\Map(),
			"local" => new \Runtime\Map(),
		]);
		$this->use_module_name = true;
		$this->use_window = false;
	}
	static function getClassName(){ return "BayLang.LangNode.TranslatorNode"; }
	static function getMethodsList(){ return null; }
	static function getMethodInfoByName($field_name){ return null; }
}