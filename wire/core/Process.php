<?php

/**
 * ProcessWire Process
 *
 * Process is the base Module class for each part of ProcessWire's web admin.
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2010 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://www.processwire.com
 * http://www.ryancramer.com
 *
 */

abstract class Process extends WireData implements Module {

	/**
	 * Per the Module interface, return an array of information about the Process
	 *
	 * The 'permission' property is specific to Process instances, and allows this Process to delegate it's permission
	 * to another Process. Meaning, if the other Process has permission, then it is assumed that this one does too. 
	 * This is only applicable to instances where you don't wnat to manage this Process's permission separately. 
	 *
 	 */
	public static function getModuleInfo() {
		return array(
			'title' => '',		// printable name/title of module
			'version' => 001, 	// version number of module
			'summary' => '', 	// 1 sentence summary of module
			'href' => '', 		// URL to more information (optional)
			'permanent' => true, 	// true if module is permanent and thus not uninstallable
			'permission' => '', 	// className of module that this module gets it's permission from (optional)
			); 
	}

	/**
	 * Per the Module interface, Initialize the Process, loading any related CSS or JS files
	 *
	 */
	public function init() { 
		$class = $this->className();
		if(is_file($this->config->paths->$class . "$class.css")) $this->config->styles->add($this->config->urls->$class . "$class.css"); 
		if(is_file($this->config->paths->$class . "$class.js")) $this->config->scripts->add($this->config->urls->$class . "$class.js"); 
	}

	/**
	 * Per the Module interface, Install the Process module
	 *
	 * By default a permission equal to the name of the class is installed, unless overridden with the 'permission' property in getModuleInfo().
	 *
	 */
	public function ___install() { 
		$permission = $this->className();
		$info = $this->getModuleInfo();
		if(!empty($info['permission'])) $permission = $info['permission']; 
		$this->installPermission($permission); 
	}

	/**
	 * Install a new permission for use by this Process
	 *
	 * @param string $permissionName Short permission name (without Process class)
	 *
	 */
	public function installPermission($permissionName) {
		if(strpos($permissionName, $this->className()) !== 0) $permissionName = $this->className . ucfirst($permissionName); 
		if(!$this->fuel('permissions')->get($permissionName)) $this->fuel('permissions')->addNew($permissionName, $this); 
	}

	/**
	 * Check if the current user has the requested permission, specific to this Process
	 *
	 * This is meant for checking Process-specific permissions as installed by Process::installPermission().
	 * It automatically prepends the Process className before the permission so that calling $this->hasPermission("delete") 
	 * would translate to $user->hasPermission("ProcessNameDelete"). As a result, don't use this method for checking any 
	 * permissions that weren't expressly installed by by the Process because they will always return false.
	 *
	 * @param string $permissionName Short permission name (without Process class) 
	 * @param HasRole $context Optional context for the permission check (Page object, for example)
	 * @return bool
	 *
	 */
	public function hasPermission($permissionName, HasRoles $context = null) {
		if(strpos($permissionName, $this->className()) !== 0) $permissionName = $this->className . ucfirst($permissionName); 
		return $this->fuel('user')->hasPermission($permissionName, $context); 
	}

	/**
	 * Uninstall this Process
	 *
	 * Note that the Modules class handles removal of any Permissions that the Process may have installed 
	 *
	 */
	public function ___uninstall() { }

	/**
	 * Execute this Process and return the output 
	 *
	 * @return string
	 *
	 */
	public function ___execute() { }

	/**
	 * Get a value stored in this Process
	 *
	 */
	public function get($key) {
		if(($value = $this->getFuel($key)) !== null) return $value; 
		return parent::get($key); 
	}

	/**
	 * Per the Module interface, Process modules only retain one instance in memory
	 *
	 */
	public function isSingular() {
		return true; 
	}

	/**
	 * Per the Module interface, Process modules are not loaded until requested from from the API
	 *
	 */
	public function isAutoload() {
		return false; 
	}

}
