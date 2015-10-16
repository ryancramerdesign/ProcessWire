<?php

/**
 * ProcessWire boot
 *
 * ProcessWire 3.x (development), Copyright 2015 by Ryan Cramer
 * https://processwire.com
 *
 */

if(!defined("PROCESSWIRE")) define("PROCESSWIRE", 300); 
if(!defined("PROCESSWIRE_CORE_PATH")) define("PROCESSWIRE_CORE_PATH", __DIR__ . '/');

require_once(PROCESSWIRE_CORE_PATH . "Fuel.php");
require_once(PROCESSWIRE_CORE_PATH . "Interfaces.php");
require_once(PROCESSWIRE_CORE_PATH . "Exceptions.php");
require_once(PROCESSWIRE_CORE_PATH . "Wire.php");
require_once(PROCESSWIRE_CORE_PATH . "WireData.php");
require_once(PROCESSWIRE_CORE_PATH . "WireClassLoader.php");
require_once(PROCESSWIRE_CORE_PATH . "FilenameArray.php");
require_once(PROCESSWIRE_CORE_PATH . "Paths.php");
require_once(PROCESSWIRE_CORE_PATH . "Config.php");
require_once(PROCESSWIRE_CORE_PATH . "Functions.php");
require_once(PROCESSWIRE_CORE_PATH . "LanguageFunctions.php");
require_once(PROCESSWIRE_CORE_PATH . "WireShutdown.php");

