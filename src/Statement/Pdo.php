<?php namespace fenrir\system;

/**
 * @copyright (c) 2014, freia Team
 * @license BSD-2 <http://freialib.github.io/license.txt>
 * @package freia Library
 */
class PdoStatement {

	/**
	 * @var \PDOStatement
	 */
	protected $stmt = null;

	/**
	 * @var string
	 */
	protected $query = null;

	/**
	 * @return static
	 */
	static function instance(\PDO $dbh, $statement) {
		$i = new static;
		$i->stmt = $dbh->prepare($statement);
		$i->query = $statement;
		return $i;
	}

// ---- Basic assignment ------------------------------------------------------

	/**
	 * @return static $this
	 */
	function str($parameter, $value) {
		$this->stmt->bindValue($parameter, $value, \PDO::PARAM_STR);
		return $this;
	}

	/**
	 * @return static $this
	 */
	function num($parameter, $value) {

		# we need to convert to number to avoid PDO passing it as a string in
		# the query; the PARAM_INT doesn't matter, it will just get quoted
		# as string, and the resulting comparison errors are quite simply
		# horrible to track down and debug

		// we perform this simple check to avoid introducing errors
		if (is_string($value) && preg_match('/^[0-9.]+$/', $value)) {

			// as per the comment at the start, we need to make sure pdo gets
			// an actual numeric type so it doesn't botch everything into a
			// string every time

			if (strpos($value, '.') === false) {
				$value = (int) $value;
			}
			else { // found the "."
				$value = (float) $value;
			}
		}

		$this->stmt->bindValue($parameter, $value, \PDO::PARAM_INT);
		return $this;
	}

	/**
	 * @return static $this
	 */
	function bool($parameter, $value, array $map = null) {
		if ($value === true || $value === false) {
			$this->stmt->bindValue($parameter, $value, \PDO::PARAM_BOOL);
		}
		else { // non-boolean
			$this->stmt->bindValue($parameter, $this->booleanize($value, $map), \PDO::PARAM_BOOL);
		}

		return $this;
	}

	/**
	 * @return static $this
	 */
	function date($parameter, $value) {
		if (empty($value)) {
			$value = null;
		}

		$this->stmt->bindValue($parameter, $value, \PDO::PARAM_STR);
		return $this;
	}

// ---- Basic Binding ---------------------------------------------------------

	/**
	 * @return static $this
	 */
	function bindstr($parameter, &$variable) {
		$this->stmt->bindParam($parameter, $variable, \PDO::PARAM_STR);
		return $this;
	}

	/**
	 * @return static $this
	 */
	function bindnum($parameter, &$variable) {
		$this->stmt->bindParam($parameter, $variable, \PDO::PARAM_INT);
		return $this;
	}

	/**
	 * @return static $this
	 */
	function bindbool($parameter, &$variable) {
		$this->stmt->bindParam($parameter, $variable, \PDO::PARAM_BOOL);

		return $this;
	}

	/**
	 * @return static $this
	 */
	function binddate($parameter, &$variable) {
		$this->stmt->bindParam($parameter, $variable, \PDO::PARAM_STR);
		return $this;
	}

// ---- Stored procedure arguments --------------------------------------------

	/**
	 * @return static $this
	 */
	function arg($parameter, &$variable) {
		$this->stmt->bindParam
			(
				$parameter,
				$variable,
				\PDO::PARAM_STR | \PDO::PARAM_INPUT_OUTPUT
			);

		return $this;
	}

// ---- Execution -------------------------------------------------------------

	/**
	 * Execute the statement.
	 *
	 * @return static $this
	 */
	function execute() {
		try {
			$this->stmt->execute();
		}
		catch (\Exception $pdo_exception) {
			$message = $pdo_exception->getMessage();
			$message .= "\n".\hlin\Text::reindent($this->query, "\t");
			throw new Panic($message, 500, $pdo_exception);
		}

		return $this;
	}

	/**
	 * Featch as object.
	 *
	 * @return mixed
	 */
	function fetch_object($class = 'stdClass', array $constructor_args = null) {
		return $this->stmt->fetchObject($class, $constructor_args);
	}

	/**
	 * Fetch row as associative.
	 *
	 * @return array or null
	 */
	function fetch_entry() {
		$result = $this->stmt->fetch(\PDO::FETCH_ASSOC);

		if ($result === false) {
			return null;
		}
		else { // succesfully retrieved statement
			return $result;
		}
	}

	/**
	 * Retrieves all rows. Rows are retrieved as arrays. Empty result will
	 * return an empty array.
	 *
	 * @return array
	 */
	function fetch_all() {
		return $this->stmt->fetchAll(\PDO::FETCH_ASSOC);
	}

// ---- Advanced Helpers ------------------------------------------------------

	/**
	 * Shorthand for retrieving value from a querie that performs a COUNT, SUM
	 * or some other calculation.
	 *
	 * @return mixed
	 */
	function fetch_calc($on_null = null) {
		$calc_entry = $this->fetch_entry();
		$value = array_pop($calc_entry);

		if ($value !== null) {
			return $value;
		}
		else { // null value
			return $on_null;
		}
	}

// ---- Multi-Assignment ------------------------------------------------------

	/**
	 * @return static $this
	 */
	function strs(array $params, array $filter = null, $varkey = ':') {
		if ($filter === null) {
			foreach ($params as $key => $value) {
				$this->str($varkey.$key, $value);
			}
		}
		else { // filtered
			foreach ($filter as $key) {
				$this->str($varkey.$key, $params[$key]);
			}
		}

		return $this;
	}

	/**
	 * @return static $this
	 */
	function nums(array $params, array $filter = null, $varkey = ':') {
		if ($filter === null) {
			foreach ($params as $key => $value) {
				$this->num($varkey.$key, $value);
			}
		}
		else { // filtered
			foreach ($filter as $key) {
				$this->num($varkey.$key, $params[$key]);
			}
		}

		return $this;
	}

	/**
	 * @return static $this
	 */
	function bools(array $params, array $filter = null, array $map = null, $varkey = ':') {
		if ($filter === null) {
			foreach ($params as $key => $value) {
				$this->bool($varkey.$key, $value, $map);
			}
		}
		else { // filtered
			foreach ($filter as $key) {
				$this->bool($varkey.$key, $params[$key], $map);
			}
		}

		return $this;
	}

	/**
	 * @return static $this
	 */
	function dates(array $params, array $filter = null, $varkey = ':') {
		if ($filter === null) {
			foreach ($params as $key => $value) {
				$this->date($varkey.$key, $value);
			}
		}
		else { // filtered
			foreach ($filter as $key) {
				$this->date($varkey.$key, $params[$key]);
			}
		}

		return $this;
	}

// ---- Multi-Binding ---------------------------------------------------------


	/**
	 * @return static $this
	 */
	function bindstrs(array &$params, array $filter = null, $varkey = ':') {
		if ($filter === null) {
			foreach ($params as $key => &$value) {
				$this->bindstr($varkey.$key, $value);
			}
		}
		else { // filtered
			foreach ($filter as $key) {
				$this->bindstr($varkey.$key, $params[$key]);
			}
		}

		return $this;
	}

	/**
	 * @return static $this
	 */
	function bindnums(array &$params, array $filter = null, $varkey = ':') {
		if ($filter === null) {
			foreach ($params as $key => &$value) {
				$this->bindnum($varkey.$key, $value);
			}
		}
		else { // filtered
			foreach ($filter as $key) {
				$this->bindnum($varkey.$key, $params[$key]);
			}
		}

		return $this;
	}

	/**
	 * @return static $this
	 */
	function bindbools(array &$params, array $filter = null, $varkey = ':') {
		if ($filter === null) {
			foreach ($params as $key => &$value) {
				$this->bindbool($varkey.$key, $value);
			}
		}
		else { // filtered
			foreach ($filter as $key) {
				$this->bindbool($varkey.$key, $params[$key]);
			}
		}

		return $this;
	}

	/**
	 * @return static $this
	 */
	function binddates(array &$params, array $filter = null, $varkey = ':') {
		if ($filter === null) {
			foreach ($params as $key => &$value) {
				$this->binddate($varkey.$key, $value);
			}
		}
		else { // filtered
			foreach ($filter as $key) {
				$this->binddate($varkey.$key, $params[$key]);
			}
		}

		return $this;
	}

// ----  Stored procedure arguments -------------------------------------------

	/**
	 * @return static $this
	 */
	function args(array &$params, array $filter = null, $varkey = ':') {
		if ($filter === null) {
			foreach ($params as $key => &$value) {
				$this->bindarg($varkey.$key, $value);
			}
		}
		else { // filtered
			foreach ($filter as $key) {
				$this->bindarg($varkey.$key, $params[$key]);
			}
		}

		return $this;
	}

// ---- Private ---------------------------------------------------------------

	/**
	 * @return boolean
	 */
	protected function booleanize($value, array $map = null) {
		$map !== null or $map = array
			(
				// truthy
				'true' => true,
				'on' => true,
				'yes' => true,
				// falsy
				'false' => false,
				'off' => false,
				'no' => false,
			);

		// augment map
		$map['1'] = true;
		$map[1] = true;
		$map['0'] = false;
		$map[0] = false;

		if (isset($map[$value])) {
			return $map[$value];
		}
		else if (is_bool($value)) {
			return $value;
		}
		else { // undefined boolean
			throw new Panic("Unrecognized boolean value passed: $value");
		}
	}

} # class
