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
	 * Array of component names to filenames
	 *
	 * @var array
	 *
	 */
	protected $components = array();

	/**
	 * Components that have been requested
	 *
	 * @var array
	 *
	 */
	protected $requested = array();

	/**
	 * True after module has been init'd, required by add()
	 *
	 * @var bool
	 *
	 */
	protected $initialized = false;

	/**
	 * Whether to automatically load CSS files with the same name as this module
	 * 
	 * @var bool
	 * 
	 */
	protected $loadStyles = true;
	
	/**
	 * Whether to automatically load JS files with the same name as this module
	 *
	 * @var bool
	 *
	 */
	protected $loadScripts = true; 
	
	/**
	 * Add an optional component that can be used with this module
	 *
	 * @param string $name
	 * @param string $file
	 * @return this
	 *
	 */
	public function addComponent($name, $file) {
		$this->components[$name] = $file;
		return $this;
	}

	/**
	 * Add an array of optional components
	 *
	 * @param array $components
	 * @return this
	 *
	 */
	public function addComponents(array $components) {
		$this->components = array_merge($this->components, $components);
		return $this;
	}

	/**
	 * Per the Module interface, Initialize the Process, loading any related CSS or JS files
	 *
	 */
	public function init() {
		
		$class = $this->className();
		$info = $this->wire('modules')->getModuleInfo($this, array('verbose' => false));
		$version = (int) isset($info['version']) ? $info['version'] : 0;
		
		if($this->loadStyles && is_file($this->config->paths->$class . "$class.css")) {
			$this->config->styles->add($this->config->urls->$class . "$class.css?v=$version");
		}
		if($this->loadScripts && is_file($this->config->paths->$class . "$class.js")) {
			if(!$this->wire('config')->debug && is_file($this->config->paths->$class . "$class.min.js")) {
				$this->config->scripts->add($this->config->urls->$class . "$class.min.js?v=$version");
			} else {
				$this->config->scripts->add($this->config->urls->$class . "$class.js?v=$version");
			}
		}

		if(count($this->requested)) {
			foreach($this->requested as $name) {
				$url = $this->components[$name]; 
				if(strpos($url, '/') === false) $url = $this->wire('config')->urls->$class . $url;
				$url .= "?v=$version";
				$this->wire('config')->scripts->add($url);
			}
			$this->requested = array();
		}

		$this->initialized = true;
	}

	/**
	 * Use an extra named component
	 *
	 * @param $name
	 * @return this
	 *
	 */
	public function ___use($name) {

		$name = $this->wire('sanitizer')->name($name);
		$class = $this->className();

		if(!isset($this->components[$name])) {
			$this->error("Unrecognized $class component requested: $name");
			return $this;
		}

		if($this->initialized) {
			$url = $this->components[$name];
			if(strpos($url, '/') === false) $url = $this->wire('config')->urls->$class . $url;
			$this->wire('config')->scripts->add($url);
		} else {
			$this->requested[$name] = $name;
		}

		return $this;
	}

	public function ___install() { }
	public function ___uninstall() { }
	public function isSingular() { return true; }	
	public function isAutoload() { return false; }
}

