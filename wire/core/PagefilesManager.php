<?php

/**
 * ProcessWire PagefilesManager
 *
 * Manages Pagefiles, ensuring proper storage in published/draft dirs and migration of files between them
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2010 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://www.processwire.com
 * http://www.ryancramer.com
 *
 */

class PagefilesManager extends Wire {

	/**
	 * Reference to the Page object this PagefilesManager is managing
	 *
	 */
	protected $page; 

	/**
	 * Construct the PagefilesManager and ensure all needed paths are created
	 *
	 * @param Page $page
	 *
	 */
	public function __construct(Page $page) {
		$this->setPage($page); 
		$this->createPath();
	}

	/**
	 * Set the page 
	 *
	 */
	public function setPage(Page $page) {
		$this->page = $page; 
	}

	/** 
	 * Get an array of all published filenames (using basename as value)
	 *
	 * @return array Array of basenames
	 *
	 */
	public function getFiles() {
		$files = array();
		foreach(new DirectoryIterator($this->path()) as $file) {
			if($file->isDot() || $file->isDir()) continue; 
			// if($file->isFile()) $files[] = $file->getBasename(); // PHP 5.2.2
			if($file->isFile()) $files[] = $file->getFilename();
		}
		return $files; 
	}

	/**
	 * Create the directory path where published files will be restored
	 *
	 * @return bool True on success, false if not
	 *
	 */
	public function createPath() {
		$path = $this->path();
		if(is_dir($path)) return true; 
		if(!mkdir($path)) return false; 
		if($this->config->chmodDir) chmod($path, octdec($this->config->chmodDir)); 
		return true; 
	}

	/**
	 * Empty out the published files (delete all of them)
	 *
	 */
	public function emptyPath($rmdir = false) {
		$dir = new DirectoryIterator($this->path()); 
		foreach($dir as $file) {
			if($file->isDir() || $file->isDot()) continue; 
			if($file->isFile()) unlink($file->getPathname()); 
		}
		if($rmdir) rmdir($this->path()); 
	}

	/**
	 * Empties all file paths related to the Page, and removes the directories
	 *
	 */
	public function emptyAllPaths() {
		$this->emptyPath(true); 
	}

	/**
	 * Get the published path
 	 *
	 */
	public function path() {
		if(!$this->page->id) throw new WireException("New page '{$this->page->url}' must be saved before files can be accessed from it"); 
		return $this->config->paths->files . $this->page->id . '/';
	}

	/**
	 * Return the auto-determined URL
	 *
	 */
	public function url() {
		return $this->config->urls->files . $this->page->id . '/';
	}

	/**
	 * For hooks to listen to on page save action
	 *
	 * Executed before a page draft/published assets are moved around, when changes to files may be best to execute
	 *
	 */
	public function ___save() { }

	/**	
	 * Uncache/unload any data that should be unloaded with the page
	 *
	 */
	public function uncache() {
		$this->page = null;
	}
	

}
