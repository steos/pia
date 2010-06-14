<?php

namespace pia;

class Annotation
{
	private $name;
	private $params;

	function __construct($name, array $params = array()) {
		$this->name = $name;
		$this->params = $params;
	}

	function getName() {
		return $this->name;
	}

	function getParams() {
		return $this->params;
	}

	function hasParam($name) {
		return array_key_exists($name, $this->params);
	}

	function getParam($name) {
		return @$this->params[$name];
	}

	function toPhpString() {
		$params = var_export($this->params, true);
		return "new \pia\Annotation('$this->name', $params)";
	}
}