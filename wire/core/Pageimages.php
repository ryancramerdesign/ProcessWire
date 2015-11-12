<?php

/**
 * ProcessWire Pageimages
 *
 * Pageimages are a collection of Pageimage objects.
 *
 * Typically a Pageimages object will be associated with a specific field attached to a Page. 
 * There may be multiple instances of Pageimages attached to a given Page (depending on what fields are in it's fieldgroup).
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2015 by Ryan Cramer 
 * This file licensed under Mozilla Public License v2.0 http://mozilla.org/MPL/2.0/
 * 
 * https://processwire.com
 *
 */

class Pageimages extends Pagefiles {

	/**
	 * Per the WireArray interface, items must be of type Pagefile
	 *
	 */
	public function isValidItem($item) {
		return $item instanceof Pageimage;
	}

	/**
	 * Add a new Pageimage item, or create one from it's filename and add it.
	 *
	 * @param Pageimage|string $item If item is a string (filename) then the Pageimage instance will be created automatically.
	 * @return $this
	 *
	 */
	public function add($item) {
		if(is_string($item)) $item = new Pageimage($this, $item); 
		return parent::add($item); 
	}

	/**
	 * Per the WireArray interface, return a blank Pageimage
	 *
	 */
	public function makeBlankItem() {
		return new Pageimage($this, ''); 
	}

	/**
	 * Does this field have the given file name? If so, return it, if not return null.
	 *
	 * @param string $name Basename is assumed
	 * @return null|Pagefile Returns Pagefile object if found, null if not
	 *
	 */
	public function getFile($name) {
	
		$hasFile = parent::getFile($name); 
		if($hasFile) return $hasFile; 
	
		// populate $base with $name sans ImageSizer info and extension
		$name = basename($name);
		$pos = strpos($name, '.'); 
		$base = substr($name, 0, $pos);
	
		foreach($this as $pagefile) {
			if(strpos($pagefile->basename, $base) !== 0) continue;
			// they start the same, is it a variation?
			if(!$pagefile->isVariation($name)) continue;
			// if we are here we found a variation
			$hasFile = $pagefile;
			break;
		}
			
		return $hasFile;
	}

	/**
	 * Get an array of all image variations on this field indexed by original file
	 * 
	 * More information on any variation filename can be retrieved from Pageimage::isVariation('filename.jpg')
	 * 
	 * @return array like array('original-file.jpg' => array('variation1.jpg', variation2.jpg', etc.)
	 * 
	 */
	public function getAllVariations() {
		
		$variations = array();
		$extensions = array();
		$basenames = array();
		
		foreach($this as $pageimage) {
			$name = $pageimage->basename;
			$ext = $pageimage->ext;
			$extensions[$name] = $ext;
			$basenames[$name] = basename($name, ".$ext");
			$variations[$name] = array();	
		}
		
		foreach(new DirectoryIterator($this->path()) as $file) {
			
			if($file->isDir() || $file->isDot()) continue;

			$_ext = $file->getExtension();
			$_name = $file->getBasename(); 
			$_base = basename($_name, ".$_ext"); 
			
			foreach($variations as $name => $unused) {
				
				// if filenames match then it's an original, not a variation
				if($name === $_name) continue;
				
				// if files don't share same extension, skip
				$ext = $extensions[$name];
				if($ext != $_ext) continue; 
			
				// if files don't start the same, it's not a variation
				$base = $basenames[$name];
				if(strpos($_base, $base) !== 0) continue;

				// if the part up to the first period isn't the same, then it's not a variation
				$test1 = substr($name, 0, strpos($name, '.'));
				$test2 = substr($_name, 0, strpos($_name, '.'));
				if($test1 !== $test2) continue; 
				
				// if we reach this point, we've found a variation
				$variations[$name][] = $_name; 
			}
		}
		
		return $variations; 
	}
}
