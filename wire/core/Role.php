<?php

/**
 * ProcessWire Role
 *
 * A Role is a container for Permissions, and intended to be assigned to Users.
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2010 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://www.processwire.com
 * http://www.ryancramer.com
 *
 */

class Role extends WireArray implements Saveable, HasLookupItems {

	/**
	 * Permanent database ID of the Guest Role
	 *
	 */
	const guestRoleID = 1; 

	/**
	 * Permanent database ID of the Superuser Role
	 *
	 */
	const superRoleID = 2; 

	/**
	 * Permanent database ID of the Owner Role
 	 *
	 */
	const ownerRoleID = 3; 

	/**
	 * Fields as they appear in the roles table
	 *
	 */
	protected $settings = array(
		'id' => 0, 
		'name' => '', 
		);

	/**
	 * Per WireArray interface, only Permission instances may be added to a Role
	 *
	 */
	public function isValidItem($item) {
		return $item instanceof Permission; 
	}

	/**
	 * Per WireArray interface, return a blank permission
	 *
	 */
	public function makeBlankItem() {
		return new Permission();
	}


	/**
	 * Per WireArray interface, numeric keys are used
	 *
	 */
	public function isValidKey($key) {
		return is_int($key) || ctype_digit("$key"); 
	}

	/**
	 * Per WireArray interface, the Permissions are indexed by their database ID
	 *
	 */
	public function getItemKey($item) {
		return $item->id; 
	}

	/**
	 * Per Saveable interface, return Role data as saved in the Roles table
	 *
	 */
	public function getTableData() {
		return $this->settings; 
	}

	/**
	 * Per HasLookupItems interface, return Permissions that are joined to a Role 
	 *	
 	 */ 
	public function getLookupItems() {
		return $this; 
	}

	/**
	 * Per HasLookupItems interface, add a Permission to this Role
	 *	
 	 */ 
	public function addLookupItem($item) {
		if($item) $this->add($item); 
		return $this; 
	}

	/**
	 * Get a setting or custom property of the Role
	 *
	 * @param string $key
	 * @return mixed
	 *
	 */
	public function get($key) {
		if(isset($this->settings[$key])) return $this->settings[$key]; 
		return parent::get($key);
	}

	/**
	 * Set a setting or custom property for this Role
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return this
	 *
	 */
	public function set($key, $value) {
		if($key === 'id') $this->settings['id'] = (int) $value; 
			else if($key === 'name') $this->settings['name'] = $this->fuel('sanitizer')->name($value); 
			else return parent::set($key, $value); 
		return $this; 
	}

	/**
	 * Add a Permission to this Role
	 *
	 * Permission may be specified by name, id or Permission instance
	 *
	 * @param string|id|Permission $item
	 * @return this
	 *
	 */
	public function add($item) {
		if(!is_object($item)) $item = $this->getFuel('permissions')->get($item); 
		return parent::add($item); 
	}

	/**
	 * Remove a Permission from this Role
	 *
	 * Permission may be specified by name, id or Permission instance
	 *
	 * @param string|id|Permission $item
	 * @return this
	 *
	 */
	public function remove($item) {
		if(!is_object($item)) $item = $this->getFuel('permissions')->get($item); 
		return parent::remove($item); 
	}

	/**
	 * Add a Permission to this Role, alias of add()
	 * 
	 * @see add()
	 *
	 */
	public function addPermission($item) {
		return $this->add($item); 
	}

	/**
	 * Remove a Permission from this Role, alias of remove()
	 * 
	 * @see remove()
	 *
	 */
	public function removePermission($item) {
		return $this->remove($item); 
	}

	/**
	 * Save this Role to DB, alias of Roles::save()
	 *
	 * @return this
	 *
	 */ 
	public function save() {
		$this->getFuel('roles')->save($this); 
		return $this;
	}

	/**
	 * Does this Role have the requested Permission?
	 *
	 * Permission may be id, name, or Permission object
	 *
	 * @param string|int|Permission $key
	 * @return bool
	 *
	 */
	public function hasPermission($key) {
		if($this->id === self::superRoleID) return true; 
		$has = false;
		if(is_object($key)) $key = "$key";
		foreach($this as $permission) {
			if($key === $permission->id || $key === $permission->name) {
				$has = true; 
				break;
			}
		}
		return $has; 
	}

	public function getPermissions() {
		return $this->getLookupItems();
	}

	/**
	 * Is this a permanent/system role?
	 *
	 * @return bool
 	 *
	 */
	public function isPermanent() {
		return in_array($this->id, array(self::guestRoleID, self::superRoleID, self::ownerRoleID)); 
	}

	/**
	 * The string value of a Role is it's name
	 *
	 */
	public function __toString() {
		return $this->name; 
	}


}
