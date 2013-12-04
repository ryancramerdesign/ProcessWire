<?php

/**
 * ProcessWire API Bootstrap
 *
 * Initializes all the ProcessWire classes and prepares them for API use
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2013 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://processwire.com
 *
 */

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
	const versionMinor = 3; 
	const versionRevision = 8; 
	
	const statusBoot = 0; // system is booting
	const statusInit = 2; // system and modules are initializing
	const statusReady = 4; // system and $page are ready
	const statusRender = 8; // $page's template is being rendered
	const statusFinished = 16; // request has been delivered
	const statusFailed = 1024; // request failed due to exception or 404

	/**
	 * Given a Config object, instantiates ProcessWire and it's API
 	 *
	 */ 
	public function __construct(Config $config) {
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

		ini_set("date.timezone", $config->timezone);
		ini_set('default_charset','utf-8');

		if(!$config->templateExtension) $config->templateExtension = 'php';
		if(!$config->httpHost) {
			if(isset($_SERVER['HTTP_HOST']) && $host = $_SERVER['HTTP_HOST']) {
				if(!preg_match('/^[-a-zA-Z0-9.:]+$/D', $host)) $host = '';
				$config->httpHost = $host;
			}
		}

		$config->https = !empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on'; 
		$config->ajax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest');
		$config->version = self::versionMajor . "." . self::versionMinor . "." . self::versionRevision; 
		$this->setStatus(self::statusBoot);

	}

	/**
	 * Load's ProcessWire using the supplied Config and populates all API fuel
 	 *
	 * $param Config $config
 	 *
	 */
	public function load(Config $config) {

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
			throw new WireDatabaseException($e->getMessage()); 
		}

		$modules = new Modules($config->paths->modules, $config->paths->siteModules);
		Wire::setFuel('modules', $modules, true); 

		if(!$updater = $modules->get('SystemUpdater')) {
			$modules->resetCache();
			$modules->get('SystemUpdater');
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

		$fieldtypes->init();
		$fields->init();
		$fieldgroups->init();
		$templates->init();

		if(!$t = $templates->get('permission')) throw new WireException("Missing system template: 'permission'"); 
		$permissions = new Permissions($t, $config->permissionsPageID); 
		$this->wire('permissions', $permissions, true); 

		if(!$t = $templates->get('role')) throw new WireException("Missing system template: 'role'"); 
		$roles = new Roles($t, $config->rolesPageID); 
		$this->wire('roles', $roles, true); 

		if(!$t = $templates->get('user')) throw new WireException("Missing system template: 'user'"); 
		$users = new Users($t, $config->usersPageID); 
		$this->wire('users', $users, true); 

		// the current user can only be determined after the session has been initiated
		$session = new Session(); 
		$this->wire('session', $session, true); 
		$this->wire('user', $users->getCurrentUser()); 
		$this->wire('input', new WireInput(), true); 

		// populate admin URL before modules init()
		$config->urls->admin = $config->urls->root . ltrim($pages->_path($config->adminRootPageID), '/'); 

		$this->setStatus(self::statusInit);
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
		
		if($status == self::statusInit) {
			$this->wire('modules')->triggerInit();
			
		} else if($status == self::statusReady) {
			$this->wire('modules')->triggerReady();
		}
	}

	/**
	 * Set a new API variable
	 * 
	 * Alias of $this->wire(), but for setting only, for syntactic convenience.
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
	
}




