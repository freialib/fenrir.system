<?php namespace fenrir\system;

/**
 * @copyright (c) 2016, freia Team
 * @license BSD-2 <http://freialib.github.io/license.txt>
 * @package freia Library
 */
trait SQLStatementCommonTrait {

	/**
	 * @return static $this
	 */
	function date($parameter, $value) {
		if (empty($value)) {
			$value = null;
		}

		return $this->str($parameter, $value);
	}

	/**
	 * @return static $this
	 */
	function binddate($parameter, &$variable) {
		return $this->bindstr($parameter, $variable);
	}

// ---- Advanced Helpers ------------------------------------------------------

	/**
	 * Automatically calculates and sets :offset and :limit based on a page
	 * input. If page or limit are null, the limit will be set to the maximum
	 * integer value.
	 *
	 * @return static $this
	 */
	function page($page, $limit = null, $offset = 0) {
		if ($page === null || $limit === null) {
			// retrieve all rows
			$this->num(':offset', $offset);
			$this->num(':count', PHP_INT_MAX);
		}
		else { // $page != null
			$this->num(':offset', $limit * ($page - 1) + $offset);
			$this->num(':count', $limit);
		}

		return $this;
	}

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
		$map !== null or $map = [

			// truthy
			'true' => true,
			'on' => true,
			'yes' => true,

			// falsy
			'false' => false,
			'off' => false,
			'no' => false,

		];

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

	/**
	 * @return string formatted query
	 */
	protected function formatQuery($query, $indentLevel = 4) {

		$indent = str_repeat(' ', $indentLevel);

		// assume tabs are always 4 spaces
		$query = str_replace("\t", "    ", $query);
		// line processing
		$rawQueryLines = explode("\n", str_replace("\n\r", "\n", trim($query)));

		$firstline = '';
		if (count($rawQueryLines) > 0 && trim($rawQueryLines[0]) != '') {
			$firstline = $indent.ltrim(array_shift($rawQueryLines))."\n";
		}

		$queryLines = [];
		$shortestIndent = 64;
		foreach ($rawQueryLines as $line) {
			$trimmedLine = ltrim($line);
			if (strlen($trimmedLine) > 0) {
				$queryLines[] = $line;
				$lineIndentLength = strlen($line) - strlen($trimmedLine);
				if ($lineIndentLength < $shortestIndent) {
					$shortestIndent = $lineIndentLength;
				}
			}
		}

		// we trim to lowest tab count
		$indentOffset = intval($shortestIndent / 4) * 4;

		$formattedQueryLines = [];
		foreach ($queryLines as $line) {
			$formattedQueryLines[] = $indent.substr($line, $indentOffset);
		}

		return $firstline.implode("\n", $formattedQueryLines)."\n\n";
	}

} # trait
