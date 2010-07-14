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

use pia\lexer\Lexer;

class Registry implements \IteratorAggregate
{
	private $annotations;
	private $reverseIndexEnabled;
	private $reverseIndex;
	function __construct(array $annotations = array()) {
		$this->annotations = $annotations;
		$this->reverseIndexEnabled = false;
		$this->reverseIndex = array();
	}

	function setReverseIndexEnabled($reverseIndexEnabled) {
		$this->reverseIndexEnabled = $reverseIndexEnabled;
	}

	function isReverseIndexEnabled() {
		return $this->reverseIndexEnabled;
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
		$annotations = $parser->parse();
		if ($this->reverseIndexEnabled) {
			foreach ($annotations as &$annotation) {
				$this->reverseIndex[$annotation->getName()][] = $reflect;
			}
		}
		$this->annotations[$key] = $annotations;
	}

	function find($annotationName) {
		if ($this->reverseIndexEnabled) {
			return @$this->reverseIndex[$annotationName];
		}
		else {
			$reflections = array();
			foreach ($this->annotations as $key => &$annotations) {
				foreach ($annotations as &$annotation) {
					if ($annotation->getName() != $annotationName) {
						continue;
					}
					$reflections[] = $this->getReflectionFromKey($key);
				}
			}
			return $reflections;
		}
	}

	function getReflectionFromKey($key) {
		// property
		if (substr_count($key, '::$')) {
			list($class, $property) = explode('::$', $key, 2);
			return new \ReflectionProperty($class, $property);
		}
		// method
		else if (substr_count($key, '::')) {
			list($class, $method) = explode('::', $key, 2);
			return new \ReflectionMethod($class, $method);
		}
		// function
		else if ($key[0] == '#') {
			return new \ReflectionFunction(substr($key, 1));
		}
		else {
			return new \ReflectionClass($key);
		}
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