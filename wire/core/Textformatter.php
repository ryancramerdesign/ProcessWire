<?php

/**
 * ProcessWire Textformatter
 *
 * Provides the base class for Textformatting Modules
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2010 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://www.processwire.com
 * http://www.ryancramer.com
 *
 */

abstract class Textformatter extends Wire implements Module {

	/**
	 * Return an array of module information
	 *
	 * Array is associative with the following fields: 
	 * - title: An alternate title, if you don't want to use the class name.
	 * - version: an integer that indicates the version number, 101 = 1.0.1
	 * - summary: a summary of the module (1 paragraph max)
	 *
	 * @return array
	 *
	 */
	public static function getModuleInfo() {
		// just an example, should be overridden
		return array(
			'title' => 'Unknown Textformatter', 
			'version' => 0, 
			'summary' => '', 
			); 
	}

	/**
	 * Format the given text string
	 *
 	 * This is deprecated so use the formatValue() function instead. 
	 *
	 * @deprecated
	 * @param string $str
	 *
	 */
	public function format(&$str) {
	}

	/**
	 * Format the given text string.
	 *
 	 * Newer version with Page and Field provided.  
	 *
	 * Override this function completely when providing your own text formatter. No need to call the parent.
	 *
	 * @param string $str
	 *
	 */
	public function formatValue(Page $page, Field $field, &$value) {
		$this->format($value); 
	}

	/**
	 * Optional method to initialize the module. 
	 *
	 * This is called after ProcessWire's API is fully ready for use and hooks
	 *
	 */
	public function init() { }

	/**
	 * Perform any installation procedures specific to this module, if needed. 
	 *
	 */
	public function ___install() { }

	/**
	 * Perform any uninstall procedures specific to this module, if needed. 
	 *
	 */
	public function ___uninstall() { }

	/**
	 * Only one instatance of a textformatter is loaded at runtime
	 *
	 */
	public function isSingular() {
		return true; 
	}

	/**
	 * Textformatters are not autoload, in that they don't load until requested by the api. 
	 *
	 */
	public function isAutoload() {
		return false; 
	}
}
