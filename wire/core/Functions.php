<?php

/**
 * ProcessWire Functions
 *
 * Common API functions useful outside of class scope
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2013 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://processwire.com
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
 * @deprecated
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
 * @param bool $beautify Beautify the encoded data when possible for better human readability? (requires PHP 5.4+)
 * @return string String of JSON data
 *
 */
function wireEncodeJSON(array $data, $allowEmpty = false, $beautify = false) {
	if($allowEmpty !== true) $data = wireMinArray($data, $allowEmpty, true); 
	if(!count($data)) return '';
	$flags = 0; 
	if($beautify && defined("JSON_PRETTY_PRINT")) $flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
	return json_encode($data, $flags);
}

/**
 * Minimize an array to remove empty values
 *
 * @param array $data Array to reduce
 * @param bool|array $allowEmpty Should empty values be allowed in the encoded data?
 *	- Specify false to exclude all empty values (this is the default if not specified).
 * 	- Specify true to allow all empty values to be retained (thus no point in calling this function). 
 * 	- Specify an array of keys (from data) that should be retained if you want some retained and not others.
 * 	- Specify the digit 0 to retain values that are 0, but not other types of empty values.
 * @param bool $convert Perform type conversions where appropriate: i.e. convert digit-only string to integer
 * @return array
 *
 */
function wireMinArray(array $data, $allowEmpty = false, $convert = false) {
	
	foreach($data as $key => $value) {

		if($convert && is_string($value)) { 
			// make sure ints are stored as ints
			if(ctype_digit("$value") && $value <= PHP_INT_MAX) {
				if($value === "0" || $value[0] != '0') { // avoid octal conversions (leading 0)
					$value = (int) $value;
				}
			}
		} else if(is_array($value) && count($value)) {
			$value = wireMinArray($value, $allowEmpty, $convert); 	
		}

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
	
	return $data; 
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
 * @param bool $recursive If set to true, all directories will be created as needed to reach the end. 
 * @return bool
 *
 */ 
function wireMkdir($path, $recursive = false) {
	if(!strlen($path)) return false; 
	if(!is_dir($path)) {
		if($recursive) {
			$parentPath = substr($path, 0, strrpos(rtrim($path, '/'), '/')); 
			if(!is_dir($parentPath) && !wireMkdir($parentPath, true)) return false;
		}
		if(!@mkdir($path)) return false;
	}
	$chmodDir = wire('config')->chmodDir;
	if($chmodDir) @chmod($path, octdec($chmodDir));
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
 * Change the mode of a file or directory, consistent with PW's chmodFile/chmodDir settings
 * 
 * @param string $path May be a directory or a filename
 * @param bool $recursive If set to true, all files and directories in $path will be recursively set as well.
 * @param string If you want to set the mode to something other than PW's chmodFile/chmodDir settings, 
	you may override it by specifying it here. Ignored otherwise. Format should be a string, like "0755".
 * @return bool Returns true if all changes were successful, or false if at least one chmod failed. 
 * @throws WireException when it receives incorrect chmod format
 *
 */ 
function wireChmod($path, $recursive = false, $chmod = null) {

	if(is_null($chmod)) {
		// default: pull values from PW config
		$chmodFile = wire('config')->chmodFile;
		$chmodDir = wire('config')->chmodDir;
	} else {
		// optional, manually specified string
		if(!is_string($chmod)) throw new WireException("chmod must be specified as a string like '0755'"); 
		$chmodFile = $chmod;
		$chmodDir = $chmod;
	}

	$numFails = 0;

	if(is_dir($path)) {
		// $path is a directory
		if($chmodDir) if(!@chmod($path, octdec($chmodDir))) $numFails++;

		// change mode of files in directory, if recursive
		if($recursive) foreach(new DirectoryIterator($path) as $file) {
			if($file->isDot()) continue; 
			$mod = $file->isDir() ? $chmodDir : $chmodFile;     
			if($mod) if(!@chmod($file->getPathname(), octdec($mod))) $numFails++;
			if($file->isDir()) {
				if(!wireChmod($file->getPathname(), true, $chmod)) $numFails++;
			}
		}
	} else {
		// $path is a file
		$mod = $chmodFile; 
		if($mod) if(!chmod($path, octdec($mod))) $numFails++;
	}

	return $numFails == 0; 
}

/**
 * Copy all files in directory $src to directory $dst
 * 
 * The default behavior is to also copy directories recursively. 
 * 
 * @param string $src Path to copy files from
 * @param string $dst Path to copy files to. Directory is created if it doesn't already exist.
 * @param bool $recursive Whether to copy directories within recursively. Default=true.
 * @return bool True on success, false on failure.
 * 
 */
function wireCopy($src, $dst, $recursive = true) {

	if(substr($src, -1) != '/') $src .= '/';
	if(substr($dst, -1) != '/') $dst .= '/';

	$dir = opendir($src);
	if(!$dir) return false; 
	if(!wireMkdir($dst)) return false;

	while(false !== ($file = readdir($dir))) {
		if($file == '.' || $file == '..') continue;
		if($recursive && is_dir($src . $file)) {
			wireCopy($src . $file, $dst . $file);
		} else {
			copy($src . $file, $dst . $file);
			$chmodFile = wire('config')->chmodFile;
			if($chmodFile) @chmod($dst . $file, octdec($chmodFile));
		}
	}

	closedir($dir);
	return true;
}

/**
 * Unzips the given ZIP file to the destination directory
 * 
 * @param string $file ZIP file to extract
 * @param string $dst Directory where files should be unzipped into. Directory is created if it doesn't exist.
 * @return array Returns an array of filenames (excluding $dst) that were unzipped.
 * @throws WireException All error conditions result in WireException being thrown.
 * 
 */
function wireUnzipFile($file, $dst) {

	$dst = rtrim($dst, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
	
	if(!class_exists('ZipArchive')) throw new WireException("PHP's ZipArchive class does not exist"); 
	if(!is_file($file)) throw new WireException("ZIP file does not exist"); 
	if(!is_dir($dst)) wireMkdir($dst, true);	
	
	$names = array();
	$chmodFile = wire('config')->chmodFile; 
	$chmodDir = wire('config')->chmodDir;
	
	$zip = new ZipArchive();
	$res = $zip->open($file); 
	if($res !== true) throw new WireException("Unable to open ZIP file, error code: $res"); 
	
	for($i = 0; $i < $zip->numFiles; $i++) {
		$name = $zip->getNameIndex($i); 
		if($zip->extractTo($dst, $name)) {
			$names[$i] = $name; 
			$filename = $dst . ltrim($name, '/');
			if(is_dir($filename)) {
				if($chmodDir) chmod($filename, octdec($chmodDir));
			} else if(is_file($filename)) {
				if($chmodFile) chmod($filename, octdec($chmodFile));
			}
		}
	}
	
	$zip->close();
	
	return $names; 
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
 * @throws WireException
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

	@ob_end_clean();
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
 * @param bool|int $abbreviate Whether to use abbreviations for shorter strings. 
 * 	Specify boolean TRUE for abbreviations.
 * 	Specify integer 1 for extra short abbreviations.
 * 	Specify boolean FALSE or omit for no abbreviations.
 * @return string
 *
 */
function wireRelativeTimeStr($ts, $abbreviate = false) {

	if(empty($ts)) return __('Never', __FILE__); 

	$justNow = __('just now', __FILE__); 
	$ago = __('ago', __FILE__); 
	$prependAgo = '';
	$fromNow = __('from now', __FILE__); 
	$prependFromNow = '';
	$space = ' ';

	if($abbreviate === 1) {
		$justNow = __('now', __FILE__); 
		$ago = '';
		$prependAgo = '-';
		$fromNow = '';
		$prependFromNow = '+';
		$space = ''; 

		$periodsSingular = array(
			__("s", __FILE__), 
			__("m", __FILE__), 
			__("hr", __FILE__), 
			__("d", __FILE__), 
			__("wk", __FILE__), 
			__("mon", __FILE__), 
			__("yr", __FILE__), 
			__("decade", __FILE__)
			);

		$periodsPlural = array(
			__("s", __FILE__), 
			__("m", __FILE__), 
			__("hr", __FILE__), 
			__("d", __FILE__), 
			__("wks", __FILE__), 
			__("mths", __FILE__), 
			__("yrs", __FILE__), 
			__("decades", __FILE__)
			); 
	} else if($abbreviate === true) {

		$justNow = __('now', __FILE__); 
		$fromNow = '';
		$prependFromNow = __('in', __FILE__) . ' ';

		$periodsSingular = array(
			__("sec", __FILE__), 
			__("min", __FILE__), 
			__("hr", __FILE__), 
			__("day", __FILE__), 
			__("week", __FILE__), 
			__("month", __FILE__), 
			__("year", __FILE__), 
			__("decade", __FILE__)
			);

		$periodsPlural = array(
			__("secs", __FILE__), 
			__("mins", __FILE__), 
			__("hrs", __FILE__), 
			__("days", __FILE__), 
			__("weeks", __FILE__), 
			__("months", __FILE__), 
			__("years", __FILE__), 
			__("decades", __FILE__)
			); 

	} else {
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
	}

	
	$lengths = array("60","60","24","7","4.35","12","10");
	$now = time();
	if(!ctype_digit("$ts")) $ts = strtotime($ts);
	if(empty($ts)) return "";

	// is it future date or past date
	if($now > $ts) {    
		$difference = $now - $ts;
		$tense = $ago; 
		$prepend = $prependAgo; 
	} else {
		$difference = $ts - $now;
		$tense = $fromNow; 
		$prepend = $prependFromNow; 
	}

	for($j = 0; $difference >= $lengths[$j] && $j < count($lengths)-1; $j++) {
		$difference /= $lengths[$j];
	}

	$difference = round($difference);
	if(!$difference) return $justNow; 
	
	$periods = $difference != 1 ? $periodsPlural : $periodsSingular; 
	$period = $periods[$j];
	
	// return sprintf('%s%d%s%s %s', $prepend, (int) $difference, $space, $period, $tense); // i.e. 2 days ago (d=qty, 2=period, 3=tense)

	$quantity = $prepend . $difference . $space; 
	$format = __('Q P T', __FILE__); // Relative time order: Q=Quantity, P=Period, T=Tense (i.e. 2 Days Ago)
	$out = str_replace(array('Q', 'P', 'T'), array(" $quantity", " $period", " $tense"), $format); 
	if($abbreviate === 1) $out = str_replace("$quantity $period", "$quantity$period", $out); 
	return trim($out); 
}


/**
 * Send an email or retrieve the mailer object
 *
 * Note 1: The order of arguments is different from PHP's mail() function. 
 * Note 2: If no arguments are specified it simply returns a WireMail object (see #4 below).
 *
 * This function will attempt to use an installed module that extends WireMail.
 * If no module is installed, WireMail (which uses PHP mail) will be used instead.
 *
 * This function can be called in these ways:
 *
 * 1. Default usage: 
 * 
 *    wireMail($to, $from, $subject, $body, $options); 
 * 
 *
 * 2. Specify body and/or bodyHTML in $options array (perhaps with other options): 
 * 
 *    wireMail($to, $from, $subject, $options); 
 *
 *
 * 3. Specify both $body and $bodyHTML as arguments, but no $options: 
 * 
 *    wireMail($to, $from, $subject, $body, $bodyHTML); 
 * 
 *
 * 4. Specify a blank call to wireMail() to get the WireMail sending object. This can
 *    be either WireMail() or a class descending from it. If a WireMail descending
 *    module is installed, it will be returned rather than WireMail():
 * 
 *    $mail = wireMail(); 
 *    $mail->to('user@domain.com')->from('you@company.com'); 
 *    $mail->subject('Mail Subject')->body('Mail Body Text')->bodyHTML('Body HTML'); 
 *    $numSent = $mail->send();
 * 
 *
 * @param string|array $to Email address TO. For multiple, specify CSV string or array. 
 * @param string $from Email address FROM. This may be an email address, or a combined name and email address. 
 *	Example of combined name and email: Karen Cramer <karen@processwire.com>
 * @param string $subject Email subject
 * @param string|array $body Email body or omit to move straight to $options
 * @param array|string $options Array of options OR the $bodyHTML string. Array $options are:
 * 	body: string
 * 	bodyHTML: string
 * 	headers: associative array of header name => header value
 *	Any additional options will be sent along to the WireMail module or class, in tact.
 * @return int|WireMail Returns number of messages sent or WireMail object if no arguments specified. 
 *
 */

function wireMail($to = '', $from = '', $subject = '', $body = '', $options = array()) { 

	$mail = null; 
	$modules = wire('modules'); 

	// attempt to locate an installed module that overrides WireMail
	foreach($modules as $module) {
		$parents = class_parents("$module"); 
		if(in_array('WireMail', $parents) && $modules->isInstalled("$module")) { 
			$mail = wire('modules')->get("$module"); 
			break;
		}
	}
	// if no module found, default to WireMail
	if(is_null($mail)) $mail = new WireMail(); 

	// reset just in case module was not singular
	$mail->to(); 

	if(empty($to)) {
		// use case #4: no arguments supplied, just return the WireMail object
		return $mail;
	}

	$defaults = array(
		'body' => $body, 
		'bodyHTML' => '', 
		'headers' => array(), 
		); 

	if(is_array($body)) {
		// use case #2: body is provided in $options
		$options = $body; 
	} else if(is_string($options)) {
		// use case #3: body and bodyHTML are provided, but no $options
		$options = array('bodyHTML' => $options); 
	} else {
		// use case #1: default behavior
	}
		
	$options = array_merge($defaults, $options); 

	try {
		// configure the mail
		$mail->to($to)->from($from)->subject($subject)->body($options['body']); 
		if(strlen($options['bodyHTML'])) $mail->bodyHTML($options['bodyHTML']); 
		if(count($options['headers'])) foreach($options['headers'] as $k => $v) $mail->header($k, $v); 
		// send along any options we don't recognize
		foreach($options as $key => $value) {
			if(!array_key_exists($key, $defaults)) $mail->$key = $value; 
		}
		$numSent = $mail->send(); 

	} catch(Exception $e) {
		if(wire('config')->debug) $mail->error($e->getMessage());
		$numSent = 0;
	}

	return $numSent; 	
}


/**
 * Given a string $str and values $vars, replace tags in the string with the values
 *
 * The $vars may also be an object, in which case values will be pulled as properties of the object. 
 *
 * By default, tags are specified in the format: {first_name} where first_name is the name of the
 * variable to pull from $vars, '{' is the opening tag character, and '}' is the closing tag char.
 *
 * The tag parser can also handle subfields and OR tags, if $vars is an object that supports that.
 * For instance {products.title} is a subfield, and {first_name|title|name} is an OR tag. 
 *
 * @param string $str The string to operate on (where the {tags} might be found)
 * @param WireData|object|array Object or associative array to pull replacement values from. 
 * @param array $options Array of optional changes to default behavior, including: 
 * 	- tagOpen: The required opening tag character(s), default is '{'
 *	- tagClose: The optional closing tag character(s), default is '}'
 *	- recursive: If replacement value contains tags, populate those too? Default=false. 
 *	- removeNullTags: If a tag resolves to a NULL, remove it? If false, tag will remain. Default=true. 
 *	- entityEncode: Entity encode the values pulled from $vars? Default=false. 
 *	- entityDecode: Entity decode the values pulled from $vars? Default=false.
 * @return string String with tags populated. 
 *
 */
function wirePopulateStringTags($str, $vars, array $options = array()) {

	$defaults = array(
		// opening tag (required)
		'tagOpen' => '{', 
		// closing tag (optional)
		'tagClose' => '}', 
		// if replacement value contains tags, populate those too?
		'recursive' => false, 
		// if a tag value resolves to a NULL, remove it? If false, tag will be left in tact.
		'removeNullTags' => true, 
		// entity encode values pulled from $vars?
		'entityEncode' => false, 	
		// entity decode values pulled from $vars?
		'entityDecode' => false, 
		);

	$options = array_merge($defaults, $options); 

	// check if this string even needs anything populated
	if(strpos($str, $options['tagOpen']) === false) return $str; 
	if(strlen($options['tagClose']) && strpos($str, $options['tagClose']) === false) return $str; 

	// find all tags
	$tagOpen = preg_quote($options['tagOpen']);
	$tagClose = preg_quote($options['tagClose']); 
	$numFound = preg_match_all('/' . $tagOpen . '([-_.|a-zA-Z0-9]+)' . $tagClose . '/', $str, $matches);
	if(!$numFound) return $str; 
	$replacements = array();

	// create a list of replacements by finding replacement values in $vars
	foreach($matches[1] as $key => $fieldName) {

		$tag = $matches[0][$key];
		if(isset($replacements[$tag])) continue; // if already found, don't continue
		$fieldValue = null;

		if(is_object($vars)) {
			if($vars instanceof WireData) $fieldValue = $vars->get($fieldName); 
				else $fieldValue = $vars->$fieldName; 
		} else if(is_array($vars)) {
			$fieldValue = isset($vars[$fieldName]) ? $vars[$fieldName] : null;
		}

		if($options['entityEncode']) $fieldValue = htmlentities($fieldValue, ENT_QUOTES, 'UTF-8', false); 
		if($options['entityDecode']) $fieldValue = html_entity_decode($fieldValue, ENT_QUOTES, 'UTF-8'); 

		$replacements[$tag] = $fieldValue; 
	}

	// replace the tags 
	foreach($replacements as $tag => $value) {

		// populate tags recursively, if asked to do so
		if($options['recursive'] && strpos($value, $options['tagOpen'])) {
			$opt = array_merge($options, array('recursive' => false)); // don't go recursive beyond 1 level
			$value = wirePopulateStringTags($value, $vars, $opt); 
		}

		// replace tags with replacement values
		$str = str_replace($tag, $value, $str); 
	}

	return $str; 
}


/**
 * Return a new temporary directory/path ready to use for files
 * 
 * @param object|string $name Provide the object that needs the temp dir, or name your own string
 * @param int $maxAge Maximum age of temp dir files in seconds
 * @return WireTempDir
 * 
 */
function wireTempDir($name, $maxAge = 120) {
	static $tempDirs = array();
	if(isset($tempDirs[$name])) return $tempDirs[$name]; 
	$tempDir = new WireTempDir($name, $maxAge); 
	$tempDirs[$name] = $tempDir; 
	return $tempDir; 
}
