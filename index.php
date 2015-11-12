<?php

/**
 * ProcessWire Bootstrap
 *
 * This file may be used to bootstrap either the http web accessible
 * version, or the command line client version of ProcessWire. 
 *
 * Note: if you happen to change any directory references in here, please
 * do so after you have installed the site, as the installer is not informed
 * of any changes made in this file. 
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2015 by Ryan Cramer 
 * Licensed under Mozilla Public License v2.0 http://mozilla.org/MPL/2.0/
 * 
 * https://processwire.com
 *
 * @version 2.7
 *
 */

define("PROCESSWIRE", 270); // index version

/**
 * Build the ProcessWire configuration
 *
 * @return Config
 *
 */
function ProcessWireBootConfig() {

	/*
	 * Define installation paths and urls
	 *
	 */
	$rootPath = dirname(__FILE__);
	if(DIRECTORY_SEPARATOR != '/') $rootPath = str_replace(DIRECTORY_SEPARATOR, '/', $rootPath); 

	if(isset($_SERVER['HTTP_HOST'])) {
		$httpHost = strtolower($_SERVER['HTTP_HOST']);

		// when serving pages from a web server
		$rootURL = rtrim(dirname($_SERVER['SCRIPT_NAME']), "/\\") . '/';

		// check if we're being included from another script and adjust the rootPath accordingly
		$sf = empty($_SERVER['SCRIPT_FILENAME']) ? '' : dirname(realpath($_SERVER['SCRIPT_FILENAME']));
		$f = dirname(realpath(__FILE__)); 
		if($sf && $sf != $f && strpos($sf, $f) === 0) {
			$x = rtrim(substr($sf, strlen($f)), '/');
			$rootURL = substr($rootURL, 0, strlen($rootURL) - strlen($x));
		}
		unset($sf, $f, $x);

	} else {
		// when included from another app or command line script
		$httpHost = '';
		$rootURL = '/';
	}

	/*
	 * Allow for an optional /index.config.php file that can point to a different site configuration per domain/host.
	 *
	 */     
	$siteDir = 'site'; 
	$indexConfigFile = $rootPath . "/index.config.php"; 

	if(is_file($indexConfigFile)) {
		// optional config file is present in root
		@include($indexConfigFile);
		$hostConfig = function_exists("ProcessWireHostSiteConfig") ? ProcessWireHostSiteConfig() : array();
		if($httpHost && isset($hostConfig[$httpHost])) $siteDir = $hostConfig[$httpHost];
			else if(isset($hostConfig['*'])) $siteDir = $hostConfig['*']; // default override
	} 

	// other default directories
	$wireDir = 'wire';
	$coreDir = "$wireDir/core";
	$assetsDir = "$siteDir/assets";
	$adminTplDir = 'templates-admin';

	/*
	 * Setup ProcessWire class autoloads
	 *
	 */
	require("$rootPath/$coreDir/ProcessWire.php");

	/*
	 * Setup configuration data and default paths/urls
	 *
	 */
	$config = new Config();
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

	/*
	 * Styles and scripts are CSS and JS files, as used by the admin application.
	 * But reserved here if needed by other apps and templates.
	 *
	 */
	$config->styles = new FilenameArray();
	$config->scripts = new FilenameArray();

	/*
	 * Include system and user-specified configuration options
	 *
	 */
	include("$rootPath/$wireDir/config.php");
	$configFile = "$rootPath/$siteDir/config.php";
	$configFileDev = "$rootPath/$siteDir/config-dev.php";
	@include(is_file($configFileDev) ? $configFileDev : $configFile); 

	/*
	 * $config->debugIf: optional setting to determine if debug mode should be on or off
	 * 
	 */
	if($config->debugIf && is_string($config->debugIf)) {
		$debugIf = trim($config->debugIf);
		if(strpos($debugIf, '/') === 0) $debugIf = (bool) @preg_match($debugIf, $_SERVER['REMOTE_ADDR']); // regex IPs
			else if(is_callable($debugIf)) $debugIf = $debugIf(); // callable function to determine debug mode for us 
			else $debugIf = $debugIf === $_SERVER['REMOTE_ADDR']; // exact IP match
		$config->debug = $debugIf; 
	}

	/*
	 * If debug mode is on then echo all errors, if not then disable all error reporting
	 *
	 */
	if($config->debug) {
		error_reporting(E_ALL | E_STRICT);
		ini_set('display_errors', 1);
	} else {
		error_reporting(0);
		ini_set('display_errors', 0);
	}

	/*
	 * If PW2 is not installed, go to the installer
	 *
	 */
	if(!$config->dbName) { 
		if(is_file("./install.php") && strtolower($_SERVER['REQUEST_URI']) == strtolower($rootURL)) {
			require("./install.php");
			exit(0);
		} else {
			header("HTTP/1.1 404 Page Not Found");
			echo "404 page not found (no site configuration or install.php available)";
			exit(0); 
		}
	}

	/*
	 * Prepare any PHP ini_set options
	 *
	 */
	session_name($config->sessionName); 
	ini_set('session.use_cookies', true); 
	ini_set('session.use_only_cookies', 1);
	ini_set('session.cookie_httponly', 1); 
	ini_set('session.gc_maxlifetime', $config->sessionExpireSeconds);

	if(ini_get('session.save_handler') == 'files') {
		if(ini_get('session.gc_probability') == 0) {
			// Some debian distros replace PHP's gc without fully implementing it,
			// which results in broken garbage collection if the save_path is set. 
			// As a result, we avoid setting the save_path when this is detected. 
		} else {
			ini_set("session.save_path", rtrim($config->paths->sessions, '/'));
		}
	}

	return $config; 
}

/**
 * Shutdown function for cleanup of externally bootstrapped requests
 *
 */
function ProcessWireExternalShutdown() {
	if(error_get_last()) return;
	$process = wire('process'); 
	if($process == 'ProcessPageView') $process->finished();
}

/**
 * Determine if the request is one we should handle (internal) or one that another
 * script coming after this (external) will handle. 
 *
 */
$internal = isset($_SERVER['HTTP_HOST']) && realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME']);
if(!$internal) register_shutdown_function('ProcessWireExternalShutdown'); 

/*
 * If you include ProcessWire's index.php from another script, or from a
 * command-line script, the $wire variable or wire() function is your 
 * connection to the API of this ProcessWire instance. 
 *
 */
$process = null;
$wire = null;

/**
 * Build the ProcessWire configuration
 *
 */
$config = ProcessWireBootConfig(); 

/*
 * Load and execute ProcessWire
 *
 */
try { 
	
	/*
	 * Bootstrap ProcessWire's core and make the API available with $wire or wire()
	 *
	 */
	$wire = new ProcessWire($config);
	$process = $wire->modules->get('ProcessPageView');
	$wire->wire('process', $process); 
	echo $process->execute($internal);
	$internal ? $process->finished() : extract($wire->wire('all')->getArray());

} catch(Exception $e) {

	/*
	 * Formulate error message and send to the error handler
	 *
	 */
	if($process) $process->failed($e);
	$wire ? $wire->trackException($e) : $config->trackException($e);
	$errorMessage = "Exception: " . $e->getMessage() . " (in " . $e->getFile() . " line " . $e->getLine() . ")";
	if($config->debug || ($wire && $wire->user && $wire->user->isSuperuser())) $errorMessage .= "\n\n" . $e->getTraceAsString();
	trigger_error($errorMessage, E_USER_ERROR); 
}

