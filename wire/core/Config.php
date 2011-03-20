<?php

/**
 * ProcessWire Config
 *
 * Handles ProcessWire configuration data
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2010 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://www.processwire.com
 * http://www.ryancramer.com
 *
 */
class Config extends WireData { 

	/**
	 * List of config keys that are also exported in javascript
	 *
	 */
	protected $jsFields = array();

	/**
	 * Set a config field that is shared in Javascript, OR retrieve one or all params already set
	 *
	 * Specify only a $key and omit the $value in order to retrieve an existing set value.
	 * Specify no params to retrieve in array of all existing set values.
	 *
	 * @param string $key 
	 * @param mixed $value
 	 *
	 */
	public function js($key = null, $value = null) {

		if(is_null($key)) {
			$data = array();
			foreach($this->jsFields as $field) {
				$data[$field] = $this->get($field); 
			}
			return $data; 

		} else if(is_null($value)) {
			return in_array($key, $this->jsFields) ? $this->get($key) : null;
		}

		$this->jsFields[] = $key; 
		return parent::set($key, $value); 
	}
}

