<?php

/**
 * ProcessWire WireData
 *
 * This is the base data container class used throughout ProcessWire. 
 * It provides get and set access to properties internally stored in a $data array. 
 * Otherwise it is identical to the Wire class. 
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2010 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://www.processwire.com
 * http://www.ryancramer.com
 *
 */

class WireData extends Wire implements IteratorAggregate {

	/**
	 * Array where get/set properties are stored
	 *
	 */
	protected $data = array(); 

	/**
	 * Set a value 
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return this
	 *
	 */
	public function set($key, $value) {
		static $last = '';
		if($key == 'data') {
			if(!is_array($value)) $value = (array) $value;
			return $this->setArray($value); 
		}
		$v = isset($this->data[$key]) ? $this->data[$key] : null;
		if(!$this->isEqual($key, $v, $value)) $this->trackChange($key); 
		$this->data[$key] = $value; 
		return $this; 
	}

	/**
	 * Same as set() but triggers no change tracking or hooks
	 *
	 * If trackChanges is false, then this is no different than set().
	 * If trackChanges is true, then the value will be set but not recorded in the changes list.
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return this
	 *
	 */
	public function setQuietly($key, $value) {
		$track = $this->trackChanges; 
		if($track) $this->setTrackChanges(false);
		$this->set($key, $value);
		if($track) $this->setTrackChanges(true);
		return $this;
	}

	/**
	 * Is $value1 equal to $value2?
	 *
	 * This template method provided so that descending classes can optionally determine 
 	 * whether a change should be tracked. 
	 *
	 * @param string $key Name of the key that triggered the check (see WireData::set)
	 * @param mixed $value1
	 * @param mixed $value2
	 * @return bool
	 *
	 */
	protected function isEqual($key, $value1, $value2) {
		return $value1 === $value2; 	
	}

	/**
	 * Set an array of key=value pairs
	 *
	 * @param array $data
	 * @return this
	 * @see set()
	 *
	 */
	public function setArray(array $data) {
		foreach($data as $key => $value) $this->set($key, $value); 
		return $this; 
	}

	/**
	 * Provides direct reference access to set values in the $data array
	 *
	 */
	public function __set($key, $value) {
		$this->set($key, $value); 
	}

	/**
	 * Provides direct reference access to retrieve values in the $data array
	 *
	 * If the given $key is an object, it will cast it to a string. 
	 * If the given key is a string with "|" pipe characters in it, it will try all till it finds a value. 
	 *
 	 * @param string|object $key
	 * @return mixed|null Returns null if the key was not found. 
	 *
	 */
	public function get($key) {
		if(is_object($key)) $key = "$key";
		if(array_key_exists($key, $this->data)) return $this->data[$key]; 
		if(strpos($key, '|')) {
			$keys = explode('|', $key); 
			foreach($keys as $k) if($value = $this->get($k)) return $value; 
		}
		return parent::__get($key); // back to Wire
	}

	/**
	 * Returns the full $data array
	 *
	 */
	public function getArray() {
		return $this->data; 
	}

	/**
	 * Provides direct reference access to variables in the $data array
	 *
	 * Otherwise the same as get()
	 *
	 * @param string $key
	 *
	 */
	public function __get($key) {
		return $this->get($key); 
	}

	/**
	 * Remove a given $key from the $data array
	 *
	 * @param string $key
	 * @return this
	 *
	 */
	public function remove($key) {
		unset($this->data[$key]); 
		$this->trackChange("unset:$key"); 
		return $this;
	}

	/**
	 * Make the $data array iterable through this object, per the IteratorAggregate interface
	 *
	 */
	public function getIterator() {
		return new ArrayObject($this->data); 
	}

	/**
	 * Does this WireData have the given property in it's $data?
	 *
	 * @param string $key	
	 * @return bool
	 *
	 */
	public function has($key) {
		return ($this->get($key) !== null); 
	}

	/**
	 * Ensures that isset() and empty() work for this classes properties. 
	 *
	 */
	public function __isset($key) {
		return isset($this->data[$key]);
	}

	/**
	 * Ensures that unset() works for this classes data. 
	 *
	 */
	public function __unset($key) {
		$this->remove($key); 
	}

}

