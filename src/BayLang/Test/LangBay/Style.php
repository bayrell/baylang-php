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
use BayLang\OpCodes\BaseOpCode;
use BayLang\OpCodes\OpString;
use BayLang\CoreTranslator;


class Style
{
	var $parser;
	
	
	/**
	 * Reset
	 */
	function reset()
	{
		/* Create parser */
		$parser = new \BayLang\LangBay\ParserBay();
		$parser->current_namespace_name = "App";
		$parser->current_class_name = "Test";
		$parser->uses->set("Button", "Runtime.Widget.Button");
		$this->parser = $parser;
	}
	
	
	/**
	 * Set content
	 */
	function setContent($content)
	{
		$this->parser->setContent($content);
	}
	
	
	/**
	 * Translate
	 */
	function translate($content, $debug = false)
	{
		$this->setContent($content . "}");
		/* Parse */
		$items = new \Runtime\Vector();
		$res = $this->parser->parser_html::readCssBodyItems($this->parser, $items, new \Runtime\Vector());
		$op_code = $res->get(1);
		/* Get items */
		$items = $items->map(function ($op_code){ return $op_code->value; });
		$result = \Runtime\rs::join("\n", $items);
		/* Debug output */
		if ($debug)
		{
			echo($items);
			echo($result);
		}
		return new \Runtime\Vector($op_code, $result);
	}
	
	
	
	function test1()
	{
		$this->reset();
		$content = \Runtime\rs::join("\n", new \Runtime\Vector(
			".main_page{",
			"\tpadding: 20px;",
			"}",
		));
		$css_content = \Runtime\rs::join("\n", new \Runtime\Vector(
			".main_page.h-71c3{padding: 20px}",
		));
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($css_content, $res->get(1), $css_content);
	}
	
	
	
	function test2()
	{
		$this->reset();
		$content = \Runtime\rs::join("\n", new \Runtime\Vector(
			".main_page{",
			"\t.test1{",
			"\t\tpadding: 20px;",
			"\t}",
			"}",
		));
		$css_content = \Runtime\rs::join("\n", new \Runtime\Vector(
			".main_page.h-71c3 .test1.h-71c3{padding: 20px}",
		));
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($css_content, $res->get(1), $css_content);
	}
	
	
	
	function test3()
	{
		$this->reset();
		$content = \Runtime\rs::join("\n", new \Runtime\Vector(
			".main_page{",
			"\t&__test1{",
			"\t\tpadding: 20px;",
			"\t}",
			"}",
		));
		$css_content = \Runtime\rs::join("\n", new \Runtime\Vector(
			".main_page__test1.h-71c3{padding: 20px}",
		));
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($css_content, $res->get(1), $css_content);
	}
	
	
	
	function test4()
	{
		$this->reset();
		$content = \Runtime\rs::join("\n", new \Runtime\Vector(
			".main_page{",
			"\t&__test1{",
			"\t\t&_test2{",
			"\t\t\tpadding: 20px;",
			"\t\t}",
			"\t}",
			"}",
		));
		$css_content = \Runtime\rs::join("\n", new \Runtime\Vector(
			".main_page__test1_test2.h-71c3{padding: 20px}",
		));
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($css_content, $res->get(1), $css_content);
	}
	
	
	
	function test5()
	{
		$this->reset();
		$content = \Runtime\rs::join("\n", new \Runtime\Vector(
			".main_page{",
			"\t&__test1{",
			"\t\t.test2{",
			"\t\t\tpadding: 20px;",
			"\t\t}",
			"\t}",
			"}",
		));
		$css_content = \Runtime\rs::join("\n", new \Runtime\Vector(
			".main_page__test1.h-71c3 .test2.h-71c3{padding: 20px}",
		));
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($css_content, $res->get(1), $css_content);
	}
	
	
	
	function test6()
	{
		$this->reset();
		$content = \Runtime\rs::join("\n", new \Runtime\Vector(
			".main_page{",
			"\t.test1{",
			"\t\t&__test2{",
			"\t\t\tpadding: 20px;",
			"\t\t}",
			"\t}",
			"}",
		));
		$css_content = \Runtime\rs::join("\n", new \Runtime\Vector(
			".main_page.h-71c3 .test1__test2.h-71c3{padding: 20px}",
		));
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($css_content, $res->get(1), $css_content);
	}
	
	
	
	function test7()
	{
		$this->reset();
		$content = \Runtime\rs::join("\n", new \Runtime\Vector(
			".main_page{",
			"\t%(Button)widget_button{",
			"\t\tpadding: 20px;",
			"\t}",
			"}",
		));
		$css_content = \Runtime\rs::join("\n", new \Runtime\Vector(
			".main_page.h-71c3 .widget_button.h-8dd7{padding: 20px}",
		));
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($css_content, $res->get(1), $css_content);
	}
	
	
	
	function test8()
	{
		$this->reset();
		$content = \Runtime\rs::join("\n", new \Runtime\Vector(
			".main_page{",
			"\t%(Button)widget_button{",
			"\t\t&__test1{",
			"\t\t\tpadding: 20px;",
			"\t\t}",
			"\t}",
			"}",
		));
		$css_content = \Runtime\rs::join("\n", new \Runtime\Vector(
			".main_page.h-71c3 .widget_button__test1.h-8dd7{padding: 20px}",
		));
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($css_content, $res->get(1), $css_content);
	}
	
	
	
	function test9()
	{
		$this->reset();
		$content = \Runtime\rs::join("\n", new \Runtime\Vector(
			".main_page{",
			"\t%(Button)widget_button{",
			"\t\t.test1{",
			"\t\t\tpadding: 20px;",
			"\t\t}",
			"\t}",
			"}",
		));
		$css_content = \Runtime\rs::join("\n", new \Runtime\Vector(
			".main_page.h-71c3 .widget_button.h-8dd7 .test1.h-71c3{padding: 20px}",
		));
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($css_content, $res->get(1), $css_content);
	}
	
	
	
	function test10()
	{
		$this->reset();
		$content = \Runtime\rs::join("\n", new \Runtime\Vector(
			".main_page{",
			"\tp{",
			"\t\tfont-weight: bold;",
			"\t}",
			"}",
		));
		$css_content = \Runtime\rs::join("\n", new \Runtime\Vector(
			".main_page.h-71c3 p{font-weight: bold}",
		));
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($css_content, $res->get(1), $css_content);
	}
	
	
	
	function test11()
	{
		$this->reset();
		$content = \Runtime\rs::join("\n", new \Runtime\Vector(
			".main_page{",
			"\tpadding: 20px;",
			"\tcolor: green;",
			"}",
		));
		$css_content = \Runtime\rs::join("\n", new \Runtime\Vector(
			".main_page.h-71c3{padding: 20px;color: green}",
		));
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($css_content, $res->get(1), $css_content);
	}
	
	
	
	function test12()
	{
		$this->reset();
		$content = \Runtime\rs::join("\n", new \Runtime\Vector(
			".main_page{",
			"\tpadding: 20px;",
			"\tcolor: green;",
			"\t@media (max-width: 950px){",
			"\t\tdisplay: none;",
			"\t}",
			"}",
		));
		$css_content = \Runtime\rs::join("\n", new \Runtime\Vector(
			".main_page.h-71c3{padding: 20px;color: green}",
			"@media (max-width: 950px){.main_page.h-71c3{display: none}}",
		));
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($css_content, $res->get(1), $css_content);
	}
	
	
	/* ========= Class init functions ========= */
	function _init()
	{
	}
	static function getClassName(){ return "BayLang.Test.LangBay.Style"; }
	static function getMethodsList()
	{
		return new \Runtime\Vector("test1", "test2", "test3", "test4", "test5", "test6", "test7", "test8", "test9", "test10", "test11", "test12");
	}
	static function getMethodInfoByName($field_name)
	{
		if ($field_nane == "test1") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);if ($field_nane == "test2") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);if ($field_nane == "test3") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);if ($field_nane == "test4") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);if ($field_nane == "test5") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);if ($field_nane == "test6") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);if ($field_nane == "test7") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);if ($field_nane == "test8") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);if ($field_nane == "test9") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);if ($field_nane == "test10") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);if ($field_nane == "test11") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);if ($field_nane == "test12") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);
		return null;
	}
}