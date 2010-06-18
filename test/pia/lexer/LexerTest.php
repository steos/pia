<?php
/* This file is part of Pia.
 *
 * Pia is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
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

require_once 'pia/lexer/TokenStream.php';
require_once 'pia/lexer/Lexer.php';
require_once 'pia/lexer/Token.php';

use pia\lexer\Lexer;
use pia\lexer\Token;

class LexerTest extends \PHPUnit_Framework_TestCase
{
	function testNext() {
		$input = <<<PHPDOC
/**
  * foobar lorem ipsum
  * @pia.annotation
  * @pia.is.cool(this=true)
  * @types(foo="lorem ipsum",
  *		bar=3, baz=[1,2],
  *		lorem=["a":"b", "c":3, "d":null],
  *		ipsum=7.2e-5
  *	)
  */
PHPDOC;
		$lexer = new Lexer($input);

		$this->assertTokenType(Token::LITERAL, $lexer->next());
		$this->assertTokenType(Token::LITERAL, $lexer->next());
		$this->assertTokenType(Token::LITERAL, $lexer->next());

		$this->assertTokenType(Token::AT, $lexer->next());
		$this->assertTokenType(Token::LITERAL, $lexer->next());

		$this->assertTokenType(Token::AT, $lexer->next());
		$this->assertTokenType(Token::LITERAL, $lexer->next());
		$this->assertTokenType(Token::PAREN_L, $lexer->next());
		$this->assertTokenType(Token::LITERAL, $lexer->next());
		$this->assertTokenType(Token::EQ, $lexer->next());
		$this->assertTokenType(Token::LITERAL, $lexer->next());
		$this->assertTokenType(Token::PAREN_R, $lexer->next());

		$this->assertTokenType(Token::AT, $lexer->next());
		$this->assertTokenType(Token::LITERAL, $lexer->next());
		$this->assertTokenType(Token::PAREN_L, $lexer->next());
		$this->assertTokenType(Token::LITERAL, $lexer->next());
		$this->assertTokenType(Token::EQ, $lexer->next());
		$this->assertTokenType(Token::STRING, $lexer->next());
		$this->assertTokenType(Token::COMMA, $lexer->next());
		$this->assertTokenType(Token::LITERAL, $lexer->next());
		$this->assertTokenType(Token::EQ, $lexer->next());
		$this->assertTokenType(Token::NUMERIC, $lexer->next());
		$this->assertTokenType(Token::COMMA, $lexer->next());
		$this->assertTokenType(Token::LITERAL, $lexer->next());
		$this->assertTokenType(Token::EQ, $lexer->next());
		$this->assertTokenType(Token::BRACK_L, $lexer->next());
		$this->assertTokenType(Token::NUMERIC, $lexer->next());
		$this->assertTokenType(Token::COMMA, $lexer->next());
		$this->assertTokenType(Token::NUMERIC, $lexer->next());
		$this->assertTokenType(Token::BRACK_R, $lexer->next());
		$this->assertTokenType(Token::COMMA, $lexer->next());
		$this->assertTokenType(Token::LITERAL, $lexer->next());
		$this->assertTokenType(Token::EQ, $lexer->next());
		$this->assertTokenType(Token::BRACK_L, $lexer->next());
		$this->assertTokenType(Token::STRING, $lexer->next());
		$this->assertTokenType(Token::COLON, $lexer->next());
		$this->assertTokenType(Token::STRING, $lexer->next());
		$this->assertTokenType(Token::COMMA, $lexer->next());
		$this->assertTokenType(Token::STRING, $lexer->next());
		$this->assertTokenType(Token::COLON, $lexer->next());
		$this->assertTokenType(Token::NUMERIC, $lexer->next());
		$this->assertTokenType(Token::COMMA, $lexer->next());
		$this->assertTokenType(Token::STRING, $lexer->next());
		$this->assertTokenType(Token::COLON, $lexer->next());
		$this->assertTokenType(Token::LITERAL, $lexer->next());
		$this->assertTokenType(Token::BRACK_R, $lexer->next());
		$this->assertTokenType(Token::COMMA, $lexer->next());
		$this->assertTokenType(Token::LITERAL, $lexer->next());
		$this->assertTokenType(Token::EQ, $lexer->next());
		$this->assertTokenType(Token::NUMERIC, $lexer->next());
		$this->assertTokenType(Token::PAREN_R, $lexer->next());
		$this->assertNull($lexer->next());
	}

	function testMultibyteInput() {
		$input = <<<PHPDOC
/**
  * @ö(ä="ü")
  */
PHPDOC;
		$lexer = new Lexer($input);
		$lexer->setMultibyteSafe(true);
		$this->assertTokenType(Token::AT, $lexer->next());
		$this->assertTokenType(Token::LITERAL, $lexer->peek());
		$this->assertEquals('ö', $lexer->next()->getText());
		$this->assertTokenType(Token::PAREN_L, $lexer->next());
		$this->assertTokenType(Token::LITERAL, $lexer->peek());
		$this->assertEquals('ä', $lexer->next()->getText());
		$this->assertTokenType(Token::EQ, $lexer->next());
		$this->assertTokenType(Token::STRING, $lexer->peek());
		$this->assertEquals('ü', $lexer->next()->getText());
		$this->assertTokenType(Token::PAREN_R, $lexer->next());
	}

	private function assertTokenType($expectedType, Token $tok) {
		$this->assertEquals($expectedType, $tok->getType());
	}

	private function getAllTokens(Lexer $lexer) {
		$tokens = array();
		while ($token = $lexer->next()) {
			$tokens[] = $token;
		}
		return $tokens;
	}
}