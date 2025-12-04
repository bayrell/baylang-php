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
namespace BayLang\Compiler\Commands;

use Runtime\Method;
use Runtime\Console\BaseCommand;
use Runtime\Console\CommandsList;


class Version extends \Runtime\Console\BaseCommand
{
	/**
	 * Returns name
	 */
	static function getName(){ return "version"; }
	
	
	/**
	 * Returns description
	 */
	static function getDescription(){ return "Show version"; }
	
	
	/**
	 * Run task
	 */
	static function run()
	{
		$runtime_version = new \Runtime\Method("Runtime.ModuleDescription", "getModuleVersion");
		$lang_version = new \Runtime\Method("BayLang.ModuleDescription", "getModuleVersion");
		\Runtime\rtl::print("Lang version: " . $lang_version->apply());
		\Runtime\rtl::print("Runtime version: " . $runtime_version->apply());
		return static::SUCCESS;
	}
	
	
	/* ========= Class init functions ========= */
	function _init()
	{
		parent::_init();
	}
	static function getClassName(){ return "BayLang.Compiler.Commands.Version"; }
	static function getMethodsList(){ return null; }
	static function getMethodInfoByName($field_name){ return null; }
}