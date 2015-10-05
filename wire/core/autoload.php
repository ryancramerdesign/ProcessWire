<?php namespace ProcessWire;

/**
 * ProcessWire class autoloader
 *
 * ProcessWire 2.x
 * Copyright (C) 2013 by Ryan Cramer
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 *
 * http://processwire.com
 *
 */

/**
 * Handles dynamic loading of classes as registered with spl_autoload_register
 *
 */
spl_autoload_register(function($className) {

	static $modules = null;
	
	if(__NAMESPACE__) {
		$className = str_replace(__NAMESPACE__ . "\\", "", $className);
	}

	$file = PROCESSWIRE_CORE_PATH . "$className.php";

	if(is_file($file)) {
		require_once($file);

	} else {
		if(is_null($modules)) $modules = wire('modules');
		if($modules) $modules->includeModule($className);
	}
});
