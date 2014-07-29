<?php namespace fenrir\system;

/**
 * @copyright (c) 2014, freia Team
 * @license BSD-2 <http://freialib.github.io/license.txt>
 * @package freia Library
 */
interface MysqlDatabaseSignature extends PdoDatabaseSignature {

	/**
	 * eg. $db->prepare('SELECT * FROM customers');
	 *
	 * @return \fenrir\MysqlStatement
	 */
	function prepare($statement = null, array $placeholders = null);

	/**
	 * @return mixed
	 */
	function lastInsertId($name = null);

// ---- Transactions ----------------------------------------------------------

	/**
	 * Begin transaction or savepoint.
	 *
	 * @return static $this
	 */
	function begin();

	/**
	 * Commit transaction or savepoint.
	 *
	 * @return static $this
	 */
	function commit();

	/**
	 * Rollback transaction or savepoint.
	 *
	 * @return static $this
	 */
	function rollback();

} # signature
