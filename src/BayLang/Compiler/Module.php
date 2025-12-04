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
use Runtime\re;
use Runtime\lib;
use Runtime\BaseObject;
use Runtime\SerializeInterface;
use Runtime\Serializer;
use BayLang\Compiler\Project;
use BayLang\LangBay\ParserBay;
use BayLang\LangES6\TranslatorES6;
use BayLang\LangNode\TranslatorNode;
use BayLang\LangPHP\TranslatorPHP;
use BayLang\OpCodes\BaseOpCode;
use BayLang\CoreTranslator;
use BayLang\LangUtils;


class Module extends \Runtime\BaseObject implements \Runtime\SerializeInterface
{
	var $project;
	var $is_exists;
	var $path;
	var $src_path;
	var $dest_path;
	var $name;
	var $submodules;
	var $allow;
	var $assets;
	var $required_modules;
	var $routes;
	var $groups;
	var $exclude;
	
	
	/**
	 * Constructor
	 */
	function __construct($project)
	{
		parent::__construct();
		$this->project = $project;
	}
	
	
	/**
	 * Read module
	 */
	function read($module_path)
	{
		$this->is_exists = false;
		$this->path = $module_path;
		if (!\Runtime\fs::isFolder($this->path)) return;
		/* Module json file */
		$module_json_path = $this->path . "/" . "module.json";
		if (!\Runtime\fs::isFile($module_json_path)) return;
		/* Read file */
		$content = \Runtime\fs::readFile($module_json_path);
		$module_info = \Runtime\rtl::jsonDecode($content);
		if (!$module_info) return;
		if (!$module_info->has("name")) return;
		$this->is_exists = true;
		$this->name = $module_info->get("name");
		$this->dest_path = $module_info->get("dest");
		$this->src_path = $module_info->get("src");
		$this->allow = $module_info->get("allow");
		$this->assets = $module_info->get("assets");
		$this->groups = $module_info->get("groups");
		$this->required_modules = $module_info->get("require");
		$this->submodules = $module_info->get("modules");
		$this->exclude = $module_info->get("exclude");
	}
	
	
	/**
	 * Process project cache
	 */
	function serialize($serializer, $data)
	{
		$serializer->process($this, "is_exists", $data);
		$serializer->process($this, "assets", $data);
		$serializer->process($this, "groups", $data);
		$serializer->process($this, "name", $data);
		$serializer->process($this, "path", $data);
		$serializer->process($this, "routes", $data);
		$serializer->process($this, "dest_path", $data);
		$serializer->process($this, "src_path", $data);
		$serializer->process($this, "required_modules", $data);
		$serializer->process($this, "submodules", $data);
	}
	
	
	/**
	 * Returns true if module is exists
	 */
	function exists(){ return $this->is_exists; }
	
	
	/**
	 * Returns module path
	 */
	function getPath(){ return $this->path; }
	
	
	/**
	 * Returns module name
	 */
	function getName(){ return $this->name; }
	
	
	/**
	 * Returns source folder path
	 */
	function getSourceFolderPath(){ return $this->src_path ? \Runtime\fs::join(new \Runtime\Vector($this->getPath(), $this->src_path)) : null; }
	
	
	
	/**
	 * Returns dest folder path
	 */
	function getDestFolderPath($lang)
	{
		if (!$this->dest_path->has($lang)) return "";
		return \Runtime\fs::join(new \Runtime\Vector($this->getPath(), $this->dest_path->get($lang)));
	}
	
	
	/**
	 * Returns relative source path
	 */
	function getRelativeSourcePath($file_path)
	{
		$source_path = $this->getSourceFolderPath();
		if (!$source_path) return null;
		$source_path_sz = \Runtime\rs::strlen($source_path);
		if (\Runtime\rs::substr($file_path, 0, $source_path_sz) != $source_path) return null;
		return \Runtime\rs::addFirstSlash(\Runtime\rs::substr($file_path, $source_path_sz));
	}
	
	
	/**
	 * Returns true if module contains file
	 */
	function checkFile($file_full_path){ return \Runtime\rs::indexOf($file_full_path, $this->path) == 0; }
	
	
	/**
	 * Check allow list
	 */
	function checkAllow($file_name)
	{
		if (!$this->allow) return false;
		$success = false;
		for ($i = 0; $i < $this->allow->count(); $i++)
		{
			$file_match = $this->allow->get($i);
			if ($file_match == "") continue;
			$res = \Runtime\re::match($file_match, $file_name);
			/* Ignore */
			if (\Runtime\rs::charAt($file_match, 0) == "!")
			{
				if ($res)
				{
					$success = false;
				}
			}
			else
			{
				if ($res)
				{
					$success = true;
				}
			}
		}
		return $success;
	}
	
	
	/**
	 * Check exclude
	 */
	function checkExclude($relative_src_file_path)
	{
		if (!$this->exclude) return false;
		if (!$relative_src_file_path) return false;
		for ($i = 0; $i < $this->exclude->count(); $i++)
		{
			$file_match = $this->exclude->get($i);
			if ($file_match == "") continue;
			$file_match = \Runtime\re::replace("\\/", "\\/", $file_match);
			$res = \Runtime\re::match($file_match, $relative_src_file_path);
			if ($res)
			{
				return true;
			}
		}
		return false;
	}
	
	
	/**
	 * Returns class name file path
	 */
	function resolveClassName($class_name)
	{
		/* Check if class name start with module name */
		$module_name_sz = \Runtime\rs::strlen($this->getName());
		if (\Runtime\rs::substr($class_name, 0, $module_name_sz) != $this->getName())
		{
			return "";
		}
		/* Remove module name from class name */
		$class_name = \Runtime\rs::substr($class_name, $module_name_sz);
		/* Return path to class name */
		$path = $this->getSourceFolderPath();
		$arr = \Runtime\rs::split(".", $class_name);
		$arr->prepend($path);
		return \Runtime\fs::join($arr) . ".bay";
	}
	
	
	/**
	 * Resolve source path
	 */
	function resolveSourceFilePath($relative_src_file_path){ return \Runtime\fs::join(new \Runtime\Vector($this->getSourceFolderPath(), $relative_src_file_path)); }
	
	
	
	/**
	 * Resolve dest path
	 */
	function resolveDestFilePath($relative_src_file_path, $lang)
	{
		$dest_folder_path = $this->getDestFolderPath($lang);
		if ($dest_folder_path == "") return "";
		/* Get dest file path */
		$dest_file_path = \Runtime\fs::join(new \Runtime\Vector($dest_folder_path, $relative_src_file_path));
		/* Resolve extension */
		if ($lang == "php")
		{
			$dest_file_path = \Runtime\re::replace("\\.bay\$", ".php", $dest_file_path);
			$dest_file_path = \Runtime\re::replace("\\.ui\$", ".php", $dest_file_path);
		}
		else if ($lang == "es6")
		{
			$dest_file_path = \Runtime\re::replace("\\.bay\$", ".js", $dest_file_path);
			$dest_file_path = \Runtime\re::replace("\\.ui\$", ".js", $dest_file_path);
		}
		else if ($lang == "nodejs")
		{
			$dest_file_path = \Runtime\re::replace("\\.bay\$", ".js", $dest_file_path);
			$dest_file_path = \Runtime\re::replace("\\.ui\$", ".js", $dest_file_path);
		}
		return $dest_file_path;
	}
	
	
	/**
	 * Returns true if module has group
	 */
	function hasGroup($group_name)
	{
		if (\Runtime\rs::substr($group_name, 0, 1) != "@") return false;
		$group_name = \Runtime\rs::substr($group_name, 1);
		if ($this->groups == null) return false;
		if ($this->groups->indexOf($group_name) == -1) return false;
		return true;
	}
	
	
	/**
	 * Returns true if this module contains in module list include groups
	 */
	function inModuleList($module_names)
	{
		for ($i = 0; $i < $module_names->count(); $i++)
		{
			$module_name = $module_names->get($i);
			if ($this->name == $module_name) return true;
			if ($this->hasGroup($module_name)) return true;
		}
		return false;
	}
	
	
	/**
	 * Compile file
	 */
	function compile($relative_src_file_path, $lang = "")
	{
		/* Get src file path */
		$src_file_path = $this->resolveSourceFilePath($relative_src_file_path);
		if ($src_file_path == "") return false;
		if (!$this->checkFile($src_file_path)) return false;
		if ($this->checkExclude($relative_src_file_path)) return false;
		/* Check extension */
		$arr = new \Runtime\Vector("bay", "es6", "php", "py", "ui");
		$extension = \Runtime\rs::extname($src_file_path);
		if ($arr->indexOf($extension) == -1) return false;
		/* Read file */
		if (!\Runtime\fs::isFile($src_file_path)) return false;
		$file_content = \Runtime\fs::readFile($src_file_path);
		/* Parse file */
		$file_op_code = null;
		if ($extension == "bay")
		{
			$parser = new \BayLang\LangBay\ParserBay();
			$parser->setContent($file_content);
			$file_op_code = $parser->parse();
			if (!$file_op_code) return false;
		}
		else
		{
			$lang = $extension;
		}
		/* Translate project languages */
		$this->translateLanguages($relative_src_file_path, $file_op_code ? $file_op_code : $file_content, $lang);
		return true;
	}
	
	
	/**
	 * Translate file
	 */
	function translateLanguages($relative_src_file_path, $op_code, $dest_lang = "")
	{
		/* Translate to destination language */
		if ($dest_lang != "")
		{
			$this->translate($relative_src_file_path, $op_code, $dest_lang);
		}
		else
		{
			$languages = $this->project->getLanguages();
			for ($i = 0; $i < $languages->count(); $i++)
			{
				$lang = $languages->get($i);
				if ($lang == "bay") continue;
				$this->translate($relative_src_file_path, $op_code, $lang);
			}
		}
	}
	
	
	/**
	 * Translate file
	 */
	function translate($relative_src_file_path, $op_code, $lang)
	{
		/* Get dest file path */
		$dest_file_path = $this->resolveDestFilePath($relative_src_file_path, $lang);
		if ($dest_file_path == "") return false;
		/* Create translator */
		$translator = \BayLang\LangUtils::createTranslator($lang);
		if (!$translator) return false;
		/* Translate */
		$dest_file_content = "";
		if ($op_code instanceof \BayLang\OpCodes\BaseOpCode)
		{
			$dest_file_content = $translator->translate($op_code);
		}
		else if (\Runtime\rtl::isString($op_code))
		{
			$dest_file_content = $op_code;
		}
		/* Create dest folder if not exists */
		$dest_dir_name = \Runtime\rs::dirname($dest_file_path);
		if (!\Runtime\fs::isFolder($dest_dir_name))
		{
			\Runtime\fs::mkdir($dest_dir_name);
		}
		/* Save file */
		\Runtime\fs::saveFile($dest_file_path, $dest_file_content);
		return true;
	}
	
	
	/**
	 * Returns projects assets
	 */
	function getProjectAssets()
	{
		$project_assets = $this->project->getAssets();
		$project_assets = $project_assets->filter(function ($asset)
		{
			if (!$asset->has("modules")) return false;
			/* Check module in modules names */
			$modules = $asset->get("modules");
			if (!$modules) return false;
			if (!\Runtime\rtl::isCollection($modules)) return false;
			if (!$this->inModuleList($modules)) return false;
			return true;
		});
		return $project_assets;
	}
	
	
	/**
	 * Update assets
	 */
	function updateAssets()
	{
		$languages = $this->project->getLanguages();
		if ($languages->indexOf("es6") == -1) return;
		/* Builds assets with current module */
		$project_assets = $this->getProjectAssets();
		for ($i = 0; $i < $project_assets->count(); $i++)
		{
			$this->project->buildAsset($project_assets->get($i));
		}
	}
	
	
	/* ========= Class init functions ========= */
	function _init()
	{
		parent::_init();
		$this->project = null;
		$this->is_exists = false;
		$this->path = "";
		$this->src_path = "";
		$this->dest_path = new \Runtime\Map();
		$this->name = "";
		$this->submodules = null;
		$this->allow = null;
		$this->assets = null;
		$this->required_modules = null;
		$this->routes = null;
		$this->groups = null;
		$this->exclude = null;
	}
	static function getClassName(){ return "BayLang.Compiler.Module"; }
	static function getMethodsList(){ return null; }
	static function getMethodInfoByName($field_name){ return null; }
}