<?php

/**
 * ProcessWire Temporary Directory Manager
 *
 * ProcessWire 2.x
 * Copyright (C) 2014 by Ryan Cramer
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 *
 * http://processwire.com
 *
 */

class WireTempDir extends Wire {
	
	protected $classRoot = null;
	protected $tempDirRoot = null;
	protected $tempDir = null;
	protected $tempDirMaxAge = 120;
	protected $autoRemove = true; 

	/**
	 * Construct new temp dir
	 * 
	 * @param string|object $name Recommend providing the object that is using the temp dir, but can also be any string 
	 * @throws WireException if given a $root that doesn't exist
	 * 
	 */
	public function __construct($name) {
		if(is_object($name)) $name = get_class($name); 
		if(empty($name) || !is_string($name)) throw new WireException("A valid name (string) must be provided"); 
		$root = $this->wire('config')->paths->assets; 
		if(!is_writable($root)) throw new WireException("$root is not writable"); 
		$root .= get_class($this) . '/';
		$this->classRoot = $root; 
		if(!is_dir($root)) wireMkdir($root); 
		$this->tempDirRoot = $root . ".$name/";
	}

	/**
	 * Set the max age of temp files (default=120 seconds)
	 * 
	 * @param $seconds
	 * @return $this
	 * 
	 */
	public function setMaxAge($seconds) {
		$this->tempDirMaxAge = (int) $seconds; 
		return $this; 
	}

	/**
	 * Call this with 'false' to prevent temp dir from being removed automatically when object is destructed
	 * 
	 * @param bool $remove
	 * @return $this
	 * 
	 */
	public function setRemove($remove = true) {
		$this->autoRemove = (bool) $remove; 	
		return $this;
	}
	
	public function __destruct() {
		if($this->autoRemove) $this->remove();
	}
	
	/**
	 * Returns a temporary directory (path) 
	 *
	 * @return string Returns path
	 * @throws WireException If can't create temporary dir
	 *
	 */
	public function get() {
	
		if(!is_null($this->tempDir) && file_exists($this->tempDir)) return $this->tempDir;
	
		$n = 0;
		do {
			$n++;
			$tempDir = $this->tempDirRoot . "$n/";
			$exists = is_dir($tempDir);
			if($exists) {
				$time = filemtime($tempDir);
				if($time < time() - $this->tempDirMaxAge) { // dir is old and can be removed
					if(wireRmdir($tempDir, true)) $exists = false;
				}
			}
		} while($exists);
	
		if(!wireMkdir($tempDir, true)) {
			throw new WireException($this->_('Unable to create temp dir') . " - $tempDir");
		}
	
		$this->tempDir = $tempDir;
	
		return $tempDir;
	}
	
	/**
	 * Removes the temporary directory created by this object
	 * 
	 * Note that the directory is automatically removed when this object is destructed.
	 *
	 */
	public function remove() {

		$errorMessage = $this->_('Unable to remove temp dir');
		$success = true; 
	
		if(is_null($this->tempDirRoot) || is_null($this->tempDir)) return;
	
		if(is_dir($this->tempDir)) {
			// remove temporary directory created by this instance
			if(!wireRmdir($this->tempDir, true)) {
				$this->error("$errorMessage - $this->tempDir");
				$success = false;
			}
		}
	
		if(is_dir($this->tempDirRoot)) {
			// remove temporary directories created by other instances (like if one had failed at some point)
			$numSubdirs = 0;
			foreach(new DirectoryIterator($this->tempDirRoot) as $dir) {
				if(!$dir->isDir() || $dir->isDot()) continue;
				if($dir->getMTime() < (time() - $this->tempDirMaxAge)) {
					// old dir found
					$pathname = $dir->getPathname();
					if(!wireRmdir($pathname, true)) {
						$this->error("$errorMessage - $pathname");
						$success = false;
					}
				} else {
					$numSubdirs++;
				}
			}
			if(!$numSubdirs) {
				if(wireRmdir($this->tempDirRoot, true)) {
					$success = true; 
				} else {
					$this->error("$errorMessage - $pathname");
					$success = false;
				}
			}
		}
		
		return $success; 
	}

	/**
	 * Accessing this object as a string returns the temp dir
	 * 
	 * @return string
	 * 
	 */
	public function __toString() {
		return $this->get();
	}
}
