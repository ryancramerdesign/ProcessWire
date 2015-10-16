<?php namespace ProcessWire;

/**
 * ProcessWire class autoloader
 * 
 * Similar to a PSR-4 autoloader but with knowledge of modules. 
 * 
 * ProcessWire 3.x (development), Copyright 2015 by Ryan Cramer
 * https://processwire.com
 *
 */

class WireClassLoader extends Wire {
	
	protected $modules = null;
	static protected $namespaces = array();
	
	public function __construct() {
		spl_autoload_register(array($this, 'loadClass'));
	}
	
	public function addNamespace($namespace, $path) {
		if(!isset(self::$namespaces[$namespace])) self::$namespaces[$namespace] = array();
		$path = '/' . trim($path, '/') . '/';
		if(!in_array($path, self::$namespaces[$namespace])) self::$namespaces[$namespace][] = $path;
	}
	
	public function loadClass($className) {

		if(is_null($this->modules)) $this->modules = $this->wire('modules');
		
		$found = false;
		$parts = explode("\\", $className);
		$_parts = array();
		$name = array_pop($parts);
		$namespace = implode("\\", $parts);
		$_namespace = $namespace; // original and unmodified namespace
	
		while($namespace && !isset(self::$namespaces[$namespace])) {
			$_parts[] = array_pop($parts);
			$namespace = implode("\\", $parts);
		}
		
		if($namespace) {
			$paths = self::$namespaces[$namespace];
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

