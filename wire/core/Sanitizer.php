<?php

/**
 * ProcessWire Sanitizer
 *
 * Sanitizer provides shared sanitization functions as commonly used throughout ProcessWire core and modules
 *
 * Modules may also add methods to the Sanitizer as needed i.e. $this->sanitizer->addHook('myMethod', $myClass, 'myMethod'); 
 * See the Wire class definition for more details about the addHook method. 
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2013 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://processwire.com
 *
 * @link http://processwire.com/api/variables/sanitizer/ Offical $sanitizer API variable Documentation
 *
 */

class Sanitizer extends Wire {

	/**
	 * May be passed to pageName for the $beautify param, see pageName for details.
	 *
	 */
	const translate = 2; 

	/**
	 * Caches the status of multibyte support.
	 *
	 */
	protected $multibyteSupport = false;

	/**
	 * Array of allowed ascii characters for name filters
	 *
	 */
	protected $allowedASCII = array();

	/**
	 * Construct the sanitizer
	 *
	 */
	public function __construct() {
		$this->multibyteSupport = function_exists("mb_strlen"); 
		if($this->multibyteSupport) mb_internal_encoding("UTF-8");
		$this->allowedASCII = str_split('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789');
	}

	/**
	 * Internal filter used by other name filtering methods in this class
	 *
	 * @param string $value Value to filter
	 * @param array $allowedExtras Additional characters that are allowed in the value
	 * @param string 1 character replacement value for invalid characters
	 * @param bool $beautify Whether to beautify the string, specify Sanitizer::translate to perform transliteration. 
	 * @param int $maxLength
	 * @return string
	 *
	 */
	public function nameFilter($value, array $allowedExtras, $replacementChar, $beautify = false, $maxLength = 128) {
		
		static $replacements = array();

		if(!is_string($value)) $value = (string) $value;
		
		$allowed = array_merge($this->allowedASCII, $allowedExtras); 
		$needsWork = strlen(str_replace($allowed, '', $value));
		$extras = implode('', $allowedExtras);

		if($beautify && $needsWork) {
			if($beautify === self::translate && $this->multibyteSupport) {
				$value = mb_strtolower($value);

				if(empty($replacements)) {
					$configData = $this->wire('modules')->getModuleConfigData('InputfieldPageName');
					$replacements = empty($configData['replacements']) ? InputfieldPageName::$defaultReplacements : $configData['replacements'];
				}

				foreach($replacements as $from => $to) {
					if(mb_strpos($value, $from) !== false) {
						$value = mb_eregi_replace($from, $to, $value);
					}
				}
			}

			$v = iconv("UTF-8", "ASCII//TRANSLIT//IGNORE", $value);
			if($v) $value = $v;
			$needsWork = strlen(str_replace($allowed, '', $value)); 
		}

		if(strlen($value) > $maxLength) $value = substr($value, 0, $maxLength); 
		
		if($needsWork) {
			$value = str_replace(array("'", '"'), '', $value); // blank out any quotes
			$value = filter_var($value, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH | FILTER_FLAG_NO_ENCODE_QUOTES);
			$chars = $extras . 'a-zA-Z0-9';
			if(in_array('-', $allowedExtras) && strpos($chars, '-') !== 0) {
				// if hyphen present, ensure its first (per PCRE requirements)
				$chars = '-' . str_replace('-', '', $chars);
			}
			$value = preg_replace('{[^' . $chars . ']}', $replacementChar, $value);
		}

		// remove leading or trailing dashes, underscores, dots
		if($beautify) {
			if(strpos($extras, $replacementChar) === false) $extras .= $replacementChar;
			$value = trim($value, $extras);
		}

		return $value; 
	}

	/**
	 * Standard alphanumeric and dash, underscore, dot name 
	 *
	 * @param string $value
	 * @param bool|int $beautify Should be true when creating a name for the first time. Default is false.
	 *	You may also specify Sanitizer::translate (or number 2) for the $beautify param, which will make it translate letters
	 *	based on the InputfieldPageName custom config settings.
	 * @param int $maxLength Maximum number of characters allowed in the name
	 * @param string $replacement Replacement character for invalid chars: one of -_.
	 * @param array $options Extra options to replace default 'beautify' behaviors
	 * 	- allowAdjacentExtras (bool): Whether to allow [-_.] chars next to each other (default=false)
	 * 	- allowDoubledReplacement (bool): Whether to allow two of the same replacement chars [-_] next to each other (default=false)
	 *  - allowedExtras (array): Specify allowed characters (default=[-_.], not including the brackets)
	 * @return string
	 * 
	 */
	public function name($value, $beautify = false, $maxLength = 128, $replacement = '_', $options = array()) {
	
		if(!empty($options['allowedExtras']) && is_array($options['allowedExtras'])) {
			$allowedExtras = $options['allowedExtras']; 
			$allowedExtrasStr = implode('', $allowedExtras); 
		} else {
			$allowedExtras = array('-', '_', '.');
			$allowedExtrasStr = '-_.';
		}
	
		$value = $this->nameFilter($value, $allowedExtras, $replacement, $beautify, $maxLength);
		
		if($beautify) {
			
			$hasExtras = false;
			foreach($allowedExtras as $c) {
				$hasExtras = strpos($value, $c) !== false;
				if($hasExtras) break;
			}
			
			if($hasExtras) {
				
				if(empty($options['allowAdjacentExtras'])) {
					// replace any of '-_.' next to each other with a single $replacement
					$value = preg_replace('/[' . $allowedExtrasStr . ']{2,}/', $replacement, $value);
				}

				if(empty($options['allowDoubledReplacement'])) {
					// replace double'd replacements
					$r = "$replacement$replacement";
					if(strpos($value, $r) !== false) $value = preg_replace('/' . $r . '+/', $replacement, $value);
				}
	
				// replace double dots
				if(strpos($value, '..') !== false) $value = preg_replace('/\.\.+/', '.', $value);
			}
		}
		
		return $value; 
	}

	/**
	 * Standard alphanumeric and dash, underscore, dot name plus multiple names may be separated by a delimeter
	 *
	 * @param string|array $value Value(s) to filter
	 * @param string $delimeter Character that delimits values (optional)
	 * @param array $allowedExtras Additional characters that are allowed in the value (optional)
	 * @param string 1 character replacement value for invalid characters (optional)
	 * @param bool $beautify
	 * @return string|array Return string if given a string for $value, returns array if given an array for $value
	 *
	 */
	public function names($value, $delimeter = ' ', $allowedExtras = array('-', '_', '.'), $replacementChar = '_', $beautify = false) {
		$isArray = false;
		if(is_array($value)) {
			$isArray = true; 
			$value = implode(' ', $value); 
		}
		$replace = array(',', '|', '  ');
		if($delimeter != ' ' && !in_array($delimeter, $replace)) $replace[] = $delimeter; 
		$value = str_replace($replace, ' ', $value);
		$allowedExtras[] = ' ';
		$value = $this->nameFilter($value, $allowedExtras, $replacementChar, $beautify, 8192);
		if($delimeter != ' ') $value = str_replace(' ', $delimeter, $value); 
		if($isArray) $value = explode($delimeter, $value); 
		return $value;
	}


	/**
	 * Standard alphanumeric and underscore, per class or variable names in PHP
	 * 
	 * @param string $value
	 * @return string
	 *
	 */
	public function varName($value) {
		return $this->nameFilter($value, array('_'), '_'); 
	}

	/**
	 * Name filter as used by ProcessWire Fields
	 * 
	 * Note that dash and dot are excluded because they aren't allowed characters in PHP variables
	 * 
	 * @param string $value
	 * @param bool|int $beautify Should be true when creating a name for the first time. Default is false.
	 *	You may also specify Sanitizer::translate (or number 2) for the $beautify param, which will make it translate letters
	 *	based on the InputfieldPageName custom config settings.
	 * @param int $maxLength Maximum number of characters allowed in the name
	 * @return string
	 *
	 */
	public function fieldName($value, $beautify = false, $maxLength = 128) {
		return $this->nameFilter($value, array('_'), '_', $beautify, $maxLength); 
	}
	
	/**
	 * Name filter as used by ProcessWire Templates
	 *
	 * @param string $value
	 * @param bool|int $beautify Should be true when creating a name for the first time. Default is false.
	 *	You may also specify Sanitizer::translate (or number 2) for the $beautify param, which will make it translate letters
	 *	based on the InputfieldPageName custom config settings.
	 * @param int $maxLength Maximum number of characters allowed in the name
	 * @return string
	 *
	 */
	public function templateName($value, $beautify = false, $maxLength = 128) {
		return $this->nameFilter($value, array('_', '-'), '-', $beautify, $maxLength);
	}

	/**
	 * Name filter for ProcessWire Page names
	 *
	 * Because page names are often generated from a UTF-8 title, UTF8 to ASCII conversion will take place when $beautify is on
	 *
	 * @param string $value
	 * @param bool|int $beautify Should be true when creating a Page's name for the first time. Default is false. 
	 *	You may also specify Sanitizer::translate (or number 2) for the $beautify param, which will make it translate letters
	 *	based on the InputfieldPageName custom config settings. 
	 * @param int $maxLength Maximum number of characters allowed in the name
	 * @return string
	 *
	 */
	public function pageName($value, $beautify = false, $maxLength = 128) {
		return strtolower($this->name($value, $beautify, $maxLength, '-')); 
	}

	/**
	 * Name filter for ProcessWire Page names with transliteration
	 *
	 * This is the same as calling pageName with the Sanitizer::translate option for $beautify.
	 *
	 * @param string $value
	 * @param int $maxLength Maximum number of characters allowed in the name
	 * @return string
	 *
	 */
	public function pageNameTranslate($value, $maxLength = 128) {
		return $this->pageName($value, self::translate, $maxLength);
	}

	/**
	 * Format required by ProcessWire user names
	 *
	 * @deprecated, use pageName instead.
	 * @param string $value
	 * @return string
	 *
	 */
	public function username($value) {
		return $this->pageName($value); 
		/*
		$value = trim($value); 
		if(strlen($value) > 128) $value = substr($value, 0, 128); 
		if(ctype_alnum(str_replace(array('-', '_', '.', '@'), '', $value))) return $value; 
		return preg_replace('/[^-_.@a-zA-Z0-9]/', '_', trim($value)); 
		*/
	}

	/**
	 * Name filter for ProcessWire filenames (basenames only, not paths)
	 *
	 * @param string $value
	 * @param bool|int $beautify Should be true when creating a file's name for the first time. Default is false.
	 *	You may also specify Sanitizer::translate (or number 2) for the $beautify param, which will make it translate letters
	 *	based on the InputfieldPageName custom config settings.
	 * @param int $maxLength Maximum number of characters allowed in the name
	 * @return string
	 *
	 */
	public function filename($value, $beautify = false, $maxLength = 128) {

		$value = basename($value); 
		
		if(strlen($value) > $maxLength) {
			// truncate, while keeping extension in tact
			$pathinfo = pathinfo($value);
			$extLen = strlen($pathinfo['extension']) + 1; // +1 includes period
			$basename = substr($pathinfo['filename'], 0, $maxLength - $extLen);
			$value = "$basename.$pathinfo[extension]";
		}
		
		return $this->name($value, $beautify, $maxLength, '_', array(
			'allowAdjacentExtras' => true, // language translation filenames require doubled "--" chars, others may too
			)
		); 
	}

	/**
	 * Hookable alias of filename method for case consistency with other name methods (preferable to use filename)
	 *
	 */
	public function ___fileName($value, $beautify = false, $maxLength = 128) {
		return $this->filename($value, $beautify, $maxLength); 
	}

	/**
	 * Return the given path if valid, or boolean false if not.
	 *
	 * Path is validated per ProcessWire "name" convention of ascii only [-_./a-z0-9]
	 * As a result, this function is primarily useful for validating ProcessWire paths,
	 * and won't always work with paths outside ProcessWire.
	 * 
	 * This method validates only and does not sanitize. See pagePathName() for a similar
	 * method that does sanitiation. 
	 * 
	 * @param string $value Path
	 * @param int|array $options Options to modify behavior, or maxLength (int) may be specified.
	 * 	- allowDotDot: Whether to allow ".." in a path (default=false)
	 * 	- maxLength: Maximum length of allowed path (default=1024)
	 * @return bool|string Returns false if invalid, actual path (string) if valid.
	 *
	 */
	public function path($value, $options = array()) {
		if(is_int($options)) $options = array('maxLength' => $options); 
		$defaults = array(
			'allowDotDot' => false,
			'maxLength' => 1024
		);
		$options = array_merge($defaults, $options);
		if(DIRECTORY_SEPARATOR != '/') $value = str_replace(DIRECTORY_SEPARATOR, '/', $value); 
		if(strlen($value) > $options['maxLength']) return false;
		if(strpos($value, '/./') !== false || strpos($value, '//') !== false) return false;
		if(!$options['allowDotDot'] && strpos($value, '..') !== false) return false;
		if(!preg_match('{^[-_./a-z0-9]+$}iD', $value)) return false;
		return $value;
	}

	/**
	 * Sanitize a page path name
	 * 
	 * Returned path is not guaranteed to be valid or match a page, just sanitized. 
	 *
	 * @param string $value
	 * @param bool $beautify
	 * @param int $maxLength
	 * @return string
	 *
	 */
	public function pagePathName($value, $beautify = false, $maxLength = 1024) {
		$options = array(
			'allowedExtras' => array('/', '-', '_', '.')
		);
		$value = $this->name($value, $beautify, $maxLength, '-', $options); 
		// disallow double slashes
		while(strpos($value, '//') !== false) $value = str_replace('//', '/', $value); 
		// disallow relative paths
		while(strpos($value, '..') !== false) $value = str_replace('..', '.', $value);
		// disallow names that start with a period
		while(strpos($value, '/.') !== false) $value = str_replace('/.', '/', $value); 
		return $value; 
	}

	/**
	 * Returns valid email address, or blank if it isn't valid
	 *
	 * @param string $value
	 * @return string
	 * 
	 */ 
	public function email($value) {
		$value = filter_var($value, FILTER_SANITIZE_EMAIL); 
		if(filter_var($value, FILTER_VALIDATE_EMAIL)) return $value;
		return '';
	}

	/**
	 * Returns a value that may be used in an email header
	 *
	 * @param string $value
	 * @return string
	 *
	 */ 
	public function emailHeader($value) {
		$a = array("\n", "\r", "<CR>", "<LF>", "0x0A", "0x0D", "%0A", "%0D", 'content-type:', 'bcc:', 'cc:', 'to:', 'reply-to:'); 
		return trim(str_ireplace($a, ' ', $value));
	}

	/**
	 * Sanitize input text and remove tags
	 *
	 * @param string $value
	 * @param array $options See the $defaultOptions array in the method for options
	 * @return string
	 *
	 */
	public function text($value, $options = array()) {

		$defaultOptions = array(
			'multiLine' => false,
			'maxLength' => 255, 
			'maxBytes' => 1024, 
			'stripTags' => true,
			'allowableTags' => '', 
			'inCharset' => 'UTF-8', 
			'outCharset' => 'UTF-8', 
			);

		$options = array_merge($defaultOptions, $options); 

		if(!$options['multiLine']) $value = str_replace(array("\r", "\n"), " ", $value); 

		if($options['stripTags']) $value = strip_tags($value, $options['allowableTags']); 

		if($options['inCharset'] != $options['outCharset']) $value = iconv($options['inCharset'], $options['outCharset'], $value); 

		if($options['maxLength']) {
			if($this->multibyteSupport) {
				if(mb_strlen($value, $options['outCharset']) > $options['maxLength']) $value = mb_substr($value, 0, $options['maxLength'], $options['outCharset']);
			} else {
				if(strlen($value) > $options['maxLength']) $value = substr($value, 0, $options['maxLength']);
			}
		}

		if($options['maxBytes']) {
			$n = $options['maxBytes'];
			while(strlen($value) > $options['maxBytes']) {
				$n--;
				if($this->multibyteSupport) {
					$value = mb_substr($value, 0, $n, $options['outCharset']);
				} else {
					$value = substr($value, 0, $n);
				}
			}
		}

		return trim($value); 	
	}

	/**
	 * Sanitize input multiline text and remove tags
	 *
	 * @param string $value
	 * @param array $options See Sanitizer::text and $defaultOptions array for an explanation of options
	 * @return string
	 *
	 */
	public function textarea($value, $options = array()) {

		if(!isset($options['multiLine'])) $options['multiLine'] = true; 	
		if(!isset($options['maxLength'])) $options['maxLength'] = 16384; 
		if(!isset($options['maxBytes'])) $options['maxBytes'] = $options['maxLength'] * 3; 
	
		// convert \r\n to just \n
		if(empty($options['allowCRLF']) && strpos($value, "\r\n") !== false) $value = str_replace("\r\n", "\n", $value); 

		return $this->text($value, $options); 
	}

	/**
	 * Returns a valid URL, or blank if it can't be made valid 
	 *
	 * Performs some basic sanitization like adding a protocol to the front if it's missing, but leaves alone local/relative URLs. 
	 *
	 * URL is not required to confirm to ProcessWire conventions unless a relative path is given.
	 *
	 * Please note that URLs should always be entity encoded in your output. <script> is technically allowed in a valid URL, so 
	 * your output should always entity encoded any URLs that came from user input. 
	 *
	 * @param string $value URL
	 * @param bool|array $options Array of options including: 
	 * 	- allowRelative (boolean) Whether to allow relative URLs, i.e. those without domains (default=true)
	 * 	- allowQuerystring (boolean) Whether to allow query strings (default=true)
	 * 	- allowSchemes (array) Array of allowed schemes, lowercase (default=[] any)
	 *	- disallowSchemes (array) Array of disallowed schemes, lowercase (default=[file])
	 * 	- requireScheme (bool) Specify true to require a scheme in the URL, if one not present, it will be added to non-relative URLs (default=true)
	 * 	- throw (bool) Throw exceptions on invalid URLs (default=false)
	 *	Previously this was the boolean $allowRelative, and that usage will still work for backwards compatibility.
	 * @return string
	 * @throws WireException on invalid URLs, only if $options['throw'] is true. 
	 * @todo add TLD validation
	 *
	 */
	public function url($value, $options = array()) {

		$defaultOptions = array(
			'allowRelative' => true, 
			'allowQuerystring' => true,
			'allowSchemes' => array(), 
			'disallowSchemes' => array('file'), 
			'requireScheme' => true, 
			'throw' => false,
			);

		if(!is_array($options)) {
			$defaultOptions['allowRelative'] = (bool) $options; // backwards compatibility with old API
			$options = array();
		}

		$options = array_merge($defaultOptions, $options);

		if(!strlen($value)) return '';
		
		$scheme = parse_url($value, PHP_URL_SCHEME); 
		if($scheme !== false && strlen($scheme)) {
			$scheme = strtolower($scheme);
			$schemeError = false;
			if(!empty($options['allowSchemes']) && !in_array($scheme, $options['allowSchemes'])) $schemeError = true; 
			if(!empty($options['disallowSchemes']) && in_array($scheme, $options['disallowSchemes'])) $schemeError = true; 
			if($schemeError) {
				$error = sprintf($this->_('URL: Scheme "%s" is not allowed'), $scheme);
				if($options['throw']) throw new WireException($error);
				$this->error($error);
				$value = str_ireplace(array("$scheme:///", "$scheme://"), '', $value); 
			}
		}

		// this filter_var sanitizer just removes invalid characters that don't appear in domains or paths
		$value = filter_var($value, FILTER_SANITIZE_URL); 
	
		if(!$scheme) {
			// URL is missing scheme/protocol, or is local/relative

			if($options['allowRelative']) {
				// determine if this is a domain name 
				// regex legend:       (www.)?      company.         com       ( .uk or / or end)
				$dotPos = strpos($value, '.');	
				$slashPos = strpos($value, '/'); 
				if($slashPos === false) $slashPos = $dotPos+1;
				// if the first slash comes after the first dot, the dot is likely part of a domain.com/path/
				// if the first slash comes before the first dot, then it's likely a /path/product.html
				if($dotPos && $slashPos > $dotPos && preg_match('{^([^\s_.]+\.)?[^-_\s.][^\s_.]+\.([a-z]{2,6})([./:#]|$)}i', $value, $matches)) {
					// most likely a domain name
					// $tld = $matches[3]; // TODO add TLD validation to confirm it's a domain name
					$value = filter_var("http://$value", FILTER_VALIDATE_URL); // add scheme for validation

				} else if($options['allowQuerystring']) {
					// we'll construct a fake domain so we can use FILTER_VALIDATE_URL rules
					$fake = 'http://processwire.com/';
					$slash = strpos($value, '/') === 0 ? '/' : '';
					$value = $fake . ltrim($value, '/'); 
					$value = filter_var($value, FILTER_VALIDATE_URL); 
					$value = str_replace($fake, $slash, $value);

				} else {
					// most likely a relative path
					$value = $this->path($value); 
				}
				
			} else {
				// relative urls aren't allowed, so add the scheme/protocol and validate
				$value = filter_var("http://$value", FILTER_VALIDATE_URL); 
			}
			
			if(!$options['requireScheme']) {
				// if a scheme was added above (for filter_var validation) and it's not required, remove it
				$value = str_replace('http://', '', $value); 
			}
				
		} else {
			// URL already has a scheme
			$value = filter_var($value, FILTER_VALIDATE_URL); 
		}

		return $value ? $value : '';
	}

	/**
	 * Field name filter as used by ProcessWire Fields
	 * 
	 * Note that dash and dot are excluded because they aren't allowed characters in PHP variables
	 * 
	 * @param string $value
	 * @return string
	 *
	 */
	public function selectorField($value) {
		return $this->nameFilter($value, array('_'), '_'); 
	}


	/**
	 * Sanitizes a string value that needs to go in a ProcessWire selector
	 *
	 * String value is assumed to be UTF-8. Replaces non-alphanumeric and non-space with space
	 *
	 * @param string $value
	 * @param int $maxLength Maximum number of allowed characters
	 * @return string
	 * 
	 */
	public function selectorValue($value, $maxLength = 100) {

		$value = trim($value); 
		$quoteChar = '"';
		$needsQuotes = false; 

		// determine if value is already quoted and set initial value of needsQuotes
		// also pick out the initial quote style
		if(strlen($value) && ($value[0] == "'" || $value[0] == '"')) {
			$needsQuotes = true; 
		}

		// trim off leading or trailing quotes
		$value = trim($value, "\"'"); 

		// if an apostrophe is present, value must be quoted
		if(strpos($value, "'") !== false) $needsQuotes = true; 

		// if commas are present, then the selector needs to be quoted
		if(strpos($value, ',') !== false) $needsQuotes = true; 

		// disallow double quotes -- remove any if they are present
		if(strpos($value, '"') !== false) $value = str_replace('"', '', $value); 

		// selector value is limited to 100 chars
		if(strlen($value) > $maxLength) {
			if($this->multibyteSupport) $value = mb_substr($value, 0, $maxLength, 'UTF-8'); 
				else $value = substr($value, 0, $maxLength); 
		}

		// disallow some characters in selector values
		// @todo technically we only need to disallow at begin/end of string
		$value = str_replace(array('*', '~', '`', '$', '^', '|', '<', '>', '=', '[', ']', '{', '}'), ' ', $value);
	
		// disallow greater/less than signs, unless they aren't forming a tag
		// if(strpos($value, '<') !== false) $value = preg_replace('/<[^>]+>/su', ' ', $value); 

		// more disallowed chars, these may not appear anywhere in selector value
		$value = str_replace(array("\r", "\n", "#", "%"), ' ', $value);

		// see if we can avoid the preg_matches and do a quick filter
		$test = str_replace(array(',', ' ', '-'), '', $value); 

		if(!ctype_alnum($test)) {
		
			// value needs more filtering, replace all non-alphanumeric, non-single-quote and space chars
			// See: http://php.net/manual/en/regexp.reference.unicode.php
			// See: http://www.regular-expressions.info/unicode.html
			$value = preg_replace('/[^[:alnum:]\pL\pN\pP\pM\p{Sm}\p{Sc}\p{Sk} \'\/]/u', ' ', $value); 

			// disallow ampersands from beginning entity sequences
			if(strpos($value, '&') !== false) $value = str_replace('&', '& ', $value); 

			// replace multiple space characters in sequence with just 1
			$value = preg_replace('/\s\s+/u', ' ', $value); 
		}

		$value = trim($value); // trim any kind of whitespace
		$value = trim($value, '+!,'); // chars to remove from begin and end 
		
		if(!$needsQuotes && strlen($value)) {
			$a = substr($value, 0, 1); 
			$b = substr($value, -1); 
			if((!ctype_alnum($a) && $a != '/') || (!ctype_alnum($b) && $b != '/')) $needsQuotes = true;
		}
		if($needsQuotes) $value = $quoteChar . $value . $quoteChar; 
		return $value;

	}

	/**
	 * Entity encode a string
	 *
	 * Wrapper for PHP's htmlentities function that contains typical ProcessWire usage defaults
	 *
	 * The arguments used hre are identical to those for PHP's htmlentities function: 
	 * http://www.php.net/manual/en/function.htmlentities.php
	 *
	 * @param string $str
	 * @param int|bool $flags See PHP htmlentities function for flags. 
	 * @param string $encoding
	 * @param bool $doubleEncode
	 * @return string
	 *
	 */
	public function entities($str, $flags = ENT_QUOTES, $encoding = 'UTF-8', $doubleEncode = true) {
		return htmlentities($str, $flags, $encoding, $doubleEncode); 
	}
	
	/**
	 * Entity encode a string and don't double encode something if already encoded
	 *
	 * @param string $str
	 * @param int|bool $flags See PHP htmlentities function for flags. 
	 * @param string $encoding
	 * @return string
	 *
	 */
	public function entities1($str, $flags = ENT_QUOTES, $encoding = 'UTF-8') {
		return htmlentities($str, $flags, $encoding, false);
	}
	
	/**
	 * Entity encode while translating some markdown tags to HTML equivalents
	 * 
	 * Allowed markdown currently includes: 
	 * 		**strong**
	 * 		*emphasis*
	 * 		[anchor-text](url)
	 * 		~~strikethrough~~
	 * 		`code`
	 * 
	 * The primary reason to use this over full-on Markdown is that it has less overhead
	 * and is faster then full-blown Markdowon, for when you don't need it. It's also safer
	 * for text coming from user input since it doesn't allow any other HTML.
	 *
	 * @param string $str
	 * @param array $options Options include the following:
	 * 	- flags (int): See htmlentities() flags. Default is ENT_QUOTES. 
	 * 	- encoding (string): PHP encoding type. Default is 'UTF-8'. 
	 * 	- doubleEncode (bool): Whether to double encode (if already encoded). Default is true. 
	 * 	- allow (array): Only markdown that translates to these tags will be allowed. Default=array('a', 'strong', 'em', 'code', 's')
	 * 	- disallow (array): Specified tags (in the default allow list) won't be allowed. Default=array(). 
	 * 		Note: The 'disallow' is an alternative to the default 'allow'. No point in using them both. 
	 * 	- linkMarkup (string): Markup to use for links. Default='<a href="{url}" rel="nofollow" target="_blank">{text}</a>'
	 * @return string
	 *
	 */
	public function entitiesMarkdown($str, array $options = array()) {
		
		$defaults = array(
			'flags' => ENT_QUOTES, 
			'encoding' => 'UTF-8', 
			'doubleEncode' => true, 
			'allow' => array('a', 'strong', 'em', 'code', 's'), 
			'disallow' => array(), 
			'linkMarkup' => '<a href="{url}" rel="nofollow" target="_blank">{text}</a>', 
		);
		
		$options = array_merge($defaults, $options); 
		$str = $this->entities($str, $options['flags'], $options['encoding'], $options['doubleEncode']);
		
		if(strpos($str, '](') && in_array('a', $options['allow']) && !in_array('a', $options['disallow'])) {
			// link
			$linkMarkup = str_replace(array('{url}', '{text}'), array('$2', '$1'), $options['linkMarkup']); 
			$str = preg_replace('/\[(.+?)\]\(([^)]+)\)/', $linkMarkup, $str);
		}
		
		if(strpos($str, '**') !== false && in_array('strong', $options['allow']) && !in_array('strong', $options['disallow'])) {
			// strong
			$str = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $str);
		}
		
		if(strpos($str, '*') !== false && in_array('em', $options['allow']) && !in_array('em', $options['disallow'])) {
			// em
			$str = preg_replace('/\*([^*\n]+)\*/', '<em>$1</em>', $str);
		}
		
		if(strpos($str, "`") !== false && in_array('code', $options['allow']) && !in_array('code', $options['disallow'])) {
			// code
			$str = preg_replace('/`+([^`]+)`+/', '<code>$1</code>', $str);
		}
		
		if(strpos($str, '~~') !== false && in_array('s', $options['allow']) && !in_array('s', $options['disallow'])) {
			// strikethrough
			$str = preg_replace('/~~(.+?)~~/', '<s>$1</s>', $str);
		}
		
		return $str;
	}

	/**
	 * Remove entity encoded characters from a string. 
	 * 
	 * Wrapper for PHP's html_entity_decode function that contains typical ProcessWire usage defaults
	 *
	 * The arguments used hre are identical to those for PHP's heml_entity_decode function: 
	 * http://www.php.net/manual/en/function.html-entity-decode.php
	 *
	 * @param string $str
	 * @param int|bool $flags See PHP html_entity_decode function for flags. 
	 * @param string $encoding
	 * @return string
	 *
	 */
	public function unentities($str, $flags = ENT_QUOTES, $encoding = 'UTF-8') {
		return html_entity_decode($str, $flags, $encoding); 
	}

	/**
	 * Alias for unentities
	 * 
	 * @param $str
	 * @param $flags
	 * @param $encoding
	 * @return string
	 * @deprecated
	 * 
	 */
	public function removeEntities($str, $flags, $encoding) {
		return $this->unentities($str, $flags, $encoding); 
	}

	/**
	 * Purify HTML markup using HTML Purifier
	 * 
	 * See: http://htmlpurifier.org
	 * 
	 * @param string $str String to purify
	 * @param array $options See config options at: http://htmlpurifier.org/live/configdoc/plain.html
	 * @return string
	 * @throws WireException if given something other than a string
	 *
	 */
	public function purify($str, array $options = array()) {
		static $purifier = null;
		static $_options = array();
		if(!is_string($str)) throw new WireException("Sanitizer::purify requires a string"); 
		if(is_null($purifier) || print_r($options, true) != print_r($_options, true)) {
			$purifier = $this->purifier($options);
			$_options = $options; 
		}
		return $purifier->purify($str); 
	}

	/**
	 * Validate a file using FileValidator modules
	 * 
	 * Note that this is intended for validating file data, not file names. 
	 * 
	 * IMPORTANT: This method returns NULL if it can't find a validator for the file. This does 
	 * not mean the file is invalid, just that it didn't have the tools to validate it. 
	 * 
	 * @param $filename Full path and filename to validate
	 * @param array $options If available, provide array with any one or all of the following:
	 * 	'page' => Page object associated with $filename
	 * 	'field' => Field object associated with $filename
	 * 	'pagefile' => Pagefile object associated with $filename
	 * @return bool|null Returns TRUE if valid, FALSE if not, or NULL if no validator available for given file type.
	 * 
	 */
	public function validateFile($filename, array $options = array()) {
		$defaults = array(
			'page' => null,
			'field' => null, 
			'pagefile' => null,
		);
		$options = array_merge($defaults, $options);
		$extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
		$validators = $this->wire('modules')->findByPrefix('FileValidator', false);
		$isValid = null;
		foreach($validators as $validatorName) {
			$info = $this->wire('modules')->getModuleInfoVerbose($validatorName);
			if(empty($info) || empty($info['validates'])) continue;
			foreach($info['validates'] as $ext) {
				if($ext[0] == '/') {
					if(!preg_match($ext, $extension)) continue;		
				} else if($ext !== $extension) {
					continue;
				}
				$validator = $this->wire('modules')->get($validatorName);
				if(!$validator) continue;
				if(!empty($options['page'])) $validator->setPage($options['page']);
				if(!empty($options['field'])) $validator->setField($options['field']);
				if(!empty($options['pagefile'])) $validator->setPagefile($options['pagefile']);
				$isValid = $validator->isValid($filename);
				if(!$isValid) {
					// move errors to Sanitizer class so they can be retrieved
					foreach($validator->errors('clear array') as $error) {
						$this->wire('log')->error($error);
						$this->error($error);
					}
					break;
				}
			}
		}
		return $isValid;	
	}

	/**
	 * Return a new HTML Purifier instance
	 *
	 * See: http://htmlpurifier.org
	 *
	 * @param array $options See config options at: http://htmlpurifier.org/live/configdoc/plain.html
	 * @return MarkupHTMLPurifier
	 *
	 */
	public function purifier(array $options = array()) {
		$purifier = $this->wire('modules')->get('MarkupHTMLPurifier');
		foreach($options as $key => $value) $purifier->set($key, $value); 
		return $purifier;
	}

	public function __toString() {
		return "Sanitizer";
	}

}

