<?php

namespace pia;

use pia\lexer\Lexer;

class Registry implements \IteratorAggregate
{
	private $annotations;
	function __construct(array $annotations = array()) {
		$this->annotations = $annotations;
	}
	function getAnnotations($reflect) {
		if (is_object($reflect)) {
			if (!is_callable(array(&$reflect, 'getDocComment'))) {
				throw new \InvalidArgumentException();
			}
			$key = $this->getAnnotationKey($reflect);
			if (!array_key_exists($key, $this->annotations)) {
				$this->readAnnotations($reflect, $key);
			}
			return $this->annotations[$key];
		}
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
		else {
			throw new \InvalidArgumentException();
		}
	}

	function getIterator() {
		return new \ArrayIterator($this->annotations);
	}
}