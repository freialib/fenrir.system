<?php namespace fenrir\system;

/**
 * @copyright (c) 2014, freia Team
 * @license BSD-2 <http://freialib.github.io/license.txt>
 * @package freia Library
 */
abstract class PdoDatabase implements PdoDatabaseSignature {

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

		// Normalize Options Configuration
		// -------------------------------

		$default_options = array
			(
				\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'
			);

		if ( ! isset($conf['options'])) {
			$conf['options'] = $default_options;
		}
		else { // options set
			$conf['options'] = array_merge($default_options, $conf['options']);
		}

		// Normalize Attributes Configuration
		// ----------------------------------

		$default_attributes = array
			(
				\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
				\PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
			);

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
	function __destruct()
	{
		$this->dbh = null;
	}

// ---- Interface -------------------------------------------------------------

	/**
	 * @return string quoted version
	 */
	function quote($value) {
		$this->dbh or $this->setup();
		return $this->dbh->quote($value);
	}

	/**
	 * @return int number of rows affected
	 */
	function exec($statement) {
		$this->dbh or $this->setup();
		return $this->dbh->exec($statement);
	}

// ---- Private ---------------------------------------------------------------

	/**
	 * Performs database initialization.
	 */
	protected function setup() {
		$conf = $this->conf;
		$dbh = $this->dbh = new \PDO($conf['dsn'], $conf['username'], $conf['password'], $conf['options']);
		foreach ($this->conf['attributes'] as $attr => $val) {
			$dbh->setAttribute($attr, $val);
		}
		$offset = \hlin\Time::timezoneOffset($conf['timezone']);
		$dbh->exec("SET time_zone='$offset';");
	}

} # class
