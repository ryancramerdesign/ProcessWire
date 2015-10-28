<?php namespace ProcessWire;

/**
 * ProcessWire class autoloader
 * 
 * Similar to a PSR-4 autoloader but with knowledge of modules. 
 * 
 * This file is licensed under the MIT license
 * https://processwire.com/about/license/mit/
 * 
 * ProcessWire 3.x (development), Copyright 2015 by Ryan Cramer
 * https://processwire.com
 *
 */

class WireClassLoader {

	/**
	 * @var Modules|null
	 * 
	 */
	protected $modules = null;

	/**
	 * @var null|ProcessWire
	 * 
	 */
	protected $wire = null;

	/**
	 * Extensions allowed for autoload files
	 * 
	 * @var array
	 * 
	 */
	protected $extensions = array(
		'.php',
	);

	/**
	 * @var array
	 * 
	 */
	static protected $namespaces = array();

	/**
	 * @param ProcessWire $wire
	 * 
	 */
	public function __construct($wire = null) {
		if($wire) $this->wire = $wire;
		spl_autoload_register(array($this, 'loadClass'));
	}

	/**
	 * Add a recognized file extension for PHP files
	 * 
	 * Note: ".php" is already assumed, so does not need to be added.
	 * 
	 * @param string $ext
	 * 
	 */
	public function addExtension($ext) {
		if(strpos($ext, '.') !== 0) $ext = ".$ext";
		if(!in_array($ext, $this->extensions)) $this->extensions[] = $ext;
	}

	/**
	 * Add a namespace to point to a path root
	 * 
	 * Multiple root paths may be specified for a single namespace by calling this method more than once.
	 * 
	 * @param string $namespace
	 * @param string $path Full system path
	 * 
	 */
	public function addNamespace($namespace, $path) {
		if(!isset(self::$namespaces[$namespace])) self::$namespaces[$namespace] = array();
		$path = '/' . trim($path, '/') . '/';
		if(!in_array($path, self::$namespaces[$namespace])) self::$namespaces[$namespace][] = $path;
	}

	/**
	 * Load the file for the given class
	 * 
	 * @param string $className
	 * 
	 */
	public function loadClass($className) {
		
		static $level = 0;
		$level++;

		if(is_null($this->modules)) {
			if($this->wire) $this->modules = $this->wire->wire('modules');
		}
		
		$found = false;
		$parts = explode("\\", $className);
		$_parts = array();
		$name = array_pop($parts);
		$namespace = implode("\\", $parts);
		$_namespace = $namespace; // original and unmodified namespace
	
		if($this->modules && $this->modules->isModule($className)) {
			if($this->modules->includeModule($name)) {
				// success, and Modules class just included it
				$level--;
				return;
			}
		}
		
		while($namespace && !isset(self::$namespaces[$namespace])) {
			$_parts[] = array_pop($parts);
			$namespace = implode("\\", $parts);
		}

		if($namespace) {
			$paths = self::$namespaces[$namespace];
			$dir = count($_parts) ? implode("/", $_parts) . '/' : '';
			foreach($this->extensions as $ext) {
				foreach($paths as $path) {
					$file = "$path$dir$name$ext";
					if(is_file($file)) {
						$found = $file;
						break;
					}
				}
				if($found) break;
			}
		}
	
		if(!$found && $this->modules && $_namespace) {
			$path = $this->modules->getNamespacePath($_namespace);
			if($path) {
				// if namespace is for a known module, see if we can find a file in that module's directory
				// with the same name as the request class
				// @todo psr-4 support for these
				foreach($this->extensions as $est) {
					$file = "$path$name$ext";
					if(is_file($file)) {
						$found = $file;
						break;
					}
				}
			}
		}
		
		if($found) {
			include_once($found);
		}
		
		$level--;
	}
}

