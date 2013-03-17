<?php

/**
 * ProcessWire MySQLi Database
 *
 * Serves as a wrapper to PHP's mysqli classes
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2012 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://www.processwire.com
 * http://www.ryancramer.com
 *
 */

/**
 * WireDatabaseException is the exception thrown by the Database class
 *
 * If you use this class without ProcessWire, change 'extends WireException' below to be just 'extends Exception'
 *
 */
class WireDatabaseException extends WireException {}

/**
 * Database class provides a layer on top of mysqli
 *
 */
class Database extends mysqli {

	/**
	 * Log of all queries performed in this instance
	 *
	 */
	static protected $queryLog = array();

	/**
	 * Should WireDatabaseException be thrown on error?
	 *
	 */
	protected $throwExceptions = true; 

	/**
	 * Construct the Database 
	 *
	 * Since this extends MySQL all the MySQL construct params are kept in tact. 
	 * However, you may just supply an object with the following properties if you prefer: 
	 * $o->dbUser, $o->dbPass, $o->dbHost, $o->dbName, $config->dbPort, $config->dbSocket (optional).
	 * This would usually be from a ProcessWire Config ($config) API var, but kept as generic object
	 * in case someone wants to use this class elsewhere. 
	 * 
	 * @param string|$config $host Hostname or object with config properties. 
	 * @param string $user Username
	 * @param string $pass Password
	 * @param string $db Database name
	 * @param int $port Port
	 * @param string $socket Socket
	 * 
	 */
	public function __construct($host = 'localhost', $user = null, $pass = null, $db = null, $port = null, $socket = null) {

		if(is_object($host) && $host->dbHost) {
			$config = $host;
			$host = $config->dbHost; 
			$user = $config->dbUser; 
			$pass = $config->dbPass; 
			$db = $config->dbName; 
			$port = $config->dbPort; 
			$socket = $config->dbSocket ? $config->dbSocket : null;
		} else $config = null;

		@parent::__construct($host, $user, $pass, $db, $port, $socket);
		if(mysqli_connect_error()) throw new WireDatabaseException("DB connect error " . mysqli_connect_errno() . ' - ' . mysqli_connect_error()); 

		if($config) {
			if($config->dbCharset) $this->set_charset($config->dbCharset); 
				else if($config->dbSetNamesUTF8) $this->query("SET NAMES 'utf8'");
		}
	}

	/**
	 * Overrides default mysqli query method so that it also records and times queries. 
	 *
	 * @param string $sql SQL Query
	 * @param int $resultmode See http://www.php.net/manual/en/mysqli.query.php
	 * @return mixed Returns FALSE on failure. 
	 * 	For successful SELECT, SHOW, DESCRIBE or EXPLAIN queries mysqli_query() will return a MySQLi_Result object. 
	 *	For other successful queries mysqli_query() will return TRUE.
	 *
	 */
	public function query($sql, $resultmode = MYSQLI_STORE_RESULT) {

		static $timerTotalQueryTime = 0;
		static $timerFirstStartTime = 0; 

		if(is_object($sql) && $sql instanceof DatabaseQuery) $sql = $sql->getQuery();

		if(wire('config')->debug) {
			$timerKey = Debug::timer();
			if(!$timerFirstStartTime) $timerFirstStartTime = $timerKey; 
		} else $timerKey = null; 

		$result = @parent::query($sql, $resultmode); 

		if($result) {
			if(wire('config')->debug) { 
				if(isset($result->num_rows)) $sql .= " [" . $result->num_rows . " rows]";
				if(!is_null($timerKey)) {
					$elapsed = Debug::timer($timerKey); 
					$timerTotalQueryTime += $elapsed; 
					$timerTotalSinceStart = Debug::timer() - $timerFirstStartTime; 
					$sql .= " [{$elapsed}s, {$timerTotalQueryTime}s, {$timerTotalSinceStart}s]";
				}
				self::$queryLog[] = $sql; 
			}

		} else if($this->throwExceptions) {
			throw new WireDatabaseException($this->error . (wire('config')->debug ? "\n$sql" : '')); 
		}

		return $result; 
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
	 * Get array of all tables in this database.
	 *
	 * @return array
	 *
	 */
	public function getTables() {
		static $tables = array();

		if(!count($tables)) {
			$result = $this->query("SHOW TABLES"); 			
			while($row = $result->fetch_array()) $tables[] = current($row); 
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
		return in_array($str, array('=', '<', '>', '>=', '<=', '<>', '!=', '&', '~', '|', '^', '<<', '>>'));
	}

	/**
	 * Set whether Exceptions should be thrown on query errors
	 *
	 * @param bool $throwExceptions Default is true
	 *
	 */
	public function setThrowExceptions($throwExceptions = true) {
		$this->throwExceptions = $throwExceptions; 
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
	 * @param string $col
	 * @return string
	 *
	 */
	public function escapeTableCol($str) {
		if(strpos($str, '.') === false) return $this->escapeTable($str); 
		list($table, $col) = explode('.', $str); 
		return $this->escapeTable($table) . '.' . $this->escapeCol($col);
	}

	/**
	 * Escape a string value, camelCase alias of escape_string()
	 *
	 * @param string $str
	 * @return string
	 *
	 */
	public function escapeStr($str) {
		return $this->escape_string($str); 
	}

	/**
	 * Escape a string value, plus escape characters necessary for a MySQL 'LIKE' phrase
	 *
	 * @param string $str
	 * @return string
	 *
	 */
	public function escapeLike($like) {
		$like = $this->escape_string($like); 
		return addcslashes($like, '%_'); 
	}


}
