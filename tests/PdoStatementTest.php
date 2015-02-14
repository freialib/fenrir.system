<?php namespace fenrir\system\tests;

$modulepath = realpath(__DIR__.'/..');
require "$modulepath/src/Statement/Pdo.php";

class PdoStatementTester extends \fenrir\system\PdoStatement {

	static function _mock_instance() {
		return new static;
	}

	/**
	 * @return string
	 */
	function _mock_formatQuery($query, $indentLevel = 4) {
		return parent::formatQuery($query, $indentLevel);
	}

}; # tester

class PdoStatementTest extends \PHPUnit_Framework_TestCase {

	/** @test */ function
	formatQuery() {
		$tester = PdoStatementTester::_mock_instance();
		$actual = $tester->_mock_formatQuery
			(
				"	SELECT entry.title
					     , entry._id uid
					     , magic

					  FROM `[table]` entry

					  LEFT OUTER
					  JOIN `other_table` other
					    ON other.fkey = entry._id

					 WHERE entry.magic = 'potato'
				",
				4
			);

		$expected =
			"    SELECT entry.title\n".
			"         , entry._id uid\n".
			"         , magic\n".
			"      FROM `[table]` entry\n".
			"      LEFT OUTER\n".
			"      JOIN `other_table` other\n".
			"        ON other.fkey = entry._id\n".
			"     WHERE entry.magic = 'potato'\n\n";

		$this->assertEquals($expected, $actual);
	}

} # test