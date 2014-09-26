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
 * Copyright (C) 2014 by Ryan Cramer 
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
	 * Filename for verbose module info cache file
	 *
	 */
	const moduleInfoCacheVerboseName = 'ModulesVerbose.info';
	
	/**
	 * Filename for uninstalled module info cache file
	 *
	 */
	const moduleInfoCacheUninstalledName = 'ModulesUninstalled.info';

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
	 * Full system paths where modules are stored
	 * 
	 * index 0 must be the core modules path (/i.e. /wire/modules/)
	 *
	 */
	protected $paths = array();

	/**
	 * Cached module configuration data indexed by module ID
	 *
	 */
	protected $configData = array();
	
	/**
	 * Module created dates indexed by module ID
	 *
	 */
	protected $createdDates = array();

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
	 * Cache of module information (verbose text) including: summary, author, href, file, core, configurable
	 *
	 */
	protected $moduleInfoCacheVerbose = array();
	
	/**
	 * Cache of module information (verbose for uninstalled) including: summary, author, href, file, core, configurable
	 * 
	 * Note that this one is indexed by class name rather than by ID (since uninstalled modules have no ID)
	 *
	 */
	protected $moduleInfoCacheUninstalled = array();

	/**
	 * Cache of module information from DB used across multiple calls temporarily by load() method
	 *
	 */
	protected $modulesTableCache = array();

	/**
	 * Array of moduleName => substituteModuleName to be used when moduleName doesn't exist
	 * 
	 * Primarily for providing backwards compatiblity with modules assumed installed that 
	 * may no longer be in core. 
	 * 
	 * see setSubstitutes() method
	 *
	 */
	protected $substitutes = array();

	/**
	 * Properties that only appear in 'verbose' moduleInfo
	 * 
	 * @var array
	 * 
	 */
	protected $moduleInfoVerboseKeys = array(
		'summary', 
		'author', 
		'href', 
		'file', 
		'core', 
		'configurable', 
		'versionStr',
		); 

	/**
	 * Construct the Modules
	 *
	 * @param string $path Core modules path (you may add other paths with addPath method)
	 *
	 */
	public function __construct($path) {
		$this->addPath($path); 
	}

	/**
	 * Add another modules path, must be called before init()
	 *
	 * @param string $path 
	 *
	 */
	public function addPath($path) {
		$this->paths[] = $path;
	}

	/**
	 * Return all assigned module root paths
	 *
	 * @return array of modules paths, with index 0 always being the core modules path.
	 *
	 */
	public function getPaths() {
		return $this->paths; 
	}

	/**
	 * Initialize modules
	 * 
	 * Must be called after construct before this class is ready to use
	 * 
	 * @see load()
	 * 
	 */
	public function init() {
		$this->setTrackChanges(false);
		$this->loadModuleInfoCache();
		$this->loadModulesTable();
		foreach($this->paths as $path) {
			$this->load($path);
		}
		$this->modulesTableCache = array(); // clear out data no longer needed
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
	 * @param string $className Module class name
	 * @return Module
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
	 * Return a new ModulePlaceholder for the given className
	 * 
	 * @param string $className Module class this placeholder will stand in for
	 * @param string $file Full path and filename of $className
	 * @param bool $singular Is the module a singular module?
	 * @param bool $autoload Is the module an autoload module?
	 * @return ModulePlaceholder
	 *
	 */
	protected function newModulePlaceholder($className, $file, $singular, $autoload) { 
		$module = new ModulePlaceholder();
		$module->setClass($className);
		$module->singular = $singular;
		$module->autoload = $autoload;
		$module->file = $file;
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
			if($this->debug) {
				$className = get_class($module); 
				$debugKey = $this->debugTimerStart("initModule($className)"); 
			}
		
			try { 
				$module->init();
			} catch(Exception $e) {
				$className = get_class($module); 
				$this->error("Module $className failed init - " . $e->getMessage(), Notice::log); 
			}
			
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
	 * Retrieve the installed module info as stored in the database
	 *
	 * @return array Indexed by module class name => array of module info
	 *
	 */
	protected function loadModulesTable() {
		$database = $this->wire('database');
		// we use SELECT * so that this select won't be broken by future DB schema additions
		// Currently: id, class, flags, data, with created added at sysupdate 7
		$query = $database->prepare("SELECT * FROM modules ORDER BY class"); // QA
		$query->execute();
		while($row = $query->fetch(PDO::FETCH_ASSOC)) {
			$moduleID = (int) $row['id'];
			if($row['flags'] & self::flagsAutoload) {
				// preload config data for autoload modules since we'll need it again very soon
				$this->configData[$moduleID] = strlen($row['data']) ? wireDecodeJSON($row['data']) : array();
			}
			unset($row['data']);
			if(isset($row['created']) && $row['created'] != '0000-00-00 00:00:00') {
				$this->createdDates[$moduleID] = $row['created']; 
			}
			$this->modulesTableCache[$row['class']] = $row;
		}
		$query->closeCursor();
	}

	/**
	 * Given a disk path to the modules, determine all installed modules and keep track of all uninstalled (installable) modules. 
	 *
	 * @param string $path 
	 *
	 */
	protected function load($path) {

		if($this->debug) $debugKey = $this->debugTimerStart("load($path)"); 

		$installed =& $this->modulesTableCache;
		$modulesLoaded = array();
		$modulesDelayed = array();
		$modulesRequired = array();
		
		foreach($this->findModuleFiles($path, true) as $pathname) {
			
			$pathname = trim($pathname); 
			$requires = array();
			$moduleName = $this->loadModule($path, $pathname, $requires, $installed);
			if(!$moduleName) continue; 
			
			if(count($requires)) {
				
				// module not loaded because it required other module(s) not yet loaded
				foreach($requires as $requiresModuleName) {
					if(!isset($modulesRequired[$requiresModuleName])) $modulesRequired[$requiresModuleName] = array();
					if(!isset($modulesDelayed[$moduleName])) $modulesDelayed[$moduleName] = array();
					// queue module for later load
					$modulesRequired[$requiresModuleName][$moduleName] = $pathname;
					$modulesDelayed[$moduleName][] = $requiresModuleName;
				}
				
			} else {
				
				// module was successfully loaded
				$modulesLoaded[$moduleName] = 1;

				// now determine if this module had any other modules waiting on it as a dependency
				do { 
					// if no other modules require this one, then we can stop
					if(!isset($modulesRequired[$moduleName])) break;
					
					// name of delayed module loaded (if loaded)
					$loadedName = '';
					
					// iternate through delayed modules that require this one
					foreach($modulesRequired[$moduleName] as $delayedName => $delayedPathName) {
						$loadNow = true; 
						foreach($modulesDelayed[$delayedName] as $requiresModuleName) {
							if(!isset($modulesLoaded[$requiresModuleName])) $loadNow = false;
						}
						if($loadNow) {
							// all conditions satisified to load delayed module
							unset($modulesDelayed[$delayedName]); 
							unset($modulesRequired[$moduleName][$delayedName]); 
							$unused = array(); 
							$loadedName = $this->loadModule($path, $delayedPathName, $unused, $installed); 
							if($loadedName) $modulesLoaded[$loadedName] = 1; 
						} else {
							// delayed module will be loaded when its last dependency is met
						}
					}
			
					// stuff it back in for another round
					// in case the loaded module accounts for yet another dependency
					$moduleName = $loadedName; 
					
				} while($moduleName);
			}
		}
		
		if(count($modulesDelayed)) foreach($modulesDelayed as $moduleName => $requiredNames) {
			$this->error("Module '$moduleName' dependecy not fulfilled for: " . implode(', ', $requiredNames), Notice::debug);
		}
		
		if($this->debug) $this->debugTimerStop($debugKey); 
	}

	/**
	 * Load a module into memory (companion to load bootstrap method)
	 * 
	 * @param string $basepath Base path of modules being processed (path provided to the load method)
	 * @param string $pathname
	 * @param array $requires This method will populate this array with required dependencies (class names) if present.
	 * @param array $installed Array of installed modules info, indexed by module class name
	 * @return Returns module name (classname) 
	 * 
	 */
	protected function loadModule($basepath, $pathname, array &$requires, array &$installed) {
		
		$pathname = $basepath . $pathname;
		$dirname = dirname($pathname);
		$filename = basename($pathname);
		$basename = basename($filename, '.php');
		$basename = basename($basename, '.module');
		$requires = array();

		if(class_exists($basename, false) && parent::get($basename)) {
			// module was already loaded
			$dir = rtrim($this->wire('config')->paths->$basename, '/'); 
			if($dir && $dirname != $dir) {
				// there are two copies of the module on the file system (likely one in /site/modules/ and another in /wire/modules/)
				$err = sprintf($this->_('Warning: there appear to be two copies of module "%s" on the file system.'), $basename) . ' ';
				$err .= $this->_('Please remove the one in /site/modules/ unless you need them both present for some reason.');
				$this->wire('log')->error($err); 
				$rootPath = $this->wire('config')->paths->root; 
				$dir = str_replace($rootPath, '/', $dir) . "/$filename";
				$dirname = str_replace($rootPath, '/', $dirname) . "/$filename";
				$err .= "<br /><pre>1. $dir\n2. $dirname</pre>";
				$user = $this->wire('user'); 
				if($user && $user->isSuperuser()) $this->error($err, Notice::allowMarkup); 
			}
			return $basename;
		}

		// if the filename doesn't end with .module or .module.php, then stop and move onto the next
		if(!strpos($filename, '.module') || (substr($filename, -7) !== '.module' && substr($filename, -11) !== '.module.php')) return false;
		
		//  if the filename doesn't start with the requested path, then continue
		if(strpos($pathname, $basepath) !== 0) return ''; 

		// if the file isn't there, it was probably uninstalled, so ignore it
		if(!file_exists($pathname)) return '';

		// if the module isn't installed, then stop and move on to next
		if(!array_key_exists($basename, $installed)) {
			$this->installable[$basename] = $pathname;
			return '';
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

			// determine if module has dependencies that are not yet met
			if(count($moduleInfo['requires'])) {
				foreach($moduleInfo['requires'] as $requiresClass) {
					if(!class_exists($requiresClass, false)) {
						$requiresInfo = $this->getModuleInfo($requiresClass); 
						if(!empty($requiresInfo['error']) || $requiresInfo['autoload'] === true || !$this->isInstalled($requiresClass)) {	
							// we only handle autoload===true since load() only instantiates other autoload===true modules
							$requires[] = $requiresClass;
						}
					}
				}
				if(count($requires)) return $basename;
			}

			// if not defined in getModuleInfo, then we'll accept the database flag as enough proof
			// since the module may have defined it via an isAutoload() function
			if(!isset($moduleInfo['autoload'])) $moduleInfo['autoload'] = true;
			$autoload = $moduleInfo['autoload'];
			if($autoload === 'function') {
				// function is stored by the moduleInfo cache to indicate we need to call a dynamic function specified with the module itself
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
			$module = $this->newModulePlaceholder($basename, $pathname, $info['flags'] & self::flagsSingular, $autoload);
		}

		$this->moduleIDs[$basename] = $info['id'];
		$this->set($basename, $module);
		
		return $basename; 
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
			$cacheName = "Modules." . str_replace($config->paths->root, '', $path);
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
		
		try {
			$dir = new DirectoryIterator($path); 
		} catch(Exception $e) {
			$this->error($e->getMessage()); 
			$dir = null;
		}
		
		if($dir) foreach($dir as $file) {

			if($file->isDot()) continue; 

			$filename = $file->getFilename();
			$pathname = $file->getPathname();

			if(DIRECTORY_SEPARATOR != '/') {
				$pathname = str_replace(DIRECTORY_SEPARATOR, '/', $pathname); 
				$filename = str_replace(DIRECTORY_SEPARATOR, '/', $filename); 
			}

			if(strpos($pathname, '/.') !== false) {
				$pos = strrpos(rtrim($pathname, '/'), '/');
				if($pathname[$pos+1] == '.') continue; // skip hidden files and dirs
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

		if($level == 0 && $dir !== null) {
			if($cache) $cache->save($cacheName, implode("\n", $files), WireCache::expireNever); 
		}

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
		return $this->getModule($key);
	}

	/**
	 * Attempt to find a substitute for moduleName and return module if found or null if not
	 * 
	 * @param $moduleName
	 * @param array $options See getModule() options
	 * @return Module|null
	 * 
	 */
	protected function getSubstituteModule($moduleName, array $options = array()) {
		
		$module = null;
		$options['noSubstitute'] = true; // prevent recursion
		
		while(isset($this->substitutes[$moduleName]) && !$module) {
			$substituteName = $this->substitutes[$moduleName];
			$module = $this->getModule($substituteName, $options);
			if(!$module) $moduleName = $substituteName;
		}
		
		return $module;
	}

	/**
	 * Get the requested Module or NULL if it doesn't exist + specify one or more options
	 * 
	 * @param string|int $key Module className or database ID
	 * @param array $options Optional settings to change load behavior:
	 * 	- noPermissionCheck: Specify true to disable module permission checks (and resulting exception). 
	 *  - noInstall: Specify true to prevent a non-installed module from installing from this request.
	 *  - noInit: Specify true to prevent the module from being initialized. 
	 *  - noSubstitute: Specify true to prevent inclusion of a substitute module. 
	 * @return Module|null
	 * @throws WirePermissionException If module requires a particular permission the user does not have
	 * 
	 */
	public function getModule($key, array $options = array()) {
	
		if(empty($key)) return null;
		$module = null;
		$needsInit = false;

		// check for optional module ID and convert to classname if found
		if(ctype_digit("$key")) {
			if(!$key = array_search($key, $this->moduleIDs)) return null;
		}
		
		$module = parent::get($key); 
		if(!$module && empty($options['noSubstitute'])) {
			$module = $this->getSubstituteModule($key, $options); 
			if($module) return $module; // returned module is ready to use
		}
		
		if($module) {

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

		} else if(empty($options['noInstall']) && array_key_exists($key, $this->getInstallable())) {
			// check if the request is for an uninstalled module 
			// if so, install it and return it 
			$module = $this->install($key);
			$needsInit = true;
		}
		
		if($module && empty($options['noPermissionCheck'])) {
			$info = $this->getModuleInfo($key);
			if(!empty($info['permission']) && !$this->wire('user')->hasPermission($info['permission'])) {
				throw new WirePermissionException($this->_('You do not have permission to execute this module') . ' - ' . $class);
			}
		}

		// skip autoload modules because they have already been initialized in the load() method
		// unless they were just installed, in which case we need do init now
		if($module && $needsInit) {
			// if the module is configurable, then load it's config data
			// and set values for each before initializing the module
			// $this->setModuleConfigData($module); 
			// if(method_exists($module, 'init')) $module->init(); 
			if(empty($options['noInit'])) $this->initModule($module, false);
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
			$module = $this->getModule($key); 
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
		if($className && class_exists($className, false)) return true; // already included
		
		// attempt to retrieve module
		if(is_string($module)) $module = parent::get($module); 
		
		if(!$module && $className) {
			// unable to retrieve module, must be an uninstalled module
			$file = $this->getModuleFile($className); 
			if($file) {
				@include_once($file);
				if(class_exists($className, false)) return true;
			}
		}
		
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
	 * @param string $class Just a ModuleClassName, or optionally: ModuleClassName>=1.2.3 (operator and version)
	 * @return bool
	 *
	 */
	public function isInstalled($class) {

		if(is_object($class)) $class = $this->getModuleClass($class);

		$operator = null;
		$requiredVersion = null;
		$currentVersion = null;
		
		if(!ctype_alnum($class)) {
			// class has something other than just a classnae, likely operator + version
			if(preg_match('/^([a-zA-Z0-9_]+)\s*([<>=!]+)\s*([\d.]+)$/', $class, $matches)) {
				$class = $matches[1];
				$operator = $matches[2];
				$requiredVersion = $matches[3];
			}
		}
		
		if($class === 'PHP' || $class === 'ProcessWire') {
			$installed = true; 
			if(!is_null($requiredVersion)) {
				$currentVersion = $class === 'PHP' ? PHP_VERSION : $this->wire('config')->version; 
			}
		} else {
			$installed = parent::get($class) !== null;
			if($installed && !is_null($requiredVersion)) {
				$info = $this->getModuleInfo($class); 
				$currentVersion = $info['version'];
			}
		}
	
		if($installed && !is_null($currentVersion)) {
			$installed = $this->versionCompare($currentVersion, $requiredVersion, $operator); 
		}
	
		return $installed;
		
	}


	/**
	 * Is the given class name not installed?
	 *
	 * @param string $class
	 * @param bool $now Is module installable RIGHT NOW? This makes it check that all dependencies are already fulfilled (default=false)
	 * @return bool
 	 *
	 */
	public function isInstallable($class, $now = false) {
		$installable = array_key_exists($class, $this->installable); 
		if(!$installable) return false;
		if($now) {
			$requires = $this->getRequiresForInstall($class); 
			if(count($requires)) return false;
		}
		return $installable;
	}
	
	/**
	 * Install the given class name
	 *
	 * @param string $class
	 * @param array|bool $options Associative array of: 
	 * 	- dependencies (boolean, default=true): When true, dependencies will also be installed where possible. Specify false to prevent installation of uninstalled modules. 
	 * 	- resetCache (boolean, default=true): When true, module caches will be reset after installation. 
	 * @return null|Module Returns null if unable to install, or instantiated Module object if successfully installed. 
	 * @throws WireException
	 *
	 */
	public function ___install($class, $options = array()) {
		
		$defaults = array(
			'dependencies' => true, 
			'resetCache' => true, 
			);
		if(is_bool($options)) { 
			// dependencies argument allowed instead of $options, for backwards compatibility
			$dependencies = $options; 
			$options = array('dependencies' => $dependencies);
		} 
		$options = array_merge($defaults, $options); 
		$dependencyOptions = $options; 
		$dependencyOptions['resetCache'] = false; 

		if(!$this->isInstallable($class)) return null; 

		$requires = $this->getRequiresForInstall($class); 
		if(count($requires)) {
			$error = '';
			$installable = false; 
			if($options['dependencies']) {
				$installable = true; 
				foreach($requires as $requiresModule) {
					if(!$this->isInstallable($requiresModule)) $installable = false;
				}
				if($installable) {
					foreach($requires as $requiresModule) {
						if(!$this->install($requiresModule, $dependencyOptions)) {
							$error = $this->_('Unable to install required module') . " - $requiresModule. "; 
							$installable = false;
							break;
						}
					}
				}
			}
			if(!$installable) {
				throw new WireException($error . "Module $class requires: " . implode(", ", $requires)); 
			}
		}
		
		$languages = $this->wire('languages');
		if($languages) $languages->setDefault();

		$pathname = $this->installable[$class];
		require_once($pathname);
		$this->setConfigPaths($class, dirname($pathname)); 

		$module = $this->newModule($class);
		$flags = 0;
		$database = $this->wire('database');
		$moduleID = 0;
		
		if($this->isSingular($module)) $flags = $flags | self::flagsSingular; 
		if($this->isAutoload($module)) $flags = $flags | self::flagsAutoload; 

		$sql = "INSERT INTO modules SET class=:class, flags=:flags, data=''";
		if($this->wire('config')->systemVersion >=7) $sql .= ", created=NOW()";
		$query = $database->prepare($sql); 
		$query->bindValue(":class", $class, PDO::PARAM_STR); 
		$query->bindValue(":flags", $flags, PDO::PARAM_INT); 
		
		try {
			if($query->execute()) $moduleID = (int) $database->lastInsertId();
		} catch(Exception $e) {
			if($languages) $languages->unsetDefault();
			$this->error($e->getMessage()); 
			return null;
		}
		
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
				if($languages) $languages->unsetDefault(); 
				$this->error("Unable to install module '$class': " . $e->getMessage()); 
				return null;
			}
		}

		$info = $this->getModuleInfo($class, array('noCache' => true)); 
	
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
				if($languages) $languages->unsetDefault(); 
				$this->message(sprintf($this->_('Added Permission: %s'), $permission->name)); 
			} catch(Exception $e) {
				if($languages) $languages->unsetDefault(); 
				$this->error(sprintf($this->_('Error adding permission: %s'), $name)); 
			}
		}

		// check if there are any modules in 'installs' that this module didn't handle installation of, and install them
		$label = $this->_('Module Auto Install');
		
		foreach($info['installs'] as $name) {
			if(!$this->isInstalled($name)) {
				try { 
					$this->install($name, $dependencyOptions); 
					$this->message("$label: $name"); 
				} catch(Exception $e) {
					$this->error("$label: $name - " . $e->getMessage()); 	
				}
			}
		}
	
		if($languages) $languages->unsetDefault();
		if($options['resetCache']) $this->clearModuleInfoCache();

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

		} else if(strpos($filename, $this->paths[0]) === 0) {
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
	 * @return bool|int
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

		$path = dirname($filename); // full path to directory, i.e. .../site/modules/ProcessHello
		$name = basename($path); // just name of directory that module is, i.e. ProcessHello
		$parentPath = dirname($path); // full path to parent directory, i.e. ../site/modules
		$backupPath = $parentPath . "/.$name"; // backup path, in case module is backed up

		// first check that we are still in the /site/modules/ (or another non core modules path)
		$inPath = false; // is module somewhere beneath /site/modules/ ?
		$inRoot = false; // is module in /site/modules/ root? i.e. /site/modules/ModuleName.module
		
		foreach($this->paths as $key => $modulesPath) {
			if($key === 0) continue; // skip core modules path
			if(strpos("$parentPath/", $modulesPath) === 0) $inPath = true; 
			if($modulesPath === $path) $inRoot = true; 
		}

		$basename = basename($basename, '.php');
		$basename = basename($basename, '.module'); 
		
		$files = array(
			"$basename.module",
			"$basename.module.php",
			"$basename.info.php",
			"$basename.info.json",
			);
		
		if($inPath) { 
			// module is in /site/modules/[ModuleName]/
			
			$numOtherModules = 0; // num modules in dir other than this one
			$numLinks = 0; // number of symbolic links
			$dirs = array("$path/"); 
			
			do {
				$dir = array_shift($dirs); 
				$this->message("Scanning: $dir", Notice::debug); 
				
				foreach(new DirectoryIterator($dir) as $file) {
					if($file->isDot()) continue;
					if($file->isLink()) {
						$numLinks++;
						continue; 
					}
					if($file->isDir()) {
						$dirs[] = $file->getPathname();
						continue; 
					}
					if(in_array($file->getBasename(), $files)) continue; // skip known files
					if(strpos($file->getBasename(), '.module') && preg_match('{(\.module|\.module.php)$}', $file->getBasename())) {
						// another module exists in this dir, so we don't want to delete that
						$numOtherModules++;
					}
					if(preg_match('{^(' . $basename . '\.[-_.a-zA-Z0-9]+)$}', $file->getBasename(), $matches)) {
						// keep track of potentially related files in case we have to delete them individually
						$files[] = $matches[1]; 
					}
				}
			} while(count($dirs)); 
			
			if(!$inRoot && !$numOtherModules && !$numLinks) {
				// the modulePath had no other modules or directories in it, so we can delete it entirely
				$success = wireRmdir($path, true); 
				if($success) {
					$this->message("Removed directory: $path", Notice::debug);
					if(is_dir($backupPath)) {
						if(wireRmdir($backupPath, true)) $this->message("Removed directory: $backupPath", Notice::debug); 
					}
					$files = array();
				} else {
					$this->error("Failed to remove directory: $path", Notice::debug); 
				}
			}
		}

		// remove module files individually 
		foreach($files as $file) {
			$file = "$path/$file";
			if(!file_exists($file)) continue;
			if(unlink($file)) {
				$this->message("Removed file: $file", Notice::debug);
			} else {
				$this->error("Unable to remove file: $file", Notice::debug);
			}
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
			$label = $this->_('Module Auto Uninstall');
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
		
		$this->resetCache();

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
	 *
	 */
	public function getModuleID($class) {
		
		$id = 0;

		if(is_object($class)) {
			if($class instanceof Module) {
				$class = $this->getModuleClass($class); 
			} else {
				// Class is not a module
				return $id; 
			}
		}

		if(isset($this->moduleIDs[$class])) {
			$id = (int) $this->moduleIDs[$class];
			
		} else foreach($this->moduleInfoCache as $key => $info) {	
			if($info['name'] == $class) {
				$id = (int) $key;
				break;
			}
		}
		
		return $id; 
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
	 * Retrieve module info from ModuleName.info.json or ModuleName.info.php
	 * 
	 * @param $moduleName
	 * @return array
	 * 
	 */
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
			if(!is_array($info) || !count($info)) $this->error("Invalid PHP module info file for $moduleName"); 
			
		} else if(is_file($fileJSON)) {
			$info = file_get_contents($fileJSON);
			$info = json_decode($info, true);
			if(!$info) {
				$info = array();
				$this->error("Invalid JSON module info file for $moduleName");
			}
		} 
		
		return $info; 
	}

	/**
	 * Retrieve module info from internal getModuleInfo function in the class
	 * 
	 * @param $module
	 * @return array
	 * 
	 */
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
			if(is_string($module) && !class_exists($module)) $this->includeModule($module);  
			if(method_exists($module, 'getModuleInfo')) {
				$info = call_user_func(array($module, 'getModuleInfo'));
			}
		}
		
		return $info; 
	}
	
	/**
	 * Pull module info directly from the module file's getModuleInfo without letting PHP parse it
	 * 
	 * Useful for getting module info from modules that extend another module not already on the file system.
	 *
	 * @param $className
	 * @return array Only includes module info specified in the module file itself.
	 *
	 */
	protected function getModuleInfoInternalSafe($className) {
		// future addition
		// load file, preg_split by /^\s*(public|private|protected)[^;{]+function\s*([^)]*)[^{]*{/
		// isolate the one that starts has getModuleInfo in matches[1]
		// parse data from matches[2]
	}

	/**
	 * Retrieve module info for system properties: PHP or ProcessWire
	 * 
	 * @param $moduleName
	 * @return array
	 * 
	 */
	protected function getModuleInfoSystem($moduleName) {

		$info = array();
		if($moduleName === 'PHP') {
			$info['id'] = 0; 
			$info['name'] = $moduleName;
			$info['title'] = $moduleName;
			$info['version'] = PHP_VERSION;
			return $info;

		} else if($moduleName === 'ProcessWire') {
			$info['id'] = 0; 
			$info['name'] = $moduleName;
			$info['title'] = $moduleName;
			$info['version'] = $this->wire('config')->version;
			$info['requiresVersions'] = array(
				'PHP' => array('>=', '5.3.8'),
				'PHP_modules' => array('=', 'PDO,mysqli'),
				'Apache_modules' => array('=', 'mod_rewrite'),
				'MySQL' => array('>=', '5.0.15'),
			);
			$info['requires'] = array_keys($info['requiresVersions']);
		} else {
			return array();
		}
		
		$info['versionStr'] = $info['version'];
		
		return $info;

	}

	/**
	 * Returns the standard array of information for a Module
	 *
	 * @param string|Module|int $module May be class name, module instance, or module ID
	 * @param array $options Optional options to modify behavior of what gets returned
	 *  - verbose: Makes the info also include summary, author, file, core, configurable, href, versionStr (they will be usually blank without this option specified)
	 * 	- noCache: prevents use of cache to retrieve the module info
	 *  - noInclude: prevents include() of the module file, applicable only if it hasn't already been included
	 * @return array
	 * @throws WireException when a module exists but has no means of returning module info
	 * @todo move all getModuleInfo methods to their own ModuleInfo class and break this method down further. 
	 *	
	 */
	public function getModuleInfo($module, array $options = array()) {
		
		if(!isset($options['verbose'])) $options['verbose'] = false; 
		if(!isset($options['noCache'])) $options['noCache'] = false;
		
		$info = array();
		$moduleName = $module;
		$moduleID = (string) $this->getModuleID($module); // typecast to string for cache
		$fromCache = false;  // was the data loaded from cache?
		
		static $infoTemplate = array(
			// module database ID
			'id' => 0, 
			// module class name 
			'name' => '',
			// module title
			'title' => '',
			// module version
			'version' => 0,
			// module version (always formatted string)
			'versionStr' => '0.0.0', 
			// who authored the module? (included in 'verbose' mode only)
			'author' => '',
			// summary of what this module does (included in 'verbose' mode only)
			'summary' => '',
			// URL to module details (included in 'verbose' mode only)
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
			// permissions automatically installed/uninstalled with this module. array of ('permission-name' => 'Description')
			'permissions' => array(),
			// true if module is autoload, false if not. null=unknown
			'autoload' => null,
			// true if module is singular, false if not. null=unknown
			'singular' => null,
			// unix-timestamp date/time module added to system (for uninstalled modules, it is the file date)
			'created' => 0, 
			// is the module currently installed? (boolean)
			'installed' => false, 
			// verbose mode only: this is set to the module filename (from PW installation root), false when it can't be found, null when it hasn't been determined
			'file' => null, 
			// verbose mode only: this is set to true when the module is a core module, false when it's not, and null when it's not determined
			'core' => null, 
			// verbose mode only: this is set to true when the module is configurable, false when it's not, and null when it's not determined
			'configurable' => null, 
			);
	
		if($module instanceof Module) {
			// module is an instance
			$moduleName = method_exists($module, 'className') ? $module->className() : get_class($module); 
			// return from cache if available
			if(empty($options['noCache']) && !empty($this->moduleInfoCache[$moduleID])) {
				$info = $this->moduleInfoCache[$moduleID]; 
				$fromCache = true; 
			} else {
				$info = $this->getModuleInfoExternal($moduleName); 
				if(!count($info)) $info = $this->getModuleInfoInternal($module); 
			}
			
		} else if(in_array($module, array('PHP', 'ProcessWire'))) {
			// module is a system 
			$info = $this->getModuleInfoSystem($module); 
			return array_merge($infoTemplate, $info);
			
		} else {
			
			// module is a class name or ID
			if(ctype_digit("$module")) $module = $this->getModuleClass($module);
			
			// return from cache if available
			if(empty($options['noCache']) && !empty($this->moduleInfoCache[$moduleID])) {
				$info = $this->moduleInfoCache[$moduleID];
				$fromCache = true; 
				
			} else if(empty($options['noCache']) && $moduleID == 0) {
				// uninstalled module
				if(!count($this->moduleInfoCacheUninstalled)) $this->loadModuleInfoCacheVerbose(true);
				if(isset($this->moduleInfoCacheUninstalled[$moduleName])) {
					$info = $this->moduleInfoCacheUninstalled[$moduleName];
					$fromCache = true; 
				}
			}
			
			if(!$fromCache) { 
				if(class_exists($moduleName, false)) {
					// module is already in memory, check external first, then internal
					$info = $this->getModuleInfoExternal($moduleName);
					if(!count($info)) $info = $this->getModuleInfoInternal($moduleName);
					
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
		}
		
		if(!$fromCache && !count($info)) {
			$info = $infoTemplate; 
			$info['title'] = $module;
			$info['summary'] = 'Inactive';
			$info['error'] = 'Unable to locate module';
			return $info;
		}
		
		$info = array_merge($infoTemplate, $info); 
		$info['id'] = (int) $moduleID;

		if($fromCache) {
			
			if($options['verbose']) { 
				if(empty($this->moduleInfoCacheVerbose)) $this->loadModuleInfoCacheVerbose();
				if(!empty($this->moduleInfoCacheVerbose[$moduleID])) {
					$info = array_merge($info, $this->moduleInfoCacheVerbose[$moduleID]); 
				}
			}
		
		} else {
			
			// we skip everything else when module comes from cache since we can safely assume the checks below 
			// are already accounted for in the cached module info
			
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

			// what does it install?
			if(!is_array($info['installs'])) {
				$info['installs'] = str_replace(' ', '', $info['installs']); // remove whitespace
				if(strpos($info['installs'], ',') !== false) $info['installs'] = explode(',', $info['installs']); 
					else $info['installs'] = array($info['installs']); 
			}
	
			// misc
			$info['versionStr'] = $this->formatVersion($info['version']); // versionStr
			$info['name'] = $moduleName; // module name
			$info['file'] = $this->getModuleFile($moduleName, false); // module file	
			if($info['file']) $info['core'] = strpos($info['file'], '/wire/modules/') !== false; // is it core?
			
			// module configurable?
			$interfaces = @class_implements($moduleName, false); 
			$info['configurable'] = is_array($interfaces) && isset($interfaces['ConfigurableModule']); 
			
			// created date
			if(isset($this->createdDates[$moduleID])) $info['created'] = strtotime($this->createdDates[$moduleID]);
			$info['installed'] = isset($this->installable[$moduleName]) ? false : true;
			if(!$info['installed'] && !$info['created'] && isset($this->installable[$moduleName])) {
				// uninstalled modules get their created date from the file or dir that they are in (whichever is newer)
				$pathname = $this->installable[$moduleName];
				$filemtime = (int) filemtime($pathname);
				$dirname = dirname($pathname);
				$dirmtime = substr($dirname, -7) == 'modules' || strpos($dirname, $this->paths[0]) !== false ? 0 : (int) filemtime($dirname);
				$info['created'] = $dirmtime > $filemtime ? $dirmtime : $filemtime;
			}
			
			if(!$options['verbose']) foreach($this->moduleInfoVerboseKeys as $key) unset($info[$key]); 
		} 
		
		if(empty($info['created']) && isset($this->createdDates[$moduleID])) {
			$info['created'] = strtotime($this->createdDates[$moduleID]);
		}
	
		return $info;
	}

	/**
	 * Returns the verbose array of information for a Module
	 *
	 * @param string|Module|int $module May be class name, module instance, or module ID
	 * @param array $options Optional options to modify behavior of what gets returned
	 * 	- noCache: prevents use of cache to retrieve the module info
	 *  - noInclude: prevents include() of the module file, applicable only if it hasn't already been included
	 * @return array
	 * @throws WireException when a module exists but has no means of returning module info
	 *
	 */
	public function getModuleInfoVerbose($module, array $options = array()) {
		$options['verbose'] = true; 
		return $this->getModuleInfo($module, $options); 
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
	 * Get the path + filename for this module
	 * 
	 * @param string|Module $className Module class name or object instance
	 * @param bool $getURL If true, will return it as a URL from PW root install path (for shorter display purposes)
	 * @return bool|string Returns string of module file, or false on failure. 
	 * 
	 */
	public function getModuleFile($className, $getURL = false) {
		
		$file = false;
	
		// first see it's an object, and if we can get the file from the object
		if(is_object($className)) {
			$module = $className; 
			if($module instanceof ModulePlaceholder) $file = $module->file; 
			$className = $module->className();
		} 
	
		// next see if we've already got the module filename cached locally
		if(!$file && isset($this->installable[$className])) {
			$file = $this->installable[$className]; 
		} 
		
		if(!$file) {
			// next see if we can determine it from already stored paths
			$path = $this->wire('config')->paths->$className; 
			if(file_exists($path)) {
				$file = "$path$className.module";
				if(!file_exists($file)) {
					$file = "$path$className.module.php";
					if(!file_exists($file)) $file = false;
				}
			}
		}

		if(!$file) {
			// if the above two failed, try to get it from Reflection
			try {
				$reflector = new ReflectionClass($className);
				$file = $reflector->getFileName();
				
			} catch(Exception $e) {
				$file = false;
			}
		}

		if($file && DIRECTORY_SEPARATOR != '/') $file = str_replace(DIRECTORY_SEPARATOR, '/', $file); 
		if($getURL) $file = str_replace($this->wire('config')->paths->root, '/', $file); 
		
		// $this->message("getModuleFile($className)"); 
		
		return $file;
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
	 * @param Module|string $module Module instance or class name
	 * @return bool 
 	 *
	 */
	public function isSingular($module) {
		$info = $this->getModuleInfo($module); 
		if(isset($info['singular']) && $info['singular'] !== null) return $info['singular'];
		if(!is_object($module)) {
			// singular status can't be determined if module not installed and not specified in moduleInfo
			if(isset($this->installable[$module])) return null;
			$this->includeModule($module); 
		}
		if(method_exists($module, 'isSingular')) return $module->isSingular();
		return false;
	}

	/**
	 * Is the given module Autoload (automatically loaded at runtime)?
	 *
	 * @param Module|string $module Module instance or class name
	 * @return bool|string|null Returns string "conditional" if conditional autoload, true if autoload, or false if not. Or null if unavailable. 
 	 *
	 */
	public function isAutoload($module) {
		
		$info = $this->getModuleInfo($module); 
		
		if(isset($info['autoload']) && $info['autoload'] !== null) {
			// if autoload is a string (selector) or callable, then we flag it as autoload
			if(is_string($info['autoload']) || is_callable($info['autoload'])) return "conditional"; 
			return $info['autoload'];
		}
		
		if(!is_object($module)) {
			if(isset($this->installable[$module])) {
				// we are not going to be able to determine if this is autoload or not
				return null;
			}
			$this->includeModule($module); 
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
		foreach($this->paths as $path) $this->findModuleFiles($path, false); 
		foreach($this->paths as $path) $this->load($path); 
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
	 * Default behavior is to return all listed requirements, whether they are currently met by
	 * the environment or not. Specify TRUE for the 2nd argument to return only requirements
	 * that are not currently met. 
	 * 
	 * @param string $class
	 * @param bool $onlyMissing Set to true to return only required modules/versions that aren't 
	 * 	yet installed or don't have the right version. It excludes those that the class says it 
	 * 	will install (via 'installs' property of getModuleInfo)
	 * @param null|bool $versions Set to true to always include versions in the returned requirements list. 
	 * 	Set to null to always exclude versions in requirements list (so only module class names will be there).
	 * 	Set to false (which is the default) to include versions only when version is the dependency issue.
	 * 	Note versions are already included when the installed version is not adequate.
	 * @return array of strings each with ModuleName Operator Version, i.e. "ModuleName>=1.0.0"
	 *
	 */
	public function getRequires($class, $onlyMissing = false, $versions = false) {
		
		$class = $this->getModuleClass($class); 
		$info = $this->getModuleInfo($class); 
		$requires = $info['requires']; 

		// quick exit if arguments permit it 
		if(!$onlyMissing) {
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
				$i = $this->getModuleInfo($requiresClass, array('noCache' => true)); 
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
				
			} else if(is_null($versions)) {
				// request is for no versions to be included (just class names)
				$requires[$key] = $requiresClass; 

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
	 * @param int|string $currentVersion May be a number like 123 or a formatted version like 1.2.3
	 * @param int|string $requiredVersion May be a number like 123 or a formatted version like 1.2.3
	 * @param string $operator
	 * @return bool
	 *
	 */
	public function versionCompare($currentVersion, $requiredVersion, $operator) {
		
		if(ctype_digit("$currentVersion") && ctype_digit("$requiredVersion")) {
			// integer comparison is ok
			$currentVersion = (int) $currentVersion;
			$requiredVersion = (int) $requiredVersion;
			$result = false;
			
			switch($operator) {
				case '=': $result = ($currentVersion == $requiredVersion); break;
				case '>': $result = ($currentVersion > $requiredVersion); break;
				case '<': $result = ($currentVersion < $requiredVersion); break;
				case '>=': $result = ($currentVersion >= $requiredVersion); break;
				case '<=': $result = ($currentVersion <= $requiredVersion); break;
				case '!=': $result = ($currentVersion != $requiredVersion); break;
			}
			return $result;
		}

		// if either version has no periods or only one, like "1.2" then format it to stanard: "1.2.0"
		if(substr_count($currentVersion, '.') < 2) $currentVersion = $this->formatVersion($currentVersion);
		if(substr_count($requiredVersion, '.') < 2) $requiredVersion = $this->formatVersion($requiredVersion); 
		
		return version_compare($currentVersion, $requiredVersion, $operator); 
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
	 * Module class names in returned array include operator and version in the string. 
	 * 
	 * @param string $class
	 * @return array()
	 *
	 */
	public function getRequiresForUninstall($class) {
		return $this->getRequiredBy($class, false, true); 
	}
	
	/**
	 * Return array of dependency errors for given module name
	 *
	 * @param $moduleName
	 * @return array If no errors, array will be blank. If errors, array will be of strings (error messages)
	 *
	 */
	public function getDependencyErrors($moduleName) {

		$moduleName = $this->getModuleClass($moduleName);
		$info = $this->getModuleInfo($moduleName);
		$errors = array();

		if(empty($info['requires'])) return $errors;

		foreach($info['requires'] as $requiresName) {
			$error = '';

			if(!$this->isInstalled($requiresName)) {
				$error = $requiresName;

			} else if(!empty($info['requiresVersions'][$requiresName])) {
				list($operator, $version) = $info['requiresVersions'][$requiresName];
				$info2 = $this->getModuleInfo($requiresName); 
				$requiresVersion = $info2['version'];
				if(!empty($version) && !$this->versionCompare($requiresVersion, $version, $operator)) {
					$error = "$requiresName $operator $version";
				}
			}

			if($error) $errors[] = sprintf($this->_('Failed module dependency: %s requires %s'), $moduleName, $error);
		}

		return $errors;
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
			// version contains only digits
			// make sure version is at least 3 characters in length, left padded with 0s
			$len = strlen($version); 

			if($len < 3) {
				$version = str_pad($version, 3, "0", STR_PAD_LEFT);

			} else if($len > 3) {
				// they really need to use a string for this type of version, 
				// as we can't really guess, but we'll try, converting 1234 to 1.2.34
			}

			// $version = preg_replace('/(\d)(?=\d)/', '$1.', $version); 
			$version = 
				substr($version, 0, 1) . '.' . 
				substr($version, 1, 1) . '.' . 
				substr($version, 2); 
			
		} else if(strpos($version, '.') !== false) {
			// version is a formatted string
			if(strpos($version, '.') == strrpos($version, '.')) {
				// only 1 period, like: 2.0, convert that to 2.0.0
				if(preg_match('/^\d\.\d$/', $version)) $version .= ".0";
			}
			
		} else {
			// invalid version?
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
			// if module class name keys in use (i.e. ProcessModule) it's an older version of 
			// module info cache, so we skip over it to force its re-creation
			if(is_array($data) && !isset($data['ProcessModule'])) $this->moduleInfoCache = $data; 
			return true;
		}
		return false;
	}
	
	/**
	 * Load the module information cache (verbose info: summary, author, href, file, core)
	 *
	 * @param bool $uninstalled If true, it will load the uninstalled verbose cache.
	 * @return bool
	 *
	 */
	protected function loadModuleInfoCacheVerbose($uninstalled = false) {
		$name = $uninstalled ? self::moduleInfoCacheUninstalledName : self::moduleInfoCacheVerboseName;
		$data = $this->wire('cache')->get($name);
		if($data) {
			$data = json_decode($data, true);
			if(is_array($data)) {
				if($uninstalled) $this->moduleInfoCacheUninstalled = $data; 
					else $this->moduleInfoCacheVerbose = $data;
			}
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
		$this->wire('cache')->delete(self::moduleInfoCacheVerboseName);
		$this->wire('cache')->delete(self::moduleInfoCacheUninstalledName);
		$this->moduleInfoCache = array();
		$this->moduleInfoCacheVerbose = array();
		$this->moduleInfoCacheUninstalled = array();
		$this->saveModuleInfoCache();
	}

	/**
	 * Save the module information cache
	 * 
	 */
	protected function saveModuleInfoCache() {
		
		$this->moduleInfoCache = array();
		$this->moduleInfoCacheVerbose = array();
		$this->moduleInfoCacheUninstalled = array();
		
		$user = $this->wire('user'); 
		$languages = $this->wire('languages'); 
		
		if($languages) {
			// switch to default language to prevent caching of translated title/summary data
			$language = $user->language; 
			try { 
				if($language && $language->id && !$language->isDefault()) $user->language = $languages->getDefault(); // save
			} catch(Exception $e) {
				$this->error($e->getMessage());
			}
		}
	
		foreach(array(true, false) as $installed) { 
			
			$items = $installed ? $this : array_keys($this->installable);	
			
			foreach($items as $module) {
				
				$class = is_object($module) ? $module->className() : $module;
				$info = $this->getModuleInfo($class, array('noCache' => true, 'verbose' => true));
				if(!empty($info['error'])) continue;
				$moduleID = (int) $info['id']; // note ID is always 0 for uninstalled modules
				if(!$moduleID && $installed) continue; 
				unset($info['id']); // no need to double store this property since it is already the array key
				
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
			
				if($installed) { 
					
					$verboseKeys = $this->moduleInfoVerboseKeys; 
					$verboseInfo = array();
		
					foreach($verboseKeys as $key) {
						if(!empty($info[$key])) $verboseInfo[$key] = $info[$key]; 
						unset($info[$key]); // remove from regular moduleInfo 
					}
					
					$this->moduleInfoCache[$moduleID] = $info; 
					$this->moduleInfoCacheVerbose[$moduleID] = $verboseInfo; 
					
				} else {
					
					$this->moduleInfoCacheUninstalled[$class] = $info; 
				}
			}
		}
		
		$this->wire('cache')->save(self::moduleInfoCacheName, json_encode($this->moduleInfoCache), WireCache::expireNever);
		$this->wire('cache')->save(self::moduleInfoCacheVerboseName, json_encode($this->moduleInfoCacheVerbose), WireCache::expireNever);
		$this->wire('cache')->save(self::moduleInfoCacheUninstalledName, json_encode($this->moduleInfoCacheUninstalled), WireCache::expireNever); 
		
		if($languages && $language) $user->language = $language; // restore
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
		Debug::removeTimer($timerKey);
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

	/**
	 * Substitute one module for another, to be used only when $moduleName doesn't exist. 
	 *
	 * @param string $moduleName Module class name that may need a substitute
	 * @param string $substituteName Module class name you want to substitute when $moduleName isn't found.
	 * 	Specify null to remove substitute.
	 *
	 */
	public function setSubstitute($moduleName, $substituteName = null) {
		if(is_null($substituteName)) {
			unset($this->substitues[$moduleName]);
		} else {
			$this->substitutes[$moduleName] = $substituteName; 
		}
	}

	/**
	 * Substitute modules for other modules, to be used only when $moduleName doesn't exist.
	 * 
	 * This appends existing entries rather than replacing them. 
	 *
	 * @param array $substitutes Array of module name => substitute module name
	 *
	 */
	public function setSubstitutes(array $substitutes) {
		$this->substitutes = array_merge($this->substitutes, $substitutes); 
	}

}

