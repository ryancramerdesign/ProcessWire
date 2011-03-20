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
 *
 * @TODO add capability to lock fuel items from being overwritten. 
 *
 */
class Fuel implements IteratorAggregate {

	protected $data = array();

	public function set($key, $value) {
		$this->data[$key] = $value; 
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
