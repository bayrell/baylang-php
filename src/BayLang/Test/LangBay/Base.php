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


class Base
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
		$res = $this->parser->parser_expression::readExpression($this->parser);
		$op_code = $res->get(1);
		/* Translate */
		$this->translator->expression->translate($op_code, $result);
		/* Debug output */
		if ($debug)
		{
			echo($op_code);
			echo($result);
			echo(\Runtime\rs::join("", $result));
		}
		return new \Runtime\Vector($op_code, \Runtime\rs::join("", $result));
	}
	
	
	
	function testNumber()
	{
		$this->reset();
		$content = "1";
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testReal()
	{
		$this->reset();
		$content = "0.1";
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testString()
	{
		$this->reset();
		$content = "\"test\"";
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testIdentifier()
	{
		$this->reset();
		$this->addVar("a");
		$content = "a";
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testAttr1()
	{
		$this->reset();
		$content = "this.a";
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testAttr2()
	{
		$this->reset();
		$content = "this.a.b";
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testAttr3()
	{
		$this->reset();
		$content = "static::a";
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testAttr4()
	{
		$this->reset();
		$content = "parent::a";
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testAttr5()
	{
		$this->reset();
		$this->addVar("a");
		$content = "a[1]";
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testAttr6()
	{
		$this->reset();
		$this->addVar("a");
		$content = "a[1, 2]";
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testAttr7()
	{
		$this->reset();
		$this->addVar("a");
		$this->addVar("name");
		$content = "a[name]";
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testCollection1()
	{
		$this->reset();
		$content = "[]";
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testCollection2()
	{
		$this->reset();
		$content = "[1, 2, 3]";
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testCollection3()
	{
		$this->reset();
		$this->addVar("a");
		$this->addVar("b");
		$this->addVar("c");
		$content = "[a, b, c]";
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testCollection4()
	{
		$this->reset();
		$content = "[\"a\", \"b\", \"c\"]";
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testCollection5()
	{
		$this->reset();
		$content = \Runtime\rs::join("\n", new \Runtime\Vector(
			"[",
			"\t\"a\",",
			"\t\"b\",",
			"\t\"c\",",
			"]",
		));
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testDict1()
	{
		$this->reset();
		$content = "{}";
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testDict2()
	{
		$this->reset();
		$content = \Runtime\rs::join("\n", new \Runtime\Vector(
			"{",
			"\t\"name\": \"test\",",
			"}",
		));
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testDict3()
	{
		$this->reset();
		$content = \Runtime\rs::join("\n", new \Runtime\Vector(
			"{",
			"\t\"name\": \"test\",",
			"\t\"value\": 10,",
			"}",
		));
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testDict4()
	{
		$this->reset();
		$content = \Runtime\rs::join("\n", new \Runtime\Vector(
			"{",
			"\t\"obj\": {},",
			"}",
		));
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testDict5()
	{
		$this->reset();
		$content = \Runtime\rs::join("\n", new \Runtime\Vector(
			"{",
			"\t\"obj\": {",
			"\t},",
			"}",
		));
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testDict6()
	{
		$this->reset();
		$content = \Runtime\rs::join("\n", new \Runtime\Vector(
			"{",
			"\t\"obj\": [",
			"\t],",
			"}",
		));
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testDict7()
	{
		$this->reset();
		$content = \Runtime\rs::join("\n", new \Runtime\Vector(
			"{",
			"\t\"obj\": {",
			"\t\t\"name\": \"test\",",
			"\t\t\"value\": 10,",
			"\t},",
			"}",
		));
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testDict8()
	{
		$this->reset();
		$content = \Runtime\rs::join("\n", new \Runtime\Vector(
			"{",
			"\t\"obj\": {\"name\": \"test\", \"value\": 10},",
			"}",
		));
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testPreprocessor1()
	{
		$this->reset();
		$content = \Runtime\rs::join("\n", new \Runtime\Vector(
			"[",
			"\t\"1\",",
			"\t#ifdef BACKEND then",
			"\t\"2\",",
			"\t\"3\",",
			"\t#endif",
			"\t\"4\",",
			"]",
		));
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testPreprocessor2()
	{
		$this->reset();
		$content = \Runtime\rs::join("\n", new \Runtime\Vector(
			"{",
			"\t\"name\": \"test\",",
			"\t#ifdef BACKEND then",
			"\t\"value1\": 1,",
			"\t\"value2\": 2,",
			"\t#endif",
			"}",
		));
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testFn1()
	{
		$this->reset();
		$this->addVar("a");
		$content = "a()";
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testFn2()
	{
		$this->reset();
		$this->addVar("a");
		$content = "a(1, 2)";
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testFn3()
	{
		$this->reset();
		$content = "this.a(1, 2)";
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testFn4()
	{
		$this->reset();
		$content = "parent(1, 2)";
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testFn5()
	{
		$this->reset();
		$content = "static::getName(1, 2)";
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testFn6()
	{
		$this->reset();
		$this->addVar("a");
		$this->addVar("b");
		$this->addVar("c");
		$content = "a(b, c)";
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testFn7()
	{
		$this->reset();
		$this->addVar("a");
		$content = "a().b()";
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testFn8()
	{
		$this->reset();
		$this->addVar("a");
		$content = "a{}";
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testFn9()
	{
		$this->reset();
		$this->addVar("a");
		$content = \Runtime\rs::join("\n", new \Runtime\Vector(
			"a{",
			"\t\"name\": \"test\",",
			"\t\"value\": 10,",
			"}",
		));
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testNew1()
	{
		$this->reset();
		$this->addVar("Test");
		$content = "new Test()";
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testNew2()
	{
		$this->reset();
		$this->addVar("Test");
		$content = "new Test(this.name, this.value)";
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testNew3()
	{
		$this->reset();
		$this->addVar("Test");
		$content = \Runtime\rs::join("\n", new \Runtime\Vector(
			"new Test{",
			"\t\"name\": \"test\",",
			"\t\"value\": 10,",
			"}",
		));
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testNew4()
	{
		$this->reset();
		$this->addVar("Query");
		$content = "new Query().select(\"table\")";
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testNew5()
	{
		$this->reset();
		$content = "new Collection<string>()";
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	/* ========= Class init functions ========= */
	function _init()
	{
	}
	static function getClassName(){ return "BayLang.Test.LangBay.Base"; }
	static function getMethodsList()
	{
		return new \Runtime\Vector("testNumber", "testReal", "testString", "testIdentifier", "testAttr1", "testAttr2", "testAttr3", "testAttr4", "testAttr5", "testAttr6", "testAttr7", "testCollection1", "testCollection2", "testCollection3", "testCollection4", "testCollection5", "testDict1", "testDict2", "testDict3", "testDict4", "testDict5", "testDict6", "testDict7", "testDict8", "testPreprocessor1", "testPreprocessor2", "testFn1", "testFn2", "testFn3", "testFn4", "testFn5", "testFn6", "testFn7", "testFn8", "testFn9", "testNew1", "testNew2", "testNew3", "testNew4", "testNew5");
	}
	static function getMethodInfoByName($field_name)
	{
		if ($field_nane == "testNumber") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);if ($field_nane == "testReal") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);if ($field_nane == "testString") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);if ($field_nane == "testIdentifier") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);if ($field_nane == "testAttr1") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);if ($field_nane == "testAttr2") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);if ($field_nane == "testAttr3") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);if ($field_nane == "testAttr4") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);if ($field_nane == "testAttr5") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);if ($field_nane == "testAttr6") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);if ($field_nane == "testAttr7") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);if ($field_nane == "testCollection1") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);if ($field_nane == "testCollection2") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);if ($field_nane == "testCollection3") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);if ($field_nane == "testCollection4") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);if ($field_nane == "testCollection5") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);if ($field_nane == "testDict1") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);if ($field_nane == "testDict2") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);if ($field_nane == "testDict3") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);if ($field_nane == "testDict4") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);if ($field_nane == "testDict5") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);if ($field_nane == "testDict6") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);if ($field_nane == "testDict7") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);if ($field_nane == "testDict8") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);if ($field_nane == "testPreprocessor1") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);if ($field_nane == "testPreprocessor2") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);if ($field_nane == "testFn1") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);if ($field_nane == "testFn2") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);if ($field_nane == "testFn3") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);if ($field_nane == "testFn4") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);if ($field_nane == "testFn5") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);if ($field_nane == "testFn6") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);if ($field_nane == "testFn7") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);if ($field_nane == "testFn8") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);if ($field_nane == "testFn9") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);if ($field_nane == "testNew1") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);if ($field_nane == "testNew2") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);if ($field_nane == "testNew3") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);if ($field_nane == "testNew4") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);if ($field_nane == "testNew5") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);
		return null;
	}
}