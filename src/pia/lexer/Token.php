<?php
/* This file is part of Pia.
 *
 * Pia is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, version 3 of the License.
 *
 * Pia is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Pia. If not, see <http://www.gnu.org/licenses/>.
 */

namespace pia\lexer;

/**
 * This class represents a lexer token.
 */
class Token
{
	// symbols
	const AT 		= 11;
	const PAREN_L 	= 12;
	const PAREN_R 	= 13;
	const EQ 		= 14;
	const COMMA		= 15;
	const BRACK_L	= 16;
	const BRACK_R	= 17;
	const COLON		= 18;

	// literals
	const LITERAL 	= 21;
	const STRING	= 22;
	const NUMERIC 	= 23;

	private $type;
	private $text;
	private $startOffset;
	private $endOffset;

	function __construct($type, $text, $startOffset, $endOffset) {
		$this->type = $type;
		$this->text = $text;
		$this->startOffset = $startOffset;
		$this->endOffset = $endOffset;
	}

	static function stringFromType($type) {
		static $map;
		if ($map == null) {
			$class = new \ReflectionClass(__CLASS__);
			$map = array_flip($class->getConstants());
		}
		return @$map[$type];
	}

	static function lookupType($char)
	{
		static $map;
		if ($map == null) {
			$map = array(
				'@' => self::AT,
				'(' => self::PAREN_L,
				')' => self::PAREN_R,
				'=' => self::EQ,
				',' => self::COMMA,
				'['	=> self::BRACK_L,
				']'	=> self::BRACK_R,
				':'	=> self::COLON,
			);
		}

		if (array_key_exists($char, $map)) {
			return $map[$char];
		}

		return false;
	}

	function getType() {
		return $this->type;
	}

	function getText() {
		return $this->text;
	}

	function getStartOffset() {
		return $this->startOffset;
	}

	function getEndOffset() {
		return $this->endOffset;
	}

	function isLiteral() {
		return $this->type == self::LITERAL
			|| $this->type == self::NUMERIC
			|| $this->type == self::STRING;
	}

	function __toString()
	{
		return sprintf('%-16s %-32s @ %02d,%02d',
			self::stringFromType($this->type),
			sprintf('"%s"', $this->text),
			$this->startOffset,
			$this->endOffset
		);
	}
}