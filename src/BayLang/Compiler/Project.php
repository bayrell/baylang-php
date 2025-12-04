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

use Runtime\fs;
use Runtime\lib;
use Runtime\BaseObject;
use Runtime\SerializeInterface;
use Runtime\Serializer;
use BayLang\Compiler\Module;


class Project extends \Runtime\BaseObject implements \Runtime\SerializeInterface
{
	var $path;
	var $info;
	var $modules;
	
	
	/**
	 * Constructor
	 */
	function __construct()
	{
		parent::__construct();
	}
	
	
	/**
	 * Read project
	 */
	static function readProject($project_path)
	{
		$project = new \BayLang\Compiler\Project();
		$project->read($project_path);
		if (!$project->exists()) return null;
		$project->readModules();
		return $project;
	}
	
	
	/**
	 * Read project
	 */
	function read($project_path)
	{
		$this->info = null;
		$this->path = $project_path;
		$project_json_path = \Runtime\fs::join(new \Runtime\Vector($this->path, "project.json"));
		if (!\Runtime\fs::isFolder($this->path)) return;
		if (!\Runtime\fs::isFile($project_json_path)) return;
		/* Read file */
		$content = \Runtime\fs::readFile($project_json_path);
		$this->info = \Runtime\rtl::jsonDecode($content);
	}
	
	
	/**
	 * Save project
	 */
	function save()
	{
		$project_json_path = \Runtime\fs::join(new \Runtime\Vector($this->path, "project.json"));
		$content = \Runtime\rtl::json_encode($this->info, \Runtime\rtl::JSON_PRETTY);
		\Runtime\fs::saveFile($project_json_path, $content);
	}
	
	
	/**
	 * Process project cache
	 */
	function serialize($serializer, $data)
	{
		$serializer->processItems($this, "modules", $data, function ($serializer, $module){ return new \BayLang\Compiler\Module($this, $module->get("path")); });
	}
	
	
	/**
	 * Returns true if project is exists
	 */
	function exists()
	{
		if (!$this->info) return false;
		if (!$this->info->has("name")) return false;
		return true;
	}
	
	
	/**
	 * Returns project path
	 */
	function getPath(){ return $this->exists() ? $this->path : ""; }
	
	
	/**
	 * Returns project file_name
	 */
	function getID(){ return $this->exists() ? \Runtime\rs::basename($this->path) : ""; }
	
	
	/**
	 * Returns project name
	 */
	function getName(){ return $this->exists() ? $this->info->get("name") : ""; }
	
	
	/**
	 * Set project name
	 */
	function setName($name)
	{
		$this->info->set("name", $name);
	}
	
	
	/**
	 * Returns project description
	 */
	function getDescription(){ return $this->exists() ? $this->info->get("description") : ""; }
	
	
	/**
	 * Set project description
	 */
	function setDescription($description)
	{
		$this->info->set("description", $description);
	}
	
	
	/**
	 * Returns project type
	 */
	function getType(){ return $this->exists() ? $this->info->get("type") : ""; }
	
	
	/**
	 * Set project type
	 */
	function setType($project_type)
	{
		$this->info->set("type", $project_type);
	}
	
	
	/**
	 * Returns assets
	 */
	function getAssets(){ return $this->exists() ? $this->info->get("assets") : new \Runtime\Vector(); }
	
	
	/**
	 * Returns languages
	 */
	function getLanguages(){ return $this->exists() ? $this->info->get("languages") : new \Runtime\Vector(); }
	
	
	/**
	 * Returns modules
	 */
	function getModules(){ return $this->modules->copy(); }
	
	
	/**
	 * Returns module
	 */
	function getModule($module_name){ return $this->modules->get($module_name); }
	
	
	/**
	 * Returns modules by group name
	 */
	function getModulesByGroupName($group_name)
	{
		/* Get modules */
		$modules = $this->modules->transition(function ($module, $module_name){ return $module; });
		/* Filter modules by group */
		$modules = $modules->filter(function ($module) use (&$group_name){ return $module->hasGroup($group_name); });
		/* Get names */
		$modules = $modules->map(function ($item){ return $item->name; });
		/* Return modules */
		return $modules;
	}
	
	
	/**
	 * Find module by file name
	 */
	function findModuleByFileName($file_name)
	{
		$res = null;
		$module_path_sz = -1;
		$module_names = \Runtime\rtl::list($this->modules->keys());
		for ($i = 0; $i < $module_names->count(); $i++)
		{
			$module_name = $module_names->get($i);
			$module = $this->modules->get($module_name);
			if ($module->checkFile($file_name))
			{
				$sz = \Runtime\rs::strlen($module->path);
				if ($module_path_sz < $sz)
				{
					$module_path_sz = $sz;
					$res = $module;
				}
			}
		}
		return $res;
	}
	
	
	/**
	 * Read modules
	 */
	function readModules()
	{
		$this->modules = new \Runtime\Map();
		/* Read sub modules */
		$this->readSubModules($this->path, $this->info->get("modules"));
	}
	
	
	/**
	 * Read module
	 */
	function readModule($folder_path)
	{
		$module = new \BayLang\Compiler\Module($this);
		$module->read($folder_path);
		if ($module->exists())
		{
			/* Set module */
			$this->modules->set($module->getName(), $module);
			/* Read sub modules */
			$this->readSubModules($module->getPath(), $module->submodules);
		}
	}
	
	
	/**
	 * Read sub modules
	 */
	function readSubModules($path, $items)
	{
		if (!$items) return;
		for ($i = 0; $i < $items->count(); $i++)
		{
			$item = $items->get($i);
			$module_src = $item->get("src");
			$module_type = $item->get("type");
			$folder_path = \Runtime\fs::join(new \Runtime\Vector($path, $module_src));
			/* Read from folder */
			if ($module_type == "folder")
			{
				$this->readModuleFromFolder($folder_path);
			}
			else
			{
				$this->readModule($folder_path);
			}
		}
	}
	
	
	/**
	 * Read sub modules
	 */
	function readModuleFromFolder($folder_path)
	{
		if (!\Runtime\fs::isFolder($folder_path)) return;
		$items = \Runtime\fs::listDir($folder_path);
		for ($i = 0; $i < $items->count(); $i++)
		{
			$file_name = $items->get($i);
			if ($file_name == ".") continue;
			if ($file_name == "..") continue;
			/* Read module */
			$this->readModule(\Runtime\fs::join(new \Runtime\Vector($folder_path, $file_name)));
		}
	}
	
	
	/**
	 * Sort modules
	 */
	function sortRequiredModules($modules)
	{
		$result = new \Runtime\Vector();
		$add_module = function ($module_name) use (&$modules, &$result, &$add_module)
		{
			if ($modules->indexOf($module_name) == -1) return;
			/* Get module by name */
			$module = $this->modules->get($module_name);
			if (!$module) return;
			/* Add required modules */
			if ($module->required_modules != null)
			{
				for ($i = 0; $i < $module->required_modules->count(); $i++)
				{
					$add_module($module->required_modules->get($i));
				}
			}
			/* Add module if not exists */
			if ($result->indexOf($module_name) == -1)
			{
				$result->push($module_name);
			}
		};
		for ($i = 0; $i < $modules->count(); $i++)
		{
			$add_module($modules->get($i));
		}
		return $result;
	}
	
	
	/**
	 * Returns assets modules
	 */
	function getAssetModules($asset)
	{
		$modules = $asset->get("modules");
		/* Extends modules */
		$new_modules = new \Runtime\Vector();
		$modules->each(function ($module_name) use (&$new_modules)
		{
			if (\Runtime\rs::substr($module_name, 0, 1) == "@")
			{
				/* Get group modules by name */
				$group_modules = $this->getModulesByGroupName($module_name);
				/* Append group modules */
				$new_modules->appendItems($group_modules);
			}
			else
			{
				$new_modules->push($module_name);
			}
		});
		$modules = $new_modules->removeDuplicates();
		/* Sort modules by requires */
		$modules = $this->sortRequiredModules($modules);
		return $modules;
	}
	
	
	/**
	 * Build asset
	 */
	function buildAsset($asset)
	{
		$asset_path_relative = $asset->get("dest");
		if ($asset_path_relative == "") return;
		/* Get asset dest path */
		$asset_path = \Runtime\fs::join(new \Runtime\Vector($this->path, $asset_path_relative));
		$asset_content = "";
		/* Get modules names in asset */
		$modules = $this->getAssetModules($asset);
		for ($i = 0; $i < $modules->count(); $i++)
		{
			$module_name = $modules->get($i);
			$module = $this->modules->get($module_name);
			if (!$module) continue;
			/* Get files */
			for ($j = 0; $j < $module->assets->count(); $j++)
			{
				$file_name = $module->assets->get($j);
				$file_source_path = $module->resolveSourceFilePath($file_name);
				$file_dest_path = $module->resolveDestFilePath($file_name, "es6");
				if ($file_dest_path)
				{
					if (\Runtime\fs::isFile($file_dest_path))
					{
						$content = \Runtime\fs::readFile($file_dest_path);
						$asset_content .= $content . "\n";
					}
					else if (\Runtime\fs::isFile($file_source_path) && \Runtime\rs::extname($file_source_path) == "js")
					{
						$content = \Runtime\fs::readFile($file_source_path);
						$asset_content .= $content . "\n";
					}
				}
			}
		}
		/* Create directory if does not exists */
		$dir_name = \Runtime\rs::dirname($asset_path);
		if (!\Runtime\fs::isDir($dir_name))
		{
			\Runtime\fs::mkdir($dir_name);
		}
		/* Save file */
		\Runtime\fs::saveFile($asset_path, $asset_content);
	}
	
	
	/* ========= Class init functions ========= */
	function _init()
	{
		parent::_init();
		$this->path = "";
		$this->info = new \Runtime\Map();
		$this->modules = null;
	}
	static function getClassName(){ return "BayLang.Compiler.Project"; }
	static function getMethodsList(){ return null; }
	static function getMethodInfoByName($field_name){ return null; }
}