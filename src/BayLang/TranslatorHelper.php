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

use Runtime\BaseObject;
use BayLang\CoreTranslator;
use BayLang\OpCodes\BaseOpCode;
use BayLang\OpCodes\OpDeclareFunction;
use BayLang\OpCodes\OpItems;


class TranslatorHelper extends \Runtime\BaseObject
{
	var $translator;
	
	
	/**
	 * Constructor
	 */
	function __construct($translator)
	{
		parent::__construct();
		$this->translator = $translator;
	}
	
	
	/**
	 * Returns methods with annotations
	 */
	function getMethodsWithAnnotations($op_code, $result)
	{
		for ($i = 0; $i < $op_code->count(); $i++)
		{
			$op_code_item = $op_code->get($i);
			if ($op_code_item instanceof \BayLang\OpCodes\OpDeclareFunction)
			{
				if ($op_code_item->annotations && $op_code_item->annotations->count() > 0)
				{
					$result->push($op_code_item);
				}
			}
		}
	}
	
	
	/* ========= Class init functions ========= */
	function _init()
	{
		parent::_init();
		$this->translator = null;
	}
	static function getClassName(){ return "BayLang.TranslatorHelper"; }
	static function getMethodsList(){ return null; }
	static function getMethodInfoByName($field_name){ return null; }
}