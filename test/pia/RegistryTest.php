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

require_once 'pia/Registry.php';

/**
 * @foo
 * @pi(value=3.14)
 */
function annotatedMockup() {}

function mockup() {}

class RegistryTest extends \PHPUnit_Framework_TestCase
{
	function testTopLevelFunctionAnnotations() {
		$reg = new Registry();
		$func = new \ReflectionFunction('pia\annotatedMockup');
		$an = $reg->getAnnotations($func);
		$this->assertEquals(2, count($an));
		$this->assertEquals('foo', $an[0]->getName());
		$this->assertEquals('pi', $an[1]->getName());
		$this->assertEquals(3.14, $an[1]->getParam('value'));
	}

	function testTopLevelFunctionAnnotations2() {
		$reg = new Registry();
		$func = new \ReflectionFunction('pia\mockup');
		$this->assertFalse($reg->hasAnnotations($func));
	}

	function testGetFunctionAnnotationKey() {
		$reg = new Registry();
		$func = new \ReflectionFunction('pia\mockup');
		$key = $reg->getAnnotationKey($func);
		$this->assertEquals('#pia\mockup', $key);
	}

	/**
	 * @annotated.property(foo="bar", pi=3.14, lorem=["ipsum", "dolor"])
	 */
	private $property;

	function testGetPropertyAnnotation() {
		$reg = new Registry();
		$class = new \ReflectionClass('pia\RegistryTest');
		$prop = $class->getProperty('property');
		$an = $reg->getAnnotations($prop);
		$this->assertEquals(1, count($an));
		$an = $an[0];
		$this->assertEquals('annotated.property', $an->getName());
		$this->assertEquals(array(
			'foo' => 'bar',
			'pi' => 3.14,
			'lorem' => array("ipsum", "dolor")
		), $an->getParams());
	}

	/**
	 * @hook(a=1, b="2", c=.3, d=["e":"f"])
	 */
	function mock() {}

	function testGetMethodAnnotation() {
		$reg = new Registry();
		$class = new \ReflectionClass('pia\RegistryTest');
		$method = $class->getMethod('mock');
		$an = $reg->getAnnotations($method);
		$an = $an[0];
		$this->assertEquals('hook', $an->getName());
		$this->assertEquals(array(
			'a' => 1,
			'b' => '2',
			'c' => .3,
			'd' => array('e' => 'f'),
		), $an->getParams());
	}

	/**
	 * @should.fail(
	 */
	private $shouldfail;

	/**
	 * @expectedException pia\ParseException
	 */
	function testShouldFail() {
		$reg = new Registry();
		$class = new \ReflectionClass('pia\RegistryTest');
		$prop = $class->getProperty('shouldfail');
		$reg->getAnnotations($prop);
	}

	/**
	 * @should.fail(foo="bar)
	 */
	private $shouldfail2;

	/**
	 * @expectedException pia\ParseException
	 */
	function testShouldFail2() {
		$reg = new Registry();
		$class = new \ReflectionClass('pia\RegistryTest');
		$prop = $class->getProperty('shouldfail2');
		$reg->getAnnotations($prop);
	}

	/**
	 * @should.fail(foo=lorem ipsum)
	 */
	private $shouldfail3;

	/**
	 * @expectedException pia\ParseException
	 */
	function testShouldFail3() {
		$reg = new Registry();
		$class = new \ReflectionClass('pia\RegistryTest');
		$prop = $class->getProperty('shouldfail3');
		$an = $reg->getAnnotations($prop);
	}

	/**
	 * @should.fail(foo=])
	 */
	private $shouldfail4;

	/**
	 * @expectedException pia\ParseException
	 */
	function testShouldFail4() {
		$reg = new Registry();
		$class = new \ReflectionClass('pia\RegistryTest');
		$prop = $class->getProperty('shouldfail4');
		$reg->getAnnotations($prop);
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	function testInvalidArgument() {
		$reg = new Registry();
		$reg->getAnnotations(new \stdClass());
	}

	function testGetAnnotationKey() {
		$reg = new Registry();
		$class = new \ReflectionClass('pia\RegistryTest');
		$key = $reg->getAnnotationKey($class);
		$this->assertEquals('pia\RegistryTest', $key);
		$key = $reg->getAnnotationKey($class->getProperty('property'));
		$this->assertEquals('pia\RegistryTest::$property', $key);
		$key = $reg->getAnnotationKey($class->getMethod('mock'));
		$this->assertEquals('pia\RegistryTest::mock', $key);
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	function testGetAnnotationKeyWithInvalidArgument() {
		$reg = new Registry();
		$reg->getAnnotationKey('foobar');
	}

	function testHasAnnotations() {
		$reg = new Registry();
		$class = new \ReflectionClass('pia\RegistryTest');
		$this->assertTrue($reg->hasAnnotations(
			$class->getProperty('property')));
		$this->assertFalse($reg->hasAnnotations(
			$class->getMethod('testHasAnnotations')));
	}

	/**
	 * @foo
	 * @bar
	 */
	private $testFindProp1;

	/**
	 * @foo
	 */
	private $testFindProp2;

	/**
	 * @foo
	 * @baz
	 */
	function testFind() {
		$reg = new Registry();
		$class = new \ReflectionClass('pia\RegistryTest');
		$reg->readAnnotations(new \ReflectionProperty('pia\RegistryTest', 'testFindProp1'));
		$reg->readAnnotations(new \ReflectionProperty('pia\RegistryTest', 'testFindProp2'));
		$reg->readAnnotations(new \ReflectionMethod('pia\RegistryTest', 'testFind'));
		$reg->readAnnotations(new \ReflectionFunction('pia\annotatedMockup'));

		$an = $reg->find('foo');
		$this->assertEquals(4, count($an));
		$this->assertTrue($an[0]instanceof \ReflectionProperty);
		$this->assertEquals('testFindProp1', $an[0]->getName());
		$this->assertTrue($an[1]instanceof \ReflectionProperty);
		$this->assertEquals('testFindProp2', $an[1]->getName());
		$this->assertTrue($an[2]instanceof \ReflectionMethod);
		$this->assertEquals('testFind', $an[2]->getName());
		$this->assertTrue($an[3]instanceof \ReflectionFunction);
		$this->assertEquals('pia\annotatedMockup', $an[3]->getName());

		$an = $reg->find('bar');
		$this->assertEquals(1, count($an));
		$this->assertTrue($an[0] instanceof \ReflectionProperty);
		$this->assertEquals('testFindProp1', $an[0]->getName());

		$an = $reg->find('baz');
		$this->assertEquals(1, count($an));
		$this->assertTrue($an[0] instanceof \ReflectionMethod);
		$this->assertEquals('testFind', $an[0]->getName());
	}

	function testFindWithReverseIndex() {
		$reg = new Registry();
		$reg->setReverseIndexEnabled(true);
		$class = new \ReflectionClass('pia\RegistryTest');
		$reg->readAnnotations(new \ReflectionProperty('pia\RegistryTest', 'testFindProp1'));
		$reg->readAnnotations(new \ReflectionProperty('pia\RegistryTest', 'testFindProp2'));
		$reg->readAnnotations(new \ReflectionMethod('pia\RegistryTest', 'testFind'));
		$reg->readAnnotations(new \ReflectionFunction('pia\annotatedMockup'));

		$an = $reg->find('foo');
		$this->assertEquals(4, count($an));
		$this->assertTrue($an[0]instanceof \ReflectionProperty);
		$this->assertEquals('testFindProp1', $an[0]->getName());
		$this->assertTrue($an[1]instanceof \ReflectionProperty);
		$this->assertEquals('testFindProp2', $an[1]->getName());
		$this->assertTrue($an[2]instanceof \ReflectionMethod);
		$this->assertEquals('testFind', $an[2]->getName());
		$this->assertTrue($an[3]instanceof \ReflectionFunction);
		$this->assertEquals('pia\annotatedMockup', $an[3]->getName());

		$an = $reg->find('bar');
		$this->assertEquals(1, count($an));
		$this->assertTrue($an[0] instanceof \ReflectionProperty);
		$this->assertEquals('testFindProp1', $an[0]->getName());

		$an = $reg->find('baz');
		$this->assertEquals(1, count($an));
		$this->assertTrue($an[0] instanceof \ReflectionMethod);
		$this->assertEquals('testFind', $an[0]->getName());
	}

	/**
	 * @ä(ö="ü")
	 */
	function testMultibyteSafety() {
		$reg = new Registry;
		$method = new \ReflectionMethod('pia\RegistryTest', 'testMultibyteSafety');
		$an = $reg->getAnnotations($method);
		$this->assertEquals(1, count($an));
		$this->assertEquals('ä', $an[0]->getName());
		$this->assertTrue($an[0]->hasParam('ö'));
		$this->assertEquals('ü', $an[0]->getParam('ö'));
	}
}
