<?php namespace hlin;

$modulepath = realpath(__DIR__.'/..');

class PHP {
	function pnn($classname) {
		return $classname;
	}
}

class Arr {

	/**
	 * PHP join with a callback
	 *
	 * This is slightly different from implode($glue, array_map) in that it can
	 * ignore entries if the entry in question passes false out.
	 *
	 * @return string
	 */
	static function join($glue, array $list, callable $manipulator) {
		$glued = '';
		foreach ($list as $key => $value) {
			$item = $manipulator($key, $value);
			if ($item !== false) {
				$glued .= $glue.$item;
			}
		}

		if ( ! empty($glued)) {
			$glued = substr($glued, strlen($glue));
		}

		return $glued;
	}

}

trait RepoTrait {
	// empty
}

namespace fenrir\system\tests;

class PdoStatementMock {

	static function instance() {
		$i = new static;
		return $i;
	}

	function strs() { return $this; }
	function bools() { return $this; }
	function nums() { return $this; }
	function execute() { return $this; }
	function fetch_all() { return $this; }

} # mock

class PdoDatabaseMock {

	static function instance() {
		$i = new static;
		return $i;
	}

	function lastInsertId() { return '[last-inserted-id]'; }

	public $sql = null;
	public $consts = null;

	function prepare($sql, $consts) {
		$this->sql = $sql;
		$this->consts = $consts;
		return PdoStatementMock::instance();
	}

	function quote($str) {
		return "'$str'";
	}

} # mock

require "$modulepath/src/Trait/MysqlRepo.php";

class MysqlTraitTester {

	use \fenrir\system\MysqlRepoTrait;

	static function instance() {
		$i = new static;
		$i->db = PdoDatabaseMock::instance();
		return $i;
	}

	function constants() {
		return [
			'table' => 'example-table',
			'model' => 'example.Model'
		];
	}

	function _mock_sql() {
		$lines = array_map(function ($line) {
			return trim($line);
		}, explode("\n", trim($this->db->sql)));

		$filtered_line = [];
		foreach ($lines as $line) {
			if ( ! empty($line)) {
				$filtered_line[] = $line;
			}
		}

		return implode("\n", $filtered_line);
	}

	function _mock_sqlinsert(array $fields, array $nums = [], array $bools = []) {
		return $this->sqlinsert($fields, $nums, $bools);
	}

	function _mock_idfield() {
		return $this->idfield();
	}

} # tester

class MysqlTraitOverwritenTester extends MysqlTraitTester {

	/**
	 * @return \fenrir\MysqlDatabase
	 */
	protected function sqlselect($fields, $where, $order_by, $limiter) {

		$db = $this->db;

		return $db->prepare
			(
				"
					SELECT $fields
					  FROM `[table]` entry
					  JOIN `[profile]` profile
					    ON profile._id = entry.profile_id
					 $where
					 $order_by
					$limiter
				",
				[ 'table' => $this->constants()['table'] ]
			);
	}

} # tester

class MysqlTraitTest extends \PHPUnit_Framework_TestCase {

	/** @test */ function
	sqlinsert() {

		$tester = MysqlTraitTester::instance();

		// Insert

		$tester->_mock_sqlinsert([ 'field1' => 'value1', 'field2' => 'value2' ]);
		$this->assertEquals
			(
				"INSERT INTO `[table]`\n(`field1`, `field2`)\nVALUES (:field1 , :field2 )",
				$tester->_mock_sql()
			);

		$tester->_mock_sqlinsert([ 'field1' => 'value1', 'field2' => 'value2', 'field3' => 'value3', 'field4' => 'value4' ], ['field3'], ['field4']);
		$this->assertEquals
			(
				"INSERT INTO `[table]`\n(`field1`, `field2`, `field3`, `field4`)\nVALUES (:field1 , :field2 , :field3 , :field4 )",
				$tester->_mock_sql()
			);
	}

	/** @test */ function
	idfield() {
		$tester = MysqlTraitTester::instance();
		$this->assertEquals('_id', $tester->_mock_idfield());
	}

	/** @test */ function
	find_with_overwritten_sqlfind() {

		$tester = MysqlTraitOverwritenTester::instance();

		// Basic Search

		$tester->find([ 'profile.title' => 'lorem ipsum' ]);
		$this->assertEquals
			(
				"SELECT entry.*\n".
				"FROM `[table]` entry\n".
				"JOIN `[profile]` profile\n".
				"ON profile._id = entry.profile_id\n".
				"WHERE profile.`title` <=> 'lorem ipsum'",
				$tester->_mock_sql()
			);

	}

	/** @test */ function
	find() {

		$tester = MysqlTraitTester::instance();

		// Empty Search

		$tester->find();
		$this->assertEquals
			(
				"SELECT entry.*\nFROM `[table]` entry",
				$tester->_mock_sql()
			);

		// Targetted Search

		$tester->find([ '_id' => 11 ]);
		$this->assertEquals
			(
				"SELECT entry.*\n".
				"FROM `[table]` entry\n".
				"WHERE entry.`_id` <=> 11",
				$tester->_mock_sql()
			);

		// Limited Search

		$tester->find([ '%limit' => 11 ]);
		$this->assertEquals
			(
				"SELECT entry.*\n".
				"FROM `[table]` entry\n".
				"LIMIT 11",
				$tester->_mock_sql()
			);

		$tester->find([ '%limit' => 11, '%offset' => 13 ]);
		$this->assertEquals
			(
				"SELECT entry.*\n".
				"FROM `[table]` entry\n".
				"LIMIT 11 OFFSET 13",
				$tester->_mock_sql()
			);

		// Filtered Search

		$tester->find([ '%fields' => ['_id', 'article_title' => 'title', 'name' => 'profile.name'] ]);
		$this->assertEquals
			(
				"SELECT entry.`_id` `_id`, entry.`title` `article_title`, profile.`name` `name`\n".
				"FROM `[table]` entry",
				$tester->_mock_sql()
			);

		// Order By

		$tester->find([ '%order_by' => [ 'title' => 'desc', 'entry.name' => 'asc' ] ]);
		$this->assertEquals
			(
				"SELECT entry.*\n".
				"FROM `[table]` entry\n".
				"ORDER BY entry.`title` desc, entry.`name` asc",
				$tester->_mock_sql()
			);

		// Constraints

		$tester->find([ '_id' => [ '>=' => 2 ] ]);
		$this->assertEquals
			(
				"SELECT entry.*\n".
				"FROM `[table]` entry\n".
				"WHERE entry.`_id` >= 2",
				$tester->_mock_sql()
			);

		$tester->find([ 'datetime' => [ 'between' => ['2014-10-01', '2014-12-02'] ] ]);
		$this->assertEquals
			(
				"SELECT entry.*\n".
				"FROM `[table]` entry\n".
				"WHERE entry.`datetime` between '2014-10-01' AND '2014-12-02'",
				$tester->_mock_sql()
			);

		$tester->find([ 'datetime' => [ 'in' => ['abc', 'def', 'ghi'] ] ]);
		$this->assertEquals
			(
				"SELECT entry.*\n".
				"FROM `[table]` entry\n".
				"WHERE entry.`datetime` in ('abc', 'def', 'ghi')",
				$tester->_mock_sql()
			);

		$tester->find([ 'title' => [ 'like' => '%B' ] ]);
		$this->assertEquals
			(
				"SELECT entry.*\n".
				"FROM `[table]` entry\n".
				"WHERE entry.`title` like '%B'",
				$tester->_mock_sql()
			);

		$tester->find([ 'count' => [ '<>' => 12345 ] ]);
		$this->assertEquals
			(
				"SELECT entry.*\n".
				"FROM `[table]` entry\n".
				"WHERE entry.`count` <> 12345",
				$tester->_mock_sql()
			);

		$tester->find([ 'email' => [ '!=' => null ] ]);
		$this->assertEquals
			(
				"SELECT entry.*\n".
				"FROM `[table]` entry\n".
				"WHERE entry.`email` IS NOT NULL",
				$tester->_mock_sql()
			);

		$tester->find([ 'email' => [ '=' => null ] ]);
		$this->assertEquals
			(
				"SELECT entry.*\n".
				"FROM `[table]` entry\n".
				"WHERE entry.`email` IS NULL",
				$tester->_mock_sql()
			);

		$tester->find([ 'is_magic' => [ '=' => false ] ]);
		$this->assertEquals
			(
				"SELECT entry.*\n".
				"FROM `[table]` entry\n".
				"WHERE entry.`is_magic` = FALSE",
				$tester->_mock_sql()
			);

		$tester->find([ 'is_magic' => [ '!=' => true ] ]);
		$this->assertEquals
			(
				"SELECT entry.*\n".
				"FROM `[table]` entry\n".
				"WHERE entry.`is_magic` != TRUE",
				$tester->_mock_sql()
			);
	}

} # test