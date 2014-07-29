<?php namespace fenrir\system;

/**
 * @copyright (c) 2014, freia Team
 * @license BSD-2 <http://freialib.github.io/license.txt>
 * @package freia Library
 */
trait SqlMethodPdxTrait {

	/**
	 * @var \fenrir\system\MysqDatabaseSignature
	 */
	protected $db = null;

	/**
	 * @return static
	 */
	static function instance(\fenrir\system\MysqlDatabaseSignature $db) {
		$i = new static;
		$i->db = $db;
		return $i;
	}

} # class
