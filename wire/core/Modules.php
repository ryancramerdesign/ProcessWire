<?php

/**
 * ProcessWire Modules
 *
 * Loads and manages all runtime modules for ProcessWire
 *
 * Note that when iterating, find(), or calling any other method that returns module(s), excepting get(), a ModulePlaceholder may be
 * returned rather than a real Module. ModulePlaceholders are used in instances when the module may or may not be needed at runtime
 * in order to save resources. As a result, anything iterating through these Modules should check to make sure it's not a ModulePlaceholder
 * before using it. If it's a ModulePlaceholder, then the real Module can be instantiated/retrieved by $modules->get($className).
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2013 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://processwire.com
 *
 */

class Modules extends WireArray {
	
	/**
	 * Flag indicating the module may have only one instance at runtime. 
	 *
	 */
	const flagsSingular = 1; 

	/**
	 * Flag indicating that the module should be instantiated at runtime, rather than when called upon. 
	 *
	 */
	const flagsAutoload = 2;

	/**
	 * Filename for module info cache file
	 *
	 */
	const moduleInfoCacheName = 'Modules.info';

	/**
	 * Array of modules that are not currently installed, indexed by className => filename
	 *
	 */
	protected $installable = array(); 

	/**
	 * An array of module database IDs indexed by: class => id 
	 *
	 * Used internally for database operations
	 *
	 */
	protected $moduleIDs = array();

	/**
	 * Path where system modules are stored
	 *
	 */
	protected $modulePath = '';

	/**
	 * Path where site-specific modules are stored
	 *
	 */
	protected $modulePath2  = '';

	/**
	 * Cached module configuration data
	 *
	 */
	protected $configData = array();

	/**
	 * Have the modules been init'd() ?
	 *
	 */
	protected $initialized = false;

	/**
	 * Whether or not module debug mode is active
	 *
	 */
	protected $debug = false; 

	/**
	 * Becomes an array if debug mode is on
	 *
	 */
	protected $debugLog = array();

	/**
	 * Modules that specify an anonymous function returning true or false on whether they should be autoloaded
	 *
	 */
	protected $conditionalAutoloadModules = array();

	/**
	 * Cache of module information
	 *
	 */
	protected $moduleInfoCache = array();

	/**
	 * Construct the Modules
	 *
	 * @param string $path Path to modules
	 * @param string $path2 Optional path to siteModules
	 * @see load()
	 *
	 */
	public function __construct($path, $path2 = null) {
		$this->setTrackChanges(false); 
		$this->modulePath = $path;
		$this->loadModuleInfoCache();
		$this->load($path); 
		if($path2 && is_dir($path2)) {
			$this->modulePath2 = $path2; 
			$this->load($path2);
		}
	}

	/**
	 * Modules class accepts only Module instances, per the WireArray interface
 	 *
	 */
	public function isValidItem($item) {
		return $item instanceof Module;
	}

	/**
	 * The key/index used for each module in the array is it's class name, per the WireArray interface
 	 *
	 */
	public function getItemKey($item) {
		return $this->getModuleClass($item); 
	}

	/**
	 * There is no blank/generic module type, so makeBlankItem returns null
 	 *
	 */
	public function makeBlankItem() {
		return null; 
	}

	/**
	 * Make a new/blank WireArray
 	 *
	 */
	public function makeNew() {
		// ensures that find(), etc. operations don't initalize a new Modules() class
		return new WireArray();
	}

	/**
	 * Make a new populated copy of a WireArray containing all the modules
	 *
	 * @return WireArray
 	 *
	 */
	public function makeCopy() {
		// ensures that find(), etc. operations don't initalize a new Modules() class
		$copy = $this->makeNew();
		foreach($this->data as $key => $value) $copy[$key] = $value; 
		$copy->resetTrackChanges($this->trackChanges()); 
		return $copy; 
	}

	/**
	 * Initialize all the modules that are loaded at boot
	 *
	 */
	public function triggerInit($modules = null, $completed = array(), $level = 0) {
	
		if($this->debug) $debugKey = $this->debugTimerStart("triggerInit$level"); 
		$queue = array();
		if(is_null($modules)) $modules = $this;

		foreach($modules as $class => $module) {
			
			if($module instanceof ModulePlaceholder && !$module->autoload) continue; 
			
			if($this->debug) $debugKey2 = $this->debugTimerStart("triggerInit$level($class)"); 
			$info = $this->getModuleInfo($module); 
			$skip = false;

			// module requires other modules
			foreach($info['requires'] as $requiresClass) {
				if(!in_array($requiresClass, $completed)) {
					$dependencyInfo = $this->getModuleInfo($requiresClass);
					// if dependency isn't an autoload one, then we can continue and not worry about it
					if(empty($dependencyInfo['autoload'])) continue;
					// dependency is autoload and required by this module, so queue this module to init later
					$queue[$class] = $module;
					$skip = true;
					break;
				}
			}
			if(!$skip) {
				if($info['autoload'] !== false) {
					if($info['autoload'] === true || $this->isAutoload($module)) $this->initModule($module);
				}
				$completed[] = $class;
			}
			if($this->debug) $this->debugTimerStop($debugKey2); 
		}

		// if there is a dependency queue, go recursive till the queue is completed
		if(count($queue) && $level < 3) $this->triggerInit($queue, $completed, $level+1);

		$this->initialized = true;
		if($this->debug) if($debugKey) $this->debugTimerStop($debugKey);
		if(!$level && empty($this->moduleInfoCache)) $this->saveModuleInfoCache();
	}

	/**
	 * Given a class name, return the constructed module
	 *
	 */
	protected function newModule($className) {
		if($this->debug) $debugKey = $this->debugTimerStart("newModule($className)"); 
		if(!class_exists($className, false)) $this->includeModule($className); 
		$module = new $className(); 
		if($this->debug) $this->debugTimerStop($debugKey); 
		return $module; 
	}

	/**
	 * Initialize a single module
	 * 
	 * @param Module $module
	 * @param bool $clearSettings If true, module settings will be cleared when appropriate to save space. 
	 *
	 */
	protected function initModule(Module $module, $clearSettings = true) {
		
		// if the module is configurable, then load it's config data
		// and set values for each before initializing themodule
		$this->setModuleConfigData($module);
		
		if(method_exists($module, 'init')) {
			$className = get_class($module); 
			if($this->debug) $debugKey = $this->debugTimerStart("initModule($className)"); 
			$module->init();
			if($this->debug) $this->debugTimerStop($debugKey);
		}

		// if module is autoload (assumed here) and singular, then
		// we no longer need the module's config data, so remove it
		if($clearSettings && $this->isSingular($module)) {
			$id = $this->getModuleID($module);
			unset($this->configData[$id]);
		}
		
	}

	/**
	 * Call ready for a single module
	 *
	 */
	protected function readyModule(Module $module) {
		if(method_exists($module, 'ready')) {
			if($this->debug) $debugKey = $this->debugTimerStart("readyModule(" . $module->className() . ")"); 
			$module->ready();
			if($this->debug) $this->debugTimerStop($debugKey); 
		}
	}

	/**
	 * Trigger all modules 'ready' method, if they have it.
	 *
	 * This is to indicate to them that the API environment is fully ready and $page is in fuel.
	 *
 	 * This is triggered by ProcessPageView::ready
	 *
	 */
	public function triggerReady() {
		if($this->debug) $debugKey = $this->debugTimerStart("triggerReady"); 
		
		foreach($this->conditionalAutoloadModules as $className => $func) {
			if(is_string($func)) {
				// selector string
				if(!$this->wire('page')->is($func)) continue; 
			} else {
				// anonymous function
				if(!is_callable($func)) continue; 
				if(!$func()) continue;
			}
			$module = $this->newModule($className); 
			$this->set($className, $module); 
			$this->initModule($module);
		}
		$this->conditionalAutoloadModules = array();

		foreach($this as $module) {
			if($module instanceof ModulePlaceholder) continue;
			if(!method_exists($module, 'ready')) continue;
			$info = $this->getModuleInfo($module); 
			if($info['autoload'] === false) continue; 
			if(!$this->isAutoload($module)) continue; 
			$this->readyModule($module);
		}
		
		if($this->debug) $this->debugTimerStop($debugKey); 
	}

	/**
	 * Given a disk path to the modules, instantiate all installed modules and keep track of all uninstalled (installable) modules. 
	 *
	 * @param string $path 
	 *
	 */
	protected function load($path) {

		static $installed = array();
		$database = $this->wire('database');
		if($this->debug) $debugKey = $this->debugTimerStart("load($path)"); 

		if(!count($installed)) {
			$query = $database->prepare("SELECT id, class, flags, data FROM modules ORDER BY class"); // QA
			$query->execute();
			while($row = $query->fetch(PDO::FETCH_ASSOC)) {
				if($row['flags'] & self::flagsAutoload) {
					// preload config data for autoload modules since we'll need it again very soon
					$this->configData[$row['id']] = wireDecodeJSON($row['data']);
				}
				unset($row['data']);
				$installed[$row['class']] = $row;
			}
			$query->closeCursor();
		}

		$files = $this->findModuleFiles($path, true); 

		foreach($files as $pathname) {

			$pathname = $path . $pathname;
			$dirname = dirname($pathname);
			$filename = basename($pathname); 
			$basename = basename($filename, '.php'); 
			$basename = basename($basename, '.module');

			if(class_exists($basename, false)) {
				// possibly more than one of the same modules on the system 
				continue; 
			}

			// if the filename doesn't end with .module or .module.php, then stop and move onto the next
			if(!strpos($filename, '.module') || (substr($filename, -7) !== '.module' && substr($filename, -11) !== '.module.php')) continue; 

			//  if the filename doesn't start with the requested path, then continue
			if(strpos($pathname, $path) !== 0) continue; 

			// if the file isn't there, it was probably uninstalled, so ignore it
			if(!file_exists($pathname)) continue;

			// if the module isn't installed, then stop and move on to next
			if(!array_key_exists($basename, $installed)) {	
				$this->installable[$basename] = $pathname; 
				continue; 
			}

			$info = $installed[$basename]; 
			$this->setConfigPaths($basename, $dirname); 
			$module = null; 
			$autoload = false;

			if($info['flags'] & self::flagsAutoload) { 
				// this is an Autoload mdoule. 
				// include the module and instantiate it but don't init() it,
				// because it will be done by Modules::init()
				$moduleInfo = $this->getModuleInfo($basename); 
				// if not defined in getModuleInfo, then we'll accept the database flag as enough proof
				// since the module may have defined it via an isAutoload() function
				if(!isset($moduleInfo['autoload'])) $moduleInfo['autoload'] = true;
				$autoload = $moduleInfo['autoload'];
				if($autoload === 'function') {
					// function is stored by the moduleInfo cache to indicate we need to call a dynamic functino specified with the module itself
					include_once($pathname); 
					$i = $basename::getModuleInfo();
					$autoload = isset($i['autoload']) ? $i['autoload'] : true;
				}
				// check for conditional autoload
				if(!is_bool($autoload) && (is_string($autoload) || is_callable($autoload))) {
					// anonymous function or selector string
					$this->conditionalAutoloadModules[$basename] = $autoload;
					$this->moduleIDs[$basename] = $info['id'];
					$autoload = true; 
				} else if($autoload) {
					include_once($pathname); 
					$module = $this->newModule($basename); 
				}
			}

			if(is_null($module)) {
				// placeholder for a module, which is not yet included and instantiated
				$module = new ModulePlaceholder(); 
				$module->setClass($basename); 
				$module->singular = $info['flags'] & self::flagsSingular; 
				$module->autoload = $autoload;
				$module->file = $pathname; 
			}

			$this->moduleIDs[$basename] = $info['id']; 
			$this->set($basename, $module); 
		}
		
		if($this->debug) $this->debugTimerStop($debugKey); 

	}

	/**
	 * Find new module files in the given $path
	 *
	 * If $readCache is true, this will perform the find from the cache
	 *
	 * @param string $path Path to the modules
	 * @param bool $readCache Optional. If set to true, then this method will attempt to read modules from the cache. 
	 * @param int $level For internal recursive use.
	 * @return array Array of module files
	 *
	 */
	protected function findModuleFiles($path, $readCache = false, $level = 0) {

		static $startPath;

		$config = $this->wire('config');
		$cache = $this->wire('cache'); 

		/*
		if($level == 0) {
			$startPath = $path;
			$cacheFilename = $config->paths->cache . "Modules." . md5($path) . ".cache";
			if($readCache && is_file($cacheFilename)) {
				$cacheContents = explode("\n", file_get_contents($cacheFilename)); 
				if(!empty($cacheContents)) return $cacheContents;
			}
		}
		*/
		
		if($level == 0) {
			$startPath = $path;
			$cacheName = "Modules." . str_replace($this->wire('config')->paths->root, '', $path);
			//$cacheFilename = $config->paths->cache . $cacheName . ".cache";
			if($readCache && $cache) {
				$cacheContents = $cache->get($cacheName); 
				if(!empty($cacheContents)) {
					$cacheContents = explode("\n", $cacheContents); 
					return $cacheContents;
				}
			}
		}

		$files = array();
		$dir = new DirectoryIterator($path); 
		
		foreach($dir as $file) {

			if($file->isDot()) continue; 

			$filename = $file->getFilename();
			$pathname = $file->getPathname();

			if(DIRECTORY_SEPARATOR != '/') {
				$pathname = str_replace(DIRECTORY_SEPARATOR, '/', $pathname); 
				$filename = str_replace(DIRECTORY_SEPARATOR, '/', $filename); 
			}

			// if it's a directory with a .module file in it named the same as the dir, then descend into it
			if($file->isDir() && ($level < 1 || (is_file("$pathname/$filename.module") || is_file("$pathname/$filename.module.php")))) {
				$files = array_merge($files, $this->findModuleFiles($pathname, false, $level + 1));
			}

			// if the filename doesn't end with .module or .module.php, then stop and move onto the next
			if((!strpos($filename, '.module') || substr($filename, -7) !== '.module') && substr($filename, -11 !== '.module.php')) {
				continue; 
			}
		
			$files[] = str_replace($startPath, '', $pathname); 
		}

		if($level == 0) {
			if($cache) $cache->save($cacheName, implode("\n", $files), WireCache::expireNever); 
			//@file_put_contents($cacheFilename, implode("\n", $files), LOCK_EX);  // remove LOCK_EX ?
		}
		//if($config->chmodFile) @chmod($cacheFilename, octdec($config->chmodFile));

		return $files;
	}


	/**
	 * Setup entries in config->urls and config->paths for the given module
	 *
	 * @param string $moduleName
	 * @param string $path
	 *
	 */
	protected function setConfigPaths($moduleName, $path) {
		$config = $this->wire('config'); 
		$path = rtrim($path, '/'); 
		$path = substr($path, strlen($config->paths->root)) . '/';
		$config->paths->set($moduleName, $path);
		$config->urls->set($moduleName, $path); 
	}

	/**
	 * Get the requsted Module or NULL if it doesn't exist. 
	 *
	 * If the module is a ModulePlaceholder, then it will be converted to the real module (included, instantiated, init'd) .
	 * If the module is not installed, but is installable, it will be installed, instantiated, and init'd. 
	 * This method is the only one guaranteed to return a real [non-placeholder] module. 
	 *
	 * @param string|int $key Module className or database ID
	 * @return Module|null
	 * @throws WirePermissionException If module requires a particular permission the user does not have
	 *
	 */
	public function get($key) {

		$module = null; 
		$needsInit = false; 

		// check for optional module ID and convert to classname if found
		if(ctype_digit("$key")) {
			if(!$key = array_search($key, $this->moduleIDs)) return null;
		}

		if($module = parent::get($key)) {

			// check if it's a placeholder, and if it is then include/instantiate/init the real module 
			// OR check if it's non-singular, so that a new instance is created
			if($module instanceof ModulePlaceholder || !$this->isSingular($module)) {
				$placeholder = $module; 
				$class = $this->getModuleClass($placeholder); 
				if($module instanceof ModulePlaceholder) $this->includeModule($module); 
				$module = $this->newModule($class); 
				// if singular, save the instance so it can be used in later calls
				if($this->isSingular($module)) $this->set($key, $module); 
				$needsInit = true; 
			}

		} else if(array_key_exists($key, $this->getInstallable())) {
			// check if the request is for an uninstalled module 
			// if so, install it and return it 
			$module = $this->install($key); 
			$needsInit = true; 
		}

		// skip autoload modules because they have already been initialized in the load() method
		// unless they were just installed, in which case we need do init now
		if($module && $needsInit) { 
			// if the module is configurable, then load it's config data
			// and set values for each before initializing the module
			// $this->setModuleConfigData($module); 
			// if(method_exists($module, 'init')) $module->init(); 
			$this->initModule($module, false); 
		}
		
		$info = $this->getModuleInfo($module); 
		if(!empty($info['permission']) && !$this->wire('user')->hasPermission($info['permission'])) {
			throw new WirePermissionException($this->_('You do not have permission to execute this module') . ' - ' . $class);
		}

		return $module; 
	}

	/**
	 * Get the requested module and reset cache + install it if necessary.
	 *
	 * This is exactly the same as get() except that this one will rebuild the modules cache if
	 * it doesn't find the module at first. If the module is on the file system, this
	 * one will return it in some instances that a regular get() can't. 
	 *
	 * @param string|int $key Module className or database ID
	 * @return Module|null
	 *
	 */
	public function getInstall($key) {
		$module = $this->get($key); 
		if(!$module) {
			$this->resetCache();
			$module = $this->get($key); 
		}
		return $module; 
	}

	/**
	 * Include the file for a given module, but don't instantiate it 
	 *
	 * @param ModulePlaceholder|Module|string Expects a ModulePlaceholder or className
	 * @return bool true on success
	 *
	 */
	public function includeModule($module) {
		$className = '';
		if(is_object($module)) $className = $module->className();
			else if(is_string($module)) $className = $module; 
		if($className && class_exists($className, false)) return true; 
		if(is_string($module)) $module = parent::get($module); 
		if(!$module) return false; 

		if($module instanceof ModulePlaceholder) {
			include_once($module->file); 			
		} else {
			// it's already been included, no doubt
		}
		return true; 
	}

	/**
	 * Find modules based on a selector string and ensure any ModulePlaceholders are loaded in the returned result
	 *
	 * @param string $selector
	 * @return Modules
	 *	
	 */
	public function find($selector) {
		$a = parent::find($selector); 
		if($a) {
			foreach($a as $key => $value) {
				$a[$key] = $this->get($value->className()); 
			}
		}
		return $a; 
	}

	/**
	 * Get an array of all modules that aren't currently installed
	 *
	 * @return array Array of elements with $className => $pathname
	 *
	 */
	public function getInstallable() {
		return $this->installable; 
	}

	/**
	 * Is the given class name installed?
	 *
	 * @param string $class
	 * @return bool
	 *
	 */
	public function isInstalled($class) {
		if(is_object($class)) $class = $this->getModuleClass($class); 
		if($class == 'PHP' || $class == 'ProcessWire') return true; 
		return (parent::get($class) !== null);
	}


	/**
	 * Is the given class name not installed?
	 *
	 * @param string $class
	 * @return bool
 	 *
	 */
	public function isInstallable($class) {
		return array_key_exists($class, $this->installable); 
	}

	/**
	 * Install the given class name
	 *
	 * @param string $class
	 * @return null|Module Returns null if unable to install, or instantiated Module object if successfully installed. 
	 * @throws WireException
	 *
	 */
	public function ___install($class) {

		if(!$this->isInstallable($class)) return null; 
		$pathname = $this->installable[$class]; 	
		require_once($pathname); 
		$this->setConfigPaths($class, dirname($pathname)); 

		$requires = $this->getRequiresForInstall($class); 
		if(count($requires)) throw new WireException("Module $class requires: " . implode(", ", $requires)); 

		$module = $this->newModule($class);

		$flags = 0;
		$database = $this->wire('database');
		$moduleID = 0;
		
		if($this->isSingular($module)) $flags = $flags | self::flagsSingular; 
		if($this->isAutoload($module)) $flags = $flags | self::flagsAutoload; 
	
		$query = $database->prepare("INSERT INTO modules SET class=:class, flags=:flags, data=''"); 
		$query->bindValue(":class", $class, PDO::PARAM_STR); 
		$query->bindValue(":flags", $flags, PDO::PARAM_INT); 
		if($query->execute()) $moduleID = (int) $database->lastInsertId();
			
		$this->moduleIDs[$class] = $moduleID;

		$this->add($module); 
		unset($this->installable[$class]); 

		// note: the module's install is called here because it may need to know it's module ID for installation of permissions, etc. 
		if(method_exists($module, '___install') || method_exists($module, 'install')) {
			try {
				$module->install();

			} catch(Exception $e) {
				// remove the module from the modules table if the install failed
				$moduleID = (int) $moduleID; 
				$query = $database->prepare('DELETE FROM modules WHERE id=:id LIMIT 1'); // QA
				$query->bindValue(":id", $moduleID, PDO::PARAM_INT); 
				$query->execute();
				throw new WireException("Unable to install module '$class': " . $e->getMessage()); 
			}
		}

		$info = $this->getModuleInfo($class); 
	
		// if this module has custom permissions defined in its getModuleInfo()['permissions'] array, install them 
		foreach($info['permissions'] as $name => $title) {
			$name = $this->wire('sanitizer')->pageName($name); 
			if(ctype_digit("$name") || empty($name)) continue; // permission name not valid
			$permission = $this->wire('permissions')->get($name); 
			if($permission->id) continue; // permision already there
			try {
				$permission = $this->wire('permissions')->add($name); 
				$permission->title = $title; 
				$this->wire('permissions')->save($permission); 
				$this->message(sprintf($this->_('Added Permission: %s'), $permission->name)); 
			} catch(Exception $e) {
				$this->error(sprintf($this->_('Error adding permission: %s'), $name)); 
			}
		}

		// check if there are any modules in 'installs' that this module didn't handle installation of, and install them
		$label = $this->_('Module Auto Install');
		foreach($info['installs'] as $name) {
			if(!$this->isInstalled($name)) {
				try { 
					$this->install($name); 
					$this->message("$label: $name"); 
				} catch(Exception $e) {
					$this->error("$label: $name - " . $e->getMessage()); 	
				}
			}
		}

		return $module; 
	}

	/**
	 * Returns whether the module can be uninstalled
	 *
	 * @param string|Module $class
	 * @param bool $returnReason If true, the reason why it can't be uninstalled with be returned rather than boolean false.
	 * @return bool|string 
	 *
	 */
	public function isUninstallable($class, $returnReason = false) {

		$reason = '';
		$reason1 = "Module is not already installed";
		$class = $this->getModuleClass($class); 

		if(!$this->isInstalled($class)) {
			$reason = $reason1;

		} else {
			$this->includeModule($class); 
			if(!class_exists($class, false)) $reason = $reason1; 
		}

		if(!$reason) { 
			// if the moduleInfo contains a non-empty 'permanent' property, then it's not uninstallable
			$info = $this->getModuleInfo($class); 
			if(!empty($info['permanent'])) {
				$reason = "Module is permanent"; 
			} else {
				$dependents = $this->getRequiresForUninstall($class); 	
				if(count($dependents)) $reason = "Module is required by other modules that must be removed first"; 
			}

			if(!$reason && in_array('Fieldtype', class_parents($class))) {
				foreach(wire('fields') as $field) {
					$fieldtype = get_class($field->type);
					if($fieldtype == $class) { 
						$reason = "This module is a Fieldtype currently in use by one or more fields";
						break;
					}
				}
			}
		}
		
		if($returnReason && $reason) return $reason;
	
		return $reason ? false : true; 	
	}

	/**
	 * Returns whether the module can be deleted (have it's files physically removed)
	 *
	 * @param string|Module $class
	 * @param bool $returnReason If true, the reason why it can't be removed will be returned rather than boolean false.
	 * @return bool|string 
	 *
	 */
	public function isDeleteable($class, $returnReason = false) {

		$reason = '';
		$class = $this->getModuleClass($class); 

		$filename = isset($this->installable[$class]) ? $this->installable[$class] : null;
		$dirname = dirname($filename); 

		if(empty($filename) || $this->isInstalled($class)) {
			$reason = "Module must be uninstalled before it can be deleted.";

		} else if(is_link($filename) || is_link($dirname) || is_link(dirname($dirname))) {
			$reason = "Module is linked to another location";

		} else if(!is_file($filename)) {
			$reason = "Module file does not exist";

		} else if(strpos($filename, $this->modulePath2) !== 0) {
			$reason = "Core modules may not be deleted.";

		} else if(!is_writable($filename)) {
			$reason = "We have no write access to the module file, it must be removed manually.";
		}

		if($returnReason && $reason) return $reason;
	
		return $reason ? false : true; 	
	}

	/**
	 * Delete the given module, physically removing its files
	 *
	 * @param string $class
	 * @return bool
	 * @throws WireException If module can't be deleted, exception will be thrown containing reason. 
	 *
	 */
	public function ___delete($class) {

		$class = $this->getModuleClass($class); 
		$reason = $this->isDeleteable($class, true); 
		if($reason !== true) throw new WireException($reason); 

		$filename = $this->installable[$class];
		$basename = basename($filename); 

		// double check that $class is consistent with the actual $basename	
		if($basename === "$class.module" || $basename === "$class.module.php") {
			// good, this is consistent with the format we require
		} else {
			throw new WireException("Unrecognized module filename format"); 
		}

		// now determine if module is the owner of the directory it exists in
		// this is the case if the module class name is the same as the directory name

		// full path to directory, i.e. .../site/modules/ProcessHello
		$path = dirname($filename); 

		// full path to parent directory, i.e. ../site/modules
		$path = dirname($path); 

		// first check that we are still in the /site/modules/, and ...
		// now attempt to re-construct it with the $class as the directory name
		if(strpos("$path/", $this->modulePath2) === 0 && is_file("$path/$class/$basename")) {
			// we have a directory that we can recursively delete
			$rmPath = "$path/$class/";
			$this->message("Removing path: $rmPath", Notice::debug); 
			$success = wireRmdir($rmPath, true); 

		} else if(is_file($filename)) {
			$this->message("Removing file: $filename", Notice::debug); 
			$success = unlink($filename); 
		}
		
		return $success; 
	}


	/**
	 * Uninstall the given class name
	 *
	 * @param string $class
	 * @return bool
	 * @throws WireException
	 *
	 */
	public function ___uninstall($class) {

		$class = $this->getModuleClass($class); 
		$reason = $this->isUninstallable($class, true); 
		if($reason !== true) {
			// throw new WireException("$class - Can't Uninstall - $reason"); 
			return false;
		}

		$info = $this->getModuleInfo($class); 
		$module = $this->get($class); 
		
		if(method_exists($module, '___uninstall') || method_exists($module, 'uninstall')) {
			// note module's uninstall method may throw an exception to abort the uninstall
			$module->uninstall();
		}
		$database = $this->wire('database'); 
		$query = $database->prepare('DELETE FROM modules WHERE class=:class LIMIT 1'); // QA
		$query->bindValue(":class", $class, PDO::PARAM_STR); 
		$query->execute();
	
		// remove all hooks attached to this module
		$hooks = $module instanceof Wire ? $module->getHooks() : array();
		foreach($hooks as $hook) {
			$this->message("Removed hook $class => " . $hook['options']['fromClass'] . " $hook[method]", Notice::debug); 
			$module->removeHook($hook['id']); 
		}
	
		// remove all hooks attached to other ProcessWire objects
		$hooks = array_merge(wire()->getHooks('*'), Wire::$allLocalHooks);
		foreach($hooks as $hook) {
			$toClass = get_class($hook['toObject']); 
			if($class === $toClass) {
				$hook['toObject']->removeHook($hook['id']);
				$this->message("Removed hook $class => " . $hook['options']['fromClass'] . " $hook[method]", Notice::debug); 
			}
		}

		// check if there are any modules still installed that this one says it is responsible for installing
		foreach($this->getUninstalls($class) as $name) {

			// catch uninstall exceptions at this point since original module has already been uninstalled
			$label = $this_>_('Module Auto Uninstall');
			try { 
				$this->uninstall($name); 
				$this->message("$label: $name"); 

			} catch(Exception $e) {
				$this->error("$label: $name - " . $e->getMessage()); 
			}
		}
	
		// add back to the installable list
		if(class_exists("ReflectionClass")) {
			$reflector = new ReflectionClass($class);
			$this->installable[$class] = $reflector->getFileName(); 
		}

		unset($this->moduleIDs[$class]);
		$this->remove($module);
	
		// delete permissions installed by this module
		if(isset($info['permissions']) && is_array($info['permissions'])) {
			foreach($info['permissions'] as $name => $title) {
				$name = $this->wire('sanitizer')->pageName($name); 
				if(ctype_digit("$name") || empty($name)) continue; 
				$permission = $this->wire('permissions')->get($name); 
				if(!$permission->id) continue; 
				try { 
					$this->wire('permissions')->delete($permission); 
					$this->message(sprintf($this->_('Deleted Permission: %s'), $name)); 
				} catch(Exception $e) {
					$this->error(sprintf($this->_('Error deleting permission: %s'), $name)); 
				}
			}
		}

		return true; 
	}

	/**
	 * Return an array of other module class names that are uninstalled when the given one is
	 * 
	 * The opposite of this function is found in the getModuleInfo array property 'installs'. 
	 * Note that 'installs' and uninstalls may be different, as only modules in the 'installs' list
	 * that indicate 'requires' for the installer module will be uninstalled.
	 * 
	 * @param $class
	 * @return array
	 * 
	 */
	public function getUninstalls($class) {
		
		$uninstalls = array();
		$class = $this->getModuleClass($class);
		if(!$class) return $uninstalls;
		$info = $this->getModuleInfo($class);
		
		// check if there are any modules still installed that this one says it is responsible for installing
		foreach($info['installs'] as $name) {

			// if module isn't installed, then great
			if(!$this->isInstalled($name)) continue;

			// if an 'installs' module doesn't indicate that it requires this one, then leave it installed
			$i = $this->getModuleInfo($name);
			if(!in_array($class, $i['requires'])) continue; 
			
			// add it to the uninstalls array
			$uninstalls[] = $name;
		}
		
		return $uninstalls;
	}

	/**
	 * Returns the database ID of a given module class, or 0 if not found
	 *
	 * @param string|Module $class
	 * @return int
	 * @throws WireException
	 *
	 */
	public function getModuleID($class) {

		if(is_object($class)) {
			if($class instanceof Module) $class = $this->getModuleClass($class); 
				else throw new WireException("Unknown module type"); 
		}

		return isset($this->moduleIDs[$class]) ? (int) $this->moduleIDs[$class] : 0; 
	}

	/**
	 * Returns the module's class name. 
	 *
	 * Given a numeric database ID, returns the associated module class name or false if it doesn't exist
	 *
	 * Given a Module or ModulePlaceholder instance, returns the Module's class name. 
	 *
	 * If the module has a className() method then it uses that rather than PHP's get_class().
	 * This is important because of placeholder modules. For example, get_class would return 
	 * 'ModulePlaceholder' rather than the correct className for a Module.
	 *
	 * @param string|int|Module
	 * @return string|bool The Module's class name or false if not found. 
	 *	Note that 'false' is only possible if you give this method a non-Module, or an integer ID 
	 * 	that doesn't correspond to a module ID. 
	 *
	 */
	public function getModuleClass($module) {

		if($module instanceof Module) {
			if(method_exists($module, 'className')) return $module->className();	
			return get_class($module); 

		} else if(is_int($module) || ctype_digit("$module")) {
			return array_search((int) $module, $this->moduleIDs); 

		}  else if(is_string($module)) { 
			if(array_key_exists($module, $this->moduleIDs)) return $module; 
			if(array_key_exists($module, $this->installable)) return $module; 
		}

		return false; 
	}

	protected function getModuleInfoExternal($moduleName) {
		
		// ...attempt to load info by info file (Module.info.php or Module.info.json)
		if(!empty($this->installable[$moduleName])) {
			$path = dirname($this->installable[$moduleName]) . '/';
		} else {
			$path = $this->wire('config')->paths->$moduleName;
		}
		
		if(empty($path)) return array();

		// module exists and has a dedicated path on the file system
		// we will try to get info from a PHP or JSON info file
		$filePHP = $path . "$moduleName.info.php";
		$fileJSON = $path . "$moduleName.info.json";

		$info = array();
		if(is_file($filePHP)) {
			include($filePHP); // will populate $info automatically
			
		} else if(is_file($fileJSON)) {
			$info = file_get_contents($fileJSON);
			$info = json_decode($info, true);
			if(!$info) throw new WireException("Invalid JSON module info for $moduleName");
		} 
		
		return $info; 
	}
	
	protected function getModuleInfoInternal($module) {
		
		$info = array();
		
		if($module instanceof ModulePlaceholder) {
			$this->includeModule($module); 
			$module = $module->className();
		}
		
		if($module instanceof Module) {
			if(method_exists($module, 'getModuleInfo')) {
				$info = $module::getModuleInfo();
			}
			
		} else if($module) {
			if(method_exists($module, 'getModuleInfo')) {
				$info = call_user_func(array($module, 'getModuleInfo'));
			}
		}
		
		return $info; 
	}
	
	protected function getModuleInfoSystem($moduleName) {
		
		if($moduleName === 'PHP') {
			$info = array();
			$info['name'] = $moduleName;
			$info['title'] = $moduleName;
			$info['version'] = PHP_VERSION;
			return $info;

		} else if($moduleName === 'ProcessWire') {
			$info = array();
			$info['name'] = $moduleName;
			$info['title'] = $moduleName;
			$info['version'] = $this->wire('config')->version;
			$info['requiresVersions'] = array(
				'PHP' => '>=5.3.8',
				'PHP_modules' => 'PDO,mysqli',
				'Apache_modules' => 'mod_rewrite',
				'MySQL' => '>=5.0.15',
			);
			$info['requires'] = array_keys($info['requiresVersions']);
		}
		
		return $info;

	}

	/**
	 * Returns the standard array of information for a Module
	 *
	 * @param string|Module|int $module May be class name, module instance, or module ID
	 * @param array $options Optional options to modify behavior of what gets returned
	 * @return array
	 * @throws WireException when a module exists but has no means of returning module info
	 *	
	 */
	public function getModuleInfo($module, array $options = array()) {
		
		if(!isset($options['verbose'])) $options['verbose'] = true; 
		
		$info = array();
		$moduleName = $module; 
		
		static $verboseInfo = array(
			// module class name 
			'name' => '',
			// module title
			'title' => '',
			// module version
			'version' => 0,
			// who authored the module?
			'author' => '',
			// summary of what this module does
			'summary' => '',
			// URL to module details
			'href' => '',
			// Optional name of icon representing this module (currently font-awesome icon names, excluding the "fa-" portion)
			'icon' => '', 
			// this method converts this to array of module names, regardless of how the module specifies it
			'requires' => array(),
			// module name is key, value is array($operator, $version). Note 'requiresVersions' index is created by this function.
			'requiresVersions' => array(),
			// array of module class names
			'installs' => array(),
			// permission required to execute this module
			'permission' => '',
			// permissions automaticall installed/uninstalled with this module. array of ('permission-name' => 'Description')
			'permissions' => array(),
			// true if module is autoload, false if not. null=unknown
			'autoload' => null,
			// true if module is singular, false if not. null=unknown
			'singular' => null,
			);

		if($module instanceof Module) {
			// module is an instance
			$moduleName = method_exists($module, 'className') ? $module->className() : get_class($module); 
			// return from cache if available
			if(empty($options['noCache']) && !empty($this->moduleInfoCache[$moduleName])) return $this->moduleInfoCache[$moduleName]; 
			$info = $this->getModuleInfoInternal($module); 
			if(!count($info)) $info = $this->getModuleInfoExternal($moduleName); 
			
		} else if(in_array($module, array('PHP', 'ProcessWire'))) {
			// module is a system 
			$info = $this->getModuleInfoSystem($module); 
			
		} else {
			
			// module is a class name or ID
			if(ctype_digit("$module")) $module = $this->getModuleClass($module);
			// return from cache if available
			if(empty($options['noCache']) && !empty($this->moduleInfoCache[$module])) {
				return $this->moduleInfoCache[$module];
			}
	
			if(class_exists($moduleName, false)) {
				// module is already in memory, check internal first, then external
				$info = $this->getModuleInfoInternal($moduleName); 
				if(!count($info)) $info = $this->getModuleInfoExternal($moduleName); 
				
			} else {
				// module is not in memory, check external first, then internal
				$info = $this->getModuleInfoExternal($moduleName);
				if(!count($info)) {
					if(isset($this->installable[$moduleName])) include_once($this->installable[$moduleName]); 
					// info not available externally, attempt to locate it interally
					$info = $this->getModuleInfoInternal($moduleName); 
				}
			}
		}
		
		if(!count($info)) {
			$info = $verboseInfo; 
			$info['title'] = $module;
			$info['summary'] = 'Inactive';
			$info['error'] = 'Unable to locate module';
			return $info;
		}
		
		$info = array_merge($verboseInfo, $info); 

		// if $info[requires] or $info[installs] isn't already an array, make it one
		if(!is_array($info['requires'])) {
			$info['requires'] = str_replace(' ', '', $info['requires']); // remove whitespace
			if(strpos($info['requires'], ',') !== false) $info['requires'] = explode(',', $info['requires']); 
				else $info['requires'] = array($info['requires']); 
		}

		// populate requiresVersions
		foreach($info['requires'] as $key => $class) {
			if(!ctype_alnum($class)) {
				// has a version string
				list($class, $operator, $version) = $this->extractModuleOperatorVersion($class); 
				$info['requires'][$key] = $class; // convert to just class
			} else {
				// no version string
				$operator = '>=';
				$version = 0; 
			}
			$info['requiresVersions'][$class] = array($operator, $version); 
		}

		if(!is_array($info['installs'])) {
			$info['installs'] = str_replace(' ', '', $info['installs']); // remove whitespace
			if(strpos($info['installs'], ',') !== false) $info['installs'] = explode(',', $info['installs']); 
				else $info['installs'] = array($info['installs']); 
		}

		$info['name'] = $moduleName; 
		
		return $info;
	}

	/**
	 * Given a class name, return an array of configuration data specified for the Module
	 *
	 * Corresponds to the modules.data table in the database
	 *
	 * Applicable only for modules that implement the ConfigurableModule interface
	 *
	 * @param string|Module $className
	 * @return array
	 *
	 */
	public function getModuleConfigData($className) {

		if(is_object($className)) $className = $className->className();
		if(!$id = $this->moduleIDs[$className]) return array();
		if(isset($this->configData[$id])) return $this->configData[$id]; 

		// if the class doesn't implement ConfigurableModule, then it's not going to have any configData
		if(!in_array('ConfigurableModule', class_implements($className))) return array();
		
		$database = $this->wire('database'); 
		$query = $database->prepare("SELECT data FROM modules WHERE id=:id"); // QA
		$query->bindValue(":id", (int) $id, PDO::PARAM_INT); 
		$query->execute();
		$data = $query->fetchColumn(); 
		$query->closeCursor();
		
		if(empty($data)) $data = array();
			else $data = wireDecodeJSON($data); 
		$this->configData[$id] = $data; 

		return $data; 	
	}

	/**
	 * Populate configuration data to a ConfigurableModule
	 *
	 * If the Module has a 'setConfigData' method, it will send the array of data to that. 
	 * Otherwise it will populate the properties individually. 
	 *
	 * @param Module $module
	 * @param array $data Configuration data (key = value), or omit if you want it to retrieve the config data for you.
	 * 
	 */
	protected function setModuleConfigData(Module $module, $data = null) {

		if(!$module instanceof ConfigurableModule) return; 
		if(!is_array($data)) $data = $this->getModuleConfigData($module); 

		if(method_exists($module, 'setConfigData') || method_exists($module, '___setConfigData')) {
			$module->setConfigData($data); 
			return;
		}

		foreach($data as $key => $value) {
			$module->$key = $value; 
		}
	}

	/**
	 * Given a module class name and an array of configuration data, save it for the module
	 *
	 * @param string|Module $className
	 * @param array $configData
	 * @return bool True on success
	 * @throws WireException
	 *
	 */
	public function ___saveModuleConfigData($className, array $configData) {
		if(is_object($className)) $className = $className->className();
		if(!$id = $this->moduleIDs[$className]) throw new WireException("Unable to find ID for Module '$className'"); 
		$this->configData[$id] = $configData; 
		$json = count($configData) ? wireEncodeJSON($configData, true) : '';
		$database = $this->wire('database'); 	
		$query = $database->prepare("UPDATE modules SET data=:data WHERE id=:id"); // QA
		$query->bindValue(":data", $json, PDO::PARAM_STR);
		$query->bindValue(":id", (int) $id, PDO::PARAM_INT); 
		$result = $query->execute();
		return $result;
	}

	/**
	 * Is the given module Singular (single instance)?
	 *
	 * isSingular and isAutoload Module methods have been deprecated. So this method, and isAutoload() 
	 * exist in part to enable singular and autoload properties to be set in getModuleInfo, rather than 
	 * with methods. 
 	 *
	 * Note that isSingular() and isAutoload() are not deprecated for ModulePlaceholder, so the Modules
	 * class isn't going to stop looking for them. 
	 *
	 * @param Module $module
	 * @return bool 
 	 *
	 */
	public function isSingular(Module $module) {
		$info = $this->getModuleInfo($module, array('verbose' => false));
		if(isset($info['singular']) && $info['singular'] !== null) return $info['singular'];
		if(method_exists($module, 'isSingular')) return $module->isSingular();
		return false;
	}

	/**
	 * Is the given module Autoload (automatically loaded at runtime)?
	 *
	 * @param Module $module
	 * @return bool|string Returns string "conditional" if conditional autoload, true if autoload, or false if not.  
 	 *
	 */
	public function isAutoload(Module $module) {
		$info = $this->getModuleInfo($module, array('verbose' => false));
		if(isset($info['autoload']) && $info['autoload'] !== null) {
			// if autoload is a string (selector) or callable, then we flag it as autoload
			if(is_string($info['autoload']) || is_callable($info['autoload'])) return "conditional"; 
			return $info['autoload'];
		}
		if(method_exists($module, 'isAutoload')) return $module->isAutoload();
		return false; 
	}
	
	/**
	 * Returns whether the modules have been initialized yet
	 *
 	 * @return bool
	 *
	 */
	public function isInitialized() {
		return $this->initialized; 
	}

	/**
	 * Reset the cache that stores module files by recreating it
	 *
	 */
	public function resetCache() {
		if($this->wire('config')->systemVersion < 6) return;
		$this->clearModuleInfoCache();
		$this->findModuleFiles($this->modulePath, false); 
		if($this->modulePath2) $this->findModuleFiles($this->modulePath2, false); 
		$this->load($this->modulePath); 
		if($this->modulePath2) $this->load($this->modulePath2);
	}

	/**
	 * Return an array of module class names that require the given one
	 * 
	 * @param string $class
	 * @param bool $uninstalled Set to true to include modules dependent upon this one, even if they aren't installed.
	 * @param bool $installs Set to true to exclude modules that indicate their install/uninstall is controlled by $class.
	 * @return array()
	 *
	 */
	public function getRequiredBy($class, $uninstalled = false, $installs = false) {

		$class = $this->getModuleClass($class); 
		$info = $this->getModuleInfo($class); 
		$dependents = array();

		foreach($this as $module) {
			$c = $this->getModuleClass($module); 	
			if(!$uninstalled && !$this->isInstalled($c)) continue; 
			$i = $this->getModuleInfo($c); 
			if(!count($i['requires'])) continue; 
			if($installs && in_array($c, $info['installs'])) continue; 
			if(in_array($class, $i['requires'])) $dependents[] = $c; 
		}

		return $dependents; 
	}

	/**
	 * Return an array of module class names required by the given one
	 * 
	 * @param string $class
	 * @param bool $uninstalled Set to true to return only required modules that aren't yet installed or those that $class says it will install (via 'installs' property of getModuleInfo)
	 * @param bool $versions Set to true to always include versions in the requirements list. Note versions are already include when the installed version is not adequate.
	 * @return array()
	 *
	 */
	public function getRequires($class, $uninstalled = false, $versions = false) {

		$class = $this->getModuleClass($class); 
		$info = $this->getModuleInfo($class); 
		$requires = $info['requires']; 

		// quick exit if arguments permit it 
		if(!$uninstalled) {
			if($versions) foreach($requires as $key => $value) {
				list($operator, $version) = $info['requiresVersions'][$value]; 
				if(empty($version)) continue; 
				if(ctype_digit("$version")) $version = $this->formatVersion($version); 
				if(!empty($version)) $requires[$key] .= "$operator$version";
			}
			return $requires; 
		}

		foreach($requires as $key => $requiresClass) {

			if(in_array($requiresClass, $info['installs'])) {
				// if this module installs the required class, then we can stop now
				// and we assume it's installing the version it wants
				unset($requires[$key]); 
			}

			list($operator, $requiresVersion) = $info['requiresVersions'][$requiresClass];
			$installed = true; 

			if($requiresClass == 'PHP') {
				$currentVersion = PHP_VERSION; 

			} else if($requiresClass == 'ProcessWire') { 
				$currentVersion = $this->wire('config')->version; 

			} else if($this->isInstalled($requiresClass)) {
				if(!$requiresVersion) {
					// if no version is specified then requirement is already met
					unset($requires[$key]); 
					continue; 
				}
				$i = $this->getModuleInfo($requiresClass); 
				$currentVersion = $i['version'];
			} else {
				// module is not installed
				$installed = false; 
			}

			if($installed && $this->versionCompare($currentVersion, $requiresVersion, $operator)) {
				// required version is installed
				unset($requires[$key]); 

			} else if(empty($requiresVersion)) {
				// just the class name is fine
				continue; 

			} else {
				// update the requires string to clarify what version it requires
				if(ctype_digit("$requiresVersion")) $requiresVersion = $this->formatVersion($requiresVersion); 
				$requires[$key] = "$requiresClass$operator$requiresVersion";
			}
		}

		return $requires; 
	}


	/**
	 * Compare one module version to another, returning TRUE if they match the $operator or FALSE otherwise
	 *
	 * @param int $currentVersion
	 * @param int|string $requiredVersion
	 * @param string $operator
	 * @return bool
	 *
	 */
	protected function versionCompare($currentVersion, $requiredVersion, $operator) {
		$return = false;
		if(is_string($requiredVersion)) {
			if(!is_string($currentVersion)) $currentVersion = $this->formatVersion($currentVersion); 
			$return = version_compare($currentVersion, $requiredVersion, $operator); 
		} else {
			switch($operator) {
				case '=': $return = ($currentVersion == $requiredVersion); break;
				case '>': $return = ($currentVersion > $requiredVersion); break;
				case '<': $return = ($currentVersion < $requiredVersion); break;
				case '>=': $return = ($currentVersion >= $requiredVersion); break;
				case '<=': $return = ($currentVersion <= $requiredVersion); break;
				case '!=': $return = ($currentVersion != $requiredVersion); break;
			}
		}
		return $return;
	}

	/**
	 * Return array of ($module, $operator, $requiredVersion)
	 *
	 * $version will be 0 and $operator blank if there are no requirements.
	 * 
	 * @param string $require Module class name with operator and version string
	 * @return array of array($moduleClass, $operator, $version)
	 *
	 */
	protected function extractModuleOperatorVersion($require) {

		if(ctype_alnum($require)) {
			// no version is specified
			return array($require, '', 0); 
		}

		$operators = array('<=', '>=', '<', '>', '!=', '='); 
		$operator = '';
		foreach($operators as $o) {
			if(strpos($require, $o)) {
				$operator = $o;
				break;
			}
		}

		// if no operator found, then no version is being specified
		if(!$operator) return array($require, '', 0); 

		// extract class and version
		list($class, $version) = explode($operator, $require); 

		// make version an integer if possible
		if(ctype_digit("$version")) $version = (int) $version; 
	
		return array($class, $operator, $version); 
	}

	/**
	 * Return an array of module class names required by the given one to be installed before this one.
	 *
	 * Excludes modules that are required but already installed. 
	 * Excludes uninstalled modules that $class indicates it handles via it's 'installs' getModuleInfo property.
	 * 
	 * @param string $class
	 * @return array()
	 *
	 */
	public function getRequiresForInstall($class) {
		return $this->getRequires($class, true); 
	}

	/**
	 * Return an array of module class names required by the given one to be uninstalled before this one.
	 *
	 * Excludes modules that the given one says it handles via it's 'installs' getModuleInfo property.
	 * 
	 * @param string $class
	 * @return array()
	 *
	 */
	public function getRequiresForUninstall($class) {
		return $this->getRequiredBy($class, false, true); 
	}

	/**
	 * Given a module version number, format it in a consistent way as 3 parts: 1.2.3 
	 * 
	 * @param $version int|string
	 * @return string
	 * 
	 */
	public function formatVersion($version) {
		$version = trim($version); 
		if(!ctype_digit(str_replace('.', '', $version))) {
			// if version has some characters other than digits or periods, remove them
			$version = preg_replace('/[^\d.]/', '', $version); 
		}
		if(ctype_digit("$version")) {
			// make sure version is at least 3 characters in length
			if(strlen($version) < 3) $version = str_pad($version, 3, "0", STR_PAD_LEFT);
			// if version has only digits, then insert periods
			$version = preg_replace('/(\d)(?=\d)/', '$1.', $version); 
			
		} else if(strpos($version, '.') !== false) {
			// version is a formatted string
			if(strpos($version, '.') == strrpos($version, '.')) {
				// only 1 period, like: 2.0 
				if(preg_match('/^\d\.\d$/', $version)) $version .= ".0";
			}
		}
		if(!strlen($version)) $version = '0.0.0';
		return $version;
	}
	
	/**
	 * Load the module information cache
	 * 
	 * @return bool
	 * 
	 */
	protected function loadModuleInfoCache() {
		$data = $this->wire('cache')->get(self::moduleInfoCacheName); 	
		if($data) { 
			$data = json_decode($data, true); 
			if(is_array($data)) $this->moduleInfoCache = $data; 
			return true;
		}
		return false;
	}

	/**
	 * Clear the module information cache
	 * 
	 */
	protected function clearModuleInfoCache() {
		$this->wire('cache')->delete(self::moduleInfoCacheName); 
		$this->saveModuleInfoCache();
	}

	/**
	 * Save the module information cache
	 * 
	 */
	protected function saveModuleInfoCache() {
		
		$this->moduleInfoCache = array();
		
		foreach($this as $module) {
			
			$class = $module->className();
			$info = $this->getModuleInfo($class); 
			
			if(is_null($info['autoload'])) {
				$info['autoload'] = $this->isAutoload($module); 
			} else if(!is_bool($info['autoload']) && !is_string($info['autoload']) && is_callable($info['autoload'])) {
				// runtime function, identify it only with 'function' so that it can be recognized later as one that
				// needs to be dynamically loaded
				$info['autoload'] = 'function';
			}
			
			if(is_null($info['singular'])) {
				$info['singular'] = $this->isSingular($module); 
			} 
			
			if(!empty($info['error'])) continue;
			
			$this->moduleInfoCache[$class] = $info; 
		}
		
		$this->wire('cache')->save(self::moduleInfoCacheName, json_encode($this->moduleInfoCache), WireCache::expireNever); 
	}

	/**
	 * Start a debug timer, only works when module debug mode is on ($this->debug)
	 * 
	 * @param $note
	 * @return int|null Returns a key for the debug timer
	 * 
	 */
	protected function debugTimerStart($note) {
		if(!$this->debug) return null;
		$key = count($this->debugLog);
		while(isset($this->debugLog[$key])) $key++;
		$this->debugLog[$key] = array(
			0 => Debug::timer("Modules$key"),
			1 => $note
		);
		return $key;
	}

	/**
	 * Stop a debug timer, only works when module debug mode is on ($this->debug)
	 *
	 * @param int $key The key returned by debugTimerStart
	 *
	 */
	protected function debugTimerStop($key) {
		if(!$this->debug) return;
		$log = $this->debugLog[$key];
		$timerKey = $log[0];
		$log[0] = Debug::timer($timerKey);
		$this->debugLog[$key] = $log;
		Debug::clearTimer($timerKey);
	}

	/**
	 * Return a log of module construct, init and ready times, active only when debug mode is on ($this->debug)
	 *
	 * @return array
	 *
	 */
	public function getDebugLog() {
		return $this->debugLog;
	}


}

