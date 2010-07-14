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

use pia\lexer\Token;
use pia\lexer\TokenStream;

class Lexer implements TokenStream
{
	const STATE_DEFAULT = 1;
	const STATE_STRING = 2;

	private $input;
	private $queue;
	private $line;
	private $state;
	private $offset;
	private $size;
	private $buffer;
	private $lineCount;
	private $multibyteSafe;
	private $charset;

	function __construct($input) {
		$this->setInput($input);
		$this->multibyteSafe = false;
		$this->charset = 'UTF-8';
	}

	function reset() {
		$this->lineCount = count($this->input);
		$this->line = 0;
		$this->offset = 0;
		$this->queue = array();
		$this->state = self::STATE_DEFAULT;
	}

	function setInput($input) {
		$this->input = explode("\n", trim($input, '*/'));
		$this->reset();
	}

	function setMultibyteSafe($multibyteSafe) {
		if ($multibyteSafe && !extension_loaded('iconv')) {
			throw new RuntimeException('multibyte safety requires iconv');
		}
		$this->multibyteSafe = $multibyteSafe;
	}

	function setCharset($charset) {
		$this->charset = $charset;
	}

	function next() {
		$this->populateQueue();
		return array_shift($this->queue);
	}

	function peek() {
		$this->populateQueue();
		return reset($this->queue);
	}

	private function populateQueue() {
		while (empty($this->queue) && $this->line < $this->lineCount) {
			if ($this->multibyteSafe) {
				$this->size = iconv_strlen($this->input[$this->line],
					$this->charset);
			}
			else {
				$this->size = strlen($this->input[$this->line]);
			}
			$this->offset = 0;
			$this->lex();
			$this->line++;
		}
	}

	private function lex()
	{
		while ($this->offset < $this->size) {
			switch ($this->state) {
				case self::STATE_DEFAULT:
					$this->lexDefault();
					break;
				case self::STATE_STRING:
					$this->lexString();
					break;
				default:
					throw new \RuntimeException();
			}
			$this->offset++;
		}

		if ($this->buffer) {
			$this->pushBuffer();
			$this->buffer = '';
		}
	}

	private function lexDefault()
	{
		$char = $this->nextChar();

		// skip whitespace and the "*" character
		if (ctype_space($char) || $char == '*') {
			if ($this->buffer) {
				$this->pushBuffer();
				$this->buffer = '';
			}
			return;
		}
		// enter string state
		if ($char == '"') {
			if ($this->buffer) {
				$this->pushBuffer();
				$this->buffer = '';
			}
			$this->state = self::STATE_STRING;
			return;
		}

		// symbol lookup
		if (false !== ($type = Token::lookupType($char))) {
			if ($this->buffer) {
				$this->pushBuffer();
				$this->buffer = '';
			}
			$this->queue[] = new Token($type, $char,
				$this->offset, $this->offset + 1);
			return;
		}

		$this->buffer .= $char;
	}

	private function lexString()
	{
		$char = $this->nextChar();

		if ($char == '"') {
			$this->state = self::STATE_DEFAULT;
			$this->pushBuffer(Token::STRING);
			$this->buffer = '';
			return;
		}

		$this->buffer .= $char;
	}

	private function nextChar() {
		if ($this->multibyteSafe) {
			return iconv_substr($this->input[$this->line],
				$this->offset, 1, $this->charset);
		}
		else {
			return $this->input[$this->line][$this->offset];
		}
	}

	private function pushBuffer($type = null, $offset = null)
	{
		$bufferLength = $this->multibyteSafe ?
			iconv_strlen($this->buffer, $this->charset) :
			strlen($this->buffer);

		if ($type == null && $bufferLength == 1) {
			if (false === $type = Token::lookupType($this->buffer)) {
				$type = $this->getBufferType();
			}
		}
		else if ($type == null) {
			$type = $this->getBufferType();
		}

		if ($offset == null) {
			$offset = $this->offset;
		}

		$token = new Token($type, $this->buffer,
			$offset - $bufferLength, $offset
		);

		$this->queue[] = $token;
	}

	private function getBufferType() {
		if (is_numeric($this->buffer)) {
			return Token::NUMERIC;
		}

		return Token::LITERAL;
	}
}