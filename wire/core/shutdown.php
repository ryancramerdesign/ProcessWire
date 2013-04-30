<?php

/**
 * ProcessWire shutdown handler
 *
 * ProcessWire 2.x
 * Copyright (C) 2013 by Ryan Cramer
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * Look for errors at shutdown and log them, plus echo the error if the page is editable
 *
 * http://processwire.com
 *
 */

register_shutdown_function('ProcessWireShutDown');

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
	if(!$error) return true;
	$type = $error['type'];
	if(!in_array($type, $fatalTypes)) return true;

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

