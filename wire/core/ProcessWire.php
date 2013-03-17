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

spl_autoload_register('ProcessWireClassLoader'); 

require(PROCESSWIRE_CORE_PATH . "Interfaces.php"); 
require(PROCESSWIRE_CORE_PATH . "Exceptions.php"); 
require(PROCESSWIRE_CORE_PATH . "Functions.php"); 
require(PROCESSWIRE_CORE_PATH . "LanguageFunctions.php"); 

register_shutdown_function('ProcessWireShutDown'); 

/**
 * ProcessWire Bootstrap class
 *
 * Gets ProcessWire's API ready for use
 *
 */ 
class ProcessWire extends Wire {

	const versionMajor = 2; 
	const versionMinor = 3; 
	const versionRevision = 0; 

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

		try {
			$db = new Database($config);
			Wire::setFuel('db', $db); 
		} catch(WireDatabaseException $e) {
			// catch and re-throw to prevent DB connect info from ever appearing in debug backtrace
			throw new WireDatabaseException($e->getMessage()); 
		}

		$modules = new Modules($config->paths->modules, $config->paths->siteModules);
		Wire::setFuel('modules', $modules); 

		if(!$updater = $modules->get('SystemUpdater')) {
			$modules->resetCache();
			$modules->get('SystemUpdater');
		}

		$fieldtypes = new Fieldtypes();
		$fields = new Fields();
		$fieldgroups = new Fieldgroups();
		$templates = new Templates($fieldgroups, $config->paths->templates); 

		Wire::setFuel('fieldtypes', $fieldtypes); 
		Wire::setFuel('fields', $fields); 
		Wire::setFuel('fieldgroups', $fieldgroups); 
		Wire::setFuel('templates', $templates); 

		$pages = new Pages();
		Wire::setFuel('pages', $pages, true);

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

		// populate admin URL before modules init()
		$config->urls->admin = $config->urls->root . ltrim($pages->_path($config->adminRootPageID), '/'); 

		$modules->triggerInit();

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

	} // else die($className); 
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
		E_USER_ERROR => 'Error', 
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
		E_PARSE,
		E_RECOVERABLE_ERROR,
		);

	$error = error_get_last();
	if(!$error) return; 
	$type = $error['type'];
	if(!in_array($type, $fatalTypes)) return;

	$http = isset($_SERVER['HTTP_HOST']); 
	$config = wire('config');
	$user = wire('user');
	$userName = $user ? $user->name : '?';
	$page = wire('page'); 
	$path = ($config ? $config->httpHost : '') . ($page ? $page->url : '/?/'); 
	if($config && $http) $path = ($config->https ? 'https://' : 'http://') . $path;
	$line = $error['line'];
	$file = $error['file'];
	$message = isset($types[$type]) ? $types[$type] : 'Error';
	if(strpos($error['message'], "\t") !== false) $error['message'] = str_replace("\t", ' ', $error['message']); 
	$message .= ": \t$error[message]";
	if($type != E_USER_ERROR) $message .= " (line $line of $file) ";
	$debug = false; 
	$log = null;
	$why = '';
	$who = '';

	if($config) {
		$debug = $config->debug; 
		if($config->ajax) $http = false; 
		if($config->adminEmail) {
			$logMessage = "Page: $path\nUser: $userName\n\n" . str_replace("\t", "\n", $message);
			@mail($config->adminEmail, 'ProcessWire Error Notification', $logMessage); 
		}
		if($config->paths->logs) {
			$logMessage = "$userName\t$path\t" . str_replace("\n", " ", $message); 
			$log = new FileLog($config->paths->logs . 'errors.txt');
			$log->setDelimeter("\t"); 
			$log->save($logMessage); 
		}
	}

	// we populate $who to give an ambiguous indication where the full error message has been sent
	if($log) $who .= "Error has been logged. ";
	if($config && $config->adminEmail) $who .= "Administrator has been notified. ";

	// we populate $why if we're going to show error details for any of the following reasons: 
	// otherwise $why will NOT be populated with anything
	if($debug) $why = "site is in debug mode (\$config->debug = true; in /site/config.php).";
		else if(!$http) $why = "you are using the command line API.";
		else if($user && $user->isSuperuser()) $why = "you are logged in as a Superuser.";
		else if($config && is_file($config->paths->root . "install.php")) $why = "/install.php still exists.";	
		else if($config && !is_file($config->paths->assets . "active.php")) {
			// no login has ever occurred or user hasn't logged in since upgrade before this check was in place
			// check the date the site was installed to ensure we're not dealing with an upgrade
			$installed = $config->paths->assets . "installed.php";
			if(!is_file($installed) || (filemtime($installed) > (time() - 21600))) {
				// site was installed within the last 6 hours, safe to assume it's a new install
				$why = "Superuser has never logged in.";
			}
		}

	if($why) {
		// when in debug mode, we can assume the message was already shown, so we just say why.
		// when not in debug mode, we display the full error message since error_reporting and display_errors are off.
		$why = "This error message was shown because $why $who";
		if($http) $why = "<em>$why</em>";
		$message = $debug ? $why : "$message\n\n$why";
		echo ($http ? "\n\n<p class='error WireFatalError'>" . nl2br($message) . "</p>\n\n" : "\n\n$message\n\n"); 
	} else {
		// public fatal error that doesn't reveal anything specific
		if($http) header("HTTP/1.1 500 Internal Server Error");
		// file that error message will be output in, when available
		$file = $config && $http ? $config->paths->templates . 'errors/500.html' : '';
		if($file && is_file($file)) echo str_replace('{message}', $who, file_get_contents($file)); 
			else echo "\n\nUnable to complete this request due to an error. $who\n\n";
	}

	return true; 
}

