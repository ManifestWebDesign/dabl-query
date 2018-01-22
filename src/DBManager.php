<?php

/**
 * @link https://github.com/ManifestWebDesign/DABL
 * @link http://manifestwebdesign.com/redmine/projects/dabl
 * @author Manifest Web Design
 * @license    MIT License
 */

namespace Dabl\Query;
use Dabl\Adapter\DABLPDO;
use PDOException;
use RuntimeException;

/**
 * Database Management class. Handles connections to multiple databases.
 *
 * {@example libraries/dabl/DBManager_description_1.php}
 *
 * @package dabl
 */
class DBManager {

	private static $connections = array();
	private static $parameters = array();

	private function __construct() {

	}

	private function __clone() {

	}

	/**
	 * Get the database connections.
	 * All database handles returned will be connected.
	 *
	 * @return DABLPDO[]
	 */
	static function getConnections() {
		foreach (self::$parameters as $name => $params) {
			self::connect($name);
		}
		return self::$connections;
	}

	/**
	 * Get the names of all known database connections.
	 *
	 * @return string[]
	 */
	static function getConnectionNames() {
		return array_keys(self::$parameters);
	}

	/**
	 * Get the connection for $db_name. The returned object will be
	 * connected to its database.
	 *
	 * @param String $connection_name
	 * @return DABLPDO
	 * @throws PDOException If the connection fails
	 */
	static function getConnection($connection_name = null) {
		if (null === $connection_name) {
			$keys = array_keys(self::$parameters);
			$connection_name = reset($keys);
		}

		if (!@$connection_name) {
			return null;
		}

		return self::connect($connection_name);
	}

	/**
	 * Add connection information to the manager. This will not
	 * connect the database endpoint until it is requested from the
	 * manager.
	 *
	 * @param string $connection_name Name for the connection
	 * @param array $connection_params Parameters for the connection
	 */
	static function addConnection($connection_name, $connection_params) {
		self::$parameters[$connection_name] = $connection_params;
	}

	/**
	 * Get the parameters for a given connection name
	 *
	 * @param $connection_name
	 * @return mixed
	 */
	static function getParameters($connection_name) {
		if (!array_key_exists($connection_name, self::$parameters)) {
			throw new RuntimeException("Configuration for database '$connection_name' not loaded");
		}

		return self::$parameters[$connection_name];
	}

	/**
	 * @param $connection_name
	 * @param $key
	 * @param $value
	 */
	static function setParameter($connection_name, $key, $value) {
		self::$parameters[$connection_name][$key] = $value;
	}

	/**
	 * Get the specified connection parameter from the given DB
	 * connection.
	 *
	 * @param string $connection_name
	 * @param string $key
	 * @return string|null
	 * @throws RuntimeException
	 */
	static function getParameter($connection_name, $key) {
		// don't reveal passwords through this interface
		if ('password' === $key) {
			throw new RuntimeException('DB::password is private');
		}

		if (!array_key_exists($connection_name, self::$parameters)) {
			throw new RuntimeException("Configuration for database '$connection_name' not loaded");
		}

		return @self::$parameters[$connection_name][$key];
	}

	/**
	 * (Re-)connect to the database connection named $key.
	 *
	 * @access private
	 * @since 2010-10-29
	 * @param string $connection_name Connection name
	 * @return DABLPDO Database connection
	 * @throws PDOException If the connection fails
	 */
	private static function connect($connection_name) {
		if (array_key_exists($connection_name, self::$connections)) {
			return self::$connections[$connection_name];
		}

		if (!array_key_exists($connection_name, self::$parameters)) {
			throw new RuntimeException('Connection "' . $connection_name . '" has not been set');
		}

		$conn = DABLPDO::connect(self::$parameters[$connection_name]);
		return (self::$connections[$connection_name] = $conn);
	}

	/**
	 * Disconnect from the database connection named $key.
	 *
	 * @param string $connection_name Connection name
	 * @return void
	 */
	static function disconnect($connection_name) {
		self::$connections[$connection_name] = null;
		unset(self::$connections[$connection_name]);
	}

	/**
	 * Clears parameters and references to all connections
	 */
	static function clearConnections() {
		self::$connections = array();
		self::$parameters = array();
	}

}
