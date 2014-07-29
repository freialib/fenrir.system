<?php namespace fenrir\system;

/**
 * @copyright (c) 2014, freia Team
 * @license BSD-2 <http://freialib.github.io/license.txt>
 * @package freia Library
 */
class MysqlStatement extends PdoStatement {

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
			$this->num(':limit', PHP_INT_MAX);
		}
		else { // $page != null
			$this->num(':offset', $limit * ($page - 1) + $offset);
			$this->num(':limit', $limit);
		}

		return $this;
	}

} # class
