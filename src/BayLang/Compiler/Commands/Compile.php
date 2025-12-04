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
use Runtime\Callback;
use Runtime\Console\BaseCommand;
use Runtime\Console\CommandsList;
use BayLang\CoreParser;
use BayLang\CoreTranslator;
use BayLang\LangUtils;
use BayLang\Exceptions\ParserError;
use BayLang\OpCodes\BaseOpCode;


class Compile extends \Runtime\Console\BaseCommand
{
	/**
	 * Returns name
	 */
	static function getName(){ return "compile"; }
	
	
	/**
	 * Returns description
	 */
	static function getDescription(){ return "Compile file"; }
	
	
	/**
	 * Run task
	 */
	static function run()
	{
		$command = \Runtime\rtl::getContext()->cli_args[2];
		$src_file_name = \Runtime\rtl::getContext()->cli_args[3];
		$dest_file_name = \Runtime\rtl::getContext()->cli_args[4];
		/* Check command */
		if (!$command)
		{
			\Runtime\rtl::print("Print <command> <src> <dest>");
			\Runtime\rtl::print("Command: bay_to_php, bay_to_es6, php_to_bay, php_to_es6, es6_to_bay, es6_to_php");
			return static::FAIL;
		}
		/* Check src */
		if (!$src_file_name)
		{
			\Runtime\rtl::print_error("Print src file name");
			return static::FAIL;
		}
		/* Check dest */
		if (!$dest_file_name)
		{
			\Runtime\rtl::print_error("Print dest file name");
			return static::FAIL;
		}
		\Runtime\rtl::print("Convert " . $src_file_name . " to " . $dest_file_name);
		$res = \BayLang\LangUtils::parseCommand($command);
		$parser = \BayLang\LangUtils::createParser($res->get("from"));
		$translator = \BayLang\LangUtils::createTranslator($res->get("to"));
		/* Check file exists */
		if (!\Runtime\fs::isFile($src_file_name))
		{
			\Runtime\rtl::print_error("File not found");
			return static::FAIL;
		}
		/* Read file name */
		$op_code = null;
		$content = \Runtime\fs::readFile($src_file_name);
		$output = "";
		/* Translate file */
		try
		{
			$parser->setContent($content);
			$op_code = $parser->parse();
			$output = $translator->translate($op_code);
		}
		catch (\BayLang\Exceptions\ParserError $error)
		{
			\Runtime\rtl::print_error($error->toString());
			return static::FAIL;
		}
		/* Save file */
		\Runtime\fs::saveFile($dest_file_name, $output);
		/* Return result */
		\Runtime\rtl::print("Ok");
		return static::SUCCESS;
	}
	
	
	/* ========= Class init functions ========= */
	function _init()
	{
		parent::_init();
	}
	static function getClassName(){ return "BayLang.Compiler.Commands.Compile"; }
	static function getMethodsList(){ return null; }
	static function getMethodInfoByName($field_name){ return null; }
}