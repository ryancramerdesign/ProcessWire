<?php

/**
 * ProcessWire Roles
 *
 * Manages getting/saving roles with lookups to permissions.
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2010 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://www.processwire.com
 * http://www.ryancramer.com
 *
 */

/**
 * WireArray of Role instances
 *
 */
class RolesArray extends WireArray {

        public function isValidItem($item) {
                return $item instanceof Role;
        }       

        public function isValidKey($key) {
                return is_int($key) || ctype_digit($key);
        }
        
        public function getItemKey($item) {
                return $item->id; 
        }  

	public function makeBlankItem() {
		return new Role();
	}

	/**
	 * Do any of the Roles in this RolesArray contain the requested permission?
	 *
	 * Permission may be id, name, or Permission object
	 *
	 * @param string|int|Permission $key
	 * @param HasRoles $context Optional object that must contain the same Role for permission to be granted
	 * @return bool
	 *
	 */
	public function hasPermission($key, HasRoles $context = null) {
		$has = false; 
		if(!is_null($context) && $context->hasRole(Role::superRoleID)) return true; 
		foreach($this as $role) {
			if(!is_null($context) && !$context->hasRole($role)) continue; 
			if($role->hasPermission($key)) {
				$has = true;
				break;
			}	
		}
		return $has; 
	}

	/**
 	 * Get all combined permissions in this RolesArray
	 *
	 * @param HasRoles $context Optional object that must contain the same Role for permission to be included
	 * @return PermissionsArray
	 *
	 */
	public function getPermissions(HasRoles $context = null) {
		$permissions = new PermissionsArray();
		foreach($this as $role) {
			if(!is_null($context) && !$context->roles()->has($role)) continue; 
			$permissions->import($role); 
		}
		return $permissions; 
	}
	
}

/**
 * Manages getting/saving roles with lookups to permissions
 *
 */
class Roles extends WireSaveableItemsLookup {

	/**
	 * Instance of RolesArray
	 *
	 */
	protected $rolesArray;

	/**
	 * Construct the Roles, instantiating the RolesArray and loading all roles from DB
	 *
	 */
	public function __construct() {
		$this->rolesArray = new RolesArray();
		$this->load($this->rolesArray);
	}

	/**
	 * Per WireSaveableItems interface, return all Roles
	 *
	 */
	public function getAll() {
		return $this->rolesArray;
	}

	/**
	 * Per WireSaveableItems interface, return a blank Role
	 *
	 */
	public function makeBlankItem() {
		return new Role(); 
	}

	/**
	 * Per WireSaveableItems interface, return the table where Roles are stored
	 *
	 */
	public function getTable() {
		return 'roles';
	}

	/**
	 * Per WireSaveableItemsLookup interface, return the table where roles are joined to permissions
	 *
	 */
	public function getLookupTable() {
		return 'roles_permissions';
	}

	/**
	 * Per WireSaveableItemsLookup interface, delete the given role but only if it's not a permament one
	 *
	 */
	public function ___delete(Saveable $item) {
		if(!$item instanceof Role) throw new WireException("Roles::delete requires Role instance"); 
		if($item->isPermanent()) throw new WireException("Role {$item->name} is required by the system and may not be deleted"); 
		$this->fuel('pagesRoles')->deletePagesFromRole($item); 
		$this->fuel('db')->query("DELETE from users_roles WHERE roles_id=" . (int) $item->id); 
		return parent::___delete($item); 
	}

}

