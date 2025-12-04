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

use Runtime\CoreParser;
use Runtime\Interfaces\ContextInterface;
use BayLang\LangBay\ParserBay;
use BayLang\LangBay\TranslatorBay;
use BayLang\LangES6\ParserES6;
use BayLang\LangES6\TranslatorES6;
use BayLang\LangNode\TranslatorNode;
use BayLang\LangPHP\ParserPHP;
use BayLang\LangPHP\TranslatorPHP;
use BayLang\OpCodes\BaseOpCode;
use BayLang\CoreTranslator;


class LangUtils
{
	const ERROR_PARSER = -1000;
	const ERROR_PARSER_EOF = -1001;
	const ERROR_PARSER_EXPECTED = -1002;
	
	
	/**
	 * Parse command
	 */
	static function parseCommand($command)
	{
		$from_lang = "";
		$to_lang = "";
		if ($command == "bay_to_bay")
		{
			$from_lang = "bay";
			$to_lang = "bay";
		}
		if ($command == "bay_to_php")
		{
			$from_lang = "bay";
			$to_lang = "php";
		}
		if ($command == "bay_to_es6")
		{
			$from_lang = "bay";
			$to_lang = "es6";
		}
		if ($command == "php_to_php")
		{
			$from_lang = "php";
			$to_lang = "php";
		}
		if ($command == "php_to_bay")
		{
			$from_lang = "php";
			$to_lang = "bay";
		}
		if ($command == "php_to_es6")
		{
			$from_lang = "php";
			$to_lang = "es6";
		}
		if ($command == "es6_to_es6")
		{
			$from_lang = "es6";
			$to_lang = "es6";
		}
		if ($command == "es6_to_bay")
		{
			$from_lang = "es6";
			$to_lang = "bay";
		}
		if ($command == "es6_to_php")
		{
			$from_lang = "es6";
			$to_lang = "php";
		}
		return new \Runtime\Map([
			"from" => $from_lang,
			"to" => $to_lang,
		]);
	}
	
	
	/**
	 * Create parser
	 */
	static function createParser($lang = "")
	{
		if ($lang == "bay") return new \BayLang\LangBay\ParserBay();
		else if ($lang == "es6") return new \BayLang\LangES6\ParserES6();
		else if ($lang == "php") return new \BayLang\LangPHP\ParserPHP();
		return null;
	}
	
	
	/**
	 * Create translator
	 */
	static function createTranslator($lang = "")
	{
		if ($lang == "bay") return new \BayLang\LangBay\TranslatorBay();
		else if ($lang == "es6") return new \BayLang\LangES6\TranslatorES6();
		else if ($lang == "nodejs") return new \BayLang\LangNode\TranslatorNode();
		else if ($lang == "php") return new \BayLang\LangPHP\TranslatorPHP();
		return null;
	}
	
	
	/* ========= Class init functions ========= */
	function _init()
	{
	}
	static function getClassName(){ return "BayLang.LangUtils"; }
	static function getMethodsList(){ return null; }
	static function getMethodInfoByName($field_name){ return null; }
}