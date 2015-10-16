<?php

/**
 * Common functions in root namespace for ProcessWire 2.x template/module compatibility
 * 
 * This enable backwards compatability with many templates or modules coded for ProcessWire 2.x.
 * To enable or disable, set $config->compat2x = true|false; in your /site/config.php
 *
 * ProcessWire 3.x (development), Copyright 2015 by Ryan Cramer
 * https://processwire.com
 *
 */



/**** COMMON FUNCTIONS *************************************************************************/

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
	return \ProcessWire\wire($name);
}

/**
 * Create a directory that is writable to ProcessWire and uses the $config chmod settings
 *
 * @param string $path
 * @param bool $recursive If set to true, all directories will be created as needed to reach the end.
 * @param string $chmod Optional mode to set directory to (default: $config->chmodDir), format must be a string i.e. "0755"
 * 	If omitted, then ProcessWire's $config->chmodDir setting is used instead.
 * @return bool
 *
 */
function wireMkdir($path, $recursive = false, $chmod = null) {
	return \ProcessWire\wireMkdir($path, $recursive, $chmod);
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
	return \ProcessWire\wireRmdir($path, $recursive);
}

/**
 * Change the mode of a file or directory, consistent with PW's chmodFile/chmodDir settings
 *
 * @param string $path May be a directory or a filename
 * @param bool $recursive If set to true, all files and directories in $path will be recursively set as well.
 * @param string If you want to set the mode to something other than PW's chmodFile/chmodDir settings,
 * 	you may override it by specifying it here. Ignored otherwise. Format should be a string, like "0755".
 * @return bool Returns true if all changes were successful, or false if at least one chmod failed.
 * @throws WireException when it receives incorrect chmod format
 *
 */
function wireChmod($path, $recursive = false, $chmod = null) {
	return \ProcessWire\wireChmod($path, $recursive, $chmod);
}

/**
 * Copy all files in directory $src to directory $dst
 *
 * The default behavior is to also copy directories recursively.
 *
 * @param string $src Path to copy files from
 * @param string $dst Path to copy files to. Directory is created if it doesn't already exist.
 * @param bool|array Array of options:
 * 	- recursive (boolean): Whether to copy directories within recursively. (default=true)
 * 	- allowEmptyDirs (boolean): Copy directories even if they are empty? (default=true)
 * 	- If a boolean is specified for $options, it is assumed to be the 'recursive' option.
 * @return bool True on success, false on failure.
 *
 */
function wireCopy($src, $dst, $options = array()) {
	return \ProcessWire\wireCopy($src, $dst, $options); 
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
	return \ProcessWire\wireSendFile($filename, $options, $headers);
}

/**
 * Given a unix timestamp (or date string), returns a formatted string indicating the time relative to now
 *
 * Example: 1 day ago, 30 seconds ago, etc.
 *
 * Based upon: http://www.php.net/manual/en/function.time.php#89415
 *
 * @param int|string $ts Unix timestamp or date string
 * @param bool|int|array $abbreviate Whether to use abbreviations for shorter strings.
 * 	Specify boolean TRUE for abbreviations (abbreviated where common, not always different from non-abbreviated)
 * 	Specify integer 1 for extra short abbreviations (all terms abbreviated into shortest possible string)
 * 	Specify boolean FALSE or omit for no abbreviations.
 * 	Specify associative array of key=value pairs of terms to use for abbreviations. The possible keys are:
 * 		just now, ago, from now, never
 * 		second, minute, hour, day, week, month, year, decade
 * 		seconds, minutes, hours, days, weeks, months, years, decades
 * @param bool $useTense Whether to append a tense like "ago" or "from now",
 * 	May be ok to disable in situations where all times are assumed in future or past.
 * 	In abbreviate=1 (shortest) mode, this removes the leading "+" or "-" from the string.
 * @return string
 *
 */
function wireRelativeTimeStr($ts, $abbreviate = false, $useTense = true) {
	return \ProcessWire\wireRelativeTimeStr($ts, $abbreviate, $useTense);
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
	return \ProcessWire\wireMail($to, $from, $subject, $body, $options);
}

/**
 * Given a filename, render it as a ProcessWire template file
 *
 * This is a shortcut to using the TemplateFile class.
 *
 * File is assumed relative to /site/templates/ (or a directory within there) unless you specify a full path.
 * If you specify a full path, it will accept files in or below site/templates/, site/modules/, wire/modules/.
 *
 * Note this function returns the output for you to output wherever you want (delayed output).
 * For direct output, use the wireInclude() function instead.
 *
 * @param string $filename Assumed relative to /site/templates/ unless you provide a full path name with the filename.
 * 	If you provide a path, it must resolve somewhere in site/templates/, site/modules/ or wire/modules/.
 * @param array $vars Optional associative array of variables to send to template file.
 * 	Please note that all template files automatically receive all API variables already (you don't have to provide them)
 * @param array $options Associative array of options to modify behavior:
 * 	- defaultPath: Path where files are assumed to be when only filename or relative filename is specified (default=/site/templates/)
 *  - autoExtension: Extension to assume when no ext in filename, make blank for no auto assumption (default=php)
 * 	- allowedPaths: Array of paths that are allowed (default is templates, core modules and site modules)
 * 	- allowDotDot: Allow use of ".." in paths? (default=false)
 * 	- throwExceptions: Throw exceptions when fatal error occurs? (default=true)
 * @return string|bool Rendered template file or boolean false on fatal error (and throwExceptions disabled)
 * @throws WireException if template file doesn't exist
 *
 */
function wireRenderFile($filename, array $vars = array(), array $options = array()) {
	return \ProcessWire\wireRenderFile($filename, $vars, $options);
}

/**
 * Include a PHP file passing it all API variables and optionally your own specified variables
 *
 * This is the same as PHP's include() function except for the following:
 * - It receives all API variables and optionally your custom variables
 * - If your filename is not absolute, it doesn't look in PHP's include path, only in the current dir.
 * - It only allows including files that are part of the PW installation: templates, core modules or site modules
 * - It will assume a ".php" extension if filename has no extension.
 *
 * Note this function produced direct output. To retrieve output as a return value, use the
 * wireTemplateFile function instead.
 *
 * @param $filename
 * @param array $vars Optional variables you want to hand to the include (associative array)
 * @param array $options Array of options to modify behavior:
 * 	- func: Function to use: include, include_once, require or require_once (default=include)
 *  - autoExtension: Extension to assume when no ext in filename, make blank for no auto assumption (default=php)
 * 	- allowedPaths: Array of paths include files are allowed from. Note current dir is always allowed.
 * @return bool Returns true
 * @throws WireException if file doesn't exist or is not allowed
 *
 */
function wireIncludeFile($filename, array $vars = array(), array $options = array()) {
	return \ProcessWire\wireIncludeFile($filename, $vars, $options);
}

/**
 * Format a date, using PHP date(), strftime() or other special strings (see arguments).
 *
 * This is designed to work the same wa as PHP's date() but be able to accept any common format
 * used in ProcessWire. This is helpful in reducing code in places where you might have logic
 * determining when to use date(), strftime(), or wireRelativeTimeStr().
 *
 * @param string|int $format Use one of the following:
 *  - PHP date() format
 * 	- PHP strftime() format (detected by presence of a '%' somewhere in it)
 * 	- 'relative': for a relative date/time string.
 *  - 'relative-': for a relative date/time string with no tense.
 * 	- 'rel': for an abbreviated relative date/time string.
 * 	- 'rel-': for an abbreviated relative date/time string with no tense.
 * 	- 'r': for an extra-abbreviated relative date/time string.
 * 	- 'r-': for an extra-abbreviated relative date/time string with no tense.
 * 	- 'ts': makes it return a unix timestamp
 * 	- '': blank string makes it use the system date format ($config->dateFormat)
 * 	- If given an integer and no second argument specified, it is assumed to be the second ($ts) argument.
 * @param int|string|null $ts Optionally specify the date/time stamp or strtotime() compatible string.
 * 	If not specified, current time is used.
 * @return string|bool Formatted date/time, or boolean false on failure
 *
 */
function wireDate($format = '', $ts = null) {
	return \ProcessWire\wireDate($format, $ts);
}

/**
 * Given a quantity of bytes, return a more readable size string
 *
 * @param int $size
 * @return string
 *
 */
function wireBytesStr($size) {
	return \ProcessWire\wireBytesStr($size);
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
	return \ProcessWire\wireEncodeJSON($data, $allowEmpty, $beautify);
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
	return \ProcessWire\wireDecodeJSON($json);
}


/*** LANGUAGE FUNCTIONS **************************************************************************************/

/**
 * Perform a language translation
 *
 * @param string $text Text for translation.
 * @param string $textdomain Textdomain for the text, may be class name, filename, or something made up by you. If ommitted, a debug backtrace will attempt to determine it automatically.
 * @param string $context Name of context - DO NOT USE with this function for translation as it won't be parsed for translation. Use only with the _x() function, which will be parsed.
 * @return string Translated text or original text if translation not available.
 *
 *
 */
function __($text, $textdomain = null, $context = '') {
	return \ProcessWire\__($text, $textdomain, $context);
}

/**
 * Perform a language translation in a specific context
 *
 * Used when to text strings might be the same in English, but different in other languages.
 *
 * @param string $text Text for translation.
 * @param string $context Name of context
 * @param string $textdomain Textdomain for the text, may be class name, filename, or something made up by you. If ommitted, a debug backtrace will attempt to determine automatically.
 * @return string Translated text or original text if translation not available.
 *
 */
function _x($text, $context, $textdomain = null) {
	return \ProcessWire\__($text, $textdomain, $context);
}

/**
 * Perform a language translation with singular and plural versions
 *
 * @param string $textSingular Singular version of text (when there is 1 item)
 * @param string $textPlural Plural version of text (when there are multiple items or 0 items)
 * @param int $count Quantity of items, should be 0 or more.
 * @param string $textdomain Textdomain for the text, may be class name, filename, or something made up by you. If ommitted, a debug backtrace will attempt to determine automatically.
 * @return string Translated text or original text if translation not available.
 *
 */
function _n($textSingular, $textPlural, $count, $textdomain = null) {
	return \ProcessWire\_n($textSingular, $textPlural, $count, $textdomain);
}

