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

use Runtime\io;
use Runtime\Console\BaseCommand;
use Runtime\Console\CommandsList;
use BayLang\Compiler\Module;
use BayLang\Compiler\Project;
use BayLang\Compiler\Commands\Make;


class MakeAll extends \Runtime\Console\BaseCommand
{
	/**
	 * Returns name
	 */
	static function getName(){ return "make_all"; }
	
	
	/**
	 * Returns description
	 */
	static function getDescription(){ return "Make all modules"; }
	
	
	/**
	 * Run task
	 */
	static function run()
	{
		/* Read project */
		$project = \BayLang\Compiler\Project::readProject(\Runtime\rtl::getContext()->base_path);
		if (!$project)
		{
			\Runtime\rtl::error("Project not found");
			return static::ERROR;
		}
		$make = new \BayLang\Compiler\Commands\Make();
		$modules = $project->getModules();
		$keys = \Runtime\rtl::list($modules->keys());
		for ($i = 0; $i < $keys->count(); $i++)
		{
			$module_name = $keys->get($i);
			$module = $modules->get($module_name);
			\Runtime\rtl::print(\Runtime\rtl::color("yellow", "Compile " . $module->name));
			$make->compile($project, $module);
		}
		return static::SUCCESS;
	}
	
	
	/* ========= Class init functions ========= */
	function _init()
	{
		parent::_init();
	}
	static function getClassName(){ return "BayLang.Compiler.Commands.MakeAll"; }
	static function getMethodsList(){ return null; }
	static function getMethodInfoByName($field_name){ return null; }
}