<?php

/**
 * ProcessWire Functions
 *
 * Common API functions useful outside of class scope
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2010 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://www.processwire.com
 * http://www.ryancramer.com
 *
 */

/**
 * Return a ProcessWire API variable, or NULL if it doesn't exist
 *
 * And the wire() function is the recommended way to access the API when included from other PHP scripts.
 * Like the fuel() function, except that ommitting $name returns the current ProcessWire instance rather than the fuel.
 * The distinction may not matter in most cases.
 *
 * @param string $name If ommitted, returns a Fuel object with references to all the fuel.
 * @return mixed Fuel value if available, NULL if not. 
 *
 */
function wire($name = 'wire') {
	return Wire::getFuel($name); 
}

/**
 * Return all Fuel, or specified ProcessWire API variable, or NULL if it doesn't exist.
 *
 * Same as Wire::getFuel($name) and Wire::getAllFuel();
 * When a $name is specified, this function is identical to the wire() function.
 * Both functions exist more for consistent naming depending on usage. 
 *
 * @param string $name If ommitted, returns a Fuel object with references to all the fuel.
 * @return mixed Fuel value if available, NULL if not. 
 *
 */
function fuel($name = '') {
	if(!$name) return Wire::getAllFuel();
	return Wire::getFuel($name); 
}

/**
 * Indent the given string with $numTabs tab characters
 *
 * Newlines are assumed to be \n
 * 
 * Watch out when using this function with strings that have a <textarea>, you may want to have it use \r newlines, at least temporarily. 
 *
 * @param string $str String that needs the tabs
 * @param int $numTabs Number of tabs to insert per line (note any existing tabs are left as-is, so indentation is retained)
 * @param string $str The provided string but with tabs inserted
 *
 */
if(!function_exists("tabIndent")): 
	function tabIndent($str, $numTabs) {
		$tabs = str_repeat("\t", $numTabs);
		$str = str_replace("\n", "\n$tabs", $str);
		return $str;
	}
endif; 

/**
 * Remove newlines from the given string and return it 
 * 
 * @param string $str
 * @return string
 *
 */
function removeNewlines($str) {
        return str_replace(array("\r", "\n", "\r\n"), ' ', $str);
}

/**
 * Emulate register globals OFF
 *
 * Should be called after session_start()
 *
 * This function is from the PHP documentation at: 
 * http://www.php.net/manual/en/faq.misc.php#faq.misc.registerglobals
 *
 */
function unregisterGLOBALS() {

	if(!ini_get('register_globals')) {
		return;
	}

	// Might want to change this perhaps to a nicer error
	if(isset($_REQUEST['GLOBALS']) || isset($_FILES['GLOBALS'])) {
		die();
	}

	// Variables that shouldn't be unset
	$noUnset = array('GLOBALS', '_GET', '_POST', '_COOKIE', '_REQUEST', '_SERVER', '_ENV', '_FILES');

	$input = array_merge($_GET, $_POST, $_COOKIE, $_SERVER, $_ENV, $_FILES, isset($_SESSION) && is_array($_SESSION) ? $_SESSION : array());

	foreach ($input as $k => $v) {
		if(!in_array($k, $noUnset) && isset($GLOBALS[$k])) {
	    		unset($GLOBALS[$k]);
		}
	}
}

