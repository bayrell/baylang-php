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


class Html
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
		$res = $this->parser->parser_program::readProgram($this->parser);
		$op_code = $res->get(1);
		/* Translate */
		$this->translator->html->translate($op_code, $result);
		/* Debug output */
		if ($debug)
		{
			echo($op_code);
			echo($result);
			echo(\Runtime\rs::join("", $result));
		}
		return new \Runtime\Vector($op_code, \Runtime\rs::join("", $result));
	}
	
	
	
	function test1()
	{
		$this->reset();
		$content = \Runtime\rs::join("\n", new \Runtime\Vector(
			"<class name=\"App.Component\">",
			"</class>",
		));
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function test2()
	{
		$this->reset();
		$content = \Runtime\rs::join("\n", new \Runtime\Vector(
			"<class name=\"App.Component\">",
			"",
			"<use name=\"App.Test\" />",
			"",
			"</class>",
		));
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function test3()
	{
		$this->reset();
		$content = \Runtime\rs::join("\n", new \Runtime\Vector(
			"<class name=\"App.Component\">",
			"",
			"<use name=\"App.Test\" as=\"TestAlias\" />",
			"",
			"</class>",
		));
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function test4()
	{
		$this->reset();
		$content = \Runtime\rs::join("\n", new \Runtime\Vector(
			"<class name=\"App.Component\">",
			"",
			"<use name=\"Runtime.Web.Button\" component=\"true\" />",
			"<use name=\"Runtime.Web.Text\" component=\"true\" />",
			"",
			"</class>",
		));
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testTemplate1()
	{
		$this->reset();
		$content = \Runtime\rs::join("\n", new \Runtime\Vector(
			"<class name=\"App.Component\">",
			"",
			"<template>",
			"</template>",
			"",
			"</class>",
		));
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testTemplate2()
	{
		$this->reset();
		$content = \Runtime\rs::join("\n", new \Runtime\Vector(
			"<class name=\"App.Component\">",
			"",
			"<template name=\"renderItem\">",
			"</template>",
			"",
			"</class>",
		));
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testTemplate3()
	{
		$this->reset();
		$content = \Runtime\rs::join("\n", new \Runtime\Vector(
			"<class name=\"App.Component\">",
			"",
			"<template name=\"renderItem\" args=\"int a, int b\">",
			"</template>",
			"",
			"</class>",
		));
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testComponent1()
	{
		$this->reset();
		$content = \Runtime\rs::join("\n", new \Runtime\Vector(
			"<class name=\"App.Component\">",
			"",
			"<use name=\"Runtime.Web.Button\" />",
			"",
			"<template>",
			"\t<Button />",
			"</template>",
			"",
			"</class>",
		));
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testComponent2()
	{
		$this->reset();
		$content = \Runtime\rs::join("\n", new \Runtime\Vector(
			"<class name=\"App.Component\">",
			"",
			"<use name=\"Runtime.Web.Button\" />",
			"",
			"<template>",
			"\t<Button>",
			"\t\tContent",
			"\t</Button>",
			"</template>",
			"",
			"</class>",
		));
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testComponent3()
	{
		$this->reset();
		$content = \Runtime\rs::join("\n", new \Runtime\Vector(
			"<class name=\"App.Component\">",
			"",
			"<use name=\"Runtime.Web.Button\" />",
			"",
			"<template>",
			"\t<Button>",
			"\t\t{{ this.model.content }}",
			"\t</Button>",
			"</template>",
			"",
			"</class>",
		));
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testComponent4()
	{
		$this->reset();
		$content = \Runtime\rs::join("\n", new \Runtime\Vector(
			"<class name=\"App.Component\">",
			"",
			"<use name=\"Runtime.Web.Button\" />",
			"",
			"<template>",
			"\t<Button @model={{ this.model }} />",
			"</template>",
			"",
			"</class>",
		));
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testComponent5()
	{
		$this->reset();
		$content = \Runtime\rs::join("\n", new \Runtime\Vector(
			"<class name=\"App.Component\">",
			"",
			"<use name=\"Runtime.Web.Button\" />",
			"",
			"<template>",
			"\t<Button name=\"test\" />",
			"</template>",
			"",
			"</class>",
		));
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testContent1()
	{
		$this->reset();
		$content = \Runtime\rs::join("\n", new \Runtime\Vector(
			"<class name=\"App.Component\">",
			"",
			"<template>",
			"\t<div class=\"widget_test\">Text</div>",
			"</template>",
			"",
			"</class>",
		));
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testContent2()
	{
		$this->reset();
		$content = \Runtime\rs::join("\n", new \Runtime\Vector(
			"<class name=\"App.Component\">",
			"",
			"<template>",
			"\t<div class=\"widget_test\">{{ \"Text\" }}</div>",
			"</template>",
			"",
			"</class>",
		));
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testContent3()
	{
		$this->reset();
		$content = \Runtime\rs::join("\n", new \Runtime\Vector(
			"<class name=\"App.Component\">",
			"",
			"<template>",
			"\t<div class=\"widget_test\">@raw{{ \"Text\" }}</div>",
			"</template>",
			"",
			"</class>",
		));
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testClick()
	{
		$this->reset();
		$content = \Runtime\rs::join("\n", new \Runtime\Vector(
			"<class name=\"App.Component\">",
			"",
			"<template>",
			"\t<button",
			"\t\tclass=\"widget_test\"",
			"\t\t@event:click={{",
			"\t\t\tvoid ()",
			"\t\t\t{",
			"\t\t\t\tthis.onClick();",
			"\t\t\t}",
			"\t\t}}",
			"\t>",
			"\t\tTest",
			"\t</button>",
			"</template>",
			"",
			"</class>",
		));
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testScript1()
	{
		$this->reset();
		$content = \Runtime\rs::join("\n", new \Runtime\Vector(
			"<class name=\"App.Component\">",
			"",
			"<use name=\"Runtime.Web.Button\" />",
			"",
			"<template>",
			"</template>",
			"",
			"<script>",
			"",
			"void test(){}",
			"",
			"</script>",
			"",
			"</class>",
		));
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testSlot1()
	{
		$this->reset();
		$content = \Runtime\rs::join("\n", new \Runtime\Vector(
			"<class name=\"App.Component\">",
			"",
			"<use name=\"Runtime.Widget.Button\" component=\"true\" />",
			"<use name=\"Runtime.Widget.Image\" component=\"true\" />",
			"<use name=\"Runtime.Widget.TextImage\" component=\"true\" />",
			"",
			"<template>",
			"\t<TextImage>",
			"\t\t<slot name=\"image\">",
			"\t\t\t<Image src=\"/assets/images/test.jpeg\" />",
			"\t\t</slot>",
			"\t\t<slot name=\"text\">",
			"\t\t\t<div class=\"image_text\">Text</div>",
			"\t\t</slot>",
			"\t</TextImage>",
			"</template>",
			"",
			"</class>",
		));
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testSlot2()
	{
		$this->reset();
		$content = \Runtime\rs::join("\n", new \Runtime\Vector(
			"<class name=\"App.Component\">",
			"",
			"<use name=\"Runtime.Widget.Button\" component=\"true\" />",
			"<use name=\"Runtime.Widget.Image\" component=\"true\" />",
			"<use name=\"Runtime.Widget.TextImage\" component=\"true\" />",
			"",
			"<template>",
			"\t<TextImage>",
			"\t\t<slot name=\"image\" args=\"Dict params\">",
			"\t\t\t<Image src={{ params.get(\"image\") }} />",
			"\t\t</slot>",
			"\t\t<slot name=\"text\" args=\"Dict params\">",
			"\t\t\t<div class=\"image_text\">{{ params.get(\"label\") }}</div>",
			"\t\t</slot>",
			"\t</TextImage>",
			"</template>",
			"",
			"</class>",
		));
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testStyle1()
	{
		$this->reset();
		$content = \Runtime\rs::join("\n", new \Runtime\Vector(
			"<class name=\"App.Component\">",
			"",
			"<style>",
			".page_title{",
			"\tfont-size: 18px;",
			"\ttext-align: center;",
			"}",
			"</style>",
			"",
			"</class>",
		));
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testStyle2()
	{
		$this->reset();
		$content = \Runtime\rs::join("\n", new \Runtime\Vector(
			"<class name=\"App.Component\">",
			"",
			"<style global=\"true\">",
			".page_title{",
			"\tfont-size: 18px;",
			"\ttext-align: center;",
			"}",
			"</style>",
			"",
			"</class>",
		));
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testStyle3()
	{
		$this->reset();
		$content = \Runtime\rs::join("\n", new \Runtime\Vector(
			"<class name=\"App.Component\">",
			"",
			"<style global=\"true\">",
			".main_page{",
			"background-image: \${ \"test\" };",
			"}",
			"</style>",
			"",
			"</class>",
		));
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testStyle4()
	{
		$this->reset();
		$content = \Runtime\rs::join("\n", new \Runtime\Vector(
			"<class name=\"App.Component\">",
			"",
			"<style global=\"true\">",
			"@font-face{",
			"}",
			"</style>",
			"",
			"</class>",
		));
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	
	function testStyle5()
	{
		$this->reset();
		$content = \Runtime\rs::join("\n", new \Runtime\Vector(
			"<class name=\"App.Component\">",
			"",
			"<style global=\"true\">",
			".main_page{",
			".test{",
			"font-size: 16px;",
			"}",
			"}",
			"</style>",
			"",
			"</class>",
		));
		$res = $this->translate($content);
		\Runtime\Unit\AssertHelper::equalValue($content, $res->get(1), $content);
	}
	
	
	/* ========= Class init functions ========= */
	function _init()
	{
	}
	static function getClassName(){ return "BayLang.Test.LangBay.Html"; }
	static function getMethodsList()
	{
		return new \Runtime\Vector("test1", "test2", "test3", "test4", "testTemplate1", "testTemplate2", "testTemplate3", "testComponent1", "testComponent2", "testComponent3", "testComponent4", "testComponent5", "testContent1", "testContent2", "testContent3", "testClick", "testScript1", "testSlot1", "testSlot2", "testStyle1", "testStyle2", "testStyle3", "testStyle4", "testStyle5");
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
		);if ($field_nane == "testTemplate1") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);if ($field_nane == "testTemplate2") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);if ($field_nane == "testTemplate3") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);if ($field_nane == "testComponent1") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);if ($field_nane == "testComponent2") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);if ($field_nane == "testComponent3") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);if ($field_nane == "testComponent4") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);if ($field_nane == "testComponent5") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);if ($field_nane == "testContent1") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);if ($field_nane == "testContent2") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);if ($field_nane == "testContent3") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);if ($field_nane == "testClick") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);if ($field_nane == "testScript1") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);if ($field_nane == "testSlot1") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);if ($field_nane == "testSlot2") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);if ($field_nane == "testStyle1") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);if ($field_nane == "testStyle2") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);if ($field_nane == "testStyle3") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);if ($field_nane == "testStyle4") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);if ($field_nane == "testStyle5") return new \Runtime\Vector(
			new \Runtime\Unit\Test(new \Runtime\Map())
		);
		return null;
	}
}