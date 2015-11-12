<?php

/**
 * ProcessWire PDO Database
 *
 * Serves as a wrapper to PHP's PDO class
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2015 by Ryan Cramer 
 * This file licensed under Mozilla Public License v2.0 http://mozilla.org/MPL/2.0/
 * 
 * https://processwire.com
 *
 */

/**
 * Database class provides a layer on top of mysqli
 *
 */
class WireDatabasePDO extends Wire implements WireDatabase {

	/**
	 * Log of all queries performed in this instance
	 *
	 */
	static protected $queryLog = array();

	/**
	 * Whether queries will be logged
	 * 
	 */
	protected $debugMode = false;

	/**
	 * Instance of PDO
	 * 
	 */
	protected $pdo = null;

	/**
	 * PDO connection settings
	 * 
	 */
	private $pdoConfig = array(
		'dsn' => '', 
		'user' => '',
		'pass' => '', 	
		'options' => '',
		);

	/**
	 * Create a new PDO instance from ProcessWire $config API variable
	 * 
	 * If you need to make other PDO connections, just instantiate a new WireDatabasePDO (or native PDO)
	 * rather than calling this getInstance method. 
	 * 
	 * @param Config $config
	 * @return WireDatabasePDO 
	 * @throws WireException
	 * 
	 */
	public static function getInstance(Config $config) {

		if(!class_exists('PDO')) {
			throw new WireException('Required PDO class (database) not found - please add PDO support to your PHP.'); 
		}

		$host = $config->dbHost;
		$username = $config->dbUser;
		$password = $config->dbPass;
		$name = $config->dbName;
		$socket = $config->dbSocket; 
		$charset = $config->dbCharset;
		if($socket) {
			// if socket is provided ignore $host and $port and use $socket instead:
			$dsn = "mysql:unix_socket=$socket;dbname=$name;";
		} else {
			$dsn = "mysql:dbname=$name;host=$host";
			$port = $config->dbPort;
			if($port) $dsn .= ";port=$port";
		}
		$driver_options = array(
			PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES '$charset'",
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
			);
		$database = new WireDatabasePDO($dsn, $username, $password, $driver_options); 
		$database->setDebugMode($config->debug);
		return $database;
	}
	
	public function __construct($dsn, $username = null, $password = null, array $driver_options = array()) {
		$this->pdoConfig['dsn'] = $dsn; 
		$this->pdoConfig['user'] = $username;
		$this->pdoConfig['pass'] = $password; 
		$this->pdoConfig['options'] = $driver_options; 
		$this->pdo();
	}

	/**
	 * Return the current PDO connection instance
	 *
	 * Use this instead of $this->pdo because it restores a lost connection automatically. 
	 *
	 * @return PDO
	 *
	 */
	public function pdo() {
		if(!$this->pdo) $this->pdo = new PDO(
			$this->pdoConfig['dsn'], 
			$this->pdoConfig['user'], 
			$this->pdoConfig['pass'], 
			$this->pdoConfig['options']
			); 	
		return $this->pdo;
	}

	
	public function errorCode() {
		return $this->pdo()->errorCode();
	}
	
	public function errorInfo() {
		return $this->pdo()->errorInfo();
	}
	
	public function getAttribute($attribute) {
		return $this->pdo()->getAttribute($attribute); 
	}
	
	public function setAttribute($attribute, $value) {
		return $this->pdo()->setAttribute($attribute, $value); 
	}
	
	public function lastInsertId($name = null) {
		return $this->pdo()->lastInsertId($name); 
	}
	
	public function query($statement, $note = '') {
		if($this->debugMode) $this->queryLog($statement, $note); 
		return $this->pdo()->query($statement); 
	}
	
	public function beginTransaction() {
		return $this->pdo()->beginTransaction();
	}
	
	public function inTransaction() {
		return $this->pdo()->inTransaction();
	}
	
	public function commit() {
		return $this->pdo()->commit();
	}
	
	public function rollBack() {
		return $this->pdo()->rollBack();
	}

	/**
	 * Get an array of all queries that have been executed thus far
	 *
	 * Active in ProcessWire debug mode only
	 *
	 * @return array
	 *
	 */
	static public function getQueryLog() {
		return self::$queryLog; 
	}

	/**
	 * Prepare an SQL statement for accepting bound parameters
	 * 
	 * @param string $statement
	 * @param array|string $driver_options Driver options array or you may specify $note here
	 * @param string $note Debug notes to save with query in debug mode
	 * @return PDOStatement
	 * 
	 */
	public function prepare($statement, $driver_options = array(), $note = '') {
		if(is_string($driver_options)) {
			$note = $driver_options; 
			$driver_options = array();
		}
		if($this->debugMode) $this->queryLog($statement, $note); 
		return $this->pdo()->prepare($statement, $driver_options);
	}
	
	public function exec($statement, $note = '') {
		$this->queryLog($statement, $note); 
		return $this->pdo()->exec($statement);
	}
	
	protected function queryLog($sql, $note) {
		self::$queryLog[] = $sql . ($note ? " -- $note" : "");
	}

	/**
	 * Get array of all tables in this database.
	 *
	 * @return array
	 *
	 */
	public function getTables() {
		static $tables = array();

		if(!count($tables)) {
			$query = $this->query("SHOW TABLES"); 			
			while($col = $query->fetchColumn()) $tables[] = $col;
		} 

		return $tables; 
	}

	/**
	 * Is the given string a database comparison operator?
	 *
	 * @param string $str 1-2 character opreator to test
	 * @return bool 
	 *
	 */
	public function isOperator($str) {
		return in_array($str, array('=', '<', '>', '>=', '<=', '<>', '!=', '&', '~', '&~', '|', '^', '<<', '>>'), true);
	}

	/**
	 * Sanitize a table name for _a-zA-Z0-9
	 *
	 * @param string $table
	 * @return string
	 *
	 */
	public function escapeTable($table) {
		$table = (string) trim($table); 
		if(ctype_alnum($table)) return $table; 
		if(ctype_alnum(str_replace('_', '', $table))) return $table;
		return preg_replace('/[^_a-zA-Z0-9]/', '_', $table);
	}

	/**
	 * Sanitize a column name for _a-zA-Z0-9
	 *
	 * @param string $col
	 * @return string
	 *
	 */
	public function escapeCol($col) {
		return $this->escapeTable($col);
	}

	/**
	 * Sanitize a table.column string, where either part is optional
	 *
	 * @param string $str
	 * @return string
	 *
	 */
	public function escapeTableCol($str) {
		if(strpos($str, '.') === false) return $this->escapeTable($str); 
		list($table, $col) = explode('.', $str); 
		return $this->escapeTable($table) . '.' . $this->escapeCol($col);
	}

	/**
	 * Escape a string value, same as $db->quote() but without surrounding quotes
	 *
	 * @param string $str
	 * @return string
	 *
	 */
	public function escapeStr($str) {
		return substr($this->pdo()->quote($str), 1, -1);
	}

	/**
	 * Escape a string value, for backwards compatibility till PDO transition complete
	 *
	 * @deprecated
	 * @param string $str
	 * @return string
	 *
	 */
	public function escape_string($str) {
		return $this->escapeStr($str); 
	}

	/**
	 * Quote and escape a string value
	 *
	 * @param string $str
	 * @return string
	 *
	 */
	public function quote($str) {
		return $this->pdo()->quote($str);
	}

	/**
	 * Escape a string value, plus escape characters necessary for a MySQL 'LIKE' phrase
	 *
	 * @param string $like
	 * @return string
	 *
	 */
	public function escapeLike($like) {
		$like = $this->escapeStr($like); 
		return addcslashes($like, '%_'); 
	}
	
	public function setDebugMode($debugMode) {
		$this->debugMode = (bool) $debugMode; 
	}
	
	public function __get($key) {
		if($key == 'pdo') return $this->pdo();
		return parent::__get($key);
	}
	
	public function closeConnection() {
		$this->pdo = null;
	}

	/**
	 * Retrieve new instance of WireDatabaseBackups ready to use with this connection
	 * 
	 * See WireDatabaseBackup class for usage. 
	 * 
	 * @return WireDatabaseBackup
	 * @throws WireException|Exception on fatal error
	 * 
	 */
	public function backups() {
	
		$path = $this->wire('config')->paths->assets . 'backups/database/';
		if(!is_dir($path)) {
			wireMkdir($path, true); 
			if(!is_dir($path)) throw new WireException("Unable to create path for backups: $path"); 
		}

		$backups = new WireDatabaseBackup($path); 
		$backups->setDatabase($this);
		$backups->setDatabaseConfig($this->wire('config'));
		$backups->setBackupOptions(array('user' => $this->wire('user')->name)); 
	
		return $backups; 
	}

}
