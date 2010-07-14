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

if (PHP_SAPI != 'cli') {
	echo 'Error: This is a CLI script. Please execute it with the CLI SAPI.';
	exit(99);
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

spl_autoload_extensions('.php');
spl_autoload_register();

\pia\cli\CliRunner::main($argc, $argv);