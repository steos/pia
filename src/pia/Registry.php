<?php

namespace pia;

use pia\lexer\Lexer;

class Registry implements \IteratorAggregate
{
	private $annotations;
	function __construct(array $annotations = array()) {
		$this->annotations = $annotations;
	}
	function getAnnotations($element) {
		if (is_object($element)) {
			if (!is_callable(array(&$element, 'getDocComment'))) {
				throw new \InvalidArgumentException();
			}
			$key = $this->getAnnotationKey($element);
			if (!array_key_exists($key, $this->annotations)) {
				$this->readAnnotations($element, $key);
			}
			return $this->annotations[$key];
		}
		else  if (is_string($element)) {
			return @$this->annotations[$element];
		}

		return null;
	}

	function hasAnnotations($element) {
		return count($this->getAnnotations($element)) > 0;
	}

	function readAnnotations($reflect, $key = null) {
		if ($key == null) {
			$key = $this->getAnnotationKey($reflect);
		}
		$parser = new Parser(new Lexer($reflect->getDocComment()));
		$this->annotations[$key] = $parser->parse();
	}

	function getAnnotationKey($ref) {
		if ($ref instanceof \ReflectionClass) {
			return $ref->getName();
		}
		else if ($ref instanceof \ReflectionMethod) {
			return $ref->getDeclaringClass()->getName() .
				'::' . $ref->getName();
		}
		else if ($ref instanceof \ReflectionProperty) {
			return $ref->getDeclaringClass()->getName() .
				'::$' . $ref->getName();
		}
		else if ($ref instanceof \ReflectionFunction) {
			return '#' . $ref->getName();
		}
		else {
			throw new \InvalidArgumentException();
		}
	}

	function getIterator() {
		return new \ArrayIterator($this->annotations);
	}

	function load($file) {
		$this->annotations = require $file;
	}
}