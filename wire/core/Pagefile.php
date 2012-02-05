<?php

/**
 * ProcessWire Pagefile
 *
 * Represents a single file item attached to a page, typically via a FieldtypeFile field.
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2010 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://www.processwire.com
 * http://www.ryancramer.com
 *
 */

class Pagefile extends WireData {

	/**
	 * Reference to the owning collection of Pagefiles
	 *
	 */
	protected $pagefiles; 


	/**
	 * Construct a new Pagefile
	 *
	 * @param Pagefiles $pagefiles 
	 * @param string $filename Full path and filename to this pagefile
	 *
	 */
	public function __construct(Pagefiles $pagefiles, $filename) {

		$this->pagefiles = $pagefiles; 
		if(strlen($filename)) $this->setFilename($filename); 
		$this->set('description', ''); 
		$this->set('formatted', false); // has an output formatter been run on this Pagefile?
	}

	/**
	 * Set the filename associated with this Pagefile
	 *
	 * No need to call this as it's already called from the constructor. 
	 * This exists so that Pagefile/Pageimage descendents can create cloned variations, if applicable. 
	 *
	 * @param string $filename
	 *
	 */
	public function setFilename($filename) {

		$basename = basename($filename); 
	
		if($basename != $filename && strpos($filename, $this->pagefiles->path()) !== 0) {
			$this->install($filename); 
		} else {
			$this->set('basename', $basename); 
		}

	}

	/**
	 * Install this Pagefile
	 *
	 * Implies copying the file to the correct location (if not already there), and populating it's name
	 *
	 * @param string $filename Full path and filename of file to install
	 *
	 */
	protected function ___install($filename) {

		$basename = $this->pagefiles->cleanBasename($filename, true); 
		$pathInfo = pathinfo($basename); 
		$basename = basename($basename, ".$pathInfo[extension]"); 

		// remove any extra dots in the filename
		$basename = str_replace(".", "_", $basename); 
		$basenameNoExt = $basename; 
		$basename .= ".$pathInfo[extension]"; 

		// ensure filename is unique
		$cnt = 0; 
		while(file_exists($this->pagefiles->path() . $basename)) {
			$cnt++;
			$basename = "$basenameNoExt-$cnt.$pathInfo[extension]";
		}

		$filename = str_replace(' ', '%20', trim($filename)); // per Pete
		$destination = $this->pagefiles->path() . $basename; 
		if(!@copy($filename, $destination)) throw new WireException("Unable to copy: $filename => $destination"); 
		if($this->config->chmodFile) chmod($this->pagefiles->path() . $basename, octdec($this->config->chmodFile));
		parent::set('basename', $basename); 
			
	}

	/**
	 * Sets a value in this Pagefile
	 *
	 * Externally, this would be used to set the file's basename or description
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return this
	 *
	 */
	public function set($key, $value) {
		if($key == 'basename') $value = $this->pagefiles->cleanBasename($value, false); 
		if($key == 'description') $value = $this->fuel('sanitizer')->textarea($value); 
		return parent::set($key, $value); 
	}

	/**
	 * Get a value from this Pagefile
	 *
	 * @param string $key
	 * @return mixed Returns null if value does not exist
	 *
	 */
	public function get($key) {
		$value = null; 

		if($key == 'name') $key = 'basename';
		if($key == 'pathname') $key == 'filename';

		switch($key) {
			case 'url':
			case 'filename':
			case 'description':
			case 'ext':
			case 'hash': 
			case 'filesize':
			case 'filesizeStr':
				// 'basename' property intentionally excluded 
				$value = $this->$key();
				break;
			case 'pagefiles': 
				$value = $this->pagefiles; 
				break;
			case 'page': 
				$value = $this->pagefiles->getPage(); 
				break;
		}
		if(is_null($value)) return parent::get($key); 
		return $value; 
	}

	/**
	 * Return the next Pagefile in the Pagefiles, or NULL if at the end
	 *
	 * @return Pagefile|null
	 *	
	 */
	public function getNext() {
		return $this->pagefiles->getNext($this); 
	}

	/**
	 * Return the previous Pagefile in the Pagefiles, or NULL if at the beginning
	 *
	 * @return Pagefile|null
	 *	
	 */
	public function getPrev() {
		return $this->pagefiles->getPrev($this); 
	}

	/**
	 * Return the web accessible URL to this Pagefile
	 *
	 */
	public function url() {
		return $this->pagefiles->url . $this->basename; 	
	}

	/**
	 * Returns the disk path to the Pagefile
	 *
	 */
	public function filename() {
		return $this->pagefiles->path . $this->basename;
	}

	/**
	 * Returns the basename of this Pagefile
	 *
	 */
	public function basename() {
		return parent::get('basename'); 
	}

	/**
	 * Returns the value of the description field
	 *
	 * @return string
	 *
	 */
	public function description() {
		return parent::get('description'); 
	}

	/**
	 * Has the output already been formatted?
	 *
	 */
	public function formatted() {
		return parent::get('formatted') ? true : false;
	}

	/**
	 * Returns the filesize in number of bytes
	 *
	 * @return int
	 *
	 */
	public function filesize() {
		return @filesize($this->filename()); 
	}

	/**
	 * Returns the filesize in a formatted, output-ready string
	 *
	 * @return int
	 *
	 */
	public function filesizeStr() {
		$size = $this->filesize();
		if($size < 1024) return number_format($size) . " bytes";
		$kb = round($size / 1024); 
		return number_format($kb) . " kb";
	}

	/**
	 * Returns the Pagefile's extension
	 *
	 */
	public function ext() {
		return substr($this->basename(), strrpos($this->basename(), '.')+1);
	}

	/**
	 * When dereferenced as a string, a Pagefile returns it's basename
	 *
	 */
	public function __toString() {
		return $this->basename; 
	}

	/**
	 * Return a unique MD5 hash representing this Pagefile
	 *
	 */
	public function hash() {
		if($hash = parent::get('hash')) return $hash; 	
		$this->set('hash', md5($this->basename())); 
		return parent::get('hash'); 
	}

	/**
	 * Delete the physical file on disk, associated with this Pagefile
	 *
	 */
	public function unlink() {
		return unlink($this->filename); 	
	}

	/**
	 * Rename this file to $basename
	 *
 	 * @param string $basename
	 * @return string|false Returns basename on success, or boolean false if rename failed
	 *
	 */
	public function rename($basename) {
		$basename = $this->pagefiles->cleanBasename($basename, true); 
		if(rename($this->filename, $this->pagefiles->path . $basename)) {
			$this->set('basename', $basename); 
			return $basename; 
		}
		return false; 
	}


	/**
	 * Copy this file to the new specified path
	 *
	 * @param string $path Path (not including basename)
	 * @return mixed result of copy() function
	 *
	 */
	public function copyToPath($path) {
		$result = copy($this->filename, $path . $this->basename()); 
		if($this->config->chmodFile) chmod($path . $this->basename(), octdec($this->config->chmodFile));
		return $result;
	}


	
}

