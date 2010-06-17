<?php

namespace pia;

require_once 'pia/Registry.php';

class RegistryTest extends \PHPUnit_Framework_TestCase
{
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
}