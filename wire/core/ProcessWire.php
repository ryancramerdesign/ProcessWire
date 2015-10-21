<?php namespace ProcessWire;

/**
 * ProcessWire API Bootstrap
 *
 * Initializes all the ProcessWire classes and prepares them for API use
 * 
 * ProcessWire 3.x (development), Copyright 2015 by Ryan Cramer
 * https://processwire.com
 * 
 * @todo: get language permissions to work with extra actions
 * 
 */
require_once(__DIR__ . '/boot.php');

/**
 * ProcessWire API bootstrap class
 *
 * Gets ProcessWire's API ready for use
 *
 */ 
class ProcessWire extends Wire {

	const versionMajor = 3; 
	const versionMinor = 0; 
	const versionRevision = 0; 
	const versionSuffix = 'alpha-1';
	
	const indexVersion = 300; // required version for index.php file (represented by PROCESSWIRE define)
	const htaccessVersion = 250;
	
	const statusBoot = 0; // system is booting
	const statusInit = 2; // system and modules are initializing
	const statusReady = 4; // system and $page are ready
	const statusRender = 8; // $page's template is being rendered
	const statusFinished = 16; // request has been delivered
	const statusFailed = 1024; // request failed due to exception or 404

	/**
	 * Whether debug mode is on or off
	 * 
	 * @var bool
	 * 
	 */
	protected $debug = false;

	/**
	 * Fuel manages ProcessWire API variables
	 * 
	 * This will replace the static $fuel from the Wire class in PW 3.0.
	 * Currently it is just here as a placeholder.
	 *
	 * @var Fuel|null
	 *
	 */
	protected $fuel = null;

	/**
	 * Saved path, for includeFile() method
	 * 
	 * @var string
	 * 
	 */
	protected $pathSave = '';

	/**
	 * @var SystemUpdater|null
	 * 
	 */
	protected $updater = null;

	/**
	 * ID for this instance of ProcessWire
	 * 
	 * @var int
	 * 
	 */
	protected $instanceID = 0;

	/**
	 * @var WireShutdown
	 * 
	 */
	protected $shutdown = null;
	
	/**
	 * Given a Config object, instantiates ProcessWire and it's API
	 * 
	 * @param Config $config
 	 *
	 */ 
	public function __construct(Config $config) {
		
		ini_set('display_errors', true);
		error_reporting(E_ALL | E_STRICT);

		$config->setWire($this);
		
		$this->debug = $config->debug; 
		$this->instanceID = self::addInstance($this);
		$this->setWire($this);
		
		$this->fuel = new Fuel();
		$this->fuel->set('wire', $this, true);

		$classLoader = $this->wire('classLoader', new WireClassLoader(), true);
		$classLoader->addNamespace(__NAMESPACE__, PROCESSWIRE_CORE_PATH);

		$this->wire('hooks', new WireHooks($this, $config), true);

		$this->shutdown = $this->wire(new WireShutdown());
		$this->config($config);
		$this->load($config);
	}

	public function __toString() {
		return $this->className() . " " . self::versionMajor . "." . self::versionMinor . "." . self::versionRevision; 
	}

	/**
	 * Populate ProcessWire's configuration with runtime and optional variables
 	 *
	 * $param Config $config
 	 *
	 */
	protected function config(Config $config) {

		$this->wire('config', $config, true); 
		$this->wire($config->paths);
		$this->wire($config->urls);
		
		// If debug mode is on then echo all errors, if not then disable all error reporting
		if($config->debug) {
			error_reporting(E_ALL | E_STRICT);
			ini_set('display_errors', 1);
		} else {
			error_reporting(0);
			ini_set('display_errors', 0);
		}

		ini_set('date.timezone', $config->timezone);
		ini_set('default_charset','utf-8');

		if(!$config->templateExtension) $config->templateExtension = 'php';
		if(!$config->httpHost) $config->httpHost = $this->getHttpHost($config); 

		$config->https = (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on') 
			|| (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
		
		$config->ajax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest');
		$config->cli = (!isset($_SERVER['SERVER_SOFTWARE']) && (php_sapi_name() == 'cli' || ($_SERVER['argc'] > 0 && is_numeric($_SERVER['argc']))));
		
		$version = self::versionMajor . "." . self::versionMinor . "." . self::versionRevision; 
		$config->version = $version;
		$config->versionName = trim($version . " " . self::versionSuffix);
		
		// $config->debugIf: optional setting to determine if debug mode should be on or off
		if($config->debugIf && is_string($config->debugIf)) {
			$debugIf = trim($config->debugIf);
			if(strpos($debugIf, '/') === 0) $debugIf = (bool) @preg_match($debugIf, $_SERVER['REMOTE_ADDR']); // regex IPs
				else if(is_callable($debugIf)) $debugIf = $debugIf(); // callable function to determine debug mode for us 
				else $debugIf = $debugIf === $_SERVER['REMOTE_ADDR']; // exact IP match
			$config->debug = $debugIf;
		}

		// If script is being called externally, add an extra shutdown function 
		if(!$config->internal) register_shutdown_function(function() {
			if(error_get_last()) return;
			$process = $this->wire('process');
			if($process == 'ProcessPageView') $process->finished();
		});

		$this->setStatus(self::statusBoot);
	}

	/**
	 * Safely determine the HTTP host
	 * 
	 * @param Config $config
	 * @return string
	 *
	 */
	protected function getHttpHost(Config $config) {

		$httpHosts = $config->httpHosts; 
		$port = (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] != 80) ? (':' . ((int) $_SERVER['SERVER_PORT'])) : '';
		$host = '';

		if(is_array($httpHosts) && count($httpHosts)) {
			// validate from an allowed whitelist of http hosts
			$key = false; 
			if(isset($_SERVER['SERVER_NAME'])) {
				$key = array_search(strtolower($_SERVER['SERVER_NAME']) . $port, $httpHosts, true); 
			}
			if($key === false && isset($_SERVER['HTTP_HOST'])) {
				$key = array_search(strtolower($_SERVER['HTTP_HOST']), $httpHosts, true); 
			}
			if($key === false) {
				// no valid host found, default to first in whitelist
				$host = reset($httpHosts);
			} else {
				// found a valid host
				$host = $httpHosts[$key];
			}

		} else {
			// pull from server_name or http_host and sanitize
			
			if(isset($_SERVER['SERVER_NAME']) && $host = $_SERVER['SERVER_NAME']) {
				// no whitelist available, so defer to server_name
				$host .= $port; 

			} else if(isset($_SERVER['HTTP_HOST']) && $host = $_SERVER['HTTP_HOST']) {
				// fallback to sanitized http_host if server_name not available
				// note that http_host already includes port if not 80
				$host = $_SERVER['HTTP_HOST'];
			}

			// sanitize since it did not come from a whitelist
			if(!preg_match('/^[-a-zA-Z0-9.:]+$/D', $host)) $host = ''; 
		}

		return $host; 
	}

	/**
	 * Load's ProcessWire using the supplied Config and populates all API fuel
 	 *
	 * @param Config $config
	 * @throws WireDatabaseException|WireException on fatal error
 	 *
	 */
	public function load(Config $config) {
		
		if($this->debug) {
			Debug::timer('boot'); 
			Debug::timer('boot.load'); 
		}

		$this->wire('log', new WireLog(), true); 
		$this->wire('notices', new Notices(), true); 
		$this->wire('sanitizer', new Sanitizer()); 
		$this->wire('datetime', new WireDateTime());
		$this->wire('files', new WireFileTools());
		$this->wire('mail', new WireMailTools());

		try {
			$database = $this->wire('database', WireDatabasePDO::getInstance($config), true);
			$db = $this->wire('db', new DatabaseMysqli($config), true);
		} catch(\Exception $e) {
			// catch and re-throw to prevent DB connect info from ever appearing in debug backtrace
			$this->trackException($e, true, 'Unable to load WireDatabasePDO');
			throw new WireDatabaseException($e->getMessage()); 
		}
		
		$cache = $this->wire('cache', new WireCache(), true); 
		$cache->preload($config->preloadCacheNames); 
		
		if($config->compat2x) $this->loadCompat2x($config);

		try { 		
			if($this->debug) Debug::timer('boot.load.modules');
			$modules = $this->wire('modules', new Modules($config->paths->modules), true);
			$modules->addPath($config->paths->siteModules);
			$modules->setSubstitutes($config->substituteModules); 
			$modules->init();
			if($this->debug) Debug::saveTimer('boot.load.modules');
		} catch(\Exception $e) {
			$this->trackException($e, true, 'Unable to load Modules');
			if(!$modules) throw new WireException($e->getMessage()); 	
			$this->error($e->getMessage()); 
		}
		$this->updater = $modules->get('SystemUpdater'); 
		if(!$this->updater) {
			$modules->resetCache();
			$this->updater = $modules->get('SystemUpdater');
		}

		$fieldtypes = $this->wire('fieldtypes', new Fieldtypes(), true);
		$fields = $this->wire('fields', new Fields(), true);
		$fieldgroups = $this->wire('fieldgroups', new Fieldgroups(), true);
		$templates = $this->wire('templates', new Templates($fieldgroups, $config->paths->templates), true); 
		$pages = $this->wire('pages', new Pages(), true);

		$this->initVar('fieldtypes', $fieldtypes);
		$this->initVar('fields', $fields);
		$this->initVar('fieldgroups', $fieldgroups);
		$this->initVar('templates', $templates);
		$this->initVar('pages', $pages); 
	
		if($this->debug) Debug::timer('boot.load.permissions'); 
		if(!$t = $templates->get('permission')) throw new WireException("Missing system template: 'permission'"); 
		$permissions = $this->wire('permissions', new Permissions($this, $t, $config->permissionsPageID), true); 
		if($this->debug) Debug::saveTimer('boot.load.permissions');

		if($this->debug) Debug::timer('boot.load.roles'); 
		if(!$t = $templates->get('role')) throw new WireException("Missing system template: 'role'"); 
		$roles = $this->wire('roles', new Roles($this, $t, $config->rolesPageID), true); 
		if($this->debug) Debug::saveTimer('boot.load.roles');

		if($this->debug) Debug::timer('boot.load.users'); 
		$users = $this->wire('users', new Users($this, $config->userTemplateIDs, $config->usersPageIDs), true); 
		if($this->debug) Debug::saveTimer('boot.load.users'); 

		// the current user can only be determined after the session has been initiated
		$session = $this->wire('session', new Session($this), true); 
		$this->initVar('session', $session);
		$this->wire('user', $users->getCurrentUser()); 
		$this->wire('input', new WireInput(), true); 

		// populate admin URL before modules init()
		$config->urls->admin = $config->urls->root . ltrim($pages->_path($config->adminRootPageID), '/');

		if($this->debug) Debug::saveTimer('boot.load', 'includes all boot.load timers');
		$this->setStatus(self::statusInit);
	}

	/**
	 * Load ProcessWire 2.x compatibility mode
	 * 
	 * @param Config $config
	 * 
	 */
	protected function loadCompat2x(Config $config) {
		include_once($config->paths->core . 'compat2x/Classes.php');
		include_once($config->paths->core . 'compat2x/Functions.php');
	}
	
	/**
	 * Initialize the given API var
	 * 
	 * @param string $name
	 * @param Wire $value
	 * 
	 */
	protected function initVar($name, $value) {
		if($this->debug) Debug::timer("boot.load.$name");
		$value->init();
		if($this->debug) Debug::saveTimer("boot.load.$name"); 
	}

	/**
	 * Set the system status to one of the ProcessWire::status* constants
	 * 
	 * This also triggers init/ready functions for modules, when applicable.
	 * 
	 * @param $status
	 * 
	 */
	public function setStatus($status) {
		$config = $this->wire('config');
		// don't re-trigger if this state has already been triggered
		if($config->status >= $status) return;
		$config->status = $status;
		$sitePath = $this->wire('config')->paths->site;
		
		if($status == self::statusInit) {
			$this->init();
			$this->includeFile($sitePath . 'init.php');
			
		} else if($status == self::statusReady) {
			$this->ready();
			if($this->debug) Debug::saveTimer('boot', 'includes all boot timers');
			$this->includeFile($sitePath . 'ready.php');
			
		} else if($status == self::statusFinished) {
			$this->includeFile($sitePath . 'finished.php');
			$this->finished();
		}
	}

	/**
	 * Hookable init for anyone that wants to hook immediately before any autoload modules initialized or after all modules initialized
	 * 
	 */
	protected function ___init() {
		if($this->debug) Debug::timer('boot.modules.autoload.init'); 
		$this->wire('modules')->triggerInit();
		if($this->debug) Debug::saveTimer('boot.modules.autoload.init');
	}

	/**
	 * Hookable ready for anyone that wants to hook immediately before any autoload modules ready or after all modules ready
	 *
	 */
	protected function ___ready() {
		if($this->debug) Debug::timer('boot.modules.autoload.ready'); 
		$this->wire('modules')->triggerReady();
		$this->updater->ready();
		unset($this->updater);
		if($this->debug) Debug::saveTimer('boot.modules.autoload.ready'); 
	}

	/**
	 * Hookable ready for anyone that wants to hook when the request is finished
	 *
	 */
	protected function ___finished() {
		$session = $this->wire('session'); 
		if($session) $session->maintenance();
		$cache = $this->wire('cache'); 
		if($cache) $cache->maintenance();
	}

	/**
	 * Set a new API variable
	 * 
	 * Alias of $this->wire(), but for setting only, for syntactic convenience.
	 * i.e. $this->wire()->set($key, $value); 
	 * 
	 * @param $key API variable name to set
	 * @param $value Value of API variable
	 * @param bool $lock Whether to lock the value from being overwritten
	 * @return $this
	 */
	public function set($key, $value, $lock = false) {
		$this->wire($key, $value, $lock);
		return $this;
	}
	
	public function __get($key) {
		if($key == 'shutdown') return $this->shutdown;
		if($key == 'instanceID') return $this->instanceID;
		return parent::__get($key);
	}

	/**
	 * Include a PHP file, giving it all PW API varibles in scope
	 * 
	 * File is executed in the directory where it exists.
	 * 
	 * @param $file Full path and filename
	 * @return bool True if file existed and was included, false if not.
	 * 
	 */
	protected function includeFile($file) {
		if(!file_exists($file)) return false;
		$this->pathSave = getcwd();
		chdir(dirname($file));
		$fuel = $this->fuel->getArray();
		extract($fuel);
		include($file);
		chdir($this->pathSave);
		return true; 
	}
	
	public function __call($method, $arguments) {
		if(method_exists($this, "___$method")) return parent::__call($method, $arguments); 
		$value = $this->__get($method);
		if(is_object($value)) return call_user_func_array(array($value, '__invoke'), $arguments); 
		return parent::__call($method, $arguments);
	}
	
	public function fuel($name = '') {
		if(empty($name)) return $this->fuel;
		return $this->fuel->$name;
	}
	
	/*** MULTI-INSTANCE *************************************************************************************/
	
	/**
	 * Instances of ProcessWire
	 *
	 * @var array
	 *
	 */
	static protected $instances = array();

	/**
	 * Current ProcessWire instance
	 * 
	 * @var null
	 * 
	 */
	static protected $currentInstance = null;

	/**
	 * Instance ID of this ProcessWire instance
	 * 
	 * @return int
	 * 
	 */
	public function getInstanceID() {
		return $this->instanceID;
	}

	/**
	 * Add a ProcessWire instance and return the instance ID
	 * 
	 * @param ProcessWire $wire
	 * @return int
	 * 
	 */
	protected static function addInstance(ProcessWire $wire) {
		$id = 0;
		while(isset(self::$instances[$id])) $id++;
		self::$instances[$id] = $wire;
		return $id;
	}

	/**
	 * Get all ProcessWire instances
	 * 
	 * @return array
	 * 
	 */
	public static function getInstances() {
		return self::$instances;
	}

	/**
	 * Get a ProcessWire instance by ID
	 * 
	 * @param int|null $instanceID Omit this argument to return the current instance
	 * @return null|ProcessWire
	 * 
	 */
	public static function getInstance($instanceID = null) {
		if(is_null($instanceID)) return self::getCurrentInstance();
		return isset(self::$instances[$instanceID]) ? self::$instances[$instanceID] : null;
	}
	
	/**
	 * Get the current ProcessWire instance
	 * 
	 * @return ProcessWire|null
	 * 
	 */
	public static function getCurrentInstance() {
		if(is_null(self::$currentInstance)) {
			$wire = reset(self::$instances);
			if($wire) self::setCurrentInstance($wire);
		}
		return self::$currentInstance;
	}

	/**
	 * Set the current ProcessWire instance
	 * 
	 * @param ProcessWire $wire
	 * 
	 */
	public static function setCurrentInstance(ProcessWire $wire) {
		self::$currentInstance = $wire;	
	}

	/**
	 * Remove a ProcessWire instance
	 * 
	 * @param ProcessWire $wire
	 * 
	 */
	public static function removeInstance(ProcessWire $wire) {
		foreach(self::$instances as $key => $instance) {
			if($instance === $wire) {
				unset(self::$instances[$key]);
				if(self::$currentInstance === $wire) self::$currentInstance = null;
				break;
			}
		}
	}

	/**
	 * Build a Config object for booting ProcessWire
	 * 
	 * @param $rootPath Path to root of installation where ProcessWire's index.php file is located.
	 * @param string $rootURL Should be specified only for secondary ProcessWire instances.
	 * @return Config
	 * 
	 */
	public static function buildConfig($rootPath, $rootURL = null) {
		
		if(DIRECTORY_SEPARATOR != '/') {
			$rootPath = str_replace(DIRECTORY_SEPARATOR, '/', $rootPath);
		}
		
		$rootPath = rtrim($rootPath, '/');
		$_rootURL = $rootURL;
		if(is_null($rootURL)) $rootURL = '/';
		$httpHost = '';
		$config = new Config();
		$config->dbName = '';

		if(isset($_SERVER['HTTP_HOST'])) {
			$httpHost = strtolower($_SERVER['HTTP_HOST']);

			// when serving pages from a web server
			if(is_null($_rootURL)) $rootURL = rtrim(dirname($_SERVER['SCRIPT_NAME']), "/\\") . '/';
			$realScriptFile = empty($_SERVER['SCRIPT_FILENAME']) ? '' : realpath($_SERVER['SCRIPT_FILENAME']);
			$realIndexFile = realpath($rootPath . "/index.php");

			// check if we're being included from another script and adjust the rootPath accordingly
			$sf = empty($realScriptFile) ? '' : dirname($realScriptFile);
			$f = dirname($realIndexFile);
			if($sf && $sf != $f && strpos($sf, $f) === 0) {
				$x = rtrim(substr($sf, strlen($f)), '/');
				if(is_null($_rootURL)) $rootURL = substr($rootURL, 0, strlen($rootURL) - strlen($x));
			}
			unset($sf, $f, $x);
		
			// when internal is true, we are not being called by an external script
			$config->internal = $realIndexFile == $realScriptFile;

		} else {
			// when included from another app or command line script
			$config->internal = false;
		}
		
		// Allow for an optional /index.config.php file that can point to a different site configuration per domain/host.
		$siteDir = 'site';
		$indexConfigFile = $rootPath . "/index.config.php";

		if(is_file($indexConfigFile) && !function_exists("ProcessWireHostSiteConfig")) {
			// optional config file is present in root
			@include($indexConfigFile);
			$hostConfig = function_exists("ProcessWireHostSiteConfig") ? ProcessWireHostSiteConfig() : array();
			if($httpHost && isset($hostConfig[$httpHost])) {
				$siteDir = $hostConfig[$httpHost];
			} else if(isset($hostConfig['*'])) {
				$siteDir = $hostConfig['*']; // default override
			}
		}

		// other default directories
		$wireDir = 'wire';
		$coreDir = "$wireDir/core";
		$assetsDir = "$siteDir/assets";
		$adminTplDir = 'templates-admin';
	
		// create new Config instance
		$config->urls = new Paths($rootURL);
		$config->urls->wire = "$wireDir/";
		$config->urls->site = "$siteDir/";
		$config->urls->modules = "$wireDir/modules/";
		$config->urls->siteModules = "$siteDir/modules/";
		$config->urls->core = "$coreDir/";
		$config->urls->assets = "$assetsDir/";
		$config->urls->cache = "$assetsDir/cache/";
		$config->urls->logs = "$assetsDir/logs/";
		$config->urls->files = "$assetsDir/files/";
		$config->urls->tmp = "$assetsDir/tmp/";
		$config->urls->templates = "$siteDir/templates/";
		$config->urls->adminTemplates = is_dir("$siteDir/$adminTplDir") ? "$siteDir/$adminTplDir/" : "$wireDir/$adminTplDir/";
		$config->paths = clone $config->urls;
		$config->paths->root = $rootPath . '/';
		$config->paths->sessions = $config->paths->assets . "sessions/";

		// Styles and scripts are CSS and JS files, as used by the admin application.
	 	// But reserved here if needed by other apps and templates.
		$config->styles = new FilenameArray();
		$config->scripts = new FilenameArray();

		// Include system config defaults
		include("$rootPath/$wireDir/config.php");

		// Include site-specific config settings
		$configFile = $config->paths->site . "config.php";
		$configFileDev = $config->paths->site . "config-dev.php";
		if(is_file($configFileDev)) {
			@include($configFileDev);
		} else if(is_file($configFile)) {
			@include($configFile);
		}

		return $config;
	}

}




