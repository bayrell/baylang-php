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
namespace BayLang\OpCodes;

use Runtime\Reference;
use Runtime\Serializer;
use BayLang\Caret;
use BayLang\TokenReader;
use BayLang\Exceptions\ParserUnknownError;
use BayLang\LangBay\ParserBay;
use BayLang\LangBay\ParserBayHtml;
use BayLang\OpCodes\BaseOpCode;


class OpHtmlStyle extends \BayLang\OpCodes\BaseOpCode
{
	var $op;
	var $is_global;
	var $content;
	
	
	/**
	 * Serialize object
	 */
	function serialize($serializer, $data)
	{
		parent::serialize($serializer, $data);
		$serializer->process($this, "content", $data);
		$serializer->process($this, "is_global", $data);
		$serializer->process($this, "value", $data);
	}
	
	
	/* ========= Class init functions ========= */
	function _init()
	{
		parent::_init();
		$this->op = "op_html_style";
		$this->is_global = false;
		$this->content = null;
	}
	static function getClassName(){ return "BayLang.OpCodes.OpHtmlStyle"; }
	static function getMethodsList(){ return null; }
	static function getMethodInfoByName($field_name){ return null; }
}