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
use BayLang\Exceptions\ParserUnknownError;


class Watch extends \Runtime\Console\BaseCommand
{
	/**
	 * Returns name
	 */
	static function getName(){ return "watch"; }
	
	
	/**
	 * Returns description
	 */
	static function getDescription(){ return "Watch changes"; }
	
	
	/**
	 * On change file
	 */
	function onChangeFile($changed_file_path)
	{
		/* Read project */
		$project = \BayLang\Compiler\Project::readProject(\Runtime\rtl::getContext()->base_path);
		if (!$project)
		{
			\Runtime\rtl::error("Project not found");
			return;
		}
		$make = new \BayLang\Compiler\Commands\Make();
		$module = $project->findModuleByFileName($changed_file_path);
		if ($module)
		{
			try
			{
				$file_path = $module->getRelativeSourcePath($changed_file_path);
				if (!$file_path) return;
				if ($module->checkExclude($file_path)) return;
				$extension = \Runtime\rs::extname($file_path);
				$result = $module->compile($file_path);
				if ($result)
				{
					\Runtime\rtl::print($changed_file_path);
					\Runtime\rtl::wait(1000);
					$languages = $project->getLanguages();
					for ($i = 0; $i < $languages->count(); $i++)
					{
						$lang = $languages->get($i);
						$dest_file_path = $module->resolveDestFilePath($file_path, $lang);
						if ($dest_file_path == "") continue;
						if ($extension != "bay" && $extension != $lang) continue;
						\Runtime\rtl::print("=> " . $dest_file_path);
					}
					$make->buildAsset($project, $module);
				}
			}
			catch (\BayLang\Exceptions\ParserUnknownError $e)
			{
				\Runtime\rtl::print($changed_file_path);
				\Runtime\rtl::error($e->toString());
			}
		}
	}
	
	
	/**
	 * Run task
	 */
	function run()
	{
		$base_path = \Runtime\rtl::getContext()->base_path;
		exec("which inotifywait", $output, $return_var);
		if ($return_var !== 0)
		{
			throw new \Exception("inotifywait not found. Please install inotify-tools package.");
		}
		
		$command = "inotifywait -r -m -e modify,create,delete,move '$base_path' 2>&1";
		
		$handle = popen($command, 'r');
		if (!$handle)
		{
			throw new \Exception("Failed to start inotifywait");
		}
		
		echo "Start watch\n";
		
		$files = [];
		
		/* Read inotify */
		while (!feof($handle))
		{
			$line = fgets($handle);
			if (!$line) continue;
			
			$parts = explode(" ", trim($line), 3);
			if (count($parts) < 3) continue;
			
			$directory = rtrim($parts[0], "/");
			$event = $parts[1];
			$filename = $parts[2];
			if (strpos($filename, '.') === 0) continue;
			if ($filename === "") continue;
			
			$full_path = $directory . "/" . $filename;
			if (!file_exists($full_path)) continue;
			if (is_dir($full_path)) continue;
			
			if (isset($files[$full_path]) && $files[$full_path] >= time()) continue;
			$files[$full_path] = time() + 2;
			
			$this->onChangeFile($full_path);
		}
		
		pclose($handle);
		return static::SUCCESS;
	}
	
	
	/* ========= Class init functions ========= */
	function _init()
	{
		parent::_init();
	}
	static function getClassName(){ return "BayLang.Compiler.Commands.Watch"; }
	static function getMethodsList(){ return null; }
	static function getMethodInfoByName($field_name){ return null; }
}