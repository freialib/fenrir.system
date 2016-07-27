<?php namespace fenrir\system;

#
# [!!] Mysqli support requires mysqlnd extentions
#

/**
 * @copyright (c) 2016, freia Team
 * @license BSD-2 <http://freialib.github.io/license.txt>
 * @package freia Library
 */
class MysqliDatabase implements MysqlDatabaseSignature {

	/**
	 * @var array
	 */
	protected static $instances = [];

	/**
	 * @var \PDO
	 */
	protected $dbh = null;

	/**
	 * @return static
	 */
	static function instance(array $conf) {

		if ( ! isset($conf['dsn'], $conf['username'], $conf['password'])) {
			throw new Panic('Required database configuration value missing.');
		}

		if ( ! array_key_exists('verifyServerCert', $conf)) {
			$conf['verifyServerCert'] = true;
		}

		if ( ! array_key_exists('port', $conf)) {
			$conf['port'] = 3306;
		}

		if ( ! array_key_exists('persistent', $conf)) {
			$conf['persistent'] = false;
		}

		// Parse DSN
		// ---------
		
		if ( ! preg_match('/host=(?<host>[^;]+)(;|$)/i', $conf['dsn'], $matches)) {
			throw new Panic('Unable to parse host from DSN');
		}

		$conf['host'] = $matches['host'];

		if ( ! preg_match('/dbname=(?<dbname>[^;]+)(;|$)/i', $conf['dsn'], $matches)) {
			throw new Panic('Unable to parse dbname from DSN');
		}

		$conf['dbname'] = $matches['dbname'];

		$conf['charset'] = 'utf8';
		if (preg_match('/charset=(?<charset>[^;]+)(;|$)/i', $conf['dsn'], $matches)) {
			$conf['charset'] = $matches['charset'];
		}

		// Normalize Options Configuration
		// -------------------------------

		if ( ! isset($conf['options'])) {
			$conf['options'] = [];
		}
		else { // options set
			$conf['options'] = $conf['options'];
		}

		// Normalize Attributes Configuration
		// ----------------------------------

		$default_attributes = [
			'error-mode' => [ 'key' => \PDO::ATTR_ERRMODE, 'value' => \PDO::ERRMODE_EXCEPTION ],
			'default-fetch-mode' => [ 'key' => \PDO::ATTR_DEFAULT_FETCH_MODE, 'value' => \PDO::FETCH_ASSOC ]
		];

		if ( ! isset($conf['attributes'])) {
			$conf['attributes'] = $default_attributes;
		}
		else { // attributes set
			$conf['attributes'] = array_merge($default_attributes, $conf['attributes']);
		}

		// Ensure Timezone Configuration
		// -----------------------------

		if ( ! isset($conf['timezone'])) {
			$conf['timezone'] = date_default_timezone_get();
		}

		$name = $conf['dsn'];
		if (isset(static::$instances[$name])) {
			return static::$instances[$name];
		}

		$i = new static;
		$i->conf = $conf;

		return static::$instances[$name] = $i;
	}

	/**
	 * Cleanup
	 */
	function __destruct() {
		mysqli_close($this->dbh);
		$this->dbh = null;
	}

// ---- Private ---------------------------------------------------------------

	/**
	 * Overwrite to add options.
	 */
	protected function preconnectConf($dbh) {
		// hook
	}

	/**
	 * Overwrite to add options.
	 */
	protected function postconnectConf($dbh) {
		// hook
	}

	/**
	 * Performs database initialization.
	 */
	protected function setup() {
		$conf = $this->conf;

		$dbh = $this->dbh = \mysqli_init();

		foreach ($this->conf['options'] as $key => $setting) {
			if ($setting != null && is_array($setting)) {
				mysqli_options($dbh, $setting['key'], $setting['value']);
			}
		}

		$this->preconnectConf($dbh);

		$this->establishConnection($dbh, $conf);

		$this->postconnectConf($dbh);

		/* check connection */
		if (mysqli_connect_errno()) {
			throw new Panic("Connect failed: " . \mysqli_connect_error());
		}

		\mysqli_set_charset($dbh, $conf['charset']);

		$offset = \hlin\Time::timezoneOffset($conf['timezone']);
		\mysqli_real_query($dbh, "SET time_zone='$offset';");
	}

	/**
	 * ...
	 */
	protected function establishConnection($dbh, $conf) {
		
		if (array_key_exists('ssl', $conf)) {

			$sslconf = $conf['ssl'];

			mysqli_ssl_set (
				$dbh,
				$sslconf['key'],
				$sslconf['cert'],
				$sslconf['ca'],
				null,
				null
			);

			$flags = null;
			if ($conf['verifyServerCert'] && defined('MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT')) {
				$flags = MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT;
			}

			\mysqli_real_connect (
				$dbh,
				($conf['persistent'] ? 'p:' : '') . $conf['host'],
				$conf['username'],
				$conf['password'],
				$conf['dbname'],
				$conf['port'],
				null,
				$flags
			);
		}
		else { // non-ssl

			\mysqli_real_connect (
				$dbh, 
				($conf['persistent'] ? 'p:' : '') . $conf['host'], 
				$conf['username'], 
				$conf['password'], 
				$conf['dbname'],
				$conf['port']
			);
		}
	}

	/**
	 * ...
	 */
	protected function reportFailure($statement) {
		throw new Panic($statement);
	}

// ---- Interface -------------------------------------------------------------

	/**
	 * @return string quoted version
	 */
	function quote($value) {
		$this->dbh or $this->setup();

		if (is_string($value)) {
			return '"'.\mysqli_real_escape_string($this->dbh, $value).'"';
		}
		else { // non-string
			return \mysqli_real_escape_string($this->dbh, $value);	
		}
	}

	/**
	 * @return int number of rows affected
	 */
	function exec($statement) {
		$this->dbh or $this->setup();
		if (\mysqli_multi_query($this->dbh, $statement)) {
			$res = null;
			if ($result = \mysqli_use_result($this->dbh)) {
				$res = [];
				while ($row = \mysqli_fetch_assoc($result)) {
					$res[] = $row;
				}

				\mysqli_free_result($result);
			}

			return $res;
		}
		else { // failed
			$this->reportFailure("Failed to execute $statement");
		}
	}

// ---- Statements ------------------------------------------------------------

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

			return \fenrir\MysqliStatement::instance($this->dbh, strtr($statement, $consts));
		}
		else { // placeholders === null
			return \fenrir\MysqliStatement::instance($this->dbh, $statement);
		}
	}

	/**
	 * @return mixed
	 */
	function lastInsertId($name = null) {
		return \mysqli_insert_id($this->dbh);
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
			\mysqli_autocommit($this->dbh, false);
			\mysqli_begin_transaction($this->dbh);
		}
		else { // already in a transaction
			\mysqli_query($this->dbh, 'SAVEPOINT save'.$this->savepoint);
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
			if ( ! \mysqli_commit($this->dbh)) {
				throw new Panic('Failed to commit transaction');
			}
		}
		else { // not finished with transaction yet
			\mysqli_query($this->dbh, 'RELEASE SAVEPOINT save'.$this->savepoint);
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
			\mysqli_rollback($this->dbh);
		}
		else { // not finished with transaction
			\mysqli_query('ROLLBACK TO SAVEPOINT save'.$this->savepoint);
		}

		return $this;
	}

} # class
