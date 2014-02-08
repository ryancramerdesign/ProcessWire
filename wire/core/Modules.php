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
	 * Modules that specify an anonymous function returning true or false on whether they should be autoloaded
	 *
	 */
	protected $conditionalAutoloadModules = array();

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

		$queue = array();
		if(is_null($modules)) $modules = $this;

		foreach($modules as $class => $module) {
			// $info = call_user_func(array($module, 'getModuleInfo'));
			$info = $module->getModuleInfo();
			$skip = false;

			if(isset($info['requires'])) {
				// module requires other modules
				$requires = is_array($info['requires']) ? $info['requires'] : array($info['requires']);
				foreach($requires as $requiresClass) {
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
			}
			if($skip) continue;
			if($this->isAutoload($module)) $this->initModule($module);
			$completed[] = $class;
		}

		// if there is a dependency queue, go recursive till the queue is completed
		if(count($queue) && $level < 3) $this->triggerInit($queue, $completed, $level+1);

		$this->initialized = true;
	}


	/**
	 * Initialize a single module
	 *
	 */
	protected function initModule(Module $module) {
		
		// if the module is configurable, then load it's config data
		// and set values for each before initializing themodule
		$this->setModuleConfigData($module);
		$module->init();

		// if module is autoload (assumed here) and singular, then
		// we no longer need the module's config data, so remove it
		if($this->isSingular($module)) {
			$id = $this->getModuleID($module);
			unset($this->configData[$id]);
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
		
		foreach($this->conditionalAutoloadModules as $className => $func) {
			if(is_string($func)) {
				// selector string
				if(!$this->wire('page')->is($func)) continue; 
			} else {
				// anonymous function
				if(!is_callable($func)) continue; 
				if(!$func()) continue;
			}
			$module = new $className();
			$this->set($className, $module); 
			$this->initModule($module);
		}
		$this->conditionalAutoloadModules = array();

		foreach($this as $module) {
			if($module instanceof ModulePlaceholder) continue;
			if(!method_exists($module, 'ready')) continue;
			if(!$this->isAutoload($module)) continue; 
			$module->ready();
		}
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

			// if the filename doesn't end with .module, then stop and move onto the next
			if(!strpos($filename, '.module') || (substr($filename, -7) !== '.module' && substr($filename, -11) !== '.module.php')) continue; 

			//  if the filename doesn't start with the requested path, then continue
			if(strpos($pathname, $path) !== 0) continue; 

			// if the file isn't there, it was probably uninstalled, so ignore it
			if(!is_file($pathname)) continue; 

			// if the module isn't installed, then stop and move on to next
			if(!array_key_exists($basename, $installed)) {	
				$this->installable[$basename] = $pathname; 
				continue; 
			}

			$info = $installed[$basename]; 
			$this->setConfigPaths($basename, $dirname); 

			if($info['flags'] & self::flagsAutoload) { 
				// this is an Autoload mdoule. 
				// include the module and instantiate it but don't init() it,
				// because it will be done by Modules::init()
				include_once($pathname); 
				$moduleInfo = call_user_func(array($basename, 'getModuleInfo'));
				// if not defined in getModuleInfo, then we'll accept the database flag as enough proof
				// since the module may have defined it via an isAutoload() function
				if(!isset($moduleInfo['autoload'])) $moduleInfo['autoload'] = true;
				$autoload = $moduleInfo['autoload'];
				// check for conditional autoload
				if(!is_bool($autoload) && (is_string($autoload) || is_callable($autoload))) {
					// anonymous function or selector string
					$this->conditionalAutoloadModules[$basename] = $autoload;
					$this->moduleIDs[$basename] = $info['id'];
					continue; 
				} else if($autoload) {
					$module = new $basename();
				} else {
					continue; 
				}

			} else {
				// placeholder for a module, which is not yet included and instantiated
				$module = new ModulePlaceholder(); 
				$module->setClass($basename); 
				$module->singular = $info['flags'] & self::flagsSingular; 
				$module->file = $pathname; 
			}

			$this->moduleIDs[$basename] = $info['id']; 
			$this->set($basename, $module); 
		}

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

		if($level == 0) {
			$startPath = $path;
			$cacheFilename = $config->paths->cache . "Modules." . md5($path) . ".cache";
			if($readCache && is_file($cacheFilename)) {
				$cacheContents = explode("\n", file_get_contents($cacheFilename)); 
				if(!empty($cacheContents)) return $cacheContents;
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
			if(	(!strpos($filename, '.module') || substr($filename, -7) !== '.module') && substr($filename, -11 !== '.module.php')) {
					continue; 
			}
			
			$files[] = str_replace($startPath, '', $pathname); 
		}

		if($level == 0) @file_put_contents($cacheFilename, implode("\n", $files), LOCK_EX);  // remove LOCK_EX ?
		if($config->chmodFile) @chmod($cacheFilename, octdec($config->chmodFile));

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
		$config = $this->fuel('config'); 
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
				$module = new $class(); 
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
			$this->setModuleConfigData($module); 
			$module->init();
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

		$module = new $class();

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

		// check if there are any modules in 'installs' that this module didn't handle installation of, and install them
		$label = "Module Auto Install:";
		foreach($info['installs'] as $name) {
			if(!$this->isInstalled($name)) {
				try { 
					$this->install($name); 
					$this->message("$label $name"); 
				} catch(Exception $e) {
					$this->error("$label $name - " . $e->getMessage()); 	
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
			if(!class_exists($class)) $reason = $reason1; 
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
		if($reason !== true) throw new WireException("$class - Can't Uninstall - $reason"); 

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
			$label = "Module Auto Uninstall";
			try { 
				$this->uninstall($name); 
				$this->message("$label - $name"); 

			} catch(Exception $e) {
				$this->error("$label - $name - " . $e->getMessage()); 
			}
		}
	
		// add back to the installable list
		if(class_exists("ReflectionClass")) {
			$reflector = new ReflectionClass($class);
			$this->installable[$class] = $reflector->getFileName(); 
		}

		unset($this->moduleIDs[$class]);
		$this->remove($module);

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


	/**
	 * Returns the standard array of information for a Module
	 *
	 * @param string|Module|int $module May be class name, module instance, or module ID
	 * @return array
	 *	
	 */
	public function getModuleInfo($module) {

		$info = array(
			'title' => '',
			'version' => 0,
			'author' => '',
			'summary' => '',
			'href' => '',
			'requires' => array(),
			'installs' => array(),
			);

		if($module instanceof Module || ctype_digit("$module")) {
			$module = $this->getModuleClass($module); 
		}

		if(!class_exists($module)) {

			if(isset($this->installable[$module])) {
				$filename = $this->installable[$module]; 
				include_once($filename); 
			}

			if(!class_exists($module)) {
				$info['title'] = $module; 
				$info['summary'] = 'Inactive';
				return $info;
			}
		}

		//$func = $module . "::getModuleInfo"; // requires PHP 5.2.3+
		//return call_user_func($func);
		$info = array_merge($info, call_user_func(array($module, 'getModuleInfo')));

		// if $info[requires] or $info[installs] isn't already an array, make it one
		if(!is_array($info['requires'])) $info['requires'] = array($info['requires']); 
		if(!is_array($info['installs'])) $info['installs'] = array($info['installs']); 

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
		$info = $module->getModuleInfo();
		if(isset($info['singular'])) return $info['singular'];
		if(method_exists($module, 'isSingular')) return $module->isSingular();
		// $info = call_user_func(array($module, 'getModuleInfo'));
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
		$info = $module->getModuleInfo();
		if(isset($info['autoload'])) {
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
		$this->findModuleFiles($this->modulePath); 
		if($this->modulePath2) $this->findModuleFiles($this->modulePath2); 
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
	 * @return array()
	 *
	 */
	public function getRequires($class, $uninstalled = false) {

		$class = $this->getModuleClass($class); 
		$info = $this->getModuleInfo($class); 
		$requires = $info['requires']; 

		// quick exit if arguments permit it 
		if(!$uninstalled) return $requires; 

		foreach($requires as $key => $module) {
			$c = $this->getModuleClass($module); 
			if($this->isInstalled($c) || in_array($c, $info['installs'])) {
				unset($requires[$key]); 		
			}
		}

		return $requires; 
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

}

