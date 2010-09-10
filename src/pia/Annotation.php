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

/**
 * This class represents an annotation.
 *
 * An annotation has a name and optionally named parameters. It can also be
 * serialized into a PHP code string which is used by the CLI tool to generate
 * the annotation lookup array.
 */
class Annotation
{
	private $name;
	private $params;

	/**
	 * creates a new annotation with the given name and parameters
	 *
	 * @param string $name the annotation name
	 * @param array $params the parameter map
	 */
	function __construct($name, array $params = array()) {
		$this->name = $name;
		$this->params = $params;
	}

	/**
	 * retrieves the name of this annotation
	 *
	 * @return string the annotation name
	 */
	function getName() {
		return $this->name;
	}

	/**
	 * retrieves the parameter map of this annotation
	 *
	 * @return array the parameter map
	 */
	function getParams() {
		return $this->params;
	}

	/**
	 * determines whether this annotation has a parameter with the given name
	 *
	 * @param string $name the parameter name
	 *
	 * @return bool true if the parameter exists, false if not
	 */
	function hasParam($name) {
		return array_key_exists($name, $this->params);
	}

	/**
	 * retrieves the parameter with the given name
	 *
	 * @param string $name the parameter name
	 *
	 * @return mixed the parameter value or null if it doesn't exist
	 */
	function getParam($name) {
		return @$this->params[$name];
	}

	/**
	 * serializes this annotation into a valid PHP code snippet
	 *
	 * @returns string the PHP code snippet representing this annotation
	 */
	function toPhpString() {
		$params = var_export($this->params, true);
		return "new \pia\Annotation('$this->name', $params)";
	}
}