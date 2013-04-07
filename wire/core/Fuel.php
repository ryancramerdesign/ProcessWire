<?php

/**
 * ProcessWire Fuel
 *
 * Fuel maintains a single instance each of multiple objects used throughout the application.
 * The objects contained in fuel provide access to the ProcessWire API. For instance, $pages,
 * $users, $fields, and so on. The fuel is required to keep the system running, so to speak.
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2010 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://www.processwire.com
 * http://www.ryancramer.com
 */
class Fuel implements IteratorAggregate {

	protected $data = array();
	/**
	 * @var array holds the keys that are locked.
	 */
	protected $locked = array();

	/**
	 * @param string $key The key by which the set of data will be identified.
	 * @param mixed  $value Any value to set the key to.
	 * @param bool   $lock Whether or not to lock the value making it impossible for it to be overriden.
	 *
	 * @return bool
	 */
	public function set($key, $value, $lock=false) {
		if( ! in_array($key, $this->locked)) {
			$this->data[$key] = $value;
			return true;
		}
		return false;
	}

	/**
	 * Adds a key to the locked keys array.
	 *
	 * @param string $key
	 */
	public function lock($key) {
		array_push($this->locked, $key);
	}

	/**
	 * Removes a key from the locked keys array.
	 *
	 * @param string $key
	 */
	public function unlock($key) {
		$key_array = $key;
		if (! is_array($key)) {
			$key_array = array($key);
		}
		$this->locked = array_diff($this->locked, $key_array);
	}

	public function __get($key) {
		return isset($this->data[$key]) ? $this->data[$key] : null;
	}

	public function getIterator() {
		return new ArrayObject($this->data); 
	}

	public function getArray() {
		return $this->data; 
	}
}
