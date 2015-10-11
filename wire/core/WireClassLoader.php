<?php namespace ProcessWire;

/**
 * ProcessWire class autoloader
 * 
 * This autoloader expects the PROCESSWIRE_CORE_PATH to be defined.
 *
 * ProcessWire 2.x
 * Copyright (C) 2015 by Ryan Cramer
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 *
 * https://processwire.com
 *
 */

class WireClassLoader extends Wire {
	
	protected $modules = null;
	protected $namespaces = array();
	
	public function __construct() {
		spl_autoload_register(array($this, 'loadClass'));
	}
	
	public function addNamespace($namespace, $path) {
		if(!isset($this->namespaces[$namespace])) $this->namespaces[$namespace] = array();
		$this->namespaces[$namespace][] = '/' . trim($path, '/') . '/';
	}
	
	public function loadClass($className) {

		if(is_null($this->modules)) $this->modules = $this->wire('modules');
		
		$found = false;
		$parts = explode("\\", $className);
		$_parts = array();
		$name = array_pop($parts);
		$namespace = implode("\\", $parts);
		$_namespace = $namespace; // original and unmodified namespace
	
		while($namespace && !isset($this->namespaces[$namespace])) {
			$_parts[] = array_pop($parts);
			$namespace = implode("\\", $parts);
		}
		
		if($namespace) {
			$paths = $this->namespaces[$namespace];
			$dir = count($_parts) ? implode("/", $_parts) . '/' : '';
			$file = $dir . $name . ".php";
			foreach($paths as $path) {
				if(is_file($path . $file)) {
					$found = $path . $file;
					break;
				}
			}
		}
	
		if(!$found && $this->modules) {
			if($this->modules->isModule($name)) {
				$this->modules->includeModule($className);
			} else if($_namespace) { 
				$path = $this->modules->getNamespacePath($_namespace);
				if($path) {
					// if namespace is for a known module, see if we can find a file in that module's directory
					// with the same name as the request class
					// @todo psr-4 support for these
					$file = "$path$name.php";
					if(is_file($file)) $found = $file;
				}
			}
		}
		
		if($found) include_once($found);
	}
}


