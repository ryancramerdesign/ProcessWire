<?php

/**
 * ProcessWire PagefilesManager
 *
 * Manages Pagefiles, ensuring proper storage in published/draft dirs and migration of files between them
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2013 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://processwire.com
 *
 */

class PagefilesManager extends Wire {

	/**
	 * Default prefix for secure paths when not defined by config.php
	 *
	 */
	const defaultSecurePathPrefix = '.';

	/**
	 * Prefix to all extended path directories
	 *
	 */
	const extendedDirName = '0/';

	/**
	 * Reference to the Page object this PagefilesManager is managing
	 *
	 */
	protected $page;

	/**
	 * Cached copy of $path, once it has been determined
	 *
	 */
	protected $path = null;

	/**
	 * Cached copy of $url, once it has been determined
	 *
	 */
	protected $url = null;
	
	/**
	 * Construct the PagefilesManager and ensure all needed paths are created
	 *
	 * @param Page $page
	 *
	 */
	public function __construct(Page $page) {
		$this->init($page); 
	}

	/**
	 * Initialize the PagefilesManager with a Page
	 *
 	 * Same as construct, but separated for when cloning a page
	 *
	 */
	public function init(Page $page) {
		$this->setPage($page); 
		$this->path = null; // to uncache, if this PagefilesManager has been cloned
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
	 * Get the Pagefile/Pageimage object containing the given filename
	 *
	 * @param string $name If given a URL/path, this will traverse to other pages. If given a basename, it will stay with current page.
	 * @return Pagefile|null Returns Pagefile object if found, null if not
	 *
	 */
	public function getFile($name) {
		$pagefile = null;
		if(strpos($name, '/') !== false) {
			$pageID = self::dirToPageID($name);
			if(!$pageID) return null;
			$page = $this->wire('pages')->get($pageID);
			if(!$page->id) return null;
		} else {
			$page = $this->page;
		}
		foreach($page->fieldgroup as $field) {
			if(!$field->type instanceof FieldtypeFile) continue;
			$pagefiles = $page->get($field->name);
			// if mapping to single file, ask it for the parent array
			if($pagefiles instanceof Pagefile) $pagefiles = $pagefiles->pagefiles;
			if($pagefiles instanceof Pagefiles) $pagefile = $pagefiles->getFile($name);
			if(!$pagefile) continue;
			break;
		}
		return $pagefile;
	}


	/**
	 * Recursively copy all files in $fromPath to $toPath, for internal use
	 *
	 * @param string $fromPath Path to copy from
	 * @param string $toPath Path to copy to
	 * @param bool $rename Rename files rather than copy? (makes this perform like a move rather than copy)
	 * @return int Number of files copied
	 *
	 */
	protected function _copyFiles($fromPath, $toPath, $rename = false) {

		if(!is_dir($toPath)) return 0; 

		$numCopied = 0;
		$fromPath = rtrim($fromPath, '/') . '/';
		$toPath = rtrim($toPath, '/') . '/';
	
		foreach(new DirectoryIterator($fromPath) as $file) {
			if($file->isDot()) continue; 
			
			if($file->isDir()) {
				$fromDir = $file->getPathname();
				$toDir = $toPath . $file->getFilename() . '/';
				if($rename && rename($fromDir, $toDir)) {
					$numCopied++;
				} else {
					$this->_createPath($toDir); 
					$numCopied += $this->_copyFiles($fromDir, $toDir, $rename); 
					if($rename) wireRmdir($fromDir, true); // this line not likely to ever be executed
				}
				
			} else if($file->isFile()) {
				$fromFile = $file->getPathname();
				$toFile = $toPath . $file->getFilename();
				$success = $rename ? rename($fromFile, $toFile) : copy($fromFile, $toFile);
				if($success) { 
					$numCopied++;
					// $this->message("Copied $fromFile => $toFile", Notice::debug); 
				} else {
					$this->error("Failed to copy: $fromFile"); 
				}
			}
		}

		return $numCopied; 
	}

	/**
	 * Recursively copy all files managed by this PagefilesManager into a new path
	 *
	 * @param $toPath string Path of directory to copy files into. 
	 * @return int Number of files/directories copied. 
	 *
	 */
	public function copyFiles($toPath) {
		return $this->_copyFiles($this->path(), $toPath); 
	}

	/**
	 * Recursively move all files managed by this PagefilesManager into a new path
	 *
	 * @param $toPath string Path of directory to move files into.
	 * @return int Number of files/directories moved.
	 *
	 */
	public function moveFiles($toPath) {
		$this->_createPath($toPath); 
		return $this->_copyFiles($this->path(), $toPath, true); 
	}

	/**
	 * Create a directory with proper permissions, for internal use. 
	 *
	 * @param string $path Path to create
	 * @return bool True on success, false if not
	 *
	 */
	protected function _createPath($path) {
		if(is_dir($path)) return true; 
		return wireMkdir($path, true); 
	}

	/**
	 * Create the directory path where published files will be restored
	 *
	 * @return bool True on success, false if not
	 *
	 */
	public function createPath() {
		return $this->_createPath($this->path()); 
	}

	/**
	 * Empty out the published files (delete all of them)
	 *
	 */
	public function emptyPath($rmdir = false, $recursive = true) {
		$path = $this->path();
		if(!is_dir($path)) return;
		if($recursive) {
			// clear out path and everything below it
			wireRmdir($path, true);
			if(!$rmdir) $this->_createPath($path); 
		} else {
			// only clear out files in path
			foreach(new DirectoryIterator($path) as $file) {
				if($file->isDot() || $file->isDir()) continue; 
				unlink($file->getPathname()); 
			}
			if($rmdir) {
				@rmdir($path); // will not be successful if other dirs within it
			}
		}
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
		return self::isHooked('PagefilesManager::path()') ? $this->__call('path', array()) : $this->___path();
	}
	
	/**
	 * Get the published path (for use with hooks)
	 *
	 */
	public function ___path() {
		if(is_null($this->path)) {
			if(!$this->page->id) throw new WireException("New page '{$this->page->url}' must be saved before files can be accessed from it");
			$this->path = self::_path($this->page);
		}
		return $this->path;
	}

	/**
	 * Return the auto-determined URL
	 *
	 */
	public function url() {
		return self::isHooked('PagefilesManager::url()') ? $this->__call('url', array()) : $this->___url();
	}

	/**
	 * Return the auto-determined URL, hookable version
 	 *
	 * Note: your hook can get the $page from $event->object->page; 
	 *
	 */
	public function ___url() {
		if(!is_null($this->url)) return $this->url;
		if(strpos($this->path(), $this->config->paths->files . self::extendedDirName) !== false) {
			$this->url = $this->config->urls->files . self::_dirExtended($this->page->id); 
		} else {
			$this->url = $this->config->urls->files . $this->page->id . '/';
		}
		return $this->url;
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
		$this->url = null;
		$this->path = null;
		// $this->page = null;
	}
	
	/**
	 * Handle non-function versions of some properties
	 *
	 */
	public function __get($key) {
		if($key == 'path') return $this->path();
		if($key == 'url') return $this->url();
		if($key == 'page') return $this->page; 
		return parent::__get($key);
	}

	/**
	 * Returns true if Page has a files path that exists
	 *
	 * This is a way to for $pages API functions (or others) to check if they should attempt to use 
	 * a $page's filesManager, thus ensuring directories aren't created for pages that don't need them.
	 *
	 * @param Page $page
	 * @return bool True if a path exists for the page, false if not. 
	 *
	 */
	static public function hasPath(Page $page) {
		return is_dir(self::_path($page)); 
	}

	/**
	 * Returns true if Page has a path and files
	 *
	 * @param Page $page
	 * @return bool True if $page has a path and files
	 *
	 */
	static public function hasFiles(Page $page) {
		if(!self::hasPath($page)) return false;
		$dir = opendir(self::_path($page));
		if(!$dir) return false; 
		$has = false; 
		while(!$has && ($f = readdir($dir)) !== false) $has = $f !== '..' && $f !== '.';
		return $has; 
	}

	/**
	 * Get the files path for a given page (whether it exists or not) - static
	 *
	 * @param Page $page
	 * @param bool $extended Whether to force use of extended paths, primarily for recursive use by this function only
	 * @return string 
 	 *
	 */
	static public function _path(Page $page, $extended = false) {

		$config = wire('config');
		$path = $config->paths->files; 
		
		$securePrefix = $config->pagefileSecurePathPrefix; 
		if(!strlen($securePrefix)) $securePrefix = self::defaultSecurePathPrefix;
		
		if($extended) {
			$publicPath = $path . self::_dirExtended($page->id); 
			$securePath = $path . self::_dirExtended($page->id, $securePrefix); 
		} else {
			$publicPath = $path . $page->id . '/';
			$securePath = $path . $securePrefix . $page->id . '/';
		}

		if($page->isPublic() || !$config->pagefileSecure) {
			// use the public path, renaming a secure path to public if it exists
			if(is_dir($securePath) && !is_dir($publicPath)) {
				@rename($securePath, $publicPath);
			}
			$filesPath = $publicPath;
			
		} else {
			// use the secure path, renaming the public to secure if it exists
			$hasSecurePath = is_dir($securePath);
			if(is_dir($publicPath) && !$hasSecurePath) {
				@rename($publicPath, $securePath);

			} else if(!$hasSecurePath && self::defaultSecurePathPrefix != $securePrefix) {
				// we track this just in case the prefix was newly added to config.php, this prevents 
				// losing track of the original directories
				$securePath2 = $extended ? $path . self::_dirExtended($page->id, self::defaultSecurePathPrefix) : $path . self::defaultSecurePathPrefix . $page->id . '/';
				if(is_dir($securePath2)) {
					// if the secure path prefix has been changed from undefined to defined
					@rename($securePath2, $securePath);
				}
			}
			$filesPath = $securePath;
		}
		
		if(!$extended && $config->pagefileExtendedPaths && !is_dir($filesPath)) {
			// if directory doesn't exist and extended mode is possible, specify use of the extended one
			$filesPath = self::_path($page, true); 
		}
		
		return $filesPath; 
	}

	/**
	 * Generate the directory name (after /site/assets/files/)
	 *
	 * @param int $id
	 * @param string $securePrefix Optional prefix to use for last segment in path
	 * @return string
	 *
	 */
	static public function _dirExtended($id, $securePrefix = '') {
		
		$len = strlen($id);

		if($len > 3) {
			if($len % 2 === 0) {
				$id = "0$id"; // ensure all segments are 2 chars
				$len++;
			}
			$path = chunk_split(substr($id, 0, $len-3), 2, '/') . $securePrefix . substr($id, $len-3);

		} else if($len < 3) {
			$path = $securePrefix . str_pad($id, 3, "0", STR_PAD_LEFT);

		} else {
			$path = $securePrefix . $id;
		}
		
		return self::extendedDirName . $path . '/';	
	}

	/**
	 * Given a dir (URL or path) to a files directory, return the page ID associated with it
	 *
	 * @param string $dir Maybe extended or regular, path or URL
	 * @return int
	 *
	 */ 
	static public function dirToPageID($dir) {

		$parts = explode('/', $dir); 
		$pageID = '';
		$securePrefix = wire('config')->pagefileSecurePathPrefix; 
		if(!strlen($securePrefix)) $securePrefix = self::defaultSecurePathPrefix;

		foreach(array_reverse($parts) as $key => $part) {
			$part = ltrim($part, $securePrefix); 
			if(!ctype_digit($part)) {
				if(!$key) continue; // first item, likely a filename, skip it
				break; // not first item means end of ID sequence
			}
			$id = ltrim($part, '0'); // remove leading 0 and dash
			$pageID = $id . $pageID; 
		}

		return (int) $pageID; 
	}

	/**
	 * Return a path name where temporary files can be stored
	 *
	 * @return string
	 *
	 */
	public function getTempPath() {
		static $wtd = null;
		if(is_null($wtd)) $wtd = new WireTempDir($this->className() . $this->page->id);
		return $wtd->get();
	}

}
