<?php

/**
 * ProcessWire class autoloader
 *
 * ProcessWire 2.x
 * Copyright (C) 2013 by Ryan Cramer
 * This file licensed under Mozilla Public License v2.0 http://mozilla.org/MPL/2.0/
 *
 * https://processwire.com
 *
 */

spl_autoload_register('ProcessWireClassLoader');

/**
 * Handles dynamic loading of classes as registered with spl_autoload_register
 *
 */
function ProcessWireClassLoader($className) {

	static $modules = null;

	$file = PROCESSWIRE_CORE_PATH . "$className.php";

	if(is_file($file)) {
		require($file);

	} else {
		if(is_null($modules)) $modules = wire('modules');
		if($modules) $modules->includeModule($className);
	}
}
