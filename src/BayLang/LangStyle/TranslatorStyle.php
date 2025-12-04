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
namespace BayLang\LangStyle;

use Runtime\BaseObject;
use BayLang\LangStyle\Selector;
use BayLang\OpCodes\BaseOpCode;
use BayLang\OpCodes\OpHtmlCSS;
use BayLang\OpCodes\OpHtmlCSSAttribute;
use BayLang\OpCodes\OpHtmlStyle;
use BayLang\CoreTranslator;


class TranslatorStyle extends \Runtime\BaseObject
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
	 * Add selector content
	 */
	function addSelectorContent($result, $media, $content)
	{
		if (!$result->has($media)) $result->set($media, new \Runtime\Vector());
		$items = $result->get($media);
		if (\Runtime\rtl::isString($content)) $items->push($content);
		else if ($content instanceof \Runtime\Vector) $items->appendItems($content);
	}
	
	
	/**
	 * Translate CSS
	 */
	function OpHtmlCSS($op_code, $result, $selector, $is_global)
	{
		$item_content = new \Runtime\Vector();
		$item_result = new \Runtime\Map();
		/* Get hash */
		$css_hash = \Runtime\rs::getCssHash($this->translator->current_class_name);
		/* Add selector */
		if (!$is_global && \Runtime\rs::charAt($op_code->selector, 0) != "@")
		{
			$selector->add(\BayLang\LangStyle\Selector::addHash($op_code->selector, $css_hash));
			$is_global = true;
		}
		else
		{
			$selector->add($op_code->selector);
		}
		$media = $selector->getMedia();
		$selector_path = $selector->getSelector();
		for ($i = 0; $i < $op_code->items->count(); $i++)
		{
			$op_code_item = $op_code->items->get($i);
			if ($op_code_item instanceof \BayLang\OpCodes\OpHtmlCSS)
			{
				$this->OpHtmlCSS($op_code_item, $item_result, $selector->copy(), $is_global);
			}
			else if ($op_code_item instanceof \BayLang\OpCodes\OpHtmlCSSAttribute)
			{
				$item_content->push($op_code_item->key . ": " . $op_code_item->value . ";");
			}
		}
		if ($item_content->count() > 0)
		{
			$content = $selector_path . "{" . \Runtime\rs::substr(\Runtime\rs::join("", $item_content), 0, -1) . "}";
			$this->addSelectorContent($result, $media, $content);
		}
		$keys = \Runtime\rtl::list($item_result->keys());
		for ($i = 0; $i < $keys->count(); $i++)
		{
			$key = $keys->get($i);
			$this->addSelectorContent($result, $key, $item_result->get($key));
		}
	}
	
	
	/**
	 * Translate HTML Style
	 */
	function OpHtmlStyle($op_code, $result)
	{
		$selector = new \BayLang\LangStyle\Selector();
		$selector->css_hash = \Runtime\rs::getCssHash($this->translator->current_class_name);
		for ($i = 0; $i < $op_code->content->count(); $i++)
		{
			$op_code_item = $op_code->content->get($i);
			$item_result = new \Runtime\Map();
			$this->OpHtmlCSS($op_code_item, $item_result, $selector->copy(), $op_code->is_global);
			$media_keys = \Runtime\rtl::list($item_result->keys());
			for ($j = 0; $j < $media_keys->count(); $j++)
			{
				$media = $media_keys->get($j);
				$items = $item_result->get($media);
				$content = \Runtime\rs::join("", $items);
				if ($media != "") $result->push(\Runtime\rs::trim($media) . "{" . $content . "}");
				else $result->push($content);
			}
		}
	}
	
	
	/* ========= Class init functions ========= */
	function _init()
	{
		parent::_init();
		$this->translator = null;
	}
	static function getClassName(){ return "BayLang.LangStyle.TranslatorStyle"; }
	static function getMethodsList(){ return null; }
	static function getMethodInfoByName($field_name){ return null; }
}