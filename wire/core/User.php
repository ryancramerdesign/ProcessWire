<?php

/**
 * ProcessWire User
 *
 * A ProcessWire User with it's data and array of roles.
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2010 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://www.processwire.com
 * http://www.ryancramer.com
 *
 */

class User extends WireData implements Saveable, HasRoles, HasLookupItems {

	/**
	 * Predefined database ID of the unauthenticated Guest user
	 *
	 */
	const guestUserID = 1; 

	/**
	 * Predefined database ID of a permanent Admin user (doesn't matter which one)
	 *
	 */
	const superUserID = 2; 

	/**
	 * Instance of RolesArray, roles this user belongs to
	 *
	 */
	protected $rolesArray; 

	/**
	 * Native database settings
	 *
	 */
	protected $settings = array(
		'id' => 0, 
		'name' => '', 
		'pass' => '', 
		'salt' => '', 
		);

	public function __construct() {
		$this->rolesArray = new RolesArray();
		$this->rolesArray->add($this->fuel('roles')->get(Role::guestRoleID)); 
	}

	/**
	 * Per HasLookupItems interface
	 *
	 */
	public function addLookupItem($item) {
		if($item) $this->add($this->fuel('roles')->get($item)); 
		return $this; 
	}

	/**
	 * Per HasLookupItems interface
	 *
	 */
	public function getLookupItems() {
		return $this->rolesArray; 
	}

	/**
	 * Set a user setting or data value
	 *
	 */
	public function set($key, $value) {
		if($key == 'id') $value = (int) $value; 
			else if($key == 'name') $value = $this->fuel('sanitizer')->username($value); 

		if(isset($this->settings[$key])) {
			if($this->settings[$key] != $value) $this->trackChange($key); 
			$this->settings[$key] = $value; 
		} else {
			parent::set($key, $value); 
		}
		return $this; 
	}

	/**
	 * Get a user setting or data value
	 *
	 */
	public function get($key) {
		if($key == 'roles') return $this->rolesArray; 
		if(isset($this->settings[$key])) return $this->settings[$key]; 
		return parent::get($key); 
	}

	/**
	 * Get all Roles contained by this User
	 *
	 */
	public function getAll() {
		return $this->rolesArray; 
	}

	/**
	 * Per Saveable interface
	 *
	 */
	public function save() {
		$this->getFuel('users')->save($this); 
		return $this;
	}

	/**
	 * Per Saveable interface
	 *
	 */
	public function getTableData() {
		return $this->settings; 
	}

	/**
	 * Add a Role to this user
	 *
	 */
	public function add($role) {
		if(!is_object($role)) $role = $this->fuel('roles')->get($role); 
		$this->rolesArray->add($role); 
		return $this;
	}

	/**
	 * Remove a Role from this user
	 *
	 */
	public function remove($role) {
		if(!is_object($role)) $role = $this->fuel('roles')->get($role); 
		$this->rolesArray->remove($role); 
		return $this;
	}

	/**
	 * Add a Role to this user, same as add()
	 *
	 */
	public function addRole($role) {
		return $this->add($role); 
	}

	/**
	 * Remove a Role from this user, same as remove()
	 *
	 */
	public function removeRole($role) {
		return $this->remove($role); 
	}

	/**
	 * Does this user have the given Role?
	 *
	 */
	public function hasRole($key) {
		return $this->rolesArray->has($key); 
	}

	/**
	 * Fulfills HasRoles interface
	 *
	 */
	public function roles() {
		return $this->rolesArray; 
	}

	/**
	 * Do any of the Roles in this User contain the requested permission?
	 *
	 * Permission may be id, name, or Permission object
	 *
	 * @param string|int|Permission $key
	 * @param HasRoles $context Optional Page context to include with the permission check
	 * @return bool
	 *
	 */
	public function hasPermission($key, HasRoles $context = null) {
		if($this->isSuperuser()) return true; 
		return $this->rolesArray->hasPermission($key, $context); 
	}

	/**
	 * Get all Permissions available to this user, with optional context
	 *
	 * @param HasRoles $context Optional Page context to include with the permission check
	 * @return PermissionsArray
	 *
	 */
	public function getPermissions(HasRoles $context = null) {
		return $this->rolesArray->getPermissions($context); 
		
	}

	/**
	 * Is this a logged-in user? 
	 *
	 * If true, then the user logged in at some point and is not a guest. If false, then it's the guest user.
	 *
	 * @return bool
	 *
	 */
	public function isLoggedin() {
		return $this->settings['id'] > 1; 
	}

	/**
	 * Is this a permanent (non-deleteable) user?
	 *
	 * Guest and default Admin users are permanent (User ID's 1 and 2)
	 *
	 * @return bool
	 *
	 */
	public function isPermanent() {
		return in_array($this->settings['id'], array(self::guestUserID, self::superUserID)); 
	}

	/**
	 * Does this user have access to everything? (i.e. no permission checks necessary) 
	 *
	 * @return bool
	 *
	 */
	public function isSuperuser() {
		return $this->hasRole(Role::superRoleID); 
	}

	/**
	 * Is this an anonymous/guest user (not logged in)?
	 *
	 * Note that this is the same as the isLoggedin method, but the syntax makes more sense in many instances.
	 *
	 */
	public function isGuest() {
		return $this->settings['id'] == self::guestUserID; 
	}

	/**
	 * The string value of a User is the User's name
	 *
	 */
	public function __toString() {
		return $this->settings['name']; 
	}

}

/**
 * NullUser is used as a placeholder when an original user reference is gone (deleted)
 *
 */
class NullUser extends User {
	public function __construct() {
		$this->rolesArray = new RolesArray();
		$this->settings['name'] = 'unknown';
	}
	public function hasPermission($key, HasRoles $context = null) { return false; }
	public function isLoggedin() { return false; }
	public function isPermanent() { return false; }
	public function isSuperuser() { return false; }
	public function isGuest() { return false; }
	public function __toString() { return "unknown"; }
	public function set($key, $value) { return $this;  }
	public function addLookupItem($item) { return $this; }
	public function add($role) { return $this; }
}

