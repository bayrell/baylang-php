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
namespace BayLang\LangPHP;

use Runtime\re;
use BayLang\CoreTranslator;
use BayLang\SaveOpCode;
use BayLang\TranslatorHelper;
use BayLang\LangPHP\TranslatorPHPExpression;
use BayLang\LangPHP\TranslatorPHPHtml;
use BayLang\LangPHP\TranslatorPHPOperator;
use BayLang\LangPHP\TranslatorPHPProgram;
use BayLang\LangStyle\TranslatorStyle;
use BayLang\OpCodes\BaseOpCode;


class TranslatorPHP extends \BayLang\CoreTranslator
{
	/* Translators */
	var $helper;
	var $style;
	var $expression;
	var $operator;
	var $program;
	var $html;
	
	
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
		$this->preprocessor_flags->set("BACKEND", true);
		$this->preprocessor_flags->set("PHP", true);
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
		$s = \Runtime\rs::replace("\$", "\\\$", $s);
		return "\"" . $s . "\"";
	}
	
	
	/**
	 * Returns module name
	 */
	function getModuleName($module_name)
	{
		return \Runtime\rs::replace(".", "\\", $module_name);
	}
	
	
	/**
	 * Translate BaseOpCode
	 */
	function translate($op_code)
	{
		$content = new \Runtime\Vector();
		$content->push("<?php");
		$content->push($this->newLine());
		$this->program->translate($op_code, $content);
		return \Runtime\rs::join("", $content);
	}
	
	
	/* ========= Class init functions ========= */
	function _init()
	{
		parent::_init();
		$this->helper = new \BayLang\TranslatorHelper($this);
		$this->style = new \BayLang\LangStyle\TranslatorStyle($this);
		$this->expression = new \BayLang\LangPHP\TranslatorPHPExpression($this);
		$this->operator = new \BayLang\LangPHP\TranslatorPHPOperator($this);
		$this->program = new \BayLang\LangPHP\TranslatorPHPProgram($this);
		$this->html = new \BayLang\LangPHP\TranslatorPHPHtml($this);
	}
	static function getClassName(){ return "BayLang.LangPHP.TranslatorPHP"; }
	static function getMethodsList(){ return null; }
	static function getMethodInfoByName($field_name){ return null; }
}