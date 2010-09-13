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

require_once 'pia/lexer/TokenStream.php';
require_once 'pia/lexer/Lexer.php';
require_once 'pia/lexer/Token.php';
require_once 'pia/Annotation.php';
require_once 'pia/ParseException.php';
require_once 'pia/Parser.php';

use pia\Parser;
use pia\lexer\Lexer;
use pia\Annotation;

class ParserTest extends \PHPUnit_Framework_TestCase
{
	function testParseSimpleAnnotation() {
		$input = <<<PHPDOC
/**
  * @foo.bar
  */
PHPDOC;

		$parser = new Parser(new Lexer($input));
		$annotations = $parser->parse();

		$this->assertEquals(1, count($annotations));
		$this->assertTrue($annotations[0] instanceof Annotation);
		$this->assertEquals('foo.bar', $annotations[0]->getName());
		$this->assertEquals(array(), $annotations[0]->getParams());
	}

	function testParseSimpleAnnotationWithSurroundingText() {
		$input = <<<PHPDOC
/*******************
  * lorem ipsum    *
  * @foo.bar dolor *
  * sit amet blah  *
  ******************/
PHPDOC;

		$parser = new Parser(new Lexer($input));
		$annotations = $parser->parse();

		$this->assertEquals(1, count($annotations));
		$this->assertTrue($annotations[0] instanceof Annotation);
		$this->assertEquals('foo.bar', $annotations[0]->getName());
		$this->assertEquals(array(), $annotations[0]->getParams());
	}

	function testParseMultipleSimpleAnnotationsWithSurroundingText() {
		$input = <<<PHPDOC
/*******************
  * lorem ipsum    *
  * @foo.bar dolor *
  * sit amet @lorem blah  *
  ******************/
PHPDOC;

		$parser = new Parser(new Lexer($input));
		$annotations = $parser->parse();

		$this->assertEquals(2, count($annotations));
		$this->assertTrue($annotations[0] instanceof Annotation);
		$this->assertEquals('foo.bar', $annotations[0]->getName());
		$this->assertEquals(array(), $annotations[0]->getParams());
		$this->assertEquals('lorem', $annotations[1]->getName());
		$this->assertEquals(array(), $annotations[1]->getParams());
	}

	function testParseAnnotationWithEmptyParams() {
		$input = <<<PHPDOC
/**
  * @foo.bar()
  */
PHPDOC;

		$parser = new Parser(new Lexer($input));
		$annotations = $parser->parse();
		$this->assertEquals(1, count($annotations));
		$this->assertTrue($annotations[0] instanceof Annotation);
		$this->assertEquals('foo.bar', $annotations[0]->getName());
		$this->assertEquals(array(), $annotations[0]->getParams());
	}

	function testParseAnnotationWithSingleLiteralParam() {
$input = <<<PHPDOC
/**
  * @foo.bar(lorem="ipsum dolor")
  */
PHPDOC;

		$parser = new Parser(new Lexer($input));
		$annotations = $parser->parse();
		$this->assertEquals(1, count($annotations));
		$this->assertTrue($annotations[0] instanceof Annotation);
		$this->assertEquals('foo.bar', $annotations[0]->getName());
		$this->assertTrue($annotations[0]->hasParam('lorem'));
		$this->assertEquals('ipsum dolor', $annotations[0]->getParam('lorem'));
	}

	function testParseAnnotationWithArrayParam() {
		$input = <<<PHPDOC
/**
  * @foo.bar (
  * 	foo = [ "lorem ipsum",
  * 			"dolor sit amet" ]
  *	)
  */
PHPDOC;

		$parser = new Parser(new Lexer($input));
		$annotations = $parser->parse();
		$this->assertEquals(1, count($annotations));
		$this->assertTrue($annotations[0] instanceof Annotation);
		$this->assertEquals('foo.bar', $annotations[0]->getName());
		$this->assertEquals(array(
			'foo' => array("lorem ipsum", "dolor sit amet"),
		), $annotations[0]->getParams());
	}

	function testParseAnnotationWithAssocArrayParam() {
		$input = <<<PHPDOC
/**
  * @foo.bar (
  * 	foo = [ "foo" : "lorem ipsum",
  * 			"bar" : "dolor sit amet" ]
  *	)
  */
PHPDOC;

		$parser = new Parser(new Lexer($input));
		$annotations = $parser->parse();
		$this->assertEquals(1, count($annotations));
		$this->assertTrue($annotations[0] instanceof Annotation);
		$this->assertEquals('foo.bar', $annotations[0]->getName());
		$this->assertEquals(array(
			'foo' => array("foo" => "lorem ipsum", "bar" => "dolor sit amet"),
		), $annotations[0]->getParams());
	}

	function testParseAnnotationWithRecursiveArrayParam() {
		$input = <<<PHPDOC
/**
  * @foo.bar (
  * 	foo = [ "foo" : "lorem ipsum",
  * 			"bar" : "dolor sit amet",
  * 			"baz" : [ "a", "b":3.1415, "c" ] ]
  *	)
  */
PHPDOC;

		$parser = new Parser(new Lexer($input));
		$annotations = $parser->parse();
		$this->assertEquals(1, count($annotations));
		$this->assertTrue($annotations[0] instanceof Annotation);
		$this->assertEquals('foo.bar', $annotations[0]->getName());
		$this->assertEquals(array(
			'foo' => array(
				"foo" => "lorem ipsum",
				"bar" => "dolor sit amet",
				"baz" => array("a", "b" => 3.1415, "c")
			),
		), $annotations[0]->getParams());
	}

	function testParseAnnotationWithMultipleParams() {
		$input = <<<PHPDOC
/**
  * @foo.bar(a=true,b=false,c=null,d=42,
  * 	e=3.14,f=[],g=["lorem ipsum","foo"])
  */
PHPDOC;

		$parser = new Parser(new Lexer($input));
		$annotations = $parser->parse();
		$this->assertEquals(1, count($annotations));
		$this->assertTrue($annotations[0] instanceof Annotation);
		$this->assertEquals('foo.bar', $annotations[0]->getName());
		$this->assertEquals(array(
			'a' => true,
			'b' => false,
			'c' => null,
			'd' => 42,
			'e' => 3.14,
			'f' => array(),
			'g' => array("lorem ipsum", "foo"),
		), $annotations[0]->getParams());
	}

	function testParseAnnotationWithMultipleParamsAndSurroundingText() {
		$input = <<<PHPDOC
/**
	this is a test
		which is testing @pi                                   *
(value=3.14159265) the parser                                  *
	to see if it can cope with                                 *

  * @foo.bar(                                                  *
a=true,			b=false                                        *
	,
		c
			=
				null, d = 42 ,                                 *
  * 	e=3.14,f=[], g = [ "lorem ipsum"                       *

  ,                                                            *

 			"foo"]) really messed up input
  */
PHPDOC;

		$parser = new Parser(new Lexer($input));
		$annotations = $parser->parse();
		$this->assertEquals(2, count($annotations));
		$this->assertTrue($annotations[0] instanceof Annotation);
		$this->assertEquals('pi', $annotations[0]->getName());
		$this->assertEquals(array('value' => 3.14159265), $annotations[0]->getParams());
		$this->assertTrue($annotations[1] instanceof Annotation);
		$this->assertEquals('foo.bar', $annotations[1]->getName());
		$this->assertEquals(array(
			'a' => true,
			'b' => false,
			'c' => null,
			'd' => 42,
			'e' => 3.14,
			'f' => array(),
			'g' => array("lorem ipsum", "foo"),
		), $annotations[1]->getParams());
	}

	function testParseMultibyteInput() {
		$input = <<<PHPDOC
/**
  * @ö(ä="ü")
  */
PHPDOC;
		$lexer = new Lexer($input);
		$parser = new Parser(new Lexer($input));
		$annotations = $parser->parse();
		$this->assertEquals(1, count($annotations));
		$this->assertTrue($annotations[0] instanceof Annotation);
		$this->assertEquals('ö', $annotations[0]->getName());
		$this->assertEquals(array('ä' => 'ü'), $annotations[0]->getParams());
	}

	function testReusability() {
		$input1 = <<<PHPDOC
/**
  * @foo
  */
PHPDOC;

		$input2 = <<<PHPDOC
/**
  * @bar
  */
PHPDOC;
		$parser = new Parser(new Lexer($input1));

		$an = $parser->parse();
		$this->assertEquals(1, count($an));
		$this->assertEquals('foo', $an[0]->getName());

		$parser->setLexer(new Lexer($input2));
		$an = $parser->parse();

		$this->assertEquals(1, count($an));
		$this->assertEquals('bar', $an[0]->getName());
	}

	function testLazyStrings() {
		$input = <<<PHPDOC
/**
  * @foo(bar=baz)
  * @bar(baz=[lorem:ipsum])
  * @look(ma=even-hyphens.dots#and+lots~of;other|stuff)
  */
PHPDOC;
		$parser = new Parser(new Lexer($input));
		$an = $parser->parse();
		$this->assertEquals(3, count($an));
		$this->assertEquals('foo', $an[0]->getName());
		$this->assertTrue($an[0]->hasParam('bar'));
		$this->assertEquals('baz', $an[0]->getParam('bar'));

		$this->assertEquals('bar', $an[1]->getName());
		$this->assertEquals(array('baz' => array('lorem' => 'ipsum')),
			$an[1]->getParams());

		$this->assertEquals('look', $an[2]->getName());
		$this->assertEquals('even-hyphens.dots#and+lots~of;other|stuff',
			$an[2]->getParam('ma'));
	}

	/**
	 * @expectedException pia\ParseException
	 */
	function testInvalidArrayKeyError() {
		$input = <<<PHPDOC
/**
  * @foo(bar=[[1,2]:"baz"])
  */
PHPDOC;
		$parser = new Parser(new Lexer($input));
		$parser->parse();
	}

	/**
	 * @expectedException pia\ParseException
	 */
	function testUnexpectedEofError() {
		$input = <<<PHPDOC
/**
  * @foo(bar=["foo":)
  */
PHPDOC;
		$parser = new Parser(new Lexer($input));
		$parser->parse();
	}

	/**
	 * @expectedException pia\ParseException
	 */
	function testUnexpectedEofError2() {
		$input = <<<PHPDOC
/**
  * @foo(bar=[
  */
PHPDOC;
		$parser = new Parser(new Lexer($input));
		$parser->parse();
	}

	/**
	 * @expectedException pia\ParseException
	 */
	function testMalformedArrayError() {
		$input = <<<PHPDOC
/**
  * @foo(bar=["foo" "bar"])
  */
PHPDOC;
		$parser = new Parser(new Lexer($input));
		$parser->parse();
	}
}