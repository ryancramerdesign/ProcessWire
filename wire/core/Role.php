<?php

/**
 * ProcessWire Role Page
 *
 * A type of Page used for storing an individual Role
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2011 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://www.processwire.com
 * http://www.ryancramer.com
 *
 * @property PageArray $permissions PageArray of permissions assigned to Role.
 *
 */

class Role extends Page { 

	/**
	 * Create a new Role page in memory. 
	 *
	 */
	public function __construct(Template $tpl = null) {
		if(is_null($tpl)) $tpl = $this->getPredefinedTemplate();
		$this->parent = $this->getPredefinedParent();
		parent::__construct($tpl); 
	}

	/**
	 * Get predefined template (template method)
	 * 
	 * @return Template
	 *
	 */
	protected function getPredefinedTemplate() {
		return $this->wire('templates')->get('role'); 
	}

	/**
	 * Get predefined parent page (template method)
	 * 
	 * @return Page
	 *
	 */
	protected function getPredefinedParent() {
		return $this->wire('pages')->get($this->wire('config')->rolesPageID); 
	}

	/**
	 * Does this role have the given permission name or object?
	 *
	 * @param string|Permission
	 * @return bool
	 *
	 */
	public function hasPermission($name) {

		if($name instanceof Page) {
			$permission = $name; 

		} else if(ctype_digit("$name")) { 
			$permission = $this->fuel('permissions')->get("id=$name"); 

		} else {
			$permission = $this->fuel('permissions')->get("name=$name"); 
		}

		return $this->permissions->has($permission); 
	}

	/**
	 * Add the given permission string, id or object
	 *
	 * This is the same as $role->permissions->add($permission) except this one will accept ID or name.
	 *
	 * @param string|int|Permission
	 * @return bool false if permission not recognized, true otherwise
	 *
	 */
	public function addPermission($permission) {
		if(is_string($permission) || is_int($permission)) $permission = $this->fuel('permissions')->get($permission); 
		if(is_object($permission) && $permission instanceof Permission) {
			$this->permissions->add($permission); 
			return true; 
		}
		return false;
	}

	/**
	 * Remove the given permission string, id or object
	 *
	 * This is the same as $role->permissions->remove($permission) except this one will accept ID or name.
	 *
	 * @param string|int|Permission
	 * @return bool false if permission not recognized, true otherwise
	 *
	 */
	public function removePermission($permission) {
		if(is_string($permission) || is_int($permission)) $permission = $this->fuel('permissions')->get($permission); 
		if(is_object($permission) && $permission instanceof Permission) {
			$this->permissions->remove($permission); 
			return true; 
		}
		return false;
	}

}

