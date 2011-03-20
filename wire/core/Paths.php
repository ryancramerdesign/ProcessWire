<?php

/**
 * ProcessWire Paths
 *
 * Maintains lists of file paths, primarily used by the ProcessWire configuration.
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2010 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://www.processwire.com
 * http://www.ryancramer.com
 *
 */

class Paths extends WireData {

	/**
	 * Construct the Paths
	 *
	 * @param string $root Path of the root that will be used as a base for stored paths.
	 *
	 */
	public function __construct($root) {
		$this->set('root', $root); 
	}

	/**
	 * Given a path, normalize it to "/" style directory separators if they aren't already
	 *
	 */
	public static function normalizeSeparators($path) {
		if(DIRECTORY_SEPARATOR == '/') return $path; 
		$path = str_replace(DIRECTORY_SEPARATOR, '/', $path); 
		return $path; 
	}

	/**
	 * Set the given path key
	 *
	 * If the first character of the provided path is a slash, then that specific path will be used without modification. 
	 * If the first character is anything other than a slash, then the 'root' variable will be prepended to the path. 
	 *
	 */
	public function set($key, $value) {
		$value = self::normalizeSeparators($value); 
		return parent::set($key, $value); 
	}

	/**
	 * Return the requested path variable
	 *
	 */
	public function get($key) {
		$value = parent::get($key); 
		if($key == 'root') return $value; 
		if(!is_null($value)) {
			if($value[0] == '/' || (DIRECTORY_SEPARATOR != '/' && $value[1] == ':')) return $value; 
				else $value = $this->root . $value; 
		}
		return $value; 
	}
}
