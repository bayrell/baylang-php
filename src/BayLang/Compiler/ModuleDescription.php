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
namespace BayLang\Compiler;

use Runtime\Entity\Provider;
use Runtime\Console\Annotations\ConsoleCommand;
use BayLang\ModuleDescription as BayLangModuleDescription;


class ModuleDescription
{
	/**
	 * Returns module name
	 * @return string
	 */
	static function getModuleName(){ return "BayLang.Compiler"; }
	
	
	/**
	 * Returns module name
	 * @return string
	 */
	static function getModuleVersion(){ return \BayLang\ModuleDescription::getModuleVersion(); }
	
	
	/**
	 * Returns required modules
	 * @return Map<string>
	 */
	static function requiredModules()
	{
		return new \Runtime\Map([
			"BayLang" => "*",
			"BayLang.Test" => "*",
			"Runtime.Unit" => "*",
		]);
	}
	
	
	/**
	 * Returns enities
	 */
	static function entities()
	{
		return new \Runtime\Vector(
			new \Runtime\Console\Annotations\ConsoleCommand("BayLang.Compiler.Commands.Compile"),
			new \Runtime\Console\Annotations\ConsoleCommand("BayLang.Compiler.Commands.Make"),
			new \Runtime\Console\Annotations\ConsoleCommand("BayLang.Compiler.Commands.MakeAll"),
			new \Runtime\Console\Annotations\ConsoleCommand("BayLang.Compiler.Commands.Modules"),
			new \Runtime\Console\Annotations\ConsoleCommand("BayLang.Compiler.Commands.Version"),
			new \Runtime\Console\Annotations\ConsoleCommand("BayLang.Compiler.Commands.Watch"),
		);
	}
	
	
	/* ========= Class init functions ========= */
	function _init()
	{
	}
	static function getClassName(){ return "BayLang.Compiler.ModuleDescription"; }
	static function getMethodsList(){ return null; }
	static function getMethodInfoByName($field_name){ return null; }
}