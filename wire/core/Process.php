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
	 * The 'permission' property is specific to Process instances, and allows you to specify the name of a permission
	 * required to execute this process. 
	 *
 	 */
	public static function getModuleInfo() {
		return array(
			'title' => '',		// printable name/title of module
			'version' => 1, 	// version number of module
			'summary' => '', 	// one sentence summary of module
			'href' => '', 		// URL to more information (optional)
			'permanent' => true, 	// true if module is permanent and thus not uninstallable (3rd party modules should specify 'false')
			'permission' => '', 	// name of permission required to execute this Process (optional)
			); 
	}

	/**
	 * Per the Module interface, Initialize the Process, loading any related CSS or JS files
	 *
	 */
	public function init() { 
		$class = $this->className();
		$info = $this->getModuleInfo(); 
		$version = (int) $info['version'];
		if(is_file($this->config->paths->$class . "$class.css")) $this->config->styles->add($this->config->urls->$class . "$class.css?v=$version"); 
		if(is_file($this->config->paths->$class . "$class.js")) $this->config->scripts->add($this->config->urls->$class . "$class.js?v=$version"); 
	}

	/**
	 * Per the Module interface, Install the Process module
	 *
	 * By default a permission equal to the name of the class is installed, unless overridden with the 'permission' property in getModuleInfo().
	 *
	 */
	public function ___install() { }

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
