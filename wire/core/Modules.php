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
 * Copyright (C) 2015 by Ryan Cramer 
 * This file licensed under Mozilla Public License v2.0 http://mozilla.org/MPL/2.0/
 * 
 * https://processwire.com
 *
 */

class Modules extends WireArray {
	
	/**
	 * Whether or not module debug mode is active
	 *
	 */
	protected $debug = false;

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
	 * Flag indicating the module has more than one copy of it on the file system. 
	 * 
	 */
	const flagsDuplicate = 4;

	/**
	 * When combined with flagsAutoload, indicates that the autoload is conditional 
	 * 
	 */
	const flagsConditional = 8;

	/**
	 * When combined with flagsAutoload, indicates that the module's autoload state is temporarily disabled
	 * 
	 */
	const flagsDisabled = 16; 

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
	 * Cache name for module version change cache
	 * 
	 */
	const moduleLastVersionsCacheName = 'ModulesVersions.info';

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
	 * Values are integer 1 for modules that have config data but data is not yet loaded.
	 * Values are an array for modules have have config data and has been loaded. 
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
	 * Becomes an array if debug mode is on
	 *
	 */
	protected $debugLog = array();

	/**
	 * Array of moduleName => condition
	 * 
	 * Condition can be either an anonymous function or a selector string to be evaluated at ready().
	 *
	 */
	protected $conditionalAutoloadModules = array();

	/**
	 * Cache of module information
	 *
	 */
	protected $moduleInfoCache = array();
	
	/**
	 * Cache of module information (verbose text) including: summary, author, href, file, core
	 *
	 */
	protected $moduleInfoCacheVerbose = array();
	
	/**
	 * Cache of uninstalled module information (verbose for uninstalled) including: summary, author, href, file, core
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
	 * Last known versions of modules, for version change tracking
	 *
	 * @var array of ModuleName (string) => last known version (integer|string)
	 *
	 */
	protected $modulesLastVersions = array();

	/**
	 * Array of module ID => flags (int)
	 * 
	 * @var array
	 * 
	 */
	protected $moduleFlags = array();
	
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
	 * Instance of ModulesDuplicates
	 * 
	 * @var ModulesDuplicates
	 * 
	 */	
	protected $duplicates; 

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
		'versionStr',
		'permissions',
		'page',
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
	 * Get the ModulesDuplicates instance
	 * 
	 * @return ModulesDuplicates
	 * 
	 */
	public function duplicates() {
		if(is_null($this->duplicates)) $this->duplicates = new ModulesDuplicates();
		return $this->duplicates; 
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
	
		if($this->debug) {
			$debugKey = $this->debugTimerStart("triggerInit$level");
			$this->message("triggerInit(level=$level)"); 
		}
		
		$queue = array();
		if(is_null($modules)) $modules = $this;

		foreach($modules as $class => $module) {
		
			if($module instanceof ModulePlaceholder) {
				// skip modules that aren't autoload and those that are conditional autoload
				if(!$module->autoload) continue;
				if(isset($this->conditionalAutoloadModules[$class])) continue;
			}
			
			if($this->debug) $debugKey2 = $this->debugTimerStart("triggerInit$level($class)"); 
			
			$info = $this->getModuleInfo($module); 
			$skip = false;

			// module requires other modules
			foreach($info['requires'] as $requiresClass) {
				if(in_array($requiresClass, $completed)) continue; 
				$dependencyInfo = $this->getModuleInfo($requiresClass);
				if(empty($dependencyInfo['autoload'])) {
					// if dependency isn't an autoload one, there's no point in waiting for it
					if($this->debug) $this->warning("Autoload module '$module' requires a non-autoload module '$requiresClass'");
					continue;
				} else if(isset($this->conditionalAutoloadModules[$requiresClass])) {
					// autoload module requires another autoload module that may or may not load
					if($this->debug) $this->warning("Autoload module '$module' requires a conditionally autoloaded module '$requiresClass'");
					continue; 
				}
				// dependency is autoload and required by this module, so queue this module to init later
				$queue[$class] = $module;
				$skip = true;
				break;
			}
			
			if(!$skip) {
				if($info['autoload'] !== false) {
					if($info['autoload'] === true || $this->isAutoload($module)) {
						$this->initModule($module);
					}
				}
				$completed[] = $class;
			}
			
			if($this->debug) $this->debugTimerStop($debugKey2); 
		}

		// if there is a dependency queue, go recursive till the queue is completed
		if(count($queue) && $level < 3) {
			$this->triggerInit($queue, $completed, $level + 1);
		}

		$this->initialized = true;
		
		if($this->debug) if($debugKey) $this->debugTimerStop($debugKey);
		
		if(!$level && (empty($this->moduleInfoCache))) { // || empty($this->moduleInfoCacheVerbose))) {
			if($this->debug) $this->message("saveModuleInfoCache from triggerInit"); 
			$this->saveModuleInfoCache();
		}
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
		
		if($this->debug) {
			static $n = 0;
			$this->message("initModule (" . (++$n) . "): $module"); 
		}
		
		// if the module is configurable, then load its config data
		// and set values for each before initializing the module
		$this->setModuleConfigData($module);
		
		$className = get_class($module);
		$moduleID = isset($this->moduleIDs[$className]) ? $this->moduleIDs[$className] : 0;
		if($moduleID && isset($this->modulesLastVersions[$moduleID])) {
			$this->checkModuleVersion($module);
		}
		
		if(method_exists($module, 'init')) {
			
			if($this->debug) {
				$className = get_class($module); 
				$debugKey = $this->debugTimerStart("initModule($className)"); 
			}
		
			$module->init();
			
			if($this->debug) {
				$this->debugTimerStop($debugKey);
			}
		}
		
		// if module is autoload (assumed here) and singular, then
		// we no longer need the module's config data, so remove it
		if($clearSettings && $this->isSingular($module)) {
			if(!$moduleID) $moduleID = $this->getModuleID($module);
			if(isset($this->configData[$moduleID])) $this->configData[$moduleID] = 1;
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
			if($this->debug) {
				$this->debugTimerStop($debugKey);
				static $n = 0;
				$this->message("readyModule (" . (++$n) . "): $module");
			}
		}
	}

	/**
	 * Init conditional autoload modules, if conditions allow
	 * 
	 * @return array of skipped module names
	 * 
	 */
	protected function triggerConditionalAutoload() {
		
		// conditional autoload modules that are skipped (className => 1)
		$skipped = array();

		// init conditional autoload modules, now that $page is known
		foreach($this->conditionalAutoloadModules as $className => $func) {

			if($this->debug) {
				$moduleID = $this->getModuleID($className);
				$flags = $this->moduleFlags[$moduleID];
				$this->message("Conditional autoload: $className (flags=$flags, condition=" . (is_string($func) ? $func : 'func') . ")");
			}

			$load = true;

			if(is_string($func)) {
				// selector string
				if(!$this->wire('page')->is($func)) $load = false;
			} else {
				// anonymous function
				if(!is_callable($func)) $load = false;
					else if(!$func()) $load = false;
			}

			if($load) {
				$module = $this->newModule($className);
				$this->set($className, $module);
				$this->initModule($module);
				if($this->debug) $this->message("Conditional autoload: $className LOADED");

			} else {
				$skipped[$className] = $className;
				if($this->debug) $this->message("Conditional autoload: $className SKIPPED");
			}
		}
		
		// clear this out since we don't need it anymore
		$this->conditionalAutoloadModules = array();
		
		return $skipped;
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
		
		$skipped = $this->triggerConditionalAutoload();
		
		// trigger ready method on all applicable modules
		foreach($this as $module) {
			
			if($module instanceof ModulePlaceholder) continue;
			
			// $info = $this->getModuleInfo($module); 
			// if($info['autoload'] === false) continue; 
			// if(!$this->isAutoload($module)) continue; 
			
			$class = $this->getModuleClass($module); 
			if(isset($skipped[$class])) continue; 
			
			$id = $this->moduleIDs[$class];
			if(!($this->moduleFlags[$id] & self::flagsAutoload)) continue;
			
			if(!method_exists($module, 'ready')) continue;
			
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
		$query = $database->prepare("SELECT * FROM modules ORDER BY class", "modules.loadModulesTable()"); // QA
		$query->execute();
		
		while($row = $query->fetch(PDO::FETCH_ASSOC)) {
			
			$moduleID = (int) $row['id'];
			$flags = (int) $row['flags'];
			$class = $row['class'];
			$this->moduleIDs[$class] = $moduleID;
			$this->moduleFlags[$moduleID] = $flags;
			$loadSettings = ($flags & self::flagsAutoload) || ($flags & self::flagsDuplicate) || ($class == 'SystemUpdater');
			
			if($loadSettings) {
				// preload config data for autoload modules since we'll need it again very soon
				$data = strlen($row['data']) ? wireDecodeJSON($row['data']) : array();
				$this->configData[$moduleID] = $data;
				// populate information about duplicates, if applicable
				if($flags & self::flagsDuplicate) $this->duplicates()->addFromConfigData($class, $data); 
				
			} else if(!empty($row['data'])) {
				// indicate that it has config data, but not yet loaded
				$this->configData[$moduleID] = 1; 
			}
			
			if(isset($row['created']) && $row['created'] != '0000-00-00 00:00:00') {
				$this->createdDates[$moduleID] = $row['created']; 
			}
			
			unset($row['data']); // info we don't want stored in modulesTableCache
			$this->modulesTableCache[$class] = $row;
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
				continue;
			}

			// module was successfully loaded
			$modulesLoaded[$moduleName] = 1;
			$loadedNames = array($moduleName);

			// now determine if this module had any other modules waiting on it as a dependency
			while($moduleName = array_shift($loadedNames)) {
				// iternate through delayed modules that require this one
				if(empty($modulesRequired[$moduleName])) continue; 
				
				foreach($modulesRequired[$moduleName] as $delayedName => $delayedPathName) {
					$loadNow = true;
					if(isset($modulesDelayed[$delayedName])) {
						foreach($modulesDelayed[$delayedName] as $requiresModuleName) {
							if(!isset($modulesLoaded[$requiresModuleName])) {
								$loadNow = false;
							}
						}
					}
					if(!$loadNow) continue; 
					// all conditions satisified to load delayed module
					unset($modulesDelayed[$delayedName], $modulesRequired[$moduleName][$delayedName]);
					$unused = array();
					$loadedName = $this->loadModule($path, $delayedPathName, $unused, $installed);
					if(!$loadedName) continue; 
					$modulesLoaded[$loadedName] = 1; 
					$loadedNames[] = $loadedName;
				}
			}
		}

		if(count($modulesDelayed)) foreach($modulesDelayed as $moduleName => $requiredNames) {
			$this->error("Module '$moduleName' dependency not fulfilled for: " . implode(', ', $requiredNames), Notice::debug);
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
		$duplicates = $this->duplicates();
	
		// check if module has duplicate files, where one to use has already been specified to use first
		$currentFile = $duplicates->getCurrent($basename); // returns the current file in use, if more than one
		if($currentFile) {
			// there is a duplicate file in use
			$file = rtrim($this->wire('config')->paths->root, '/') . $currentFile;
			if(file_exists($file) && $pathname != $file) {
				// file in use is different from the file we are looking at
				// check if this is a new/yet unknown duplicate
				if(!$duplicates->hasDuplicate($basename, $pathname)) {
					// new duplicate
					$duplicates->recordDuplicate($basename, $pathname, $file, $installed);
				}
				return '';
			}
		}

		// check if module has already been loaded, or maybe we've got duplicates
		if(class_exists($basename, false)) { 
			$module = parent::get($basename);
			$dir = rtrim($this->wire('config')->paths->$basename, '/');
			if($module && $dir && $dirname != $dir) {
				$duplicates->recordDuplicate($basename, $pathname, "$dir/$filename", $installed);
				return '';
			}
			if($module) return $basename;
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
			
			// this is an Autoload module. 
			// include the module and instantiate it but don't init() it,
			// because it will be done by Modules::init()
			$moduleInfo = $this->getModuleInfo($basename);

			// determine if module has dependencies that are not yet met
			if(count($moduleInfo['requires'])) {
				foreach($moduleInfo['requires'] as $requiresClass) {
					if(!class_exists($requiresClass, false)) {
						$requiresInfo = $this->getModuleInfo($requiresClass); 
						if(!empty($requiresInfo['error']) 
							|| $requiresInfo['autoload'] === true 
							|| !$this->isInstalled($requiresClass)) {	
							// we only handle autoload===true since load() only instantiates other autoload===true modules
							$requires[] = $requiresClass;
						}
					}
				}
				if(count($requires)) {
					// module has unmet requirements
					return $basename;
				}
			}

			// if not defined in getModuleInfo, then we'll accept the database flag as enough proof
			// since the module may have defined it via an isAutoload() function
			if(!isset($moduleInfo['autoload'])) $moduleInfo['autoload'] = true;
			$autoload = $moduleInfo['autoload'];
			if($autoload === 'function') {
				// function is stored by the moduleInfo cache to indicate we need to call a dynamic function specified with the module itself
				$i = $this->getModuleInfoExternal($basename); 
				if(empty($i)) {
					include_once($pathname);
					$i = $basename::getModuleInfo();
				}
				$autoload = isset($i['autoload']) ? $i['autoload'] : true;
				unset($i);
			}
			// check for conditional autoload
			if(!is_bool($autoload) && (is_string($autoload) || is_callable($autoload)) && !($info['flags'] & self::flagsDisabled)) {
				// anonymous function or selector string
				$this->conditionalAutoloadModules[$basename] = $autoload;
				$this->moduleIDs[$basename] = $info['id'];
				$autoload = true;
			} else if($autoload) {
				include_once($pathname);
				if(!($info['flags'] & self::flagsDisabled)) {
					$module = $this->newModule($basename);
				}
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
		static $callNum = 0;

		$callNum++;
		$config = $this->wire('config');
		$cache = $this->wire('cache'); 

		if($level == 0) {
			$startPath = $path;
			$cacheName = "Modules." . str_replace($config->paths->root, '', $path);
			if($readCache && $cache) {
				$cacheContents = $cache->get($cacheName); 
				if($cacheContents !== null) {
					if(empty($cacheContents) && $callNum === 1) {
						// don't accept empty cache for first path (/wire/modules/)
					} else {
						$cacheContents = explode("\n", $cacheContents);
						return $cacheContents;
					}
				}
			}
		}

		$files = array();
		
		try {
			$dir = new DirectoryIterator($path); 
		} catch(Exception $e) {
			$this->trackException($e, false, true);
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
			if(!strpos($filename, '.module')) continue; 
			if(substr($filename, -7) !== '.module' && substr($filename, -11) !== '.module.php') {
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
	 * @return Module|Inputfield|Fieldtype|Process|Textformatter|null
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
			if($this->isInstallable($key) && empty($options['noInstall'])) {
				// module is on file system and may be installed, no need to substitute
			} else {
				$module = $this->getSubstituteModule($key, $options);
				if($module) return $module; // returned module is ready to use
			}
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
			if(!$this->hasPermission($module, $this->wire('user'), $this->wire('page'))) {
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
	 * Check if user has permission for given module
	 * 
	 * @param string|object $moduleName
	 * @param User $user Optionally specify different user to consider than current.
	 * @param Page $page Optionally specify different page to consider than current.
	 * @param bool $strict If module specifies no permission settings, assume no permission.
	 * 	Default (false) is to assume permission when module doesn't say anything about it. 
	 * 	Process modules (for instance) generally assume no permission when it isn't specifically defined 
	 * 	(though this method doesn't get involved in that, leaving you to specify $strict instead). 
	 * 
	 * @return bool
	 * 
	 */
	public function hasPermission($moduleName, User $user = null, Page $page = null, $strict = false) {

		$info = $this->getModuleInfo($moduleName);
		if(empty($info['permission']) && empty($info['permissionMethod'])) return $strict ? false : true;
		
		if(is_null($user)) $user = $this->wire('user'); 	
		if($user && $user->isSuperuser()) return true; 
		if(is_object($moduleName)) $moduleName = $moduleName->className();
		
		if(!empty($info['permission'])) {
			if(!$user->hasPermission($info['permission'])) return false;
		}
		
		if(!empty($info['permissionMethod'])) {
			// module specifies a static method to call for permission
			if(is_null($page)) $page = $this->wire('page');
			$data = array(
				'wire' => $this->wire(),
				'page' => $page, 
				'user' => $user, 
				'info' => $info, 
			);
			$method = $info['permissionMethod'];
			$this->includeModule($moduleName); 
			return $moduleName::$method($data); 
		}
		
		return true; 
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
	 * Find modules matching the given prefix 
	 * 
	 * @param string $prefix Specify prefix, i.e. Process, Fieldtype, Inputfield, etc.
	 * @param bool $instantiate Specify true to return Module instances, or false to return class names (default=false)
	 * @return array of module class names or Module objects. In either case, array indexes are class names.
	 * 
	 */
	public function findByPrefix($prefix, $instantiate = false) {
		$results = array();
		foreach($this as $key => $value) {
			$className = $value->className();
			if(strpos($className, $prefix) !== 0) continue;
			if($instantiate) {
				$results[$className] = $this->get($className);
			} else {
				$results[$className] = $className;
			}
		}
		return $results;
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
		$query = $database->prepare($sql, "modules.install($class)"); 
		$query->bindValue(":class", $class, PDO::PARAM_STR); 
		$query->bindValue(":flags", $flags, PDO::PARAM_INT); 
		
		try {
			if($query->execute()) $moduleID = (int) $database->lastInsertId();
		} catch(Exception $e) {
			if($languages) $languages->unsetDefault();
			$this->trackException($e, false, true); 
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
				$error = "Unable to install module '$class': " . $e->getMessage();
				$ee = null;
				try {
					$query = $database->prepare('DELETE FROM modules WHERE id=:id LIMIT 1'); // QA
					$query->bindValue(":id", $moduleID, PDO::PARAM_INT);
					$query->execute();
				} catch(Exception $ee) {
					$this->trackException($e, false, $error)->trackException($ee, true);
				}
				if($languages) $languages->unsetDefault();
				if(is_null($ee)) $this->trackException($e, false, $error);
				return null;
			}
		}

		$info = $this->getModuleInfoVerbose($class, array('noCache' => true)); 
	
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
				$error = sprintf($this->_('Error adding permission: %s'), $name);
				$this->trackException($e, false, $error); 
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
					$error = "$label: $name - " . $e->getMessage();
					$this->trackException($e, false, $error); 
				}
			}
		}

		$this->log("Installed module '$module'"); 
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
			"$basename.config.php", 
			"{$basename}Config.php",
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
					if(strpos($file->getBasename(), '.module') && preg_match('{(\.module|\.module\.php)$}', $file->getBasename())) {
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
		
		if($success) $this->log("Deleted module '$class'"); 
			else $this->error("Failed to delete module '$class'"); 
		
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
		
		// check if there are any modules still installed that this one says it is responsible for installing
		foreach($this->getUninstalls($class) as $name) {

			// catch uninstall exceptions at this point since original module has already been uninstalled
			$label = $this->_('Module Auto Uninstall');
			try {
				$this->uninstall($name);
				$this->message("$label: $name");

			} catch(Exception $e) {
				$error = "$label: $name - " . $e->getMessage();
				$this->trackException($e, false, $error);
			}
		}

		$info = $this->getModuleInfoVerbose($class); 
		$module = $this->getModule($class, array(
			'noPermissionCheck' => true, 
			'noInstall' => true,
			// 'noInit' => true
		)); 
		if(!$module) return false;
		
		// remove all hooks attached to this module
		$hooks = $module instanceof Wire ? $module->getHooks() : array();
		foreach($hooks as $hook) {
			if($hook['method'] == 'uninstall') continue;
			$this->message("Removed hook $class => " . $hook['options']['fromClass'] . " $hook[method]", Notice::debug);
			$module->removeHook($hook['id']);
		}

		// remove all hooks attached to other ProcessWire objects
		$hooks = array_merge(wire()->getHooks('*'), Wire::$allLocalHooks);
		foreach($hooks as $hook) {
			$toClass = get_class($hook['toObject']);
			$toMethod = $hook['toMethod'];
			if($class === $toClass && $toMethod != 'uninstall') {
				$hook['toObject']->removeHook($hook['id']);
				$this->message("Removed hook $class => " . $hook['options']['fromClass'] . " $hook[method]", Notice::debug);
			}
		}
		
		if(method_exists($module, '___uninstall') || method_exists($module, 'uninstall')) {
			// note module's uninstall method may throw an exception to abort the uninstall
			$module->uninstall();
		}
		$database = $this->wire('database'); 
		$query = $database->prepare('DELETE FROM modules WHERE class=:class LIMIT 1'); // QA
		$query->bindValue(":class", $class, PDO::PARAM_STR); 
		$query->execute();
	
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
					$error = sprintf($this->_('Error deleting permission: %s'), $name);
					$this->trackException($e, false, $error);
				}
			}
		}

		$this->log("Uninstalled module '$class'"); 
		$this->resetCache();

		return true; 
	}

	/**
	 * Get flags for the given module
	 * 
	 * @param int|string|Module $class Module to add flag to
	 * @return int|false Returns integer flags on success, or boolean false on fail
	 * 
	 */
	public function getFlags($class) {
		$id = ctype_digit("$class") ? (int) $class : $this->getModuleID($class);
		if(isset($this->moduleFlags[$id])) return $this->moduleFlags[$id]; 
		if(!$id) return false;
		$query = $this->wire('database')->prepare('SELECT flags FROM modules WHERE id=:id');
		$query->bindValue(':id', $id, PDO::PARAM_INT);
		$query->execute();
		if(!$query->rowCount()) return false;
		list($flags) = $query->fetch(PDO::FETCH_NUM);
		$flags = (int) $flags; 
		$this->moduleFlags[$id] = $flags;
		return $flags; 
	}

	/**
	 * Set module flags
	 * 
	 * @param $class
	 * @param $flags
	 * @return bool
	 * 
	 */
	public function setFlags($class, $flags) {
		$flags = (int) $flags; 
		$id = ctype_digit("$class") ? (int) $class : $this->getModuleID($class);
		if(!$id) return false;
		if($this->moduleFlags[$id] === $flags) return true; 
		$query = $this->wire('database')->prepare('UPDATE modules SET flags=:flags WHERE id=:id');
		$query->bindValue(':flags', $flags);
		$query->bindValue(':id', $id);
		if($this->debug) $this->message("setFlags(" . $this->getModuleClass($class) . ", " . $this->moduleFlags[$id] . " => $flags)");
		$this->moduleFlags[$id] = $flags;
		return $query->execute();
	}

	/**
	 * Add or remove a flag from a module
	 * 
	 * @param int|string|Module $class Module to add flag to
	 * @param int Flag to add (see flags* constants)
	 * @param bool $add Specify true to add the flag or false to remove it
	 * @return bool True on success, false on fail
	 * 
	 */
	public function setFlag($class, $flag, $add = true) {
		$id = ctype_digit("$class") ? (int) $class : $this->getModuleID($class);
		if(!$id) return false;
		$flag = (int) $flag; 
		if(!$flag) return false;
		$flags = $this->getFlags($id); 
		if($add) {
			if($flags & $flag) return true; // already has the flag
			$flags = $flags | $flag;
		} else {
			if(!($flags & $flag)) return true; // doesn't already have the flag
			$flags = $flags & ~$flag;
		}
		$this->setFlags($id, $flags); 
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
		$info = $this->getModuleInfoVerbose($class);
		
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
			// remove extensions if they were included in the module name
			if(strpos($module, '.') !== false) $module = basename(basename($module, '.php'), '.module');
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
		// if($this->debug) $this->message("getModuleInfoExternal($moduleName)"); 
		
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
		if(file_exists($filePHP)) {
			include($filePHP); // will populate $info automatically
			if(!is_array($info) || !count($info)) $this->error("Invalid PHP module info file for $moduleName"); 
			
		} else if(file_exists($fileJSON)) {
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
		// if($this->debug) $this->message("getModuleInfoInternal($module)"); 
		
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
			//if(method_exists($module, 'getModuleInfo')) {
			if(is_callable("$module::getModuleInfo")) {
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
	 *  - verbose: Makes the info also include summary, author, file, core, href, versionStr (they will be usually blank without this option specified)
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
		$moduleName = $this->getModuleClass($module); 
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
			// is the module currently installed? (boolean, or null when not determined)
			'installed' => null,
			// this is set to true when the module is configurable, false when it's not, and null when it's not determined
			'configurable' => null, 
			// verbose mode only: this is set to the module filename (from PW installation root), false when it can't be found, null when it hasn't been determined
			'file' => null, 
			// verbose mode only: this is set to true when the module is a core module, false when it's not, and null when it's not determined
			'core' => null, 
			
			// other properties that may be present, but are optional, for Process modules:
			// 'nav' => array(), // navigation definition: see Process.php
			// 'useNavJSON' => bool, // whether the Process module provides JSON navigation
			// 'page' => array(), // page to create for Process module: see Process.php
			// 'permissionMethod' => string or callable // method to call to determine permission: see Process.php
			);
	
		if($module instanceof Module) {
			// module is an instance
			// $moduleName = method_exists($module, 'className') ? $module->className() : get_class($module); 
			// return from cache if available
			
			if(empty($options['noCache']) && !empty($this->moduleInfoCache[$moduleID])) {
				$info = $this->moduleInfoCache[$moduleID]; 
				$fromCache = true; 
			} else {
				$info = $this->getModuleInfoExternal($moduleName); 
				if(!count($info)) $info = $this->getModuleInfoInternal($module); 
			}
			
		} else if($module == 'PHP' || $module == 'ProcessWire') { 
			// module is a system 
			$info = $this->getModuleInfoSystem($module); 
			return array_merge($infoTemplate, $info);
			
		} else {
			
			// module is a class name or ID
			if(ctype_digit("$module")) $module = $moduleName;
			
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
		
			// populate defaults for properties omitted from cache 
			if(is_null($info['autoload'])) $info['autoload'] = false;
			if(is_null($info['singular'])) $info['singular'] = false;
			if(is_null($info['configurable'])) $info['configurable'] = false;
			if(is_null($info['core'])) $info['core'] = false;
			if(is_null($info['installed'])) $info['installed'] = true; 
			if(!empty($info['requiresVersions'])) $info['requires'] = array_keys($info['requiresVersions']);
			if($moduleName == 'SystemUpdater') $info['configurable'] = 1; // fallback, just in case
			
			// we skip everything else when module comes from cache since we can safely assume the checks below 
			// are already accounted for in the cached module info
		
		} else {
			
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
			$configurable = $this->isConfigurableModule($moduleName, false); 
			if($configurable === true || is_int($configurable) && $configurable > 1) {
				// configurable via ConfigurableModule interface
				// true=static, 2=non-static, 3=non-static $data, 4=non-static wrap,
				// 19=non-static getModuleConfigArray, 20=static getModuleConfigArray
				$info['configurable'] = $configurable; 
			} else if($configurable) {
				// configurable via external file: ModuleName.config.php or ModuleNameConfig.php file
				$info['configurable'] = basename($configurable); 
			} else {
				// not configurable
				$info['configurable'] = false;
			}
			
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
		
		if(!empty($info['file']) && (strpos($info['file'], 'wire') === 0 || strpos($info['file'], 'site') === 0)) {
			// convert relative (as stored in moduleInfo) to absolute (verbose info only)
			$info['file'] = $this->wire('config')->paths->root . $info['file'];
		}
		
		// if($this->debug) $this->message("getModuleInfo($moduleName) " . ($fromCache ? "CACHE" : "NO-CACHE")); 
		
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
		$info = $this->getModuleInfo($module, $options); 
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
		if(!isset($this->configData[$id])) return array(); // module has no config data
		if(is_array($this->configData[$id])) return $this->configData[$id]; 

		// first verify that module doesn't have a config file
		$configurable = $this->isConfigurableModule($className); 
		if(!$configurable) return array();
		
		$database = $this->wire('database'); 
		$query = $database->prepare("SELECT data FROM modules WHERE id=:id", "modules.getModuleConfigData($className)"); // QA
		$query->bindValue(":id", (int) $id, PDO::PARAM_INT); 
		$query->execute();
		$data = $query->fetchColumn(); 
		$query->closeCursor();
		
		if(empty($data)) $data = array();
			else $data = wireDecodeJSON($data); 
		if(empty($data)) $data = array();
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
			$dupFile = $this->duplicates()->getCurrent($className);
			if($dupFile) {
				$rootPath = $this->wire('config')->paths->root;
				$file = rtrim($rootPath, '/') . $dupFile;
				if(!file_exists($file)) {
					// module in use may have been deleted, find the next available one that exist
					$file = '';
					$dups = $this->duplicates()->getDuplicates($className); 
					foreach($dups['files'] as $pathname) {
						$pathname = rtrim($rootPath, '/') . $pathname;
						if(file_exists($pathname)) {
							$file = $pathname;
							break;
						}
					}
				}
			}
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

		return $file;
	}

	/**
	 * Is the given module configurable?
	 * 
	 * External configuration file: 
	 * ============================
	 * Returns string of full path/filename to ModuleName.config.php file if configurable via separate file. 
	 * 
	 * ModuleConfig interface:
	 * =======================
	 * Returns boolean true if module is configurable via the static getModuleConfigInputfields method.
	 * Returns integer 2 if module is configurable via the non-static getModuleConfigInputfields and requires no arguments.
	 * Returns integer 3 if module is configurable via the non-static getModuleConfigInputfields and requires $data array.
	 * Returns integer 4 if module is configurable via the non-static getModuleConfigInputfields and requires InputfieldWrapper argument.
	 * Returns integer 19 if module is configurable via non-static getModuleConfigArray method.
	 * Returns integer 20 if module is configurable via static getModuleConfigArray method.
	 * 
	 * Not configurable:
	 * =================
	 * Returns boolean false if not configurable
	 * 
	 * @param Module|string $className
	 * @param bool $useCache This accepts a few options: 
	 * 	- Specify boolean true to allow use of cache when available (default behavior). 
	 * 	- Specify boolean false to disable retrieval of this property from getModuleInfo (forces a new check).
	 * 	- Specify string 'interface' to check only if module implements ConfigurableModule interface. 
	 * 	- Specify string 'file' to check only if module has a separate configuration class/file.
	 * @return bool|string See details about return values above. 
	 * 
	 * @todo all ConfigurableModule methods need to be split out into their own class (ConfigurableModules?)
	 * @todo this method has two distinct parts (file and interface) that need to be split in two methods.
	 * 
	 */
	public function isConfigurableModule($className, $useCache = true) {
	
		$moduleInstance = null;
		if(is_object($className)) {
			$moduleInstance = $className;
			$className = $this->getModuleClass($moduleInstance); 
		}
		
		if($useCache === true || $useCache === 1 || $useCache === "1") {
			$info = $this->getModuleInfo($className);
			// if regular module info doesn't have configurable info, attempt it from verbose module info
			// should only be necessary for transition period between the 'configurable' property being 
			// moved from verbose to non-verbose module info (i.e. this line can be deleted after PW 2.7)
			if($info['configurable'] === null) $info = $this->getModuleInfoVerbose($className);
			if(!$info['configurable']) {
				if($moduleInstance && $moduleInstance instanceof ConfigurableModule) {
					// re-try because moduleInfo may be temporarily incorrect for this request because of change in moduleInfo format
					// this is due to reports of ProcessChangelogHooks not getting config data temporarily between 2.6.11 => 2.6.12
					$this->error("Configurable module check failed for $className, retrying...", Notice::debug);
					$useCache = false; 
				} else {
					return false;
				}
			} else {
				if($info['configurable'] === true) return $info['configurable'];
				if($info['configurable'] === 1 || $info['configurable'] === "1") return true;
				if(is_int($info['configurable']) || ctype_digit("$info[configurable]")) return (int) $info['configurable'];
				if(strpos($info['configurable'], $className) === 0) {
					if(empty($info['file'])) $info['file'] = $this->getModuleFile($className);
					if($info['file']) {
						return dirname($info['file']) . "/$info[configurable]";
					}
				}
			}
		}
		
		if($useCache !== "interface") {
			// check for separate module configuration file
			$dir = dirname($this->getModuleFile($className));
			if($dir) {
				$files = array(
					"$dir/{$className}Config.php", 
					"$dir/$className.config.php"
				); 
				$found = false;
				foreach($files as $file) {
					if(!is_file($file)) continue;
					$config = null; // include file may override
					include_once($file);
					$classConfig = $className . 'Config';
					if(class_exists($classConfig, false)) {
						$interfaces = @class_parents($classConfig, false);
						if(is_array($interfaces) && isset($interfaces['ModuleConfig'])) {
							$found = $file;
							break;
						}
					} else {
						// bypass include_once, because we need to read $config every time
						if(is_null($config)) include($file); 
						if(!is_null($config)) {
							// included file specified a $config array
							$found = $file;
							break;
						}
					}
				}
				if($found) return $file;
			}
		}

		// if file-only check was requested and we reach this point, exit with false now
		if($useCache === "file") return false;
	
		// ConfigurableModule interface checks
		
		$result = false;
		
		foreach(array('getModuleConfigArray', 'getModuleConfigInputfields') as $method) {
			
			$configurable = false;
		
			// if we have a module instance, use that for our check
			if($moduleInstance && $moduleInstance instanceof ConfigurableModule) {
				if(method_exists($moduleInstance, $method)) {
					$configurable = $method;
				} else if(method_exists($moduleInstance, "___$method")) {
					$configurable = "___$method";
				}
			}
	
			// if we didn't have a module instance, load the file to find what we need to know
			if(!$configurable) {
				if(!class_exists($className, false)) $this->includeModule($className);
				$interfaces = @class_implements($className, false);
				if(is_array($interfaces) && isset($interfaces['ConfigurableModule'])) {
					if(method_exists($className, $method)) {
						$configurable = $method;
					} else if(method_exists($className, "___$method")) {
						$configurable = "___$method";
					}
				}
			}
			
			// if still not determined to be configurable, move on to next method
			if(!$configurable) continue;
			
			// now determine if static or non-static
			$ref = new ReflectionMethod($className, $configurable);
			
			if($ref->isStatic()) {
				// config method is implemented as a static method
				if($method == 'getModuleConfigInputfields') {
					// static getModuleConfigInputfields
					$result = true;
				} else {
					// static getModuleConfigArray
					$result = 20; 
				}
				
			} else if($method == 'getModuleConfigInputfields') {
				// non-static getModuleConfigInputfields
				// we allow for different arguments, so determine what it needs
				$parameters = $ref->getParameters();
				if(count($parameters)) {
					$param0 = reset($parameters);
					if(strpos($param0, 'array') !== false || strpos($param0, '$data') !== false) {
						// method requires a $data array (for compatibility with non-static version)
						$result = 3;
					} else if(strpos($param0, 'InputfieldWrapper') !== false || strpos($param0, 'inputfields') !== false) {
						// method requires an empty InputfieldWrapper (as a convenience)
						$result = 4;
					}
				}
				// method requires no arguments
				if(!$result) $result = 2;
				
			} else {
				// non-static getModuleConfigArray
				$result = 19;
			}
		
			// if we make it here, we know we already have a result so can stop now
			break;
		}
		
		return $result;
	}

	/**
	 * Populate configuration data to a ConfigurableModule
	 *
	 * If the Module has a 'setConfigData' method, it will send the array of data to that. 
	 * Otherwise it will populate the properties individually. 
	 *
	 * @param Module $module
	 * @param array $data Configuration data (key = value), or omit if you want it to retrieve the config data for you.
	 * @return bool True if configured, false if not configurable
	 * 
	 */
	protected function setModuleConfigData(Module $module, $data = null) {

		$configurable = $this->isConfigurableModule($module); 
		if(!$configurable) return false;
		if(!is_array($data)) $data = $this->getModuleConfigData($module);
		
		if(is_string($configurable) && is_file($configurable) && strpos(basename($configurable), $module->className()) === 0) {
			// get defaults from ModuleConfig class if available
			$className = $module->className() . 'Config';
			$config = null; // may be overridden by included file
			include_once($configurable);
			if(class_exists($className)) {
				$interfaces = @class_parents($className, false);
				if(is_array($interfaces) && isset($interfaces['ModuleConfig'])) {
					$moduleConfig = new $className();
					if($moduleConfig instanceof ModuleConfig) {
						$defaults = $moduleConfig->getDefaults();
						$data = array_merge($defaults, $data);
					}
				}
			} else {
				// the file may have already been include_once before, so $config would not be set
				// so we try a regular include() next. 
				if(is_null($config)) include($configurable);
				if(is_array($config)) {
					// alternatively, file may just specify a $config array
					$moduleConfig = new ModuleConfig();
					$moduleConfig->add($config);
					$defaults = $moduleConfig->getDefaults();
					$data = array_merge($defaults, $data);
				}
			}
		}

		if(method_exists($module, 'setConfigData') || method_exists($module, '___setConfigData')) {
			$module->setConfigData($data); 
			return true;
		}

		foreach($data as $key => $value) {
			$module->$key = $value; 
		}
		
		return true; 
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

		// ensure original duplicates info is retained and validate that it is still current
		$configData = $this->duplicates()->getDuplicatesConfigData($className, $configData); 
		
		$this->configData[$id] = $configData; 
		$json = count($configData) ? wireEncodeJSON($configData, true) : '';
		$database = $this->wire('database'); 	
		$query = $database->prepare("UPDATE modules SET data=:data WHERE id=:id", "modules.saveModuleConfigData($className)"); // QA
		$query->bindValue(":data", $json, PDO::PARAM_STR);
		$query->bindValue(":id", (int) $id, PDO::PARAM_INT); 
		$result = $query->execute();
		$this->log("Saved module '$className' config data");
		return $result;
	}

	/**
	 * Get the Inputfields that configure the given module or return null if not configurable
	 * 
	 * @param string|Module|int $moduleName
	 * @param InputfieldWrapper|null $form Optionally specify the form you want Inputfields appended to.
	 * @return InputfieldWrapper|null
	 * 
	 */
	public function ___getModuleConfigInputfields($moduleName, InputfieldWrapper $form = null) {
		
		$moduleName = $this->getModuleClass($moduleName);
		$configurable = $this->isConfigurableModule($moduleName);
		if(!$configurable) return null;
		
		if(is_null($form)) $form = new InputfieldWrapper();
		$data = $this->modules->getModuleConfigData($moduleName);
		
		// check for configurable module interface
		$configurableInterface = $this->isConfigurableModule($moduleName, "interface");
		if($configurableInterface) {
			if(is_int($configurableInterface) && $configurableInterface > 1 && $configurableInterface < 20) {
				// non-static 
				/** @var ConfigurableModule $module */
				$module = $this->getModule($moduleName);
				if($configurableInterface === 2) {
					// requires no arguments
					$fields = $module->getModuleConfigInputfields();
				} else if($configurableInterface === 3) {
					// requires $data array
					$fields = $module->getModuleConfigInputfields($data);
				} else if($configurableInterface === 4) {
					// requires InputfieldWrapper
					// we allow for option of no return statement in the method
					$fields = new InputfieldWrapper();
					$_fields = $module->getModuleConfigInputfields($fields);
					if($_fields instanceof InputfieldWrapper) $fields = $_fields;
					unset($_fields);
				} else if($configurableInterface === 19) {
					// non-static getModuleConfigArray method
					$fields = new InputfieldWrapper();
					$fields->importArray($module->getModuleConfigArray());
					$fields->populateValues($module);
				}
			} else if($configurableInterface === 20) {
				// static getModuleConfigArray method
				$fields = new InputfieldWrapper();
				$fields->importArray(call_user_func(array($moduleName, 'getModuleConfigArray')));
				$fields->populateValues($data);
			} else if($configurableInterface) {
				// static getModuleConfigInputfields method
				$fields = call_user_func(array($moduleName, 'getModuleConfigInputfields'), $data);
			}
			if($fields instanceof InputfieldWrapper) {
				foreach($fields as $field) {
					$form->append($field);
				}
			} else if($fields instanceof Inputfield) {
				$form->append($fields);
			} else {
				$this->error("$moduleName.getModuleConfigInputfields() did not return InputfieldWrapper");
			}
		}
		
		// check for file-based config
		$file = $this->isConfigurableModule($moduleName, "file");
		if(!$file || !is_string($file) || !is_file($file)) return $form;
	
		$config = null;
		include_once($file);
		$configClass = $moduleName . "Config";
		$configModule = null;
		
		if(class_exists($configClass)) {
			// file contains a ModuleNameConfig class
			$configModule = new $configClass();
			
		} else {
			if(is_null($config)) include($file); // in case of previous include_once 
			if(is_array($config)) {
				// file contains a $config array
				$configModule = new ModuleConfig();
				$configModule->add($config);
			}
		} 
		
		if($configModule && $configModule instanceof ModuleConfig) {
			$defaults = $configModule->getDefaults();
			$data = array_merge($defaults, $data);
			$configModule->setArray($data);
			$fields = $configModule->getInputfields();
			if($fields instanceof InputfieldWrapper) {
				foreach($fields as $field) {
					$form->append($field);
				}
				foreach($data as $key => $value) {
					$f = $form->getChildByName($key);
					if($f) $f->attr('value', $value);
				}
			} else {
				$this->error("$configModule.getInputfields() did not return InputfieldWrapper");
			}
		}
		
		return $form;
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
		$autoload = null;
		
		if(isset($info['autoload']) && $info['autoload'] !== null) {
			// if autoload is a string (selector) or callable, then we flag it as autoload
			if(is_string($info['autoload']) || is_callable($info['autoload'])) return "conditional"; 
			$autoload = $info['autoload'];
			
		} else if(!is_object($module)) {
			if(isset($this->installable[$module])) {
				// module is not installed
				// we are not going to be able to determine if this is autoload or not
				$flags = $this->getFlags($module); 
				if($flags !== null) {
					$autoload = $flags & self::flagsAutoload;
				} else {
					// unable to determine
					return null;
				}
			} else {
				// include for method exists call
				$this->includeModule($module);
			}
		}
	
		if($autoload === null && method_exists($module, 'isAutoload')) {
			$autoload = $module->isAutoload();
		}
	
		return $autoload; 
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
		if($this->duplicates()->numNewDuplicates() > 0) $this->duplicates()->updateDuplicates(); // PR#1020
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
			// if module class name keys in use (i.e. ProcessModule) it's an older version of 
			// module info cache, so we skip over it to force its re-creation
			if(is_array($data) && !isset($data['ProcessModule'])) $this->moduleInfoCache = $data; 
			$data = $this->wire('cache')->get(self::moduleLastVersionsCacheName);
			if(is_array($data)) $this->modulesLastVersions = $data;
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
	
		// record current module versions currently in moduleInfo
		$moduleVersions = array();
		foreach($this->moduleInfoCache as $id => $moduleInfo) {
			if(isset($this->modulesLastVersions[$id])) {
				$moduleVersions[$id] = $this->modulesLastVersions[$id];
			} else {
				$moduleVersions[$id] = $moduleInfo['version'];
			}
			// $moduleVersions[$id] = $moduleInfo['version'];
		}
	
		// delete the caches
		$this->wire('cache')->delete(self::moduleInfoCacheName);
		$this->wire('cache')->delete(self::moduleInfoCacheVerboseName);
		$this->wire('cache')->delete(self::moduleInfoCacheUninstalledName);
		
		$this->moduleInfoCache = array();
		$this->moduleInfoCacheVerbose = array();
		$this->moduleInfoCacheUninstalled = array();
	
		// save new moduleInfo cache
		$this->saveModuleInfoCache();

		$versionChanges = array();
		$newModules = array();
		// compare new moduleInfo versions with the previous ones, looking for changes
		foreach($this->moduleInfoCache as $id => $moduleInfo) {
			if(!isset($moduleVersions[$id])) {
				$newModules[] = $moduleInfo['name']; 
				continue;
			}
			if($moduleVersions[$id] != $moduleInfo['version']) {
				$fromVersion = $this->formatVersion($moduleVersions[$id]);
				$toVersion = $this->formatVersion($moduleInfo['version']);
				$versionChanges[] = "$moduleInfo[name]: $fromVersion => $toVersion";
				$this->modulesLastVersions[$id] = $moduleVersions[$id];
			}
		}
	
		// report on any changes
		if(count($newModules)) {
			$this->message(
				sprintf($this->_n('Detected %d new module: %s', 'Detected %d new modules: %s', count($newModules)), 
					count($newModules), '<pre>' . implode("\n", $newModules)) . '</pre>', 
				Notice::allowMarkup);
		}
		if(count($versionChanges)) {
			$this->message(
				sprintf($this->_n('Detected %d module version change', 'Detected %d module version changes', 
					count($versionChanges)), count($versionChanges)) . 
				' (' . $this->_('will be applied the next time each module is loaded') . '):' . 
				'<pre>' . implode("\n", $versionChanges) . '</pre>', 
				Notice::allowMarkup | Notice::debug);
		}
		
		$this->updateModuleVersionsCache();
	}

	/**
	 * Update the cache of queued module version changes
	 * 
	 */
	protected function updateModuleVersionsCache() {
		foreach($this->modulesLastVersions as $id => $version) {
			// clear out stale data, if present
			if(!in_array($id, $this->moduleIDs)) unset($this->modulesLastVersions[$id]);
		}
		if(count($this->modulesLastVersions)) {
			$this->wire('cache')->save(self::moduleLastVersionsCacheName, $this->modulesLastVersions, WireCache::expireNever);
		} else {
			$this->wire('cache')->delete(self::moduleLastVersionsCacheName);
		}
	}

	/**
	 * Check the module version to make sure it is consistent with our moduleInfo
	 * 
	 * When not consistent, this triggers the moduleVersionChanged hook, which in turn
	 * triggers the $module->___upgrade($fromVersion, $toVersion) method. 
	 * 
	 * @param Module $module
	 * 
	 */
	protected function checkModuleVersion(Module $module) {
		$id = $this->getModuleID($module);
		$moduleInfo = $this->getModuleInfo($module);
		$lastVersion = isset($this->modulesLastVersions[$id]) ? $this->modulesLastVersions[$id] : null;
		if(!is_null($lastVersion)) { 
			if($lastVersion != $moduleInfo['version']) {
				$this->moduleVersionChanged($module, $lastVersion, $moduleInfo['version']);	
				unset($this->modulesLastVersions[$id]);
			}
			$this->updateModuleVersionsCache();
		}
	}

	/**
	 * Hook called when a module's version changes
	 * 
	 * This calls the module's ___upgrade($fromVersion, $toVersion) method. 
	 * 
	 * @param Module $module
	 * @param int|string $fromVersion
	 * @param int|string $toVersion
	 * 
	 */
	protected function ___moduleVersionChanged(Module $module, $fromVersion, $toVersion) {
		$moduleName = get_class($module);
		$moduleID = $this->getModuleID($module);
		$fromVersionStr = $this->formatVersion($fromVersion);
		$toVersionStr = $this->formatVersion($toVersion);
		$this->message($this->_('Upgrading module') . " ($moduleName: $fromVersionStr => $toVersionStr)");
		try {
			if(method_exists($module, '___upgrade')) {
				$module->upgrade($fromVersion, $toVersion);
			}
			unset($this->modulesLastVersions[$moduleID]);
		} catch(Exception $e) {
			$this->error("Error upgrading module ($moduleName): " . $e->getMessage());
		}
	}

	/**
	 * Update module flags if any happen to differ from what's in the given moduleInfo
	 * 
	 * @param $moduleID
	 * @param array $info
	 * 
	 */
	protected function updateModuleFlags($moduleID, array $info) {
		
		$flags = (int) $this->getFlags($moduleID); 
		
		if($info['autoload']) {
			// module is autoload
			if(!($flags & self::flagsAutoload)) {
				// add autoload flag
				$this->setFlag($moduleID, self::flagsAutoload, true);
			}
			if(is_string($info['autoload'])) {
				// requires conditional flag
				// value is either: "function", or the conditional string (like key=value)
				if(!($flags & self::flagsConditional)) $this->setFlag($moduleID, self::flagsConditional, true);
			} else {
				// should not have conditional flag
				if($flags & self::flagsConditional) $this->setFlag($moduleID, self::flagsConditional, false);
			}
			
		} else if($info['autoload'] !== null) {
			// module is not autoload
			if($flags & self::flagsAutoload) {
				// remove autoload flag
				$this->setFlag($moduleID, self::flagsAutoload, false);
			}
			if($flags & self::flagsConditional) {
				// remove conditional flag
				$this->setFlag($moduleID, self::flagsConditional, false);
			}
		}
		
		if($info['singular']) {
			if(!($flags & self::flagsSingular)) $this->setFlag($moduleID, self::flagsSingular, true); 
		} else {
			if($flags & self::flagsSingular) $this->setFlag($moduleID, self::flagsSingular, false); 
		}

	}

	/**
	 * Save the module information cache
	 * 
	 */
	protected function saveModuleInfoCache() {
		
		if($this->debug) {
			static $n = 0;
			$this->message("saveModuleInfoCache (" . (++$n) . ")"); 
		}
		
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
				$this->trackException($e, false, true); 
			}
		}
	
		foreach(array(true, false) as $installed) { 
			
			$items = $installed ? $this : array_keys($this->installable);	
			
			foreach($items as $module) {
				
				$class = is_object($module) ? $module->className() : $module;
				$info = $this->getModuleInfo($class, array('noCache' => true, 'verbose' => true));
				$moduleID = (int) $info['id']; // note ID is always 0 for uninstalled modules
				
				if(!empty($info['error'])) {
					if($this->debug) $this->warning("$class reported error: $info[error]"); 
					continue;
				}
				
				if(!$moduleID && $installed) {
					if($this->debug) $this->warning("No module ID for $class"); 
					continue;
				}
				
				if(!$this->debug) unset($info['id']); // no need to double store this property since it is already the array key
				
				if(is_null($info['autoload'])) {
					// module info does not indicate an autoload state
					$info['autoload'] = $this->isAutoload($module); 
					
				} else if(!is_bool($info['autoload']) && !is_string($info['autoload']) && is_callable($info['autoload'])) {
					// runtime function, identify it only with 'function' so that it can be recognized later as one that
					// needs to be dynamically loaded
					$info['autoload'] = 'function';
				}
			
				if(is_null($info['singular'])) {
					$info['singular'] = $this->isSingular($module); 
				}
			
				if(is_null($info['configurable'])) {
					$info['configurable'] = $this->isConfigurableModule($module, false);
				}
				
				if($moduleID) $this->updateModuleFlags($moduleID, $info);
			
				// no need to store full path
				$info['file'] = str_replace($this->wire('config')->paths->root, '', $info['file']);
		
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
	
		$caches = array(
			self::moduleInfoCacheName => 'moduleInfoCache', 
			self::moduleInfoCacheVerboseName => 'moduleInfoCacheVerbose',
			self::moduleInfoCacheUninstalledName => 'moduleInfoCacheUninstalled',
		);
		
		foreach($caches as $cacheName => $varName) {
			$data = $this->$varName;
			foreach($data as $moduleID => $moduleInfo) {
				foreach($moduleInfo as $key => $value) {
					// remove unpopulated properties
					if($key == 'installed') {
						// no need to store an installed==true property
						if($value) unset($data[$moduleID][$key]);
						
					} else if($key == 'requires' && !empty($value) && !empty($data[$moduleID]['requiresVersions'])) {
						// requiresVersions has enough info to re-construct requires, so no need to store it
						unset($data[$moduleID][$key]);
						
					} else if(($key == 'created' && empty($value))
						|| ($value === 0 && ($key == 'singular' || $key == 'autoload' || $key == 'configurable'))
						|| ($value === null || $value === "" || $value === false) 
						|| (is_array($value) && !count($value))) {
						// no need to store these false, null, 0, or blank array properties
						unset($data[$moduleID][$key]);
					} 
				}
			}
			$this->wire('cache')->save($cacheName, $data, WireCache::expireNever); 
		}
	
		$this->log('Saved module info caches'); 
		
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
			unset($this->substitutes[$moduleName]);
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

	/**
	 * Load module related CSS and JS files
	 * 
	 * Applies only to modules that carry class-named CSS and/or JS files,
	 * such as Process, Inputfield and ModuleJS modules. 
	 * 
	 * @param Module|int|string $module Module object or class name
	 * @return array Returns number of files that were added
	 * 
	 */
	public function loadModuleFileAssets($module) {

		$class = $this->getModuleClass($module);
		static $classes = array();
		if(isset($classes[$class])) return 0; // already loaded
		$info = null;
		$config = $this->wire('config');
		$path = $config->paths->$class;
		$url = $config->urls->$class;
		$debug = $config->debug;
		$version = 0; 
		$cnt = 0;

		foreach(array('styles' => 'css', 'scripts' => 'js') as $type => $ext) {
			$fileURL = '';
			$modified = 0;
			$file = "$path$class.$ext";
			$minFile = "$path$class.min.$ext";
			if(!$debug && is_file($minFile)) {
				$fileURL = "$url$class.min.$ext";
				$modified = filemtime($minFile);
			} else if(is_file($file)) {
				$fileURL = "$url$class.$ext";
				$modified = filemtime($file);
			}
			if($fileURL) {
				if(!$version) {
					$info = $this->getModuleInfo($module, array('verbose' => false));
					$version = (int) isset($info['version']) ? $info['version'] : 0;
				}
				$config->$type->add("$fileURL?v=$version-$modified");
				$cnt++;
			}
		}
		
		$classes[$class] = true; 
		
		return $cnt;
	}

	/**
	 * Enables use of $modules('ModuleName')
	 *
	 * @param string $key
	 * @return mixed
	 *
	 */
	public function __invoke($key) {
		return $this->get($key);
	}

	/**
	 * Save to the modules log
	 * 
	 * @param string $str Message to log
	 * @param string $moduleName
	 * @return WireLog
	 * 
	 */	
	public function log($str, $moduleName = '') {
		if(!in_array('modules', $this->wire('config')->logs)) return $this->___log();
		if(!is_string($moduleName)) $moduleName = (string) $moduleName; 
		if($moduleName && strpos($str, $moduleName) === false) $str .= " (Module: $moduleName)";
		return $this->___log($str, array('name' => 'modules')); 
	}
	
	public function error($text, $flags = 0) {
		$this->log($text); 
		return parent::error($text, $flags); 
	}
	
}

