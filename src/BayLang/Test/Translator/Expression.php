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


class Expression
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
	
	
	
	function testMath1()
	{
		$content = new \Runtime\Map([
			"bay" => "a + b",
			"es6" => "a + b",
			"php" => "\$a + \$b",
		]);
		$init = function ($parser, $translator)
		{
			$parser->vars->set("a", true);
			$parser->vars->set("b", true);
		};
		$this->test($content, $init);
	}
	
	
	
	function testMath2()
	{
		$content = new \Runtime\Map([
			"bay" => "a * b",
			"php" => "\$a * \$b",
		]);
		$init = function ($parser, $translator)
		{
			$parser->vars->set("a", true);
			$parser->vars->set("b", true);
		};
		$this->test($content, $init);
	}
	
	
	
	function testMath3()
	{
		$content = new \Runtime\Map([
			"bay" => "a + b * c",
			"php" => "\$a + \$b * \$c",
		]);
		$init = function ($parser, $translator)
		{
			$parser->vars->set("a", true);
			$parser->vars->set("b", true);
			$parser->vars->set("c", true);
		};
		$this->test($content, $init);
	}
	
	
	
	function testMath4()
	{
		$content = new \Runtime\Map([
			"bay" => "(a + b) * c",
			"php" => "(\$a + \$b) * \$c",
		]);
		$init = function ($parser, $translator)
		{
			$parser->vars->set("a", true);
			$parser->vars->set("b", true);
			$parser->vars->set("c", true);
		};
		$this->test($content, $init);
	}
	
	
	
	function testMath5()
	{
		$content = new \Runtime\Map([
			"bay" => "a * (b + c)",
			"php" => "\$a * (\$b + \$c)",
		]);
		$init = function ($parser, $translator)
		{
			$parser->vars->set("a", true);
			$parser->vars->set("b", true);
			$parser->vars->set("c", true);
		};
		$this->test($content, $init);
	}
	
	
	
	function testMath6()
	{
		$content = new \Runtime\Map([
			"bay" => "not a",
			"es6" => "!a",
			"php" => "!\$a",
		]);
		$init = function ($parser, $translator)
		{
			$parser->vars->set("a", true);
		};
		$this->test($content, $init);
	}
	
	
	
	function testMath7()
	{
		$content = new \Runtime\Map([
			"bay" => "not (a or b)",
			"es6" => "!(a || b)",
			"php" => "!(\$a || \$b)",
		]);
		$init = function ($parser, $translator)
		{
			$parser->vars->set("a", true);
			$parser->vars->set("b", true);
		};
		$this->test($content, $init);
	}
	
	
	
	function testMath8()
	{
		$content = new \Runtime\Map([
			"bay" => "not a or not b",
			"es6" => "!a || !b",
			"php" => "!\$a || !\$b",
		]);
		$init = function ($parser, $translator)
		{
			$parser->vars->set("a", true);
			$parser->vars->set("b", true);
		};
		$this->test($content, $init);
	}
	
	
	
	function testString()
	{
		$content = new \Runtime\Map([
			"bay" => "\"Hello \" ~ username ~ \"!\"",
			"es6" => "\"Hello \" + String(username) + String(\"!\")",
			"php" => "\"Hello \" . \$username . \"!\"",
		]);
		$init = function ($parser, $translator)
		{
			$parser->vars->set("username", true);
		};
		$this->test($content);
	}
	
	
	
	function testFn2()
	{
		$content = new \Runtime\Map([
			"bay" => "a() + b()",
			"es6" => "a() + b()",
			"php" => "\$a() + \$b()",
		]);
		$init = function ($parser, $translator)
		{
			$parser->vars->set("a", true);
			$parser->vars->set("b", true);
		};
		$this->test($content, $init);
	}
	
	
	
	function testFn3()
	{
		$content = new \Runtime\Map([
			"bay" => "(a() + b()) * c()",
			"es6" => "(a() + b()) * c()",
			"php" => "(\$a() + \$b()) * \$c()",
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
	static function getClassName(){ return "BayLang.Test.Translator.Expression"; }
	static function getMethodsList()
	{
		return new \Runtime\Vector("testMath1", "testMath2", "testMath3", "testMath4", "testMath5", "testMath6", "testMath7", "testMath8", "testString", "testFn2", "testFn3");
	}
	static function getMethodInfoByName($field_name)
	{
		if ($field_nane == "testMath1") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);if ($field_nane == "testMath2") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);if ($field_nane == "testMath3") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);if ($field_nane == "testMath4") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);if ($field_nane == "testMath5") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);if ($field_nane == "testMath6") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);if ($field_nane == "testMath7") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);if ($field_nane == "testMath8") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);if ($field_nane == "testString") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);if ($field_nane == "testFn2") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);if ($field_nane == "testFn3") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);
		return null;
	}
}