<?php

/**
 * ProcessWire PDO Database
 *
 * Serves as a wrapper to PHP's PDO class
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2013 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://processwire.com
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
		$port = $config->dbPort;
		$dsn = "mysql:dbname=$name;host=$host";
		if($port) $dsn .= ";port=$port";
		$driver_options = array(
			PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'UTF8'",
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
			);
		$database = new WireDatabasePDO($dsn, $username, $password, $driver_options); 
		$database->setDebugMode($config->debug);
		return $database;
	}
	
	public function __construct($dsn, $username = null, $password = null, array $driver_options = array()) {
		$this->pdo = new PDO($dsn, $username, $password, $driver_options); 	
	}
	
	public function errorCode() {
		return $this->pdo->errorCode();
	}
	
	public function errorInfo() {
		return $this->pdo->errorInfo();
	}
	
	public function getAttribute($attribute) {
		return $this->pdo->getAttribute($attribute); 
	}
	
	public function setAttribute($attribute, $value) {
		return $this->pdo->setAttribute($attribute, $value); 
	}
	
	public function lastInsertId($name = null) {
		return $this->pdo->lastInsertId($name); 
	}
	
	public function query($statement) {
		if($this->debugMode) self::$queryLog[] = $statement;
		return $this->pdo->query($statement); 
	}
	
	public function beginTransaction() {
		return $this->pdo->beginTransaction();
	}
	
	public function inTransaction() {
		return $this->pdo->inTransaction();
	}
	
	public function commit() {
		return $this->pdo->commit();
	}
	
	public function rollBack() {
		return $this->pdo->rollBack();
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
	
	public function prepare($statement, array $driver_options = array()) {
		if($this->debugMode) self::$queryLog[] = $statement;
		return $this->pdo->prepare($statement, $driver_options);
	}
	
	public function exec($statement) {
		if($this->debugMode) self::$queryLog[] = $statement;
		return $this->pdo->exec($statement);
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
		return in_array($str, array('=', '<', '>', '>=', '<=', '<>', '!=', '&', '~', '|', '^', '<<', '>>'), true);
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
		return substr($this->pdo->quote($str), 1, -1);
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
		return $this->pdo->quote($str);
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
		if($key == 'pdo') return $this->pdo;
		return parent::__get($key);
	}

}
