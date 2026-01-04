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
namespace BayLang\Test\Translator;

use Runtime\Exceptions\AssertException;
use Runtime\Unit\AssertHelper;
use Runtime\Unit\Test;
use BayLang\Exceptions\ParserError;
use BayLang\LangBay\ParserBay;
use BayLang\LangBay\TranslatorBay;
use BayLang\LangES6\ParserES6;
use BayLang\LangES6\TranslatorES6;
use BayLang\LangPHP\ParserPHP;
use BayLang\LangPHP\TranslatorPHP;
use BayLang\OpCodes\BaseOpCode;
use BayLang\CoreParser;
use BayLang\CoreTranslator;
use BayLang\LangUtils;


class Base
{
	/**
	 * Assert value
	 */
	function assert($command, $value1, $value2)
	{
		$message = new \Runtime\Vector(
			$command,
			"Missing:",
			$value1,
			"Exists:",
			$value2,
		);
		\Runtime\Unit\AssertHelper::equalValue($value1, $value2, \Runtime\rs::join("\n", $message));
	}
	
	
	/**
	 * Test expression
	 */
	function testExpression($command, $src, $dest, $callback = null)
	{
		$res = \BayLang\LangUtils::parseCommand($command);
		$parser = \BayLang\LangUtils::createParser($res->get("from"));
		$translator = \BayLang\LangUtils::createTranslator($res->get("to"));
		/* Init function */
		if ($callback) $callback($parser, $translator);
		/* Translate file */
		$output = new \Runtime\Vector();
		try
		{
			$parser->setContent($src);
			$op_code = $parser->parser_expression->readExpression($parser->createReader());
			$translator->expression->translate($op_code, $output);
		}
		catch (\BayLang\Exceptions\ParserError $error)
		{
			throw new \Runtime\Exceptions\AssertException($command . " " . $error->toString());
		}
		/* Check output */
		$this->assert($command, $dest, \Runtime\rs::join("", $output));
	}
	
	
	/**
	 * Test lang
	 */
	function test($content, $init = null)
	{
		$content_bay = $content->get("bay");
		$content_es6 = $content->has("es6") ? $content->get("es6") : $content_bay;
		$content_php = $content->has("php") ? $content->get("php") : $content_bay;
		$this->testExpression("bay_to_bay", $content_bay, $content_bay, $init);
		$this->testExpression("bay_to_php", $content_bay, $content_php, $init);
		$this->testExpression("bay_to_es6", $content_bay, $content_es6, $init);
		$this->testExpression("php_to_php", $content_php, $content_php, $init);
		$this->testExpression("php_to_bay", $content_php, $content_bay, $init);
		$this->testExpression("php_to_es6", $content_php, $content_es6, $init);
		$this->testExpression("es6_to_es6", $content_es6, $content_es6, $init);
		$this->testExpression("es6_to_bay", $content_es6, $content_bay, $init);
		$this->testExpression("es6_to_php", $content_es6, $content_php, $init);
	}
	
	
	
	function testNumber()
	{
		$content = new \Runtime\Map([
			"bay" => "1",
		]);
		$this->test($content);
	}
	
	
	
	function testReal()
	{
		$content = new \Runtime\Map([
			"bay" => "0.1",
		]);
		$this->test($content);
	}
	
	
	
	function testString()
	{
		$content = new \Runtime\Map([
			"bay" => "\"Test\"",
		]);
		$this->test($content);
	}
	
	
	
	function testIdentifier()
	{
		$content = new \Runtime\Map([
			"bay" => "a",
			"php" => "\$a",
		]);
		$init = function ($parser, $translator)
		{
			$parser->vars->set("a", true);
		};
		$this->test($content, $init);
	}
	
	
	
	function testFn1()
	{
		$content = new \Runtime\Map([
			"bay" => "a()",
			"php" => "\$a()",
		]);
		$init = function ($parser, $translator)
		{
			$parser->vars->set("a", true);
		};
		$this->test($content, $init);
	}
	
	
	
	function testFn2()
	{
		$content = new \Runtime\Map([
			"bay" => "a(1, 2)",
			"php" => "\$a(1, 2)",
		]);
		$init = function ($parser, $translator)
		{
			$parser->vars->set("a", true);
		};
		$this->test($content, $init);
	}
	
	
	
	function testFn6()
	{
		$content = new \Runtime\Map([
			"bay" => "a(b, c)",
			"php" => "\$a(\$b, \$c)",
		]);
		$init = function ($parser, $translator)
		{
			$parser->vars->set("a", true);
			$parser->vars->set("b", true);
			$parser->vars->set("c", true);
		};
		$this->test($content, $init);
	}
	
	
	/* ========= Class init functions ========= */
	function _init()
	{
	}
	static function getClassName(){ return "BayLang.Test.Translator.Base"; }
	static function getMethodsList()
	{
		return new \Runtime\Vector("testNumber", "testReal", "testString", "testIdentifier", "testFn1", "testFn2", "testFn6");
	}
	static function getMethodInfoByName($field_name)
	{
		if ($field_name == "testNumber") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);
		if ($field_name == "testReal") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);
		if ($field_name == "testString") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);
		if ($field_name == "testIdentifier") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);
		if ($field_name == "testFn1") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);
		if ($field_name == "testFn2") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);
		if ($field_name == "testFn6") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);
		return null;
	}
}