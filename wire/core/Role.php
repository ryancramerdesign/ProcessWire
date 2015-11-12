<?php

/**
 * ProcessWire Role Page
 *
 * A type of Page used for storing an individual Role
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2015 by Ryan Cramer 
 * This file licensed under Mozilla Public License v2.0 http://mozilla.org/MPL/2.0/
 * 
 * https://processwire.com
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
	 * Does this role have the given permission name, id or object?
	 *
	 * @param string|int|Permission
	 * @param Page|Template|null $context Optional Page or Template context
	 * @return bool
	 *
	 */
	public function hasPermission($name, $context = null) {
		
		$has = false; 
		$permission = null;
		
		if(empty($name)) {	
			// do nothing
			return $has;
		
		} else if($name instanceof Page) {
			$permission = $name;
			$has = $this->permissions->has($permission); 

		} else if(ctype_digit("$name")) {
			$name = (int) $name;
			foreach($this->permissions as $p) {
				if(((int) $p->id) === $name) {
					$permission = $p;
					$has = true;
					break;
				}
			}
			
		} else if($name == "page-add" || $name == "page-create") {
			// runtime permissions that don't have associated permission pages
			if(empty($context)) return false;
			$permission = new Permission();
			$permission->name = $name;

		} else if(is_string($name)) {
			if(!$this->wire('permissions')->has($name)) {
				if(!ctype_alnum(str_replace('-', '', $name))) $name = $this->wire('sanitizer')->pageName($name);
				$delegated = $this->wire('permissions')->getDelegatedPermissions();
				if(isset($delegated[$name])) $name = $delegated[$name];
			}
			foreach($this->permissions as $p) {
				if($p->name === $name) {
					$permission = $p;
					$has = true;
					break;
				}
			}
		}

		if($context !== null && ($context instanceof Page || $context instanceof Template)) {
			if(!$permission) $permission = $this->wire('permissions')->get($name);
			if($permission) {
				$has = $this->hasPermissionContext($has, $permission, $context);
			}
		}
		
		return $has; 
	}

	/**
	 * Return whether the role has the permission within the context of a Page or Template
	 * 
	 * @param bool $has Result from the hasPermission() method
	 * @param Permission $permission Permission to check
	 * @param Wire $context Must be a Template or Page
	 * @return bool
	 * 
	 */
	protected function hasPermissionContext($has, Permission $permission, Wire $context) {
		
		if(strpos($permission->name, "page-") !== 0) return $has;
		$type = str_replace('page-', '', $permission->name);
		if(!in_array($type, array('view', 'edit', 'add', 'create'))) $type = 'edit';
		
		$accessTemplate = $context instanceof Page ? $context->getAccessTemplate($type) : $context;
		if(!$accessTemplate) return false;
		if(!$accessTemplate->useRoles) return $has;
		
		if($permission->name == 'page-view') {
			if(!$has) return false;
			$has = $accessTemplate->hasRole($this);
			return $has;
		}
	
		if($permission->name == 'page-edit' && !$has) return false;
		
		switch($permission->name) {
			case 'page-edit':
				$has = in_array($this->id, $accessTemplate->editRoles);
				break;
			case 'page-create':
				$has = in_array($this->id, $accessTemplate->createRoles);
				break;
			case 'page-add':
				$has = in_array($this->id, $accessTemplate->addRoles);
				break;
			default:
				// some other page-* permission
				$rolesPermissions = $accessTemplate->rolesPermissions; 
				if(!isset($rolesPermissions["$this->id"])) return $has;
				foreach($rolesPermissions["$this->id"] as $permissionID) {
					$revoke = strpos($permissionID, '-') === 0;
					if($revoke) $permissionID = ltrim($permissionID, '-');
					$permissionID = (int) $permissionID;	
					if($permission->id != $permissionID) continue;
					if($has) {
						if($revoke) $has = false;
					} else {
						if(!$revoke) $has = true;
					}
					break;
				}
		}
		
		return $has;
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
		if(is_string($permission) || is_int($permission)) $permission = $this->wire('permissions')->get($permission); 
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
		if(is_string($permission) || is_int($permission)) $permission = $this->wire('permissions')->get($permission); 
		if(is_object($permission) && $permission instanceof Permission) {
			$this->permissions->remove($permission); 
			return true; 
		}
		return false;
	}

	/**
	 * Return the API variable used for managing pages of this type
	 *
	 * @return Pages|PagesType
	 *
	 */
	public function getPagesManager() {
		return $this->wire('roles');
	}

}

