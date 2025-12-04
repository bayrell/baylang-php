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

use Runtime\re;
use Runtime\BaseObject;
use BayLang\CoreTranslator;
use BayLang\OpCodes\BaseOpCode;
use BayLang\LangBay\TranslatorBayExpression;
use BayLang\LangBay\TranslatorBayHtml;
use BayLang\LangBay\TranslatorBayOperator;
use BayLang\LangBay\TranslatorBayProgram;


class TranslatorBay extends \BayLang\CoreTranslator
{
	/* Translators */
	var $expression;
	var $operator;
	var $program;
	var $html;
	
	
	/**
	 * Returns string
	 */
	function toString($s)
	{
		$s = \Runtime\re::replace("\\\\", "\\\\", $s);
		$s = \Runtime\re::replace("\"", "\\\"", $s);
		$s = \Runtime\re::replace("\n", "\\n", $s);
		$s = \Runtime\re::replace("\r", "\\r", $s);
		$s = \Runtime\re::replace("\t", "\\t", $s);
		return "\"" . $s . "\"";
	}
	
	
	/**
	 * Translate BaseOpCode
	 */
	function translate($op_code)
	{
		$content = new \Runtime\Vector();
		if ($op_code->is_component)
		{
			$this->html->translate($op_code, $content);
		}
		else
		{
			$this->program->translate($op_code, $content);
		}
		return \Runtime\rs::join("", $content);
	}
	
	
	/* ========= Class init functions ========= */
	function _init()
	{
		parent::_init();
		$this->expression = new \BayLang\LangBay\TranslatorBayExpression($this);
		$this->operator = new \BayLang\LangBay\TranslatorBayOperator($this);
		$this->program = new \BayLang\LangBay\TranslatorBayProgram($this);
		$this->html = new \BayLang\LangBay\TranslatorBayHtml($this);
	}
	static function getClassName(){ return "BayLang.LangBay.TranslatorBay"; }
	static function getMethodsList(){ return null; }
	static function getMethodInfoByName($field_name){ return null; }
}