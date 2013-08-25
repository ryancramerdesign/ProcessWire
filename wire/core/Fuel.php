<?php

/**
 * ProcessWire Fuel
 *
 * Fuel maintains a single instance each of multiple objects used throughout the application.
 * The objects contained in fuel provide access to the ProcessWire API. For instance, $pages,
 * $users, $fields, and so on. The fuel is required to keep the system running, so to speak.
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2013 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://processwire.com
 *
 */
class Fuel implements IteratorAggregate {

	protected $data = array();
	protected $lock = array();

	/**
	 * @param string $key API variable name to set - should be valid PHP variable name.
	 * @param object|mixed $value Value for the API variable.
	 * @param bool $lock Whether to prevent this API variable from being overwritten in the future.
	 * @return $this
	 * @throws WireException When you try to set a previously locked API variable, a WireException will be thrown.
	 * 
	 */
	public function set($key, $value, $lock = false) {
		if(in_array($key, $this->lock) && $value !== $this->data[$key]) {
			throw new WireException("API variable '$name' is locked and may not be set again"); 
		}
		$this->data[$key] = $value; 
		if($lock) $this->lock[] = $key;
		return $this;
	}

	/**
	 * Remove an API variable from the Fuel
	 * 
	 * @param $key
	 * @return bool Returns true on success
	 * 
	 */
	public function remove($key) {
		if(isset($this->data[$key])) {
			unset($this->data[$key]);
			return true;
		}
		return false;
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
