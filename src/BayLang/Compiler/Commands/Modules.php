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

use Runtime\Console\BaseCommand;
use Runtime\Console\CommandsList;
use BayLang\Compiler\Module;
use BayLang\Compiler\Project;
use BayLang\Compiler\ConsoleApp;


class Modules extends \Runtime\Console\BaseCommand
{
	/**
	 * Returns name
	 */
	static function getName(){ return "modules"; }
	
	
	/**
	 * Returns description
	 */
	static function getDescription(){ return "Show modules"; }
	
	
	/**
	 * Run task
	 */
	static function run()
	{
		static::showModules(true);
		return static::SUCCESS;
	}
	
	
	/**
	 * Show modules
	 */
	static function showModules($verbose = false)
	{
		$project = \BayLang\Compiler\Project::readProject(\Runtime\rtl::getContext()->base_path);
		if (!$project)
		{
			return;
		}
		$modules = $project->getModules();
		$modules_names = \Runtime\rtl::list($modules->keys())->sort();
		for ($i = 0; $i < $modules_names->count(); $i++)
		{
			$module_name = $modules_names[$i];
			$module = $modules->get($module_name);
			if ($verbose)
			{
				\Runtime\rtl::print($i + 1 . ") " . \Runtime\rtl::color("yellow", $module_name) . " - " . $module->path);
			}
			else
			{
				\Runtime\rtl::print($module_name);
			}
		}
	}
	
	
	/* ========= Class init functions ========= */
	function _init()
	{
		parent::_init();
	}
	static function getClassName(){ return "BayLang.Compiler.Commands.Modules"; }
	static function getMethodsList(){ return null; }
	static function getMethodInfoByName($field_name){ return null; }
}