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


class Operator
{
	var $debug;
	
	
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
			$op_code = $parser->parser_program->parse($parser->createReader());
			if ($this->debug) var_dump($op_code);
			$translator->program->translate($op_code, $output);
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
	function test($content, $init = null, $arr = null)
	{
		$content_bay = $content->get("bay");
		$content_es6 = $content->has("es6") ? $content->get("es6") : $content_bay;
		$content_php = $content->has("php") ? $content->get("php") : $content_bay;
		if ($content_bay instanceof \Runtime\Collection) $content_bay = \Runtime\rs::join("\n", $content_bay);
		if ($content_es6 instanceof \Runtime\Collection) $content_es6 = \Runtime\rs::join("\n", $content_es6);
		if ($content_php instanceof \Runtime\Collection) $content_php = \Runtime\rs::join("\n", $content_php);
		if ($arr == null)
		{
			$arr = new \Runtime\Vector(
				"bay_to_bay",
				"bay_to_php",
				"bay_to_es6",
				"php_to_php",
				"php_to_bay",
				"php_to_es6",
				"es6_to_es6",
				"es6_to_bay",
				"es6_to_php",
			);
		}
		if ($arr->indexOf("bay_to_bay") >= 0)
		{
			$this->testExpression("bay_to_bay", $content_bay, $content_bay, $init);
		}
		if ($arr->indexOf("bay_to_php") >= 0)
		{
			$this->testExpression("bay_to_php", $content_bay, $content_php, $init);
		}
		if ($arr->indexOf("bay_to_es6") >= 0)
		{
			$this->testExpression("bay_to_es6", $content_bay, $content_es6, $init);
		}
		if ($arr->indexOf("php_to_php") >= 0)
		{
			$this->testExpression("php_to_php", $content_php, $content_php, $init);
		}
		if ($arr->indexOf("php_to_bay") >= 0)
		{
			$this->testExpression("php_to_bay", $content_php, $content_bay, $init);
		}
		if ($arr->indexOf("php_to_es6") >= 0)
		{
			$this->testExpression("php_to_es6", $content_php, $content_es6, $init);
		}
		if ($arr->indexOf("es6_to_es6") >= 0)
		{
			$this->testExpression("es6_to_es6", $content_es6, $content_es6, $init);
		}
		if ($arr->indexOf("es6_to_bay") >= 0)
		{
			$this->testExpression("es6_to_bay", $content_es6, $content_bay, $init);
		}
		if ($arr->indexOf("es6_to_php") >= 0)
		{
			$this->testExpression("es6_to_php", $content_es6, $content_php, $init);
		}
	}
	
	
	
	function testAssign2()
	{
		$content = new \Runtime\Map([
			"bay" => "var a = 1;",
			"php" => new \Runtime\Vector(
				"<?php",
				"\$a = 1;",
			),
		]);
		$this->test($content);
	}
	
	
	
	function testAssign3()
	{
		$content = new \Runtime\Map([
			"bay" => new \Runtime\Vector(
				"var a = 1;",
				"a = 2;",
			),
			"php" => new \Runtime\Vector(
				"<?php",
				"\$a = 1;",
				"\$a = 2;",
			),
		]);
		$this->test($content);
	}
	
	
	
	function testAssign4()
	{
		$content = new \Runtime\Map([
			"bay" => new \Runtime\Vector(
				"var a = 1, b = 2;",
				"a = a + b;",
			),
			"php" => new \Runtime\Vector(
				"<?php",
				"\$a = 1;",
				"\$b = 2;",
				"\$a = \$a + \$b;",
			),
		]);
		$this->test($content, null, new \Runtime\Vector(
			"bay_to_bay",
			"bay_to_php",
			"bay_to_es6",
			"php_to_php",
			"es6_to_es6",
			"es6_to_bay",
			"es6_to_php",
		));
	}
	
	
	
	function testFor1()
	{
		$content = new \Runtime\Map([
			"bay" => new \Runtime\Vector(
				"for (var i = 0; i < 10; i++)",
				"{",
				"\tprint(i);",
				"}",
			),
			"es6" => new \Runtime\Vector(
				"for (var i = 0; i < 10; i++)",
				"{",
				"\tconsole.log(i);",
				"}",
			),
			"php" => new \Runtime\Vector(
				"<?php",
				"for (\$i = 0; \$i < 10; \$i++)",
				"{",
				"\techo(\$i);",
				"}",
			),
		]);
		$this->test($content);
	}
	
	
	
	function testIf1()
	{
		$content = new \Runtime\Map([
			"bay" => new \Runtime\Vector(
				"if (a > b)",
				"{",
				"\tprint(\"Yes\");",
				"}",
			),
			"es6" => new \Runtime\Vector(
				"if (a > b)",
				"{",
				"\tconsole.log(\"Yes\");",
				"}",
			),
			"php" => new \Runtime\Vector(
				"<?php",
				"if (\$a > \$b)",
				"{",
				"\techo(\"Yes\");",
				"}",
			),
		]);
		$init = function ($parser, $translator)
		{
			$parser->vars->set("a", true);
			$parser->vars->set("b", true);
		};
		$this->test($content, $init);
	}
	
	
	
	function testIf2()
	{
		$content = new \Runtime\Map([
			"bay" => new \Runtime\Vector(
				"if (a > b)",
				"{",
				"\tprint(\"Yes\");",
				"}",
				"else",
				"{",
				"\tprint(\"No\");",
				"}",
			),
			"es6" => new \Runtime\Vector(
				"if (a > b)",
				"{",
				"\tconsole.log(\"Yes\");",
				"}",
				"else",
				"{",
				"\tconsole.log(\"No\");",
				"}",
			),
			"php" => new \Runtime\Vector(
				"<?php",
				"if (\$a > \$b)",
				"{",
				"\techo(\"Yes\");",
				"}",
				"else",
				"{",
				"\techo(\"No\");",
				"}",
			),
		]);
		$init = function ($parser, $translator)
		{
			$parser->vars->set("a", true);
			$parser->vars->set("b", true);
		};
		$this->test($content, $init);
	}
	
	
	
	function testIf3()
	{
		$content = new \Runtime\Map([
			"bay" => new \Runtime\Vector(
				"if (a == 1)",
				"{",
				"\tprint(1);",
				"}",
				"else if (a == 2)",
				"{",
				"\tprint(2);",
				"}",
				"else if (a == 3)",
				"{",
				"\tprint(3);",
				"}",
				"else",
				"{",
				"\tprint(\"No\");",
				"}",
			),
			"es6" => new \Runtime\Vector(
				"if (a == 1)",
				"{",
				"\tconsole.log(1);",
				"}",
				"else if (a == 2)",
				"{",
				"\tconsole.log(2);",
				"}",
				"else if (a == 3)",
				"{",
				"\tconsole.log(3);",
				"}",
				"else",
				"{",
				"\tconsole.log(\"No\");",
				"}",
			),
			"php" => new \Runtime\Vector(
				"<?php",
				"if (\$a == 1)",
				"{",
				"\techo(1);",
				"}",
				"else if (\$a == 2)",
				"{",
				"\techo(2);",
				"}",
				"else if (\$a == 3)",
				"{",
				"\techo(3);",
				"}",
				"else",
				"{",
				"\techo(\"No\");",
				"}",
			),
		]);
		$init = function ($parser, $translator)
		{
			$parser->vars->set("a", true);
			$parser->vars->set("b", true);
		};
		$this->test($content, $init);
	}
	
	
	
	function testWhile1()
	{
		$content = new \Runtime\Map([
			"bay" => new \Runtime\Vector(
				"var i = 0;",
				"while (i < 10)",
				"{",
				"\ti++;",
				"}",
			),
			"php" => new \Runtime\Vector(
				"<?php",
				"\$i = 0;",
				"while (\$i < 10)",
				"{",
				"\t\$i++;",
				"}",
			),
		]);
		$this->test($content);
	}
	
	
	/* ========= Class init functions ========= */
	function _init()
	{
		$this->debug = false;
	}
	static function getClassName(){ return "BayLang.Test.Translator.Operator"; }
	static function getMethodsList()
	{
		return new \Runtime\Vector("testAssign2", "testAssign3", "testAssign4", "testFor1", "testIf1", "testIf2", "testIf3", "testWhile1");
	}
	static function getMethodInfoByName($field_name)
	{
		if ($field_nane == "testAssign2") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);if ($field_nane == "testAssign3") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);if ($field_nane == "testAssign4") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);if ($field_nane == "testFor1") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);if ($field_nane == "testIf1") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);if ($field_nane == "testIf2") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);if ($field_nane == "testIf3") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);if ($field_nane == "testWhile1") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);
		return null;
	}
}