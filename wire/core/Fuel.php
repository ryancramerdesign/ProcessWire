<?php

/**
 * ProcessWire Fuel
 *
 * Fuel maintains a single instance each of multiple objects used throughout the application.
 * The objects contained in fuel provide access to the ProcessWire API. For instance, $pages,
 * $users, $fields, and so on. The fuel is required to keep the system running, so to speak.
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2015 by Ryan Cramer 
 * This file licensed under Mozilla Public License v2.0 http://mozilla.org/MPL/2.0/
 * 
 * https://processwire.com
 * 
 * @property ProcessWire $wire
 * @property Database $db
 * @property WireDatabasePDO $database
 * @property Session $session
 * @property Notices $notices
 * @property Sanitizer $sanitizer
 * @property Fields $fields
 * @property Fieldtypes $fieldtypes
 * @property Fieldgroups $fieldgroups
 * @property Templates $templates
 * @property Pages $pages
 * @property Page $page
 * @property Process $process
 * @property Modules $modules
 * @property Permissions $permissions
 * @property Roles $roles
 * @property Users $users
 * @property User $user
 * @property WireCache $cache
 * @property WireInput $input
 * @property Languages $languages If LanguageSupport installed
 * @property Config $config
 * @property Fuel $fuel
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
		if(isset($this->lock[$key]) && $value !== $this->data[$key]) {
			throw new WireException("API variable '$key' is locked and may not be set again"); 
		}
		$this->data[$key] = $value; 
		if($lock) $this->lock[$key] = true;
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
			unset($this->lock[$key]);
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
