<?php

/**
 * ProcessWire Permissions
 *
 * Container for Permission instances. Manages Permission instances, getting and saving to DB.
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
 * WireArray of Permission instances
 *
 */
class PermissionsArray extends WireArray {

        public function isValidItem($item) {
                return $item instanceof Permission;
        }       

        public function isValidKey($key) {
                return is_int($key) || ctype_digit($key);
        }
        
        public function getItemKey($item) {
                return $item->id; 
        }  

	public function makeBlankItem() {
		return new Permission();
	}
	
}

/**
 * Manages Permission instances, getting and saving to DB
 *
 */
class Permissions extends WireSaveableItems {

	/**
	 * Instance of PermissionsArray
	 *
	 */
	protected $permissionsArray;

	/**
	 * Construct Permissions: instantiate PermissionsArray and load Permissions from DB
	 *
	 */
	public function __construct() {
		$this->permissionsArray = new PermissionsArray();
		$this->load($this->permissionsArray);
	}

	/**
 	 * Get all Permission instances, per WireSaveableItems interface
	 *
	 */
	public function getAll() {
		return $this->permissionsArray;
	}

	/**
	 * Make a blank Permission, per WireSaveableItems interface
	 *
	 */
	public function makeBlankItem() {
		return new Permission(); 
	}

	/**
	 * Get the table where Permissions are stored, per WireSaveableItems interface
	 *
	 */
	public function getTable() {
		return 'permissions';
	}

	/**
	 * Delete the Permission, per WireSaveableItems interface
	 *
	 * WireSaveableItems (parent) handles deletion of the permission, and this deletes references to the permission in the lookup table
	 *
	 */
	public function ___delete(Saveable $item) {
		$id = (int) $item->id; 
		$this->getFuel('db')->query("DELETE FROM roles_permissions WHERE permissions_id='$id'"); 
		return parent::___delete($item);
	}

	/**
	 * Install a new Permission, optinally associated with a Module
	 *
	 * @param string $name
	 * @param Module $module Optional module to associate with Permission
	 *	
	 */
	public function ___addNew($name, Module $module = null) {
		$permission = $this->makeBlankItem();
		$permission->name = $name; 
		$permission->modules_id = is_null($module) ? 0 : $this->fuel('modules')->getModuleID($module); 
		return $this->save($permission); 
	}

}
