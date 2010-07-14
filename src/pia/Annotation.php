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