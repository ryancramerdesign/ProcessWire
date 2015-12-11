<?php

/**
 * ProcessWire API Bootstrap
 *
 * Initializes all the ProcessWire classes and prepares them for API use
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2015 by Ryan Cramer 
 * This file licensed under Mozilla Public License v2.0 http://mozilla.org/MPL/2.0/
 * 
 * https://processwire.com
 * 
 * @todo: get language permissions to work with extra actions
 * 
 */

if(defined("PROCESSWIRE") && PROCESSWIRE >= 300) die("Your /index.php file is for 3.x, please replace it with one for 2.x");
define("PROCESSWIRE_CORE_PATH", dirname(__FILE__) . '/');

require(PROCESSWIRE_CORE_PATH . "autoload.php"); 
require(PROCESSWIRE_CORE_PATH . "Interfaces.php"); 
require(PROCESSWIRE_CORE_PATH . "Exceptions.php"); 
require(PROCESSWIRE_CORE_PATH . "Functions.php"); 
require(PROCESSWIRE_CORE_PATH . "LanguageFunctions.php");
require(PROCESSWIRE_CORE_PATH . "shutdown.php"); 


/**
 * ProcessWire Bootstrap class
 *
 * Gets ProcessWire's API ready for use
 *
 */ 
class ProcessWire extends Wire {

	const versionMajor = 2; 
	const versionMinor = 7; 
	const versionRevision = 2; 
	const versionSuffix = '';
	
	const indexVersion = 270; // required version for index.php file (represented by PROCESSWIRE define)
	const htaccessVersion = 250; // required version for .htaccess file
	
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
	protected $_fuel = null;

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
	 * Given a Config object, instantiates ProcessWire and it's API
	 * 
	 * @param Config $config
 	 *
	 */ 
	public function __construct(Config $config) {
		$this->debug = $config->debug; 
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

		ini_set('date.timezone', $config->timezone);
		ini_set('default_charset','utf-8');

		if(!$config->templateExtension) $config->templateExtension = 'php';
		if(!$config->httpHost) $config->httpHost = $this->getHttpHost($config); 

		$config->https = (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on') 
			|| (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
		
		if($config->https && $config->sessionCookieSecure) {
			ini_set('session.cookie_secure', 1); // #1264
			if($config->sessionNameSecure) {
				session_name($config->sessionNameSecure);
			} else {
				session_name($config->sessionName . 's');
			}
		}
		
		$config->ajax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest');
		$config->cli = (!isset($_SERVER['SERVER_SOFTWARE']) && (php_sapi_name() == 'cli' || ($_SERVER['argc'] > 0 && is_numeric($_SERVER['argc']))));
		
		$version = self::versionMajor . "." . self::versionMinor . "." . self::versionRevision; 
		$config->version = $version;
		$config->versionName = trim($version . " " . self::versionSuffix); 
		
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

		$this->wire('wire', $this, true);
		$this->wire('log', new WireLog(), true); 
		$this->wire('notices', new Notices(), true); 
		$this->wire('sanitizer', new Sanitizer()); 

		try {
			$database = WireDatabasePDO::getInstance($config);
			$this->wire('database', $database); 
			$db = new DatabaseMysqli($config);
			$this->wire('db', $db);
		} catch(Exception $e) {
			// catch and re-throw to prevent DB connect info from ever appearing in debug backtrace
			$this->trackException($e, true, 'Unable to load WireDatabasePDO');
			throw new WireDatabaseException($e->getMessage()); 
		}
		
		$cache = new WireCache(); 
		$this->wire('cache', $cache); 
		$cache->preload($config->preloadCacheNames); 

		try { 		
			if($this->debug) Debug::timer('boot.load.modules');
			$modules = new Modules($config->paths->modules);
			$modules->addPath($config->paths->siteModules);
			$this->wire('modules', $modules, true); 
			$modules->setSubstitutes($config->substituteModules); 
			$modules->init();
			if($this->debug) Debug::saveTimer('boot.load.modules');
		} catch(Exception $e) {
			$this->trackException($e, true, 'Unable to load Modules');
			if(!$modules) throw new WireException($e->getMessage()); 	
			$this->error($e->getMessage()); 
		}
		$this->updater = $modules->get('SystemUpdater'); 
		if(!$this->updater) {
			$modules->resetCache();
			$this->updater = $modules->get('SystemUpdater');
		}

		$fieldtypes = new Fieldtypes();
		$fields = new Fields();
		$fieldgroups = new Fieldgroups();
		$templates = new Templates($fieldgroups, $config->paths->templates); 

		$this->wire('fieldtypes', $fieldtypes, true); 
		$this->wire('fields', $fields, true); 
		$this->wire('fieldgroups', $fieldgroups, true); 
		$this->wire('templates', $templates, true); 
		
		$pages = new Pages();
		$this->wire('pages', $pages, true);

		$this->initVar('fieldtypes', $fieldtypes);
		$this->initVar('fields', $fields);
		$this->initVar('fieldgroups', $fieldgroups);
		$this->initVar('templates', $templates);
		$this->initVar('pages', $pages); 
	
		if($this->debug) Debug::timer('boot.load.permissions'); 
		if(!$t = $templates->get('permission')) throw new WireException("Missing system template: 'permission'"); 
		$permissions = new Permissions($t, $config->permissionsPageID); 
		$this->wire('permissions', $permissions, true);
		if($this->debug) Debug::saveTimer('boot.load.permissions');

		if($this->debug) Debug::timer('boot.load.roles'); 
		if(!$t = $templates->get('role')) throw new WireException("Missing system template: 'role'"); 
		$roles = new Roles($t, $config->rolesPageID); 
		$this->wire('roles', $roles, true);
		if($this->debug) Debug::saveTimer('boot.load.roles');

		if($this->debug) Debug::timer('boot.load.users'); 
		$users = new Users($config->userTemplateIDs, $config->usersPageIDs); 
		$this->wire('users', $users, true);
		if($this->debug) Debug::saveTimer('boot.load.users'); 

		// the current user can only be determined after the session has been initiated
		$session = new Session(); 
		$this->wire('session', $session, true); 
		$this->wire('user', $users->getCurrentUser()); 
		$this->wire('input', new WireInput(), true); 

		// populate admin URL before modules init()
		$config->urls->admin = $config->urls->root . ltrim($pages->_path($config->adminRootPageID), '/');

		if($this->debug) Debug::saveTimer('boot.load', 'includes all boot.load timers');
		$this->setStatus(self::statusInit);
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

}




