<?php

/**
 * ProcessWire API Bootstrap
 *
 * Initializes all the ProcessWire classes and prepares them for API use
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2010 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://www.processwire.com
 * http://www.ryancramer.com
 *
 */

define("PROCESSWIRE_CORE_PATH", dirname(__FILE__) . '/'); 

spl_autoload_register('ProcessWireClassLoader'); 

require(PROCESSWIRE_CORE_PATH . "Interfaces.php"); 
require(PROCESSWIRE_CORE_PATH . "Exceptions.php"); 
require(PROCESSWIRE_CORE_PATH . "Functions.php"); 

register_shutdown_function('ProcessWireShutDown'); 

/**
 * ProcessWire Bootstrap class
 *
 * Gets ProcessWire's API ready for use
 *
 */ 
class ProcessWire extends Wire {

	const versionMajor = 2; 
	const versionMinor = 1; 
	const versionRevision = '0 rc2'; 

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

		Wire::setFuel('config', $config); 

		ini_set("date.timezone", $config->timezone);

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

	}

	/**
	 * Load's ProcessWire using the supplied Config and populates all API fuel
 	 *
	 * $param Config $config
 	 *
	 */
	public function load(Config $config) {

		Wire::setFuel('wire', $this); 
		Wire::setFuel('notices', new Notices()); 
		Wire::setFuel('sanitizer', new Sanitizer()); 

		if($config->dbSocket) {
			$db = new Database($config->dbHost, $config->dbUser, $config->dbPass, $config->dbName, $config->dbPort, $config->dbSocket);
		} else {
			$db = new Database($config->dbHost, $config->dbUser, $config->dbPass, $config->dbName, $config->dbPort);
		}

		Wire::setFuel('db', $db); 
		if($config->dbCharset) $db->set_charset($config->dbCharset); 
			else if($config->dbSetNamesUTF8) $db->query("SET NAMES 'utf8'");
	
		$modules = new Modules($config->paths->modules, $config->paths->siteModules);
		$fieldtypes = new Fieldtypes();
		$fields = new Fields();
		$fieldgroups = new Fieldgroups();
		$templates = new Templates($fieldgroups, $config->paths->templates); 

		Wire::setFuel('modules', $modules); 
		Wire::setFuel('fieldtypes', $fieldtypes); 
		Wire::setFuel('fields', $fields); 
		Wire::setFuel('fieldgroups', $fieldgroups); 
		Wire::setFuel('templates', $templates); 
		Wire::setFuel('pages', new Pages(), true);

		$fieldtypes->init();
		$fields->init();
		$fieldgroups->init();
		$templates->init();

		if(!$t = $templates->get('permission')) throw new WireException("Missing system template: 'permission'"); 
		$permissions = new Permissions($t, $config->permissionsPageID); 
		Wire::setFuel('permissions', $permissions); 

		if(!$t = $templates->get('role')) throw new WireException("Missing system template: 'role'"); 
		$roles = new Roles($t, $config->rolesPageID); 
		Wire::setFuel('roles', $roles); 

		if(!$t = $templates->get('user')) throw new WireException("Missing system template: 'user'"); 
		$users = new Users($t, $config->usersPageID); 
		Wire::setFuel('users', $users); 

		// the current user can only be determined after the session has been initiated
		$session = new Session(); 
		Wire::setFuel('session', $session); 
		Wire::setFuel('user', $users->getCurrentUser()); 
		Wire::setFuel('input', new WireInput()); 

		$modules->init();

	}
}

/**
 * Handles dynamic loading of classes as registered with spl_autoload_register
 *
 */
function ProcessWireClassLoader($className) {

	if($className[0] == 'W' && $className != 'Wire' && strpos($className, 'Wire') === 0) {
		$className = substr($className, 4); 
	}

	$file = PROCESSWIRE_CORE_PATH . "$className.php"; 

	if(is_file($file)) {
		require($file); 

	} else if($modules = Wire::getFuel('modules')) {
		$modules->includeModule($className);

	} else die($className); 
}


/**
 * Look for errors at shutdown and log them, plus echo the error if the page is editable
 *
 */
function ProcessWireShutdown() {

	$types = array(
		E_ERROR => 'Error',
		E_WARNING => 'Warning',
		E_PARSE => 'Parse Error',
		E_NOTICE => 'Notice', 
		E_CORE_ERROR => 'Core Error', 
		E_CORE_WARNING => 'Core Warning', 
		E_COMPILE_ERROR => 'Compile Error', 
		E_COMPILE_WARNING => 'Compile Warning', 
		E_USER_ERROR => 'ProcessWire Error', 
		E_USER_WARNING => 'User Warning', 
		E_USER_NOTICE => 'User Notice', 
		E_STRICT => 'Strict Warning', 
		E_RECOVERABLE_ERROR => 'Recoverable Fatal Error'
		);

	$fatalTypes = array(
		E_ERROR,
		E_CORE_ERROR,
		E_COMPILE_ERROR,
		E_USER_ERROR,
		);

	$error = error_get_last();
	$type = $error['type'];

	if(in_array($type, $fatalTypes)) {

		$config = wire('config');
		$user = wire('user');
		$userName = $user ? $user->name : 'Unknown User';
		$page = wire('page'); 
		$path = $page ? $page->url : '/?/'; 
		$line = $error['line'];
		$file = $error['file'];
		$message = "$error[message]";
		$debug = false; 

		if($type != E_USER_ERROR) $message .= " (line $line of $file)";

		if($config) {
			$debug = $config->debug; 
			$logMessage = "$userName:$path:$types[$type]:$message";
			if($config->adminEmail) @mail($config->adminEmail, 'ProcessWire Error Notification', $logMessage); 
			$logMessage = str_replace("\n", " ", $logMessage); 
			if($config->paths->logs) {
				$log = new FileLog($config->paths->logs . "errors.txt");
				$log->save($logMessage); 
			}
		}

		if($debug || !isset($_SERVER['HTTP_HOST']) || ($user && $user->isSuperuser())) {
			// when in debug mode, we can assume the message was already shown, so we just say why.
			// when not in debug mode, we display the full error message since error_reporting and display_errors are off.
			if($debug) $message = "This error message was shown because the site is in DEBUG mode.";
				else $message .= "\n\nThis error message was shown because you are logged in as a Superuser.";
			if(isset($_SERVER['HTTP_HOST'])) $message = "<p class='WireFatalError'>" . nl2br($message) . "</p>";
			echo "\n\n$message\n\n";
		} else {
			header("HTTP/1.1 500 Internal Server Error");
			echo "Unable to complete this request due to an error";
		}
	}
}

