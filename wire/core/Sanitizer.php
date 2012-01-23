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
 * Copyright (C) 2010 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://www.processwire.com
 * http://www.ryancramer.com
 *
 */

class Sanitizer extends Wire {

	/**
	 * Caches the status of multibyte support.
	 *
	 */
	protected $multibyteSupport = false;

	/**
	 * Construct the sanitizer
	 *
	 */
	public function __construct() {
		$this->multibyteSupport = function_exists("mb_strlen"); 
	}

	/**
	 * Internal filter used by other name filtering methods in this class
	 *
	 * @param string $value Value to filter
	 * @param array $allowedExtras Additional characters that are allowed in the value
	 * @param string 1 character replacement value for invalid characters
	 *
	 */
	protected function nameFilter($value, array $allowedExtras, $replacementChar) {

		if(!is_string($value)) $value = (string) $value; 
		if(strlen($value) > 128) $value = substr($value, 0, 128); 
		if(ctype_alnum($value)) return $value; // quick exit if possible

		if(!ctype_alnum(str_replace($allowedExtras, '', $value))) { 
			$value = str_replace(array("'", '"'), '', $value); // blank out any quotes
			$value = filter_var($value, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH | FILTER_FLAG_NO_ENCODE_QUOTES); 
			$chars = '';
			foreach($allowedExtras as $char) $chars .= $char; 
			$chars .= 'a-zA-Z0-9';
			$value = preg_replace('/[^' . $chars . ']/', $replacementChar, $value); 
		}

		return $value; 
	}

	/**
	 * Standard alphanumeric and dash, underscore, dot name 
	 *
	 */
	public function name($value) {
		return $this->nameFilter($value, array('-', '_', '.'), '_'); 
	}

	/**
	 * Standard alphanumeric and dash, underscore, dot name plus multiple names may be separated by a delimeter
	 *
	 * @param string $value Value to filter
	 * @param string $delimeter Character that delimits values (optional)
	 * @param array $allowedExtras Additional characters that are allowed in the value (optional)
	 * @param string 1 character replacement value for invalid characters (optional)
	 *
	 */
	public function names($value, $delimeter = ' ', $allowedExtras = array('-', '_', '.'), $replacementChar = '_') {
		$replace = array(',', '|', '  ');
		if($delimeter != ' ' && !in_array($delimeter, $replace)) $replace[] = $delimeter; 
		$value = str_replace($replace, ' ', $value);
		$allowedExtras[] = ' ';
		$value = $this->nameFilter($value, $allowedExtras, $replacementChar);
		if($delimeter != ' ') $value = str_replace(' ', $delimeter, $value); 
		return $value; 
	}


	/**
	 * Standard alphanumeric and underscore, per class or variable names in PHP
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
	 */
	public function fieldName($value) {
		return $this->nameFilter($value, array('_'), '_'); 
	}

	/**
	 * Name filter for ProcessWire Page names
	 *
	 * Because page names are often generated from a UTF-8 title, UTF8 to ASCII conversion will take place when $beautify is on
	 *
	 * @param string $value
	 * @param bool $beautify Should be true when creating a Page's name for the first time. Default is false. 
	 * @return string
	 *
	 */
	public function pageName($value, $beautify = false) {

		if($beautify) {
			$v = iconv("UTF-8", "ASCII//TRANSLIT//IGNORE", $value); 
			if($v) $value = $v; 
		}

		$value = strtolower($this->nameFilter($value, array('-', '_', '.'), '-')); 

		if($beautify) {
			$value = trim($value, '-'); 

			// replace any of '-_.' next to each other with a single dash
			$value = preg_replace('/[-_.]{2,}/', '-', $value); 

			// replace double dashes
			if(strpos($value, '--') !== false) {
				$value = preg_replace('/--+/', '-', $value); 
			}

		}
		return $value; 
	}

	/**
	 * Format required by ProcessWire user names
	 *
	 */
	public function username($value) {
		$value = trim($value); 
		if(strlen($value) > 128) $value = substr($value, 0, 128); 
		if(ctype_alnum(str_replace(array('-', '_', '.', '@'), '', $value))) return $value; 
		return preg_replace('/[^-_.@a-zA-Z0-9]/', '_', trim($value)); 
	}

	/**
	 * Returns valid email address, or blank if it isn't valid
	 *
	 */ 
	public function email($value) {
		$value = filter_var($value, FILTER_SANITIZE_EMAIL); 
		if(filter_var($value, FILTER_VALIDATE_EMAIL)) return $value;
		return '';
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
			'allowableTags' => '', 
			'inCharset' => 'UTF-8', 
			'outCharset' => 'UTF-8', 
			);

		$options = array_merge($defaultOptions, $options); 

		if(!$options['multiLine']) $value = str_replace(array("\r", "\n"), " ", $value); 

		$value = strip_tags($value, $options['allowableTags']); 

		if($options['inCharset'] != $options['outCharset']) $value = iconv($options['inCharset'], $options['outCharset'], $value); 

		if($this->multibyteSupport) {
			if(mb_strlen($value, $options['outCharset']) > $options['maxLength']) $value = mb_substr($value, 0, $options['maxLength'], $options['outCharset']); 
		} else {
			if(strlen($value) > $options['maxLength']) $value = substr($value, 0, $options['maxLength']); 
		}

		$n = $options['maxBytes']; 
		while(strlen($value) > $options['maxBytes']) {
			$n--; 
			if($this->multibyteSupport) $value = mb_substr($value, 0, $n, $options['outCharset']); 			
				else $value = substr($value, 0, $n); 
		
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

		return $this->text($value, $options); 
	}

	/**
	 * Return the given path if valid, or blank if not. 
	 *
	 * Path is validated per ProcessWire "name" convention of ascii only [-_./a-z0-9]
	 * As a result, this function is primarily useful for validating ProcessWire paths,
	 * and won't always work with paths outside ProcessWire. 
	 *
	 * @param string $value Path 
	 *
	 */
	public function path($value) {
		if(!preg_match('{^[-_./a-z0-9]+$}iD', $value)) return '';
		if(strpos($value, '/./') !== false || strpos($value, '//') !== false) $value = '';		
		return $value;
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
	 * @param bool $allowRelative Whether to allow relative URLs
	 * @return string
	 * @todo add TLD validation
	 *
	 */
	public function url($value, $allowRelative = true) {

		if(!strlen($value)) return '';

		// this filter_var sanitizer just removes invalid characters that don't appear in domains or paths
		$value = filter_var($value, FILTER_SANITIZE_URL); 
		
		if(!strpos($value, ".") && $allowRelative) {
			// if there's no dot (or it's in position 0) and relative paths are allowed, 
			// we can safely assume this is a relative path.
			// relative paths must follow ProcessWire convention of ascii-only, 
			// so they are passed through the $sanitizer->path() function.
			return $this->path($value); 
		}

		if(!strpos($value, '://')) {
			// URL is missing protocol, or is local/relative

			if($allowRelative) {
				// determine if this is a domain name 
				// regex legend:       (www.)?      company.         com       ( .uk or / or end)
				if(preg_match('{^([^\s_.]+\.)?[^-_\s.][^\s_.]+\.([a-z]{2,6})([./:#]|$)}i', $value, $matches)) {
					// most likely a domain name
					// $tld = $matches[3]; // TODO add TLD validation to confirm it's a domain name
					$value = filter_var("http://$value", FILTER_VALIDATE_URL); 

				} else {
					// most likely a relative path
					$value = $this->path($value); 
				}
				
			} else {
				// relative urls aren't allowed, so add the protocol and validate
				$value = filter_var("http://$value", FILTER_VALIDATE_URL); 
			}
		}

		return $value ? $value : '';
	}

	/**
	 * Field name filter as used by ProcessWire Fields
	 * 
	 * Note that dash and dot are excluded because they aren't allowed characters in PHP variables
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
	 * 
	 */
	public function selectorValue($value) {

		$value = trim($value); 

		// determine if value is already quoted and set initial value of needsQuotes
		// also pick out the initial quote style
		if(strlen($value) && ($value[0] == "'" || $value[0] == '"')) {
			$quoteChar = $value[0]; 
			$value = trim($value, "\"'"); 
			$needsQuotes = true; 
		} else {
			$quoteChar = '"';
			$needsQuotes = false; 
		}

		// selector value is limited to 100 chars
		if(strlen($value) > 100) {
			if($this->multibyteSupport) $value = mb_substr($value, 0, 100, 'UTF-8'); 
				else $value = substr($value, 0, 100); 
		}

		// if commas are present, then the selector needs to be quoted
		if(strpos($value, ',') !== false) $needsQuotes = true; 

		// if an apostrophe is present, then make sure the value isn't already quoted with the same character
		// if it is, then switch to double quotes
		if(strpos($value, "'") !== false) {
			$needsQuotes = true; 
			if($quoteChar == "'") $quoteChar = '"';
		}

		// disallow double quotes -- remove any if they are present
		if(strpos($value, '"') !== false) $value = str_replace('"', '', $value); 

		// see if we can avoid the preg_matches and do a quick filter
		$test = str_replace(array(',', ' ', '-'), '', $value); 

		if(!ctype_alnum($test)) {
		
			// value needs more filtering, replace all non-alphanumeric, non-single-quote and space chars
			$value = preg_replace('/[^[:alnum:]\pL \'\/]/u', ' ', $value); 

			// replace multiple space characters in sequence
			$value = preg_replace('/\s\s+/u', ' ', $value); 

			//$value = iconv("UTF-8", "ISO-8859-1", $value); 
		}

		$value = trim($value);
		if($needsQuotes) $value = $quoteChar . $value . $quoteChar; 
		return $value;

	}

	public function __toString() {
		return "Sanitizer";
	}

}

