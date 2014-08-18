<?php namespace fenrir\system;

/**
 * @copyright (c) 2014, freia Team
 * @license BSD-2 <http://freialib.github.io/license.txt>
 * @package freia Library
 */
trait MysqlRepoTrait {

	use \hlin\RepoTrait;

	/**
	 * @var \fenrir\MysqlDatabase
	 */
	protected $db = null;

	/**
	 * You can specify fields via %fields
	 * You can specify limit via %limit and offset via %offset
	 * You can specify order rules via %order_by
	 *
	 * Everything else is interpreted as constraints on the entries.
	 *
	 * @return array [ models ]
	 */
	function find(array $logic) {

		$opt = [];
		foreach (['%fields', '%order_by', '%limit', '%offset'] as $key) {
			if (isset($logic[$key])) {
				$opt[$key] = $logic[$key];
				unset($logic[$key]);
			}
			else { // %filter not set
				$opt[$key] = [];
			}
		}

		$entries = $this->sqlfind
			(
				$logic,
				$opt['%fields'],
				$opt['%order_by'],
				$opt['%limit'],
				$opt['%offset']
			);

		return $this->toModels($entries);
	}

	/**
	 * @return \hlin\archetype\Model
	 */
	function entry($entry_id) {
		$modelClass = \hlin\PHP::pnn($this->constants()['model']);
		return $modelClass::instance($this->sqlentry($entry_id));
	}

	/**
	 * @return \hlin\archetype\Model
	 */
	function store(\hlin\archetype\Model $model) {
		return $this->entry($this->sqlinsert($model->toArray()));
	}

// ---- Private ---------------------------------------------------------------

	/**
	 * @return string id field
	 */
	protected function idfield() {
		return '_id';
	}

	/**
	 * @return array [ models ]
	 */
	protected function toModels($entries) {
		$modelClass = \hlin\PHP::pnn($this->constants()['model']);

		$models = [];
		foreach ($entries as $entry) {
			$models[] = $modelClass::instance($entry);
		}

		return $models;
	}

// ---- MySQL Functions -------------------------------------------------------

	/**
	 * Inserts fields, if bools or nums are specified the fields mentioned are
	 * treated as desired,
	 *
	 * @return int new entry id
	 */
	protected function sqlinsert(array $fields, $nums = [], $bools = []) {

		$db = $this->db;

		$keys = array_keys($fields);
		$strs = array_diff($keys, $bools, $nums);

		$key_fields = \hlin\Arr::join(', ', $keys, function ($k, $val) { return "`$val`"; });
		$value_fields = \hlin\Arr::join(', ', $keys, function ($k, $val) { return ":$val "; });

		$db->prepare
			(
				"
					INSERT INTO `[table]`
						   ($key_fields)
					VALUES ($value_fields)
				",
				[ 'table' => $this->constants()['table'] ]
			)
			->strs($fields, $strs)
			->bools($fields, $bools)
			->nums($fields, $nums)
			->execute();

		return $db->lastInsertId();
	}

	/**
	 * @return array
	 */
	protected function sqlfind(array $criteria = null, array $filter = null, $order_by = [], $limit = null, $offset = null) {

		$db = $this->db;

		if (empty($filter)) {
			$fields = '*';
		}
		else { // recieved filter
			$fields = implode(', ', array_map(function ($key, $val) {
				if (is_numeric($key)) {
					return $val;
				}
				else { // alternative field name provided
					return "$val $key";
				}
			}, $filter));
		}

		$where = $this->parseconstraints($criteria, true);
		$order_by = $this->parseorder($order_by, true);

		$limit = '';

		if ($limit != null) {
			$limit = " LIMIT $limit ";
		}

		if ($offset != null) {
			$limit = "{$limit}OFFSET $offset";
		}

		$select = $db->prepare
			(
				"
					SELECT $fields
					  FROM `[table]`
					 $where
					 $order_by
					$limit
				",
				[ 'table' => $this->constants()['table'] ]
			);

		return $select->execute()->fetch_all();
	}

	/**
	 * Mostly an alias to sqlsearch on id with limit of 1.
	 *
	 * @return array|null
	 */
	protected function sqlentry($entry_id) {
		$res = $this->sqlfind([ $this->idfield() => $entry_id ], null, null, 1);
		if (empty($res)) {
			return null;
		}
		else { // found entry
			return $res[0];
		}
	}

	/**
	 * [!!] Intentionally not permitting null for constraints, please perform
	 * the check in context because this method only returns the parameters to
	 * a WHERE clause not the entire WHERE clause.
	 *
	 * [!!] DO NOT expect this method to always return a non-empty value; it is
	 * possible for a constraint to resolve to nothing such as a value being
	 * constraint between null. Always check if the value return is not empty.
	 *
	 * eg.
	 *
	 *		$this->parse_constraints
	 *			(
	 *				[
	 *					'datetime' => ['between' => [$start, $end]],
	 *					'type' => 1,
	 *					'id' => ['>=' => 10000],
	 *					'given_name' => ['in' => ['John', 'Alex, 'Steve']],
	 *					'family_name => ['like' => 'B%'],
	 *				]
	 *			);
	 *
	 * If an operator is missing here, simply overwrite the method, add handling
	 * to detect and resolve your paramter, remove it from the list then pass
	 * the list to this method for processing additional paramters, and combine
	 * the result with your result.
	 *
	 * @return string
	 */
	protected function parseconstraints(array $constraints = null, $append_where = false) {

		$db = $this->db;

		if (empty($constraints)) {
			return '';
		}

		$parameter_resolver = function ($k, $value, $operator) use ($db) {
			if (is_bool($value)) {
				return "$k $operator ".($value ? 'TRUE' : 'FALSE');
			}
			else if (is_numeric($value)) {
				return "$k $operator $value";
			}
			else if (is_null($value)) {
				if ($operator == '=' || $operator == '<=>') {
					return "$k IS NULL";
				}
				else { # assume some form of negative
					return "$k IS NOT NULL";
				}
			}
			else if (preg_match('#like#', \strtolower($operator))) {
				return "$k $operator ".$db->quote($value);
			}
			else if (is_array($value)) {

				// the value to be compared an array. meaning we have
				// additional parameter processing and the operator itself
				// needs to be handled in a special way

				// we perform the preg_match because there is a NOT variant
				// to all of the following
				if (preg_match('#in#', strtolower($operator))) {
					return "$k $operator (".\hlin\Arr::join (
						', ', $value,
						function ($i, $value) use ($db) {
							return $db->quote($value);
						}
					).')';
				}
				else if (preg_match('#between#', strtolower($operator))) {
					if ($value[0] !== null && $value[1] !== null) {
						return "$k $operator ".$db->quote($value[0])." AND ".$db->quote($value[1]);
					}
					else if ($value[0] === null && $value[1] === null) {
						return false; # \hlin\Arr::join will ignore this item
					}
					else { # convert constraint to comparison
						$start = $value[0];
						$end = $value[1];
						// is negative?
						if (preg_match('#not#', strtolower($operator))) {
							// process as NOT in interval
							if ($start !== null) { # $end === null
								$start = $db->quote($start);
								return "$k < $start";
							}
							else { # $end !== null && $start == null
								$end = $db->quote($end);
								return "$k > $end";
							}
						}
						else { # positive comparison
							if ($start !== null) { # $end === null
								$start = $db->quote($start);
								return "$k >= $start";
							}
							else { # $end !== null && $start == null
								$end = $db->quote($end);
								return "$k <= $end";
							}
						}
					}
				}
				else if (in_array(strtolower($operator), ['=', '<', '>', '<=', '>='])) {
					return "$k $operator ".$db->quote($value);
				}
				else { # unknown operator
					throw new Panic("Unsupported operator [$operator].");
				}
			}
			else if ($operator == '=') {
				return "$k $operator $value";
			}
			else { # string, or string compatible
				return "$k $operator ".$db->quote($value);
			}
		};

		$result = \hlin\Arr::join (
			' AND ',      # delimiter
			$constraints, # source
			function ($k, $value) use ($parameter_resolver) {

				$k = strpbrk($k, ' .()') === false ? '`'.$k.'`' : $k;

				if (is_array($value)) {
					return $parameter_resolver($k, current($value), key($value));
				}
				else { # non-array
					return $parameter_resolver($k, $value, '<=>'); # null safe equals
				}
			}
		);

		if ($append_where) {
			if ($result !== null) {
				return 'WHERE '.$result;
			}
			else { # result === null
				return null;
			}
		}
		else { # ! append where
			return $result;
		}
	}

	/**
	 * @return string
	 */
	protected function parseorder(array $order = null, $append_where = false) {

		if (empty($order)) {
			return '';
		}

		$order_by = \hlin\Arr::join(', ', $order, function ($query, $order) {
			return strpbrk($query, ' .') === false ? "`$query` $order" : "$query $order";
		});

		if ($append_where && ! empty($order_by)) {
			return "ORDER BY $order_by";
		}
		else { // no append
			return $order_by;
		}
	}

	/**
	 * @return string
	 */
	protected function parselimiters(array $order = null, array $constraints = null) {

		$order = $this->parseorder($order);
		$constraints = $this->parseconstraints($constraints);

		$limiters = '';

		if ( ! empty($constraints)) {
			$limiters .= "WHERE $constraints";
		}

		if ( ! empty($order)) {
			$limiters .= "ORDER BY $order";
		}

		return $limiters;
	}


} # trait
