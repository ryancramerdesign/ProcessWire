<?php

/**
 * ProcessWire MySQLi Database
 *
 * Serves as a wrapper to PHP's mysqli classes
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2010 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://www.processwire.com
 * http://www.ryancramer.com
 *
 */

/**
 * WireDatabaseException is the exception thrown by the Database class
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
	 * Overrides default mysqli query method so that it also records and times queries. 
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

		$result = parent::query($sql, $resultmode); 

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
	 */
	static public function getQueryLog() {
		return self::$queryLog; 
	}

	/**
	 * Get array of all tables in this database.
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
	 */
	public function isOperator($str) {
		return in_array($str, array('=', '<', '>', '>=', '<=', '<>', '!=', '&', '~', '|', '^', '<<', '>>'));
	}

	/**
	 * Set whether Exceptions should be thrown on query errors
	 *
	 */
	public function setThrowExceptions($throwExceptions = true) {
		$this->throwExceptions = $throwExceptions; 
	}
}
