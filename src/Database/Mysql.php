<?php namespace fenrir\system;

/**
 * @copyright (c) 2014, freia Team
 * @license BSD-2 <http://freialib.github.io/license.txt>
 * @package freia Library
 */
class MysqlDatabase extends PdoDatabase implements MysqlDatabaseSignature {

	/**
	 * eg. $db->prepare('SELECT * FROM customers');
	 *
	 * @return \fenrir\MysqlStatement
	 */
	function prepare($statement = null, array $placeholders = null) {
		$this->dbh !== null or $this->setup();

		if ($placeholders !== null) {
			$consts = [];
			foreach ($placeholders as $key => $val) {
				$consts["[$key]"] = $val;
			}

			return \fenrir\MysqlStatement::instance($this->dbh, strtr($statement, $consts));
		}
		else { // placeholders === null
			return \fenrir\MysqlStatement::instance($this->dbh, $statement);
		}
	}

	/**
	 * @return mixed
	 */
	function lastInsertId($name = null) {
		return $this->dbh->lastInsertId($name);
	}

// ---- Transactions ----------------------------------------------------------

	/**
	 * @var int
	 */
	protected $savepoint = 0;

	/**
	 * Begin transaction or savepoint.
	 *
	 * @return static $this
	 */
	function begin() {
		$this->dbh or $this->setup();

		if ($this->savepoint == 0) {
			$this->dbh->beginTransaction();
		}
		else { // already in a transaction
			$this->dbh->exec('SAVEPOINT save'.$this->savepoint);
		}

		$this->savepoint++;
		return $this;
	}

	/**
	 * Commit transaction or savepoint.
	 *
	 * @return static $this
	 */
	function commit() {
		$this->savepoint--;

		if ($this->savepoint == 0) {
			$this->dbh->commit();
		}
		else { // not finished with transaction yet
			$this->dbh->exec('RELEASE SAVEPOINT save'.$this->savepoint);
		}

		return $this;
	}

	/**
	 * Rollback transaction or savepoint.
	 *
	 * @return static $this
	 */
	function rollback() {
		$this->savepoint--;

		if ($this->savepoint == 0) {
			$this->dbh->rollBack();
		}
		else { // not finished with transaction
			$this->dbh->exec('ROLLBACK TO SAVEPOINT save'.$this->savepoint);
		}

		return $this;
	}

} # class
