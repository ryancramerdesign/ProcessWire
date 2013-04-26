<?php

/**
 * ProcessWire ModuleJS
 *
 * An abstract module intended as a base for modules needing to autoload JS or CSS files. 
 *
 * If you extend this, double check that the default isSingular() and isAutoload() methods 
 * are doing what you want -- you may want to override them. 
 * 
 * See the Module interface (Module.php) for details about each method. 
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2010 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://www.processwire.com
 * http://www.ryancramer.com
 *
 */

abstract class ModuleJS extends WireData implements Module {

	/**
	 * Per the Module interface, return an array of information about the Module
	 *
 	 */
	public static function getModuleInfo() {
		return array(
			'title' => '',		// printable name/title of module
			'version' => 1, 	// version number of module
			'summary' => '', 	// 1 sentence summary of module
			'href' => '', 		// URL to more information (optional)
			'permanent' => false, 	// true if module is permanent and thus not uninstallable
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

	public function ___install() { }
	public function ___uninstall() { }
	public function isSingular() { return true; }	
	public function isAutoload() { return false; }
}

