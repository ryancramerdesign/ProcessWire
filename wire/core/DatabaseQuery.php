<?php

/**
 * ProcessWire DatabaseQuery
 *
 * Serves as a base class for other DatabaseQuery classes
 *
 * The intention behind these classes is to have a query that can safely
 * be passed between methods and objects that add to it without knowledge
 * of what other methods/objects have done to it. It also means being able
 * to build a complex query without worrying about correct syntax placement.
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2015 by Ryan Cramer 
 * This file licensed under Mozilla Public License v2.0 http://mozilla.org/MPL/2.0/
 * 
 * https://processwire.com
 * 
 * 
 * @property array $where
 *
 */
abstract class DatabaseQuery extends WireData { 
	
	protected $bindValues = array();
	
	public function bindValue($key, $value) {
		$this->bindValues[$key] = $value; 
		return $this; 
	}

	/**
	 * Enables calling the various parts of a query as functions for a fluent interface.
	 * 
	 * i.e. $query->select("id")->from("mytable")->orderby("name"); 
	 * See DatabaseSelectQuery class for an example. 
	 *
	 */
	public function __call($method, $args) {
		if(!$this->has($method)) return parent::__call($method, $args); 
		$curValue = $this->get($method); 
		$value = $args[0]; 
		if(empty($value)) return $this; 
		if(is_array($value)) $curValue = array_merge($curValue, $value); 
			else $curValue[] = trim($value, ", "); 
		$this->set($method, $curValue); 
		return $this; 
	}

	public function __set($key, $value) {
		if(is_array($this->$key)) $this->__call($key, array($value)); 
	}

	public function __get($key) {
		if($key == 'query') return $this->getQuery();
			else return parent::__get($key); 
	}

	/**
	 * Merge the contents of current query with another
	 * 
	 * @param DatabaseQuery $query
	 * @return this
	 *
	 */
	public function merge(DatabaseQuery $query) {
		foreach($query as $key => $value) {
			$this->$key = $value; 	
		}
		return $this; 
	}

	/** 
	 * Generate the SQL query based on everything set in this DatabaseQuery object
	 *
	 */
	abstract public function getQuery();

	/**
	 * Get the WHERE portion of the query
	 *
	 */
	protected function getQueryWhere() {
		if(!count($this->where)) return '';
		$where = $this->where; 
		$sql = "\nWHERE " . array_shift($where) . " ";
		foreach($where as $s) $sql .= "\nAND $s ";
		return $sql;
	}

	/**
	 * Prepare and return a PDOStatement
	 * 
	 * @return PDOStatement
	 * 
	 */
	public function prepare() {
		$query = $this->wire('database')->prepare($this->getQuery()); 
		foreach($this->bindValues as $key => $value) {
			$query->bindValue($key, $value); 
		}
		return $query; 
	}

	/**
	 * Execute the query with the current database handle
	 *
	 */
	public function execute() {
		$database = $this->wire('database');
		try { 
			$query = $this->prepare();
			$query->execute();
		} catch(Exception $e) {
			$msg = $e->getMessage();
			if(stripos($msg, 'MySQL server has gone away') !== false) $database->closeConnection();
			if($this->wire('config')->allowExceptions) throw $e; // throw original
			throw new WireException($msg); // throw WireException
		}
		return $query;
	}

}

