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

use Runtime\fs;
use Runtime\Console\BaseCommand;
use Runtime\Console\CommandsList;
use BayLang\Compiler\Module;
use BayLang\Compiler\Project;
use BayLang\Compiler\Commands\Modules;
use BayLang\Compiler\SettingsProvider;
use BayLang\Exceptions\ParserUnknownError;


class Make extends \Runtime\Console\BaseCommand
{
	/**
	 * Returns name
	 */
	static function getName(){ return "make"; }
	
	
	/**
	 * Returns description
	 */
	static function getDescription(){ return "Make module"; }
	
	
	/**
	 * Build asset
	 */
	function buildAsset($project, $module)
	{
		$languages = $project->getLanguages();
		if ($languages->indexOf("es6") != -1)
		{
			$project_assets = $module->getProjectAssets();
			for ($i = 0; $i < $project_assets->count(); $i++)
			{
				$asset_item = $project_assets->get($i);
				$project->buildAsset($asset_item);
				\Runtime\rtl::print("Bundle to => " . $asset_item->get("dest"));
			}
		}
	}
	
	
	/**
	 * Compile module
	 */
	function compile($project, $module, $lang = "")
	{
		$is_success = true;
		$module_src_path = $module->getSourceFolderPath();
		$files = \Runtime\fs::listDirRecursive($module_src_path);
		for ($i = 0; $i < $files->count(); $i++)
		{
			$file_name = $files[$i];
			$file_path = \Runtime\fs::join(new \Runtime\Vector($module_src_path, $file_name));
			/* Detect is file */
			if (!\Runtime\fs::isFile($file_path))
			{
				continue;
			}
			/* Check if not exclude */
			if ($module->checkExclude($file_name))
			{
				continue;
			}
			/* Compile */
			try
			{
				$extension = \Runtime\rs::extname($file_name);
				if ($extension == "bay")
				{
					\Runtime\rtl::print($file_name);
					$module->compile($file_name, $lang);
				}
			}
			catch (\BayLang\Exceptions\ParserUnknownError $e)
			{
				\Runtime\rtl::error($e->toString());
				$is_success = false;
			}
			catch (\Exception $e)
			{
				\Runtime\rtl::error($e);
				$is_success = false;
			}
		}
		if (!$is_success)
		{
			return false;
		}
		$this->buildAsset($project, $module);
		return true;
	}
	
	
	/**
	 * Run task
	 */
	function run()
	{
		$module_name = \Runtime\rtl::getContext()->cli_args[2];
		$lang = \Runtime\rtl::getContext()->cli_args[3];
		if (!$module_name)
		{
			\BayLang\Compiler\Commands\Modules::showModules();
			return 0;
		}
		/* Read project */
		$project = \BayLang\Compiler\Project::readProject(\Runtime\rtl::getContext()->base_path);
		if (!$project)
		{
			\Runtime\rtl::error("Project not found");
			return;
		}
		/* Get module */
		$module = $project->getModule($module_name);
		if (!$module)
		{
			\Runtime\rtl::error("Module not found");
			return;
		}
		/* Compile module */
		$is_success = $this->compile($project, $module, $lang);
		if (!$is_success)
		{
			return static::ERROR;
		}
		return static::SUCCESS;
	}
	
	
	/* ========= Class init functions ========= */
	function _init()
	{
		parent::_init();
	}
	static function getClassName(){ return "BayLang.Compiler.Commands.Make"; }
	static function getMethodsList(){ return null; }
	static function getMethodInfoByName($field_name){ return null; }
}