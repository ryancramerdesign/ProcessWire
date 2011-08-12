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
 * Copyright (C) 2010 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://www.processwire.com
 * http://www.ryancramer.com
 *
 */
abstract class DatabaseQuery extends WireData { 

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
	 * Execute the query with the current database handle
	 *
	 */
	public function execute() {
		return $this->fuel('db')->query($this); 
	}

}

