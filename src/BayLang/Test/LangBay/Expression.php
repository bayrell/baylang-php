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


class Expression
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
	
	
	
	function testMath1()
	{
		$this->reset();
		$this->addVar("a");
		$this->addVar("b");
		$content = "a + b";
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testMath2()
	{
		$this->reset();
		$this->addVar("a");
		$this->addVar("b");
		$content = "a * b";
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testMath3()
	{
		$this->reset();
		$this->addVar("a");
		$this->addVar("b");
		$this->addVar("c");
		$content = "a + b * c";
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testMath4()
	{
		$this->reset();
		$this->addVar("a");
		$this->addVar("b");
		$this->addVar("c");
		$content = "(a + b) * c";
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testMath5()
	{
		$this->reset();
		$this->addVar("a");
		$this->addVar("b");
		$this->addVar("c");
		$content = "a * (b + c)";
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testMath6()
	{
		$this->reset();
		$this->addVar("a");
		$content = "not a";
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testMath7()
	{
		$this->reset();
		$this->addVar("a");
		$this->addVar("b");
		$content = "not (a or b)";
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testMath8()
	{
		$this->reset();
		$this->addVar("a");
		$this->addVar("b");
		$content = "not a or b";
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testFn1()
	{
		$this->reset();
		$this->addVar("a");
		$this->addVar("b");
		$content = "a(this.a + this.b)";
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testFn2()
	{
		$this->reset();
		$this->addVar("a");
		$this->addVar("b");
		$content = "a() + b()";
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testFn3()
	{
		$this->reset();
		$this->addVar("a");
		$this->addVar("b");
		$this->addVar("c");
		$content = "(a() + b()) * c()";
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function test1()
	{
		$this->reset();
		$this->addVar("io");
		$this->addVar("class_name");
		$this->addVar("method_name");
		$content = "io::print(class_name ~ \"::\" ~ method_name ~ " . "\" \" ~ io::color(\"green\", \"Ok\"))";
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	/* ========= Class init functions ========= */
	function _init()
	{
	}
	static function getClassName(){ return "BayLang.Test.LangBay.Expression"; }
	static function getMethodsList()
	{
		return new \Runtime\Vector("testMath1", "testMath2", "testMath3", "testMath4", "testMath5", "testMath6", "testMath7", "testMath8", "testFn1", "testFn2", "testFn3", "test1");
	}
	static function getMethodInfoByName($field_name)
	{
		if ($field_name == "testMath1") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);
		if ($field_name == "testMath2") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);
		if ($field_name == "testMath3") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);
		if ($field_name == "testMath4") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);
		if ($field_name == "testMath5") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);
		if ($field_name == "testMath6") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);
		if ($field_name == "testMath7") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);
		if ($field_name == "testMath8") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);
		if ($field_name == "testFn1") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);
		if ($field_name == "testFn2") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);
		if ($field_name == "testFn3") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);
		if ($field_name == "test1") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);
		return null;
	}
}