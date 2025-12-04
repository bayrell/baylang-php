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

use Runtime\re;
use Runtime\BaseStruct;
use BayLang\CoreTranslator;
use BayLang\SaveOpCode;
use BayLang\TranslatorHelper;
use BayLang\LangES6\AsyncAwait;
use BayLang\LangES6\TranslatorES6AsyncAwait;
use BayLang\LangES6\TranslatorES6Expression;
use BayLang\LangES6\TranslatorES6Html;
use BayLang\LangES6\TranslatorES6Operator;
use BayLang\LangES6\TranslatorES6Program;
use BayLang\LangStyle\TranslatorStyle;
use BayLang\OpCodes\BaseOpCode;


class TranslatorES6 extends \BayLang\CoreTranslator
{
	/* Translators */
	var $style;
	var $expression;
	var $operator;
	var $program;
	var $html;
	var $helper;
	
	/* Flags */
	var $use_module_name;
	var $use_window;
	
	/**
	 * Constructor
	 */
	function __construct()
	{
		parent::__construct();
		$this->uses->set("rtl", "Runtime.rtl");
		$this->uses->set("rs", "Runtime.rs");
		$this->uses->set("BaseObject", "Runtime.BaseObject");
		$this->uses->set("Map", "Runtime.Map");
		$this->uses->set("Vector", "Runtime.Vector");
		$this->preprocessor_flags->set("FRONTEND", true);
		$this->preprocessor_flags->set("ES6", true);
		$this->preprocessor_flags->set("JAVASCRIPT", true);
	}
	
	
	/**
	 * Returns string
	 */
	function toString($s)
	{
		$s = \Runtime\rs::replace("\\", "\\\\", $s);
		$s = \Runtime\rs::replace("\"", "\\\"", $s);
		$s = \Runtime\rs::replace("\n", "\\n", $s);
		$s = \Runtime\rs::replace("\r", "\\r", $s);
		$s = \Runtime\rs::replace("\t", "\\t", $s);
		return "\"" . $s . "\"";
	}
	
	
	/**
	 * Set use modules
	 */
	function setUseModules($use_modules = null)
	{
		return null;
	}
	
	
	/**
	 * Use module
	 */
	function useModule($module_name)
	{
		if ($module_name == "rtl") return "Runtime.rtl";
		if ($module_name == "rs") return "Runtime.rs";
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
	 * Add use modules
	 */
	function addUseModules($result){}
	
	
	/**
	 * Translate BaseOpCode
	 */
	function translate($op_code)
	{
		$content = new \Runtime\Vector();
		$content->push("\"use strict;\"");
		$content->push($this->newLine());
		$this->program->translate($op_code, $content);
		return \Runtime\rs::join("", $content);
	}
	
	
	/* ========= Class init functions ========= */
	function _init()
	{
		parent::_init();
		$this->style = new \BayLang\LangStyle\TranslatorStyle($this);
		$this->expression = new \BayLang\LangES6\TranslatorES6Expression($this);
		$this->operator = new \BayLang\LangES6\TranslatorES6Operator($this);
		$this->program = new \BayLang\LangES6\TranslatorES6Program($this);
		$this->html = new \BayLang\LangES6\TranslatorES6Html($this);
		$this->helper = new \BayLang\TranslatorHelper($this);
		$this->use_module_name = false;
		$this->use_window = true;
	}
	static function getClassName(){ return "BayLang.LangES6.TranslatorES6"; }
	static function getMethodsList(){ return null; }
	static function getMethodInfoByName($field_name){ return null; }
}