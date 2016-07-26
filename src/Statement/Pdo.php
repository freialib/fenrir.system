<?php namespace fenrir\system;

/**
 * @copyright (c) 2014, freia Team
 * @license BSD-2 <http://freialib.github.io/license.txt>
 * @package freia Library
 */
class PdoStatement {

	use \fenrir\SQLStatementCommonTrait;

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
			$message .= "\n".$this->formatQuery($this->query);
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

} # class
