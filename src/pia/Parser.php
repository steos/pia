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

namespace pia;

use pia\lexer\TokenStream;
use pia\lexer\Token;
use pia\ParseException;
use pia\Annotation;

/**
 * This is the pia parser implementation.
 *
 * This class encodes the grammar of the pia annotation syntax. It's a
 * straight-forward top-down recursive descent implementation. It depends
 * on a TokenStream implementation.
 *
 * @see TokenStream
 */
class Parser
{
	private $lexer;

	/**
	 * creates a new parser with the given token stream
	 *
	 * @param TokenStream $lexer the token stream input
	 */
	function __construct(TokenStream $lexer) {
		$this->setLexer($lexer);
	}

	/**
	 * parses the token stream and returns the resulting annotations
	 *
	 * @throws ParseException if the parser fails for some reason
	 *
	 * @return array the parsed annotation instances
	 */
	function parse() {
		$annotations = array();
		while ($token = $this->lexer->next()) {
			if ($token->getType() == Token::AT) {
				$nameTok = $this->expect(Token::LITERAL);
				$nextTok = $this->lexer->peek();
				$params = array();
				if ($nextTok && $nextTok->getType() == Token::PAREN_L) {
					$this->lexer->next();
					$params = $this->parseParameterList();
				}
				$annotations[] = new Annotation($nameTok->getText(), $params);
			}
		}
		return $annotations;
	}

	/**
	 * retrieves the underlying token stream
	 *
	 * @return TokenStream the token stream instance used by this parser
	 */
	function getLexer() {
		return $this->lexer;
	}

	/**
	 * sets the token stream instance of this parser
	 *
	 * This function can be used to reuse a parser instance by supplying
	 * a new token stream instance.
	 *
	 * @param TokenStream $lexer the token stream instance
	 */
	function setLexer(TokenStream $lexer) {
		$this->lexer = $lexer;
	}

	/*
	 * parses a parameter list
	 * param-list := <param>? ( ',' <param> )*
	 */
	private function parseParameterList() {
		$params = array();
		$token = $this->expect(Token::LITERAL, Token::PAREN_R);
		while ($token->getType() != Token::PAREN_R) {
			if ($token->getType() == Token::COMMA) {
				$token = $this->expect(Token::LITERAL);
			}
			list($name, $value) = $this->parseParameter($token);
			$params[$name] = $value;
			$token = $this->expect(Token::COMMA, Token::PAREN_R);
		}
		return $params;
	}

	/*
	 * parses an annotation parameter
	 * param := <string> '=' <expr>
	 */
	private function parseParameter(Token $nameToken = null) {
		if ($nameToken == null) {
			$nameToken = $this->expect(Token::LITERAL);
		}
		$this->expect(Token::EQ);
		$value = $this->parseExpression();
		return array($nameToken->getText(), $value);
	}

	/*
	 * parses a literal value
	 */
	private function parseLiteral(Token $token = null) {
		if ($token == null) {
			$token = $this->expect(Token::NUMERIC,
				Token::LITERAL, Token::STRING);
		}
		switch ($token->getType()) {
			case Token::NUMERIC:
				return substr_count($token->getText(), '.') > 0 ?
					floatval($token->getText()) : intval($token->getText());
			case Token::LITERAL:
				switch ($token->getText()) {
					case 'null':
						return null;
					case 'true':
						return true;
					case 'false';
						return false;
				}
			default:
				return $token->getText();
		}
	}

	/*
	 * parses an expression (either a literal or an array)
	 * expr := <array> | <literal>
	 */
	private function parseExpression(Token $token = null) {
		if ($token == null) {
			$token = $this->expect(
				Token::LITERAL, Token::STRING,
				Token::NUMERIC, Token::BRACK_L
			);
		}
		$value = null;
		if ($token->isLiteral()) {
			$value = $this->parseLiteral($token);
		}
		else if ($token->getType() == Token::BRACK_L) {
			$value = $this->parseArray();
		}
		return $value;
	}

	/*
	 * parses an array
	 * array := '[' ( <array-element>? ( ',' <array-element> )* )? ']'
	 */
	private function parseArray() {
		$arr = array();
		$token = $this->nextNotNull();
		while ($token->getType() != Token::BRACK_R) {
			list($key, $value) = $this->parseArrayElement($token);
			if ($key == null) {
				$arr[] = $value;
			}
			else {
				$arr[$key] = $value;
			}
			$token = $this->nextNotNull();
			if ($token->getType() == Token::COMMA) {
				$token = $this->nextNotNull();
			}
			else if ($token->getType() == Token::BRACK_R) {
				return $arr;
			}
			else {
				throw new ParseException('expected COMMA but got ' .
					Token::stringFromType($token->getType()));
			}
		}
		return $arr;
	}

	/*
	 * parses an array element
	 * array-element := <expr> | ( <string> ':' <expr> )
	 */
	private function parseArrayElement(Token $token) {
		$key = null;
		$value = $this->parseExpression($token);
		$token = $this->lexer->peek();
		if ($token == false) {
			throw new ParseException("unexpected EOF");
		}
		if ($token->getType() == Token::COLON) {
			$this->lexer->next();
			if (is_array($value)) {
				throw new ParseException("array keys must be literals");
			}
			$key = $value;
			$value = $this->parseExpression();
		}
		return array($key, $value);
	}

	/*
	 * retrieves the next token
	 * takes an arbitrary number of arguments which should be token types
	 * and verifies that the next token is one of the specified types
	 * otherwise a ParseException is thrown
	 */
	private function expect() {
		$expectedTypes = func_get_args();
		$token = $this->lexer->next();
		if ($token == null) {
			$expect = $this->buildTokenTypeStringList($expectedTypes);
			throw new ParseException("expected \"$expect\" but got EOF");
		}
		if (!in_array($token->getType(), $expectedTypes)) {
			$expect = $this->buildTokenTypeStringList($expectedTypes);
			$actual = Token::stringFromType($token->getType());
			throw new ParseException("expected \"$expect\" but got \"$actual\"");
		}
		return $token;
	}

	/*
	 * retrieves either the next token or throws a
	 * ParseException if there are no more tokens
	 */
	private function nextNotNull() {
		$next = $this->lexer->next();
		if ($next == null) {
			throw new ParseException("unexpected EOF");
		}
		return $next;
	}

	/*
	 * builds a string list of token types
	 */
	private function buildTokenTypeStringList($types) {
		$list = array();
		foreach ($types as $type) {
			$list[] = Token::stringFromType($type);
		}
		return implode(', ', $list);
	}
}