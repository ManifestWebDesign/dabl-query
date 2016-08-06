<?php

use Dabl\Query\DBManager;

class DBManagerTest extends PHPUnit_Framework_TestCase {

	public static $defaultParams = array(
		'driver' => 'mysql',
		'host' => 'localhost',
		'dbname' => '',
		'user' => 'root',
		'password' => ''
	);

	public function setUp() {
		parent::setUp();

		DBManager::clearConnections();
		DBManager::addConnection('default', self::$defaultParams);
	}

	public function tearDown() {
		parent::tearDown();

		DBManager::clearConnections();
	}

	/**
	 * @covers DBManager::getConnections
	 */
	public function testGetConnections() {
		$connections = DBManager::getConnections();
		$this->assertInternalType('array', $connections);
		foreach ($connections as $connection) {
			$this->assertInstanceOf('\Dabl\Adapter\DABLPDO', $connection);
		}
	}

	public function testGetParameters() {
		$this->assertEquals(self::$defaultParams, DBManager::getParameters('default'));
	}

	/**
	 * @covers DBManager::getConnectionNames
	 */
	public function testGetConnectionNames() {
		$connection_names = DBManager::getConnectionNames();
		$this->assertInternalType('array', $connection_names);
		foreach ($connection_names as $connection_name) {
			$this->assertInternalType('string', $connection_name);
		}
	}

	/**
	 * @covers DBManager::getConnection
	 */
	public function testGetConnectionNoArgument() {
		$connection = DBManager::getConnection();

		$this->assertInstanceOf('\Dabl\Adapter\DABLPDO', $connection);

		$connections = DBManager::getConnections();

		// verify that $connection is the first connection
		$this->assertEquals(array_shift($connections), $connection);
	}

	/**
	 * @covers DBManager::addConnection
	 */
	public function testAddConnection() {
		DBManager::addConnection('default', self::$defaultParams);
		$this->assertEquals(self::$defaultParams, DBManager::getParameters('default'));
	}

	/**
	 * @covers DBManager::getParameter
	 */
	public function testGetParameter() {
		$this->assertEquals('root', DBManager::getParameter('default', 'user'));
	}

	/**
	 * @covers DBManager::disconnect
	 * @todo Implement testDisconnect().
	 */
	public function testDisconnect() {
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
				'This test has not been implemented yet.'
		);
	}

}