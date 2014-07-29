<?php namespace fenrir\system;

/**
 * @copyright (c) 2014, freia Team
 * @license BSD-2 <http://freialib.github.io/license.txt>
 * @package freia Library
 */
interface PdoDatabaseSignature {

	/**
	 * @return string quoted version
	 */
	function quote($value);

	/**
	 * @return int number of rows affected
	 */
	function exec($statement);

} # signature
