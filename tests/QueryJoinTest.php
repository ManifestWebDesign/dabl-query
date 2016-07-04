<?php

use Dabl\Adapter\DABLPDO;
use Dabl\Adapter\DBMySQL;
use Dabl\Query\Condition;
use Dabl\Query\Query;
use Dabl\Query\QueryJoin;

class QueryJoinTest extends PHPUnit_Framework_TestCase {

	/**
	 * @var DBMySQL
	 */
	protected $pdo;

	function setUp() {
		try {
			$this->pdo = DABLPDO::factory(array(
				'driver' => 'mysql',
				'host' => 'localhost',
				'dbname' => 'test',
				'user' => 'root',
				'password' => ''
			));
		} catch (Exception $e) {
			$this->markTestSkipped('Unable to connect to MySQL test database' + $e->getMessage());
		}
		return parent::setUp();
	}
	
	function testIsQualifiedColumn() {
		$join = new QueryJoin('');

		$this->assertFalse($join->isQualifiedColumn(new Condition));
		$this->assertFalse($join->isQualifiedColumn(new Query));
		$this->assertFalse($join->isQualifiedColumn('foo = bar'));
		$this->assertFalse($join->isQualifiedColumn('1=1'));
		$this->assertFalse($join->isQualifiedColumn('table AS alias'));

		$this->assertTrue($join->isQualifiedColumn('foo.bar'));
		$this->assertTrue($join->isQualifiedColumn('db.foo.bar'));
	}

	function testNormalJoin() {
		$join = QueryJoin::create('database.table', 'othertable.column = database.table.column');
		$this->assertEquals(
			(string) $join->getQueryStatement($this->pdo),
			'JOIN `database`.`table` ON (othertable.column = database.table.column)'
		);
	}

	function testPropelJoin() {
		$join = QueryJoin::create('foo.bar_id', 'foo2.bar_id');
		$this->assertEquals(
			(string) $join->getQueryStatement($this->pdo),
			'JOIN `foo2` ON (`foo`.`bar_id` = `foo2`.`bar_id`)'
		);
	}

	function testPropelJoinWithAlias() {
		$join = QueryJoin::create('foo.bar_id', 'foo2.bar_id')
			->setAlias('f');
		$this->assertEquals(
			(string) $join->getQueryStatement($this->pdo),
			'JOIN `foo2` AS f ON (`foo`.`bar_id` = `foo2`.`bar_id`)'
		);
	}

	function testPropelJoinWithDatabasePrefix() {
		$join = QueryJoin::create('db.foo.bar_id', 'foo2.bar_id')
			->setAlias('f');
		$this->assertEquals(
			(string) $join->getQueryStatement($this->pdo),
			'JOIN `foo2` AS f ON (`db`.`foo`.`bar_id` = `foo2`.`bar_id`)'
		);
	}

}