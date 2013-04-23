<?php

/**
 * ProcessWire Paths
 *
 * Maintains lists of file paths, primarily used by the ProcessWire configuration.
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2013 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://processwire.com
 *
 * @see http://processwire.com/api/variables/config/ Offical $config API variable Documentation
 * 
 * @property string $root Site root: /
 * @property string $templates Site templates: /site/templates/
 * @property string $adminTemplates Admin theme template files: /wire/templates-admin/ or /site/templates-admin/
 * @property string $modules Core modules: /wire/modules/
 * @property string $siteModules Site-specific modules: /site/modules/
 * @property string $core ProcessWire core files: /wire/core/
 * @property string $assets Site-specific assets: /site/assets/
 * @property string $cache Site-specific cache: /site/assets/cache/
 * @property string $logs Site-specific logs: /site/assets/logs/
 * @property string $files Site-specific files: /site/assets/files/
 * @property string $tmp Temporary files: /site/assets/tmp/
 * @property string $sessions Session files: /site/assets/sessions/
 * @property string $admin Admin URL (applicable only to $config->urls)
 * 
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
	 * @static
	 * @param string $path
	 * @return string
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
	 * @param string $key
	 * @param mixed $value If the first character of the provided path is a slash, then that specific path will be used without modification.
	 * 	If the first character is anything other than a slash, then the 'root' variable will be prepended to the path.
	 * @return this
	 *
	 */
	public function set($key, $value) {
		$value = self::normalizeSeparators($value); 
		return parent::set($key, $value); 
	}

	/**
	 * Return the requested path variable
	 *
	 * @param object|string $key
	 * @return mixed|null|string The requested path variable
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
