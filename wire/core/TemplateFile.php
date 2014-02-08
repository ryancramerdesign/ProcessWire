<?php

/**
 * ProcessWire TemplateFile
 *
 * A template file that will be loaded and executed as PHP, and it's output returned
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2013 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://processwire.com
 *
 */

class TemplateFile extends WireData {

	/**
	 * The full path and filename to the PHP template file
	 *
	 */
	protected $filename;

	/**
	 * Optional filename that is prepended to the render
	 *
	 */
	protected $prependFilename;

	/**
	 * Optional filename that is appended to the render
	 *
	 */
	protected $appendFilename; 

	/**
	 * The saved directory location before render() was called
	 *
	 */
	protected $savedDir; 

	/**
	 * Variables that will be applied globally to this and all other TemplateFile instances
	 *
	 */
	static protected $globals = array(); 

	/**
	 * Construct the template file
	 *
	 * @param string $filename Full path and filename to the PHP template file
	 *
	 */
	public function __construct($filename = '') {
		if($filename) $this->setFilename($filename); 
	}

	/**
	 * Sets the template file name, replacing whatever was set in the constructor
	 *
	 * @param string $filename Full path and filename to the PHP template file
	 * @throws WireException
	 *
	 */
	public function setFilename($filename) {
		if(!$filename) return; 
		$this->filename = $filename; 
	}

	/**
	 * Set a file to prepend to the template file at render time
	 * 
	 * @param string $filename
	 * @return bool Returns false if file doesn't exist. 
	 *
	 */
	public function setPrependFilename($filename) {
		if($filename && is_file($filename)) {
			$this->prependFilename = $filename; 
			return true; 
		} else {
			$this->prependFilename = '';
			return false;
		}
	}

	/**
	 * Set a file to append to the template file at render time
	 * 
	 * @param string $filename
	 * @return bool Returns false if file doesn't exist. 
	 *
	 */
	public function setAppendFilename($filename) {
		if($filename && is_file($filename)) {
			$this->appendFilename = $filename; 
			return true; 
		} else {
			$this->appendFilename = '';
			return false;
		}
	}

	/**
	 * Sets a variable to be globally accessable to all other TemplateFile instances
	 *
	 * Note, to set a variable for just this instance, use the set() as inherted from WireData. 
	 *
	 * @param string $name
	 * @param mixed $value
	 * @param bool $overwrite Should the value be overwritten if it already exists? (default true)
	 *
	 */
	public function setGlobal($name, $value, $overwrite = true) {
		// set template variable that will apply across all instances of Template
		if(!$overwrite && isset(self::$globals[$name])) return; 
		self::$globals[$name] = $value; 
	}

	/**
	 * Render the template -- execute it and return it's output
	 *
	 * @return string The output of the Template File
	 * @throws WireException if template file doesn't exist
	 *
	 */
	public function ___render() {

		if(!$this->filename || !is_file($this->filename)) return '';
		if(!file_exists($this->filename)) throw new WireException("Template file does not exist: '$this->filename'"); 

		$this->savedDir = getcwd();	

		chdir(dirname($this->filename)); 
		$fuel = array_merge($this->getArray(), self::$globals); // so that script can foreach all vars to see what's there

		extract($fuel); 
		ob_start();
		if($this->prependFilename) require($this->prependFilename);
		require($this->filename); 
		if($this->appendFilename) require($this->appendFilename);
		$out = "\n" . ob_get_contents() . "\n";
		ob_end_clean();

		if($this->savedDir) chdir($this->savedDir); 

		return trim($out); 
	}

	/**
	 * Get an array of all variables accessible (locally scoped) to the PHP template file
	 * 
	 * @return array
	 *
	 */
	public function getArray() {
		return array_merge($this->fuel->getArray(), parent::getArray()); 
	}

	/**
	 * Get a set property from the template file, typically to check if a template has access to a given variable
	 *
	 * @param string $property
	 * @return mixed Returns the value of the requested property, or NULL if it doesn't exist
	 *	
	 */
	public function get($property) {
		if($property == 'filename') return $this->filename; 
		if($property == 'appendFilename') return $this->appendFilename; 
		if($property == 'prependFilename') return $this->prependFilename; 
		if($value = parent::get($property)) return $value; 
		if(isset(self::$globals[$property])) return self::$globals[$property];
		return null;
	}

	/**
	 * The string value of a TemplateFile is it's PHP template filename OR it's class name if no filename is set
	 *	
	 */
	public function __toString() {
		if(!$this->filename) return $this->className();
		return $this->filename; 
	}


}

