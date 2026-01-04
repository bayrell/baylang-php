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
namespace BayLang\Test\LangBay;

use Runtime\io;
use Runtime\Unit\AssertHelper;
use Runtime\Unit\Test;
use BayLang\LangBay\ParserBay;
use BayLang\LangBay\TranslatorBay;
use BayLang\OpCodes\BaseOpCode;
use BayLang\CoreTranslator;


class Operator
{
	var $parser;
	var $translator;
	
	
	/**
	 * Reset
	 */
	function reset()
	{
		$this->parser = new \BayLang\LangBay\ParserBay();
		$this->parser = $this->parser::reset($this->parser);
		$this->translator = new \BayLang\LangBay\TranslatorBay();
		$this->translator->reset();
	}
	
	
	/**
	 * Set content
	 */
	function setContent($content)
	{
		$this->parser->setContent($content);
	}
	
	
	/**
	 * Add variable
	 */
	function addVar($var_name)
	{
		$parser = $this->parser;
		$parser->vars->set($var_name, true);
		$this->parser = $parser;
	}
	
	
	/**
	 * Translate
	 */
	function translate($content, $debug = false)
	{
		$result = new \Runtime\Vector();
		$this->setContent($content);
		/* Parse */
		$res = $this->parser->parser_operator::readOperators($this->parser);
		$op_code = $res->get(1);
		/* Translate */
		$this->translator->operator->translateItems($op_code, $result);
		/* Debug output */
		if ($debug)
		{
			echo($op_code);
			echo($result);
			echo(\Runtime\rs::join("", $result));
		}
		return new \Runtime\Vector($op_code, \Runtime\rs::join("", $result));
	}
	
	
	
	function testAssign1()
	{
		$this->reset();
		$content = \Runtime\rs::join("\n", new \Runtime\Vector(
			"{",
			"\tint a;",
			"}",
		));
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testAssign2()
	{
		$this->reset();
		$content = \Runtime\rs::join("\n", new \Runtime\Vector(
			"{",
			"\tint a = 1;",
			"}",
		));
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testAssign3()
	{
		$this->reset();
		$content = \Runtime\rs::join("\n", new \Runtime\Vector(
			"{",
			"\tint a = 1;",
			"\ta = 2;",
			"}",
		));
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testAssign4()
	{
		$this->reset();
		$content = \Runtime\rs::join("\n", new \Runtime\Vector(
			"{",
			"\tint a = 1, b = 2;",
			"\ta = a + b;",
			"}",
		));
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testAssign5()
	{
		$this->reset();
		$content = \Runtime\rs::join("\n", new \Runtime\Vector(
			"{",
			"\tthis.a = 1;",
			"}",
		));
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testAssign6()
	{
		$this->reset();
		$content = \Runtime\rs::join("\n", new \Runtime\Vector(
			"{",
			"\tCollection<string> content = [];",
			"}",
		));
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testAssign7()
	{
		$this->reset();
		$this->addVar("content");
		$content = \Runtime\rs::join("\n", new \Runtime\Vector(
			"{",
			"\tstring content = rs::join(\"\\n\", content);",
			"}",
		));
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testBreak()
	{
		$this->reset();
		$content = \Runtime\rs::join("\n", new \Runtime\Vector(
			"{",
			"\tbreak;",
			"}",
		));
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testContinue()
	{
		$this->reset();
		$content = \Runtime\rs::join("\n", new \Runtime\Vector(
			"{",
			"\tcontinue;",
			"}",
		));
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testReturn1()
	{
		$this->reset();
		$content = \Runtime\rs::join("\n", new \Runtime\Vector(
			"{",
			"\treturn;",
			"}",
		));
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testReturn2()
	{
		$this->reset();
		$content = \Runtime\rs::join("\n", new \Runtime\Vector(
			"{",
			"\treturn 1;",
			"}",
		));
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testReturn3()
	{
		$this->reset();
		$content = \Runtime\rs::join("\n", new \Runtime\Vector(
			"{",
			"\treturn true;",
			"}",
		));
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testReturn4()
	{
		$this->reset();
		$content = \Runtime\rs::join("\n", new \Runtime\Vector(
			"{",
			"\treturn this.result;",
			"}",
		));
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testInc1()
	{
		$this->reset();
		$this->addVar("a");
		$content = \Runtime\rs::join("\n", new \Runtime\Vector(
			"{",
			"\ta++;",
			"}",
		));
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testInc2()
	{
		$this->reset();
		$this->addVar("a");
		$content = \Runtime\rs::join("\n", new \Runtime\Vector(
			"{",
			"\t++a;",
			"}",
		));
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testDec1()
	{
		$this->reset();
		$this->addVar("a");
		$content = \Runtime\rs::join("\n", new \Runtime\Vector(
			"{",
			"\ta--;",
			"}",
		));
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testDec2()
	{
		$this->reset();
		$this->addVar("a");
		$content = \Runtime\rs::join("\n", new \Runtime\Vector(
			"{",
			"\t--a;",
			"}",
		));
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testFor1()
	{
		$this->reset();
		$this->addVar("io");
		$content = \Runtime\rs::join("\n", new \Runtime\Vector(
			"{",
			"\tfor (int i = 0; i < 10; i++)",
			"\t{",
			"\t\tio::print(i);",
			"\t}",
			"}",
		));
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testIf1()
	{
		$this->reset();
		$this->addVar("a");
		$this->addVar("b");
		$this->addVar("io");
		$content = \Runtime\rs::join("\n", new \Runtime\Vector(
			"{",
			"\tif (a > b)",
			"\t{",
			"\t\tio::print(\"Yes\");",
			"\t}",
			"}",
		));
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testIf2()
	{
		$this->reset();
		$this->addVar("a");
		$this->addVar("b");
		$this->addVar("io");
		$content = \Runtime\rs::join("\n", new \Runtime\Vector(
			"{",
			"\tif (a > b)",
			"\t{",
			"\t\tio::print(\"Yes\");",
			"\t}",
			"\telse",
			"\t{",
			"\t\tio::print(\"No\");",
			"\t}",
			"}",
		));
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testIf3()
	{
		$this->reset();
		$this->addVar("a");
		$this->addVar("io");
		$content = \Runtime\rs::join("\n", new \Runtime\Vector(
			"{",
			"\tif (a == 1)",
			"\t{",
			"\t\tio::print(1);",
			"\t}",
			"\telse if (a == 2)",
			"\t{",
			"\t\tio::print(2);",
			"\t}",
			"\telse if (a == 3)",
			"\t{",
			"\t\tio::print(3);",
			"\t}",
			"\telse",
			"\t{",
			"\t\tio::print(\"No\");",
			"\t}",
			"}",
		));
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testThrow1()
	{
		$this->reset();
		$this->addVar("RuntimeException");
		$content = \Runtime\rs::join("\n", new \Runtime\Vector(
			"{",
			"\tthrow new RuntimeException(\"Error\");",
			"}",
		));
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testTry1()
	{
		$this->reset();
		$this->addVar("io");
		$this->addVar("RuntimeException");
		$content = \Runtime\rs::join("\n", new \Runtime\Vector(
			"{",
			"\ttry",
			"\t{",
			"\t\tthis.translate();",
			"\t}",
			"\tcatch (RuntimeException e)",
			"\t{",
			"\t\tio::print_error(e.toString());",
			"\t}",
			"}",
		));
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testWhile1()
	{
		$this->reset();
		$this->addVar("i");
		$content = \Runtime\rs::join("\n", new \Runtime\Vector(
			"{",
			"\twhile (i < 10)",
			"\t{",
			"\t\ti++;",
			"\t}",
			"}",
		));
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testComment1()
	{
		$this->reset();
		$this->addVar("i");
		$content = \Runtime\rs::join("\n", new \Runtime\Vector(
			"{",
			"\t/* Increment value */",
			"\ti++;",
			"}",
		));
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	/* ========= Class init functions ========= */
	function _init()
	{
	}
	static function getClassName(){ return "BayLang.Test.LangBay.Operator"; }
	static function getMethodsList()
	{
		return new \Runtime\Vector("testAssign1", "testAssign2", "testAssign3", "testAssign4", "testAssign5", "testAssign6", "testAssign7", "testBreak", "testContinue", "testReturn1", "testReturn2", "testReturn3", "testReturn4", "testInc1", "testInc2", "testDec1", "testDec2", "testFor1", "testIf1", "testIf2", "testIf3", "testThrow1", "testTry1", "testWhile1", "testComment1");
	}
	static function getMethodInfoByName($field_name)
	{
		if ($field_name == "testAssign1") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);
		if ($field_name == "testAssign2") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);
		if ($field_name == "testAssign3") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);
		if ($field_name == "testAssign4") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);
		if ($field_name == "testAssign5") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);
		if ($field_name == "testAssign6") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);
		if ($field_name == "testAssign7") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);
		if ($field_name == "testBreak") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);
		if ($field_name == "testContinue") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);
		if ($field_name == "testReturn1") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);
		if ($field_name == "testReturn2") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);
		if ($field_name == "testReturn3") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);
		if ($field_name == "testReturn4") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);
		if ($field_name == "testInc1") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);
		if ($field_name == "testInc2") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);
		if ($field_name == "testDec1") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);
		if ($field_name == "testDec2") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);
		if ($field_name == "testFor1") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);
		if ($field_name == "testIf1") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);
		if ($field_name == "testIf2") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);
		if ($field_name == "testIf3") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);
		if ($field_name == "testThrow1") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);
		if ($field_name == "testTry1") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);
		if ($field_name == "testWhile1") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);
		if ($field_name == "testComment1") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);
		return null;
	}
}