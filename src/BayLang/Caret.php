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
namespace BayLang;

use Runtime\BaseObject;
use Runtime\Reference;
use Runtime\SerializeInterface;
use Runtime\Serializer;
use BayLang\BaseOpCode;
use BayLang\Exceptions\ParserError;
use BayLang\Exceptions\ParserExpected;
use BayLang\Exceptions\ParserIdentifier;
use BayLang\OpCodes\OpIdentifier;


class Caret extends \Runtime\BaseObject implements \Runtime\SerializeInterface
{
	/**
	 * Content
	 */
	var $file_name;
	var $content;
	var $content_sz;
	
	
	/**
	 * Caret pos in file
	 */
	var $pos;
	
	
	/**
	 * Caret pos X
	 */
	var $x;
	
	
	/**
	 * Caret pos Y
	 */
	var $y;
	
	
	/**
	 * Tab size
	 */
	var $tab_size;
	
	
	/**
	 * Skip comments
	 */
	var $skip_comments;
	
	
	/**
	 * Constructor
	 */
	function __construct($items = null)
	{
		parent::__construct();
		if ($items)
		{
			if ($items->has("file_name")) $this->file_name = $items->get("file_name");
			if ($items->has("content")) $this->content = $items->get("content");
			if ($items->has("content_sz")) $this->content_sz = $items->get("content_sz");
			if ($items->has("content") && !$items->has("content_sz"))
			{
				$this->content_sz = \Runtime\rs::strlen($this->content->ref);
			}
			if ($items->has("tab_size")) $this->tab_size = $items->get("tab_size");
			if ($items->has("pos")) $this->pos = $items->get("pos");
			if ($items->has("x")) $this->x = $items->get("x");
			if ($items->has("y")) $this->y = $items->get("y");
		}
	}
	
	
	/**
	 * Clone
	 */
	function clone($items = null)
	{
		return new \BayLang\Caret(new \Runtime\Map([
			"file_name" => $items ? $items->get("file_name", $this->file_name) : $this->file_name,
			"content" => $items ? $items->get("content", $this->content) : $this->content,
			"content_sz" => $items ? $items->get("content_sz", $this->content_sz) : $this->content_sz,
			"tab_size" => $items ? $items->get("tab_size", $this->tab_size) : $this->tab_size,
			"pos" => $items ? $items->get("pos", $this->pos) : $this->pos,
			"x" => $items ? $items->get("x", $this->x) : $this->x,
			"y" => $items ? $items->get("y", $this->y) : $this->y,
		]));
	}
	
	
	/**
	 * Copy caret
	 */
	function copy($items = null){ return $this->clone($items); }
	
	
	/**
	 * Serialize object
	 */
	function serialize($serializer, $data)
	{
		$serializer->process($this, "pos", $data);
		$serializer->process($this, "x", $data);
		$serializer->process($this, "y", $data);
	}
	
	
	/**
	 * Seek caret
	 */
	function seek($caret)
	{
		$this->pos = $caret->pos;
		$this->x = $caret->x;
		$this->y = $caret->y;
	}
	
	
	/**
	 * Returns caret position
	 */
	function getPosition()
	{
		return new \Runtime\Map([
			"offset" => $this->x + 1,
			"line" => $this->y + 1,
		]);
	}
	
	
	/**
	 * Returns true if eof
	 */
	function eof(){ return $this->pos >= $this->content_sz; }
	
	
	/**
	 * Returns next X
	 */
	function nextX($ch, $direction = 1)
	{
		if ($ch == "\t") return $this->x + $this->tab_size * $direction;
		if ($ch == "\n") return 0;
		return $this->x + $direction;
	}
	
	
	/**
	 * Returns next Y
	 */
	function nextY($ch, $direction = 1)
	{
		if ($ch == "\n") return $this->y + $direction;
		return $this->y;
	}
	
	
	/**
	 * Returns next char
	 */
	function nextChar(){ return \Runtime\rs::charAt($this->content->ref, $this->pos, 1); }
	
	
	/**
	 * Returns string
	 */
	function getString($start_pos, $count){ return \Runtime\rs::substr($this->content->ref, $start_pos, $count); }
	
	
	/**
	 * Returns next string
	 */
	function nextString($count){ return \Runtime\rs::substr($this->content->ref, $this->pos, $count); }
	
	
	/**
	 * Returns true if next char
	 */
	function isNextChar($ch){ return $this->nextChar() == $ch; }
	
	
	/**
	 * Returns true if next string
	 */
	function isNextString($s){ return $this->nextString(\Runtime\rs::strlen($s)) == $s; }
	
	
	/**
	 * Shift by char
	 */
	function shift($ch)
	{
		$this->x = $this->nextX($ch);
		$this->y = $this->nextY($ch);
		$this->pos = $this->pos + 1;
	}
	
	
	/**
	 * Read char
	 */
	function readChar()
	{
		$ch = \Runtime\rs::charAt($this->content->ref, $this->pos);
		$this->shift($ch);
		return $ch;
	}
	
	
	/**
	 * Read char
	 */
	function readString($count)
	{
		$s = $this->nextString($count);
		$count_chars = \Runtime\rs::strlen($s);
		for ($i = 0; $i < $count_chars; $i++)
		{
			$ch = \Runtime\rs::charAt($s, $i);
			$this->shift($ch);
		}
		return $s;
	}
	
	
	/**
	 * Returns parser error
	 */
	function error($message)
	{
		return new \BayLang\Exceptions\ParserError($message, $this, $this->file_name);
	}
	
	
	/**
	 * Returns expected error
	 */
	function expected($message)
	{
		return new \BayLang\Exceptions\ParserExpected($message, $this, $this->file_name);
	}
	
	
	/**
	 * Match char
	 */
	function matchChar($ch)
	{
		$next = $this->nextChar();
		if ($next != $ch)
		{
			throw $this->expected($ch);
		}
		$this->readChar();
	}
	
	
	/**
	 * Match string
	 */
	function matchString($s)
	{
		$count = \Runtime\rs::strlen($s);
		$next_string = $this->nextString($count);
		if ($next_string != $s)
		{
			throw $this->expected($s);
		}
		$this->readString($count);
	}
	
	
	/**
	 * Return true if is char
	 * @param char ch
	 * @return boolean
	 */
	static function isChar($ch){ return \Runtime\rs::indexOf("qazwsxedcrfvtgbyhnujmikolp", \Runtime\rs::lower($ch)) !== -1; }
	
	
	
	/**
	 * Return true if is number
	 * @param char ch
	 * @return boolean
	 */
	static function isNumberChar($ch){ return \Runtime\rs::indexOf("0123456789", $ch) !== -1; }
	
	
	
	/**
	 * Return true if char is number
	 * @param char ch
	 * @return boolean
	 */
	static function isHexChar($ch){ return \Runtime\rs::indexOf("0123456789abcdef", \Runtime\rs::lower($ch)) !== -1; }
	
	
	
	/**
	 * Return true if is string of numbers
	 * @param string s
	 * @return boolean
	 */
	static function isNumber($s)
	{
		$sz = \Runtime\rs::strlen($s);
		for ($i = 0; $i < $sz; $i++)
		{
			if (!static::isNumberChar(\Runtime\rs::charAt($s, $i))) return false;
		}
		return true;
	}
	
	
	/**
	 * Return true if char is system or space. ASCII code <= 32.
	 * @param char ch
	 * @return boolean
	 */
	static function isSkipChar($ch)
	{
		if (\Runtime\rs::ord($ch) <= 32) return true;
		return false;
	}
	
	
	/**
	 * Skip chars
	 */
	function skipChar($ch)
	{
		if ($this->nextChar() == $ch)
		{
			$this->readChar();
			return true;
		}
		return false;
	}
	
	
	/**
	 * Skip space
	 */
	function skipSpace()
	{
		while (!$this->eof() && static::isSkipChar($this->nextChar())) $this->readChar();
		return $this;
	}
	
	
	/**
	 * Skip comment
	 */
	function skipComment()
	{
		if ($this->nextString(2) == "/*")
		{
			$this->readChar();
			while (!$this->eof() && $this->nextString(2) != "*/") $this->readChar();
			$this->readString(2);
		}
		return $this;
	}
	
	
	/**
	 * Skip token
	 */
	function skipToken()
	{
		$this->skipSpace();
		if ($this->skip_comments)
		{
			while (!$this->eof() && $this->nextString(2) == "/*")
			{
				$this->skipComment();
				$this->skipSpace();
			}
		}
		return $this;
	}
	
	
	/**
	 * Returns true if token char
	 */
	function isTokenChar($ch)
	{
		return \Runtime\rs::indexOf("qazwsxedcrfvtgbyhnujmikolp0123456789_", \Runtime\rs::lower($ch)) !== -1;
	}
	
	
	/**
	 * Read next token
	 */
	function readToken()
	{
		/* Skip token */
		$this->skipToken();
		if ($this->eof()) return "";
		/* Read special token */
		$token = $this->readSpecialToken();
		if ($token)
		{
			$this->readString(\Runtime\rs::strlen($token));
			return $token;
		}
		/* Read char */
		if (!$this->isTokenChar($this->nextChar())) return $this->readChar();
		/* Read token */
		$items = new \Runtime\Vector();
		while (!$this->eof() && $this->isTokenChar($this->nextChar()))
		{
			$items->push($this->readChar());
		}
		return \Runtime\rs::join("", $items);
	}
	
	
	/**
	 * Read special token
	 */
	function readSpecialToken()
	{
		if ($this->eof()) return "";
		$s = $this->nextString(10);
		if ($s == "#endswitch") return $s;
		$s = $this->nextString(7);
		if ($s == "#ifcode" || $s == "#switch" || $s == "#elseif" || $s == "%render") return $s;
		$s = $this->nextString(6);
		if ($s == "#endif" || $s == "#ifdef" || $s == "%while") return $s;
		$s = $this->nextString(5);
		if ($s == "#case" || $s == "%else" || $s == "<?php") return $s;
		$s = $this->nextString(4);
		if ($s == "@css" || $s == "%for" || $s == "%var" || $s == "%set") return $s;
		$s = $this->nextString(3);
		if ($s == "!--" || $s == "!==" || $s == "===" || $s == "..." || $s == "#if" || $s == "%if") return $s;
		$s = $this->nextString(2);
		if ($s == "{{" || $s == "}}" || $s == "==" || $s == "!=" || $s == "<=" || $s == ">=" || $s == "=>" || $s == "->" || $s == "|>" || $s == "</" || $s == "/>" || $s == "||" || $s == "&&" || $s == "::" || $s == "+=" || $s == "-=" || $s == "~=" || $s == "**" || $s == "<<" || $s == ">>" || $s == "++" || $s == "--") return $s;
		return "";
	}
	
	
	/* ========= Class init functions ========= */
	function _init()
	{
		parent::_init();
		$this->file_name = null;
		$this->content = null;
		$this->content_sz = 0;
		$this->pos = 0;
		$this->x = 0;
		$this->y = 0;
		$this->tab_size = 4;
		$this->skip_comments = true;
	}
	static function getClassName(){ return "BayLang.Caret"; }
	static function getMethodsList(){ return null; }
	static function getMethodInfoByName($field_name){ return null; }
}