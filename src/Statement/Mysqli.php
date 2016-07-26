<?php namespace fenrir\system;

#
# [!!] Mysqli support requires mysqlnd extentions
#

/**
 * @copyright (c) 2016, freia Team
 * @license BSD-2 <http://freialib.github.io/license.txt>
 * @package freia Library
 */
class MysqliStatement {

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
	 * @var array statement parameters
	 */
	protected $params = [];

	/**
	 * @var string
	 */
	protected $refactored_stmt = null;

	/**
	 * @var mixed
	 */
	protected $resultset = null;

	/**
	 * @return static
	 */
	static function instance($dbh, $statement) {
		$i = new static;
		$i->dbh = $dbh;
		$i->query = $statement;
		return $i;
	}

// ---- Basic assignment ------------------------------------------------------

	/**
	 * @return static $this
	 */
	function str($parameter, $value) {
		$this->params[$parameter] = [ $value, 's' ];
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

		$this->params[$parameter] = [ $value, 'i' ];
		return $this;
	}

	/**
	 * @return static $this
	 */
	function bool($parameter, $value, array $map = null) {
		if ($value === true || $value === false) {
			$this->params[$parameter] = [ $value ? 1 : 0, 'i' ];
		}
		else { // non-boolean
			$this->params[$parameter] = [ $this->booleanize($value, $map) ? 1 : 0, 'i' ];
		}

		return $this;
	}

// ---- Basic Binding ---------------------------------------------------------

	/**
	 * @return static $this
	 */
	function bindstr($parameter, &$variable) {
		$this->params[$parameter] = [ &$variable, 's' ];
		return $this;
	}

	/**
	 * @return static $this
	 */
	function bindnum($parameter, &$variable) {
		$this->params[$parameter] = [ &$variable, 'i' ];
		return $this;
	}

	/**
	 * @return static $this
	 */
	function bindbool($parameter, &$variable) {
		$this->params[$parameter] = [ &$variable, 'i' ];
		return $this;
	}

// ---- Stored procedure arguments --------------------------------------------

	/**
	 * @return static $this
	 */
	function arg($parameter, &$variable) {
		$this->params[$parameter] = [ &$variable, 's' ];
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

			// we need to bind in order
			if (empty($this->refactored_stmt)) {
				$index = [];

				$this->refactored_stmt = $this->query;

				# 
				# We need to sort by length to avoid replacing keys that are
				# prefixes of other keys, thereby invaliding the bigger keys
				#

				$keylengths = [];
				foreach (\array_keys($this->params) as $key) {
					$keylengths[$key] = strlen($key);
				}

				\asort($keylengths, SORT_NUMERIC);

				$keys = \array_keys(\array_reverse($keylengths, true));

				foreach ($keys as $key) {
					$position = \strpos($this->query, $key);
					if ($position == false) {
						throw new Panic("Tried to bind parameter $key but it's not present in statement.");
					}

					$this->refactored_stmt = \str_replace($key, '?', $this->refactored_stmt);

					$index[$key] = $position;
				}

				\asort($index, SORT_NUMERIC);

				$this->stmt = \mysqli_prepare($this->dbh, $this->refactored_stmt);

				if ( ! $this->stmt) {
					throw new Panic('Failed to prepare statement');
				}

				$bindings = '';
				$args = [ $this->stmt, null ];
				foreach (array_keys($index) as $key) {
					$bindings .= $this->params[$key][1];
					$args[] = & $this->params[$key][0];
				}

				$args[1] = $bindings;

				if ( ! empty($args[1])) {
					\call_user_func_array('mysqli_stmt_bind_param', $args);
				}
			}
			
			if ( ! \mysqli_stmt_execute($this->stmt)) {
				throw new Panic('Failed to execute statement');
			}

			#
			# to avoid complications with statements not getting closed before
			# new ones are executed we fetch the result set imediatly
			#

			$this->resultset = [];
			$result = \mysqli_stmt_get_result($this->stmt);
			while ($data = mysqli_fetch_assoc($result)) {
				$this->resultset[] = $data;
			}
		}
		catch (\Exception $exception) {
			$message = $exception->getMessage();
			$message .= "\n".$this->formatQuery($this->query);
			throw new Panic($message, 500, $exception);
		}

		return $this;
	}

	/**
	 * Featch as object.
	 *
	 * @return mixed
	 */
	function fetch_object($class = 'stdClass', array $constructor_args = null) {
		throw new Panic('Not supported by Mysqli driver');
	}

	/**
	 * Fetch row as associative.
	 *
	 * @return array or null
	 */
	function fetch_entry() {
		if ($this->resultset === null) {
			throw new Panic('Tried to fetch_entry on un-executed statement');
		}

		if (empty($this->resultset)) {
			return null;
		}
		else { // succesfully retrieved statement
			return $this->resultset[0];
		}
	}

	/**
	 * Retrieves all rows. Rows are retrieved as arrays. Empty result will
	 * return an empty array.
	 *
	 * @return array
	 */
	function fetch_all() {
		return $this->resultset;
	}

} # class
