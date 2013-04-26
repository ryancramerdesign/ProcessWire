<?php

/**
 * ProcessWire Functions
 *
 * Common API functions useful outside of class scope
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2011 by Ryan Cramer 
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

/**
 * Encode array for storage and remove empty values
 *
 * Uses json_encode and works the same way except this function clears out empty root-level values.
 * It also forces number strings that can be integers to be integers. 
 *
 * The end result of all this is more optimized JSON.
 *
 * Use json_encode() instead if you don't want any empty values removed. 
 *
 * @param array $data Array to be encoded to JSON
 * @param bool|array $allowEmpty Should empty values be allowed in the encoded data? 
 *	- Specify false to exclude all empty values (this is the default if not specified). 
 * 	- Specify true to allow all empty values to be retained.
 * 	- Specify an array of keys (from data) that should be retained if you want some retained and not others.
 * 	- Specify the digit 0 to retain values that are 0, but not other types of empty values.
 * @param array $keepKeys Array of keys from $data that should still be included even if blank (optional). Applicable only if $allowEmpty is true. 
 * @return string String of JSON data
 *
 */
function wireEncodeJSON(array $data, $allowEmpty = false) {

	foreach($data as $key => $value) {

		// make sure ints are stored as ints
		if(is_string($value) && ctype_digit("$value") && $value <= PHP_INT_MAX) $value = (int) $value;

		$data[$key] = $value;

		// skip empty values whether blank, 0, empty array, etc. 
		if(empty($value)) { 

			if($allowEmpty === 0 && $value === 0) {
				// keep it because $allowEmpty === 0 means to keep 0 values only

			} else if(is_array($allowEmpty) && !in_array($key, $allowEmpty)) {
				// remove it because it's not specifically allowed in allowEmpty
				unset($data[$key]); 

			} else if(!$allowEmpty) {
				// remove the empty value
				unset($data[$key]); 
			}
		}
	}
	if(!count($data)) return '';
	return json_encode($data);
}

/**
 * Decode JSON to array
 *
 * Uses json_decode and works the same way except that arrays are forced.
 * This is the counterpart to the wireEncodeJSON() function.
 * 
 * @param string $json A JSON encoded string
 * @return array
 *
 */
function wireDecodeJSON($json) {
	if(empty($json) || $json == '[]') return array();
	return json_decode($json, true); 
}


/**
 * Create a directory that is writable to ProcessWire and uses the $config chmod settings
 * 
 * @param string $path
 * @return bool
 *
 */ 
function wireMkdir($path) {
	if(!is_dir($path)) if(!@mkdir($path)) return false;
	$chmodDir = wire('config')->chmodDir;
	if($chmodDir) chmod($path, octdec($chmodDir));
	return true; 
}

/**
 * Remove a directory
 * 
 * @param string $path
 * @param bool $recursive If set to true, all files and directories in $path will be recursively removed as well.
 * @return bool
 *
 */ 
function wireRmdir($path, $recursive = false) {
	if(!is_dir($path)) return false;
	if(!strlen(trim($path, '/.'))) return false; // just for safety, don't proceed with empty string
	if($recursive === true) {
		$files = scandir($path);
		if(is_array($files)) foreach($files as $file) {
			if($file == '.' || $file == '..') continue; 
			$pathname = "$path/$file";
			if(is_dir($pathname)) {
				wireRmdir($pathname, true); 
			} else {
				unlink($pathname); 
			}
		}
	}
 	return rmdir($path);
}

/**
 * Send the contents of the given filename via http
 *
 * This function utilizes the $content->fileContentTypes to match file extension
 * to content type headers and force-download state. 
 *
 * This function throws a WireException if the file can't be sent for some reason.
 *
 * @param string $filename Filename to send
 * @param array $options Options that you may pass in, see $_options in function for details.
 * @param array $headers Headers that are sent, see $_headers in function for details. 
 *	To remove a header completely, make its value NULL and it won't be sent.
 *
 */
function wireSendFile($filename, array $options = array(), array $headers = array()) {

	$_options = array(
		// boolean: halt program execution after file send
		'exit' => true, 
		// boolean|null: whether file should force download (null=let content-type header decide)
		'forceDownload' => null, 
		// string: filename you want the download to show on the user's computer, or blank to use existing.
		'downloadFilename' => '',
		);

	$_headers = array(
		"pragma" => "public",
		"expires" =>  "0",
		"cache-control" => "must-revalidate, post-check=0, pre-check=0",
		"content-type" => "{content-type}",
		"content-transfer-encoding" => "binary",	
		"content-length" => "{filesize}",
		);

	$options = array_merge($_options, $options);
	$headers = array_merge($_headers, $headers);
	if(!is_file($filename)) throw new WireException("File does not exist");
	$info = pathinfo($filename);
	$ext = strtolower($info['extension']);
	$contentTypes = wire('config')->fileContentTypes;
	$contentType = isset($contentTypes[$ext]) ? $contentTypes[$ext] : $contentTypes['?']; 
	$forceDownload = $options['forceDownload'];
	if(is_null($forceDownload)) $forceDownload = substr($contentType, 0, 1) === '+';
	$contentType = ltrim($contentType, '+');
	if(ini_get('zlib.output_compression')) ini_set('zlib.output_compression', 'Off');
	$tags = array('{content-type}' => $contentType, '{filesize}' => filesize($filename));

	foreach($headers as $key => $value) {
		if(is_null($value)) continue; 
		if(strpos($value, '{') !== false) $value = str_replace(array_keys($tags), array_values($tags), $value);
		header("$key: $value");
	}

	if($forceDownload) {
		$downloadFilename = empty($options['downloadFilename']) ? $info['basename'] : $options['downloadFilename'];
		header("content-disposition: attachment; filename=\"$downloadFilename\"");
	}

	@ob_clean();
	@flush();
	readfile($filename);
	if($options['exit']) exit;
}

/**
 * Given a unix timestamp (or date string), returns a formatted string indicating the time relative to now
 *
 * Example: 1 day ago, 30 seconds ago, etc. 
 *
 * Based upon: http://www.php.net/manual/en/function.time.php#89415
 *
 * @param int|string $ts Unix timestamp or date string
 * @return string
 *
 */
function wireRelativeTimeStr($ts) {

	if(empty($ts)) return "No date provided";

	$periodsSingular = array(
		__("second", __FILE__), 
		__("minute", __FILE__), 
		__("hour", __FILE__), 
		__("day", __FILE__), 
		__("week", __FILE__), 
		__("month", __FILE__), 
		__("year", __FILE__), 
		__("decade", __FILE__)
		);

	$periodsPlural = array(
		__("seconds", __FILE__), 
		__("minutes", __FILE__), 
		__("hours", __FILE__), 
		__("days", __FILE__), 
		__("weeks", __FILE__), 
		__("months", __FILE__), 
		__("years", __FILE__), 
		__("decades", __FILE__)
		); 

	$lengths = array("60","60","24","7","4.35","12","10");
	$now = time();
	if(!ctype_digit("$ts")) $ts = strtotime($ts);
	if(empty($ts)) return "Bad date";

	// is it future date or past date
	if($now > $ts) {    
		$difference = $now - $ts;
		$tense = __("ago", __FILE__);
	} else {
		$difference = $ts - $now;
		$tense = __("from now", __FILE__);
	}

	for($j = 0; $difference >= $lengths[$j] && $j < count($lengths)-1; $j++) {
		$difference /= $lengths[$j];
	}

	$difference = round($difference);
	$periods = $difference != 1 ? $periodsPlural : $periodsSingular; 
	$period = $periods[$j];

	return sprintf('%d %2$s %3$s', (int) $difference, $period, $tense); // i.e. 2 days ago (d=qty, 2=period, 3=tense)
}

