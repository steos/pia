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

namespace pia\lexer;

interface TokenStream
{
	/**
	 * retrieves the next token in the stream and advances to the next token
	 *
	 * This function must advance the internal stream position beyond
	 * the current token so they can be retrieved in sequence by
	 * successive calls.
	 * Example:
	 * <code>
	 * while (null != ($token = $stream->next())) {
	 *     // do something with $token
	 * }
	 * </code>
	 *
	 * @return pia\lexer\Token or null if there are no tokens
	 */
	function next();

	/**
	 * retrieves the current token in the stream
	 *
	 * This function must not advance the internal stream position.
	 *
	 * @return pia\lexer\Token or false if there are no tokens
	 */
	function peek();
}