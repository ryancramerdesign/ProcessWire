<?php

/**
 * ProcessWire Pagefile
 *
 * Represents a single file item attached to a page, typically via a FieldtypeFile field.
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2015 by Ryan Cramer 
 * This file licensed under Mozilla Public License v2.0 http://mozilla.org/MPL/2.0/
 * 
 * https://processwire.com
 *
 *
 * @property string $url URL to the file on the server	
 * @proeprty string $URL Same as $url property but with cache buster appended.
 * @property string $filename full disk path to the file on the server
 * @property string $name Returns the filename without the path (basename)
 * @property string $basename Returns the filename without the path (alias of name)
 * @property string $description value of the file's description field (text). Note you can also set this property directly.
 * @property string $tags value of the file's tags field (text). Note you can also set this property directly.
 * @property string $ext file's extension (i.e. last 3 or so characters)
 * @property int $filesize file size, number of bytes
 * @property int $modified timestamp of when pagefile (file, description or tags) was last modified.
 * @property int $mtime timestamp of when file (only) was last modified. 
 * @property int $created timestamp of when file was created
 * @property string $filesizeStr file size as a formatted string
 * @property Pagefiles $pagefiles the WireArray that contains this file
 * @property Page $page the $page that contains this file
 * @property Field $field the $field that contains this file
 *
 */

class Pagefile extends WireData {

	/**
	 * Timestamp 'created' used by pagefiles that are temporary, not yet published
	 * 
	 */
	const createdTemp = 10; 

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
		$this->set('tags', ''); 
		$this->set('formatted', false); // has an output formatter been run on this Pagefile?
		$this->set('modified', 0); 
		$this->set('created', 0); 
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

		if(DIRECTORY_SEPARATOR != '/') $filename = str_replace('\\' . $basename, '/' . $basename, $filename); // To correct issue with XAMPP in Windows
	
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
	 * @throws WireException
	 *
	 */
	protected function ___install($filename) {

		$basename = $this->pagefiles->cleanBasename($filename, true, false, true); 
		$pathInfo = pathinfo($basename); 
		$basename = basename($basename, ".$pathInfo[extension]"); 

		$basenameNoExt = $basename; 
		$basename .= ".$pathInfo[extension]"; 

		// ensure filename is unique
		$cnt = 0; 
		while(file_exists($this->pagefiles->path() . $basename)) {
			$cnt++;
			$basename = "$basenameNoExt-$cnt.$pathInfo[extension]";
		}

		if(strpos($filename, ' ') !== false && strpos($filename, '://') !== false) $filename = str_replace(' ', '%20', trim($filename)); // per Pete
		$destination = $this->pagefiles->path() . $basename; 
		if(!@copy($filename, $destination)) throw new WireException("Unable to copy: $filename => $destination"); 
		if($this->config->chmodFile) chmod($this->pagefiles->path() . $basename, octdec($this->config->chmodFile));
		$this->changed('file');
		parent::set('basename', $basename); 
			
	}

	/**
	 * Sets a value in this Pagefile
	 *
	 * Externally, this would be used to set the file's basename or description
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return $this
	 *
	 */
	public function set($key, $value) {
		if($key == 'basename') $value = $this->pagefiles->cleanBasename($value, false); 
		if($key == 'description') return $this->setDescription($value); 
		if($key == 'tags') $value = $this->fuel('sanitizer')->text($value);
		if($key == 'modified') $value = ctype_digit("$value") ? (int) $value : strtotime($value); 
		if($key == 'created') $value = ctype_digit("$value") ? (int) $value : strtotime($value); 

		if(strpos($key, 'description') === 0 && preg_match('/^description(\d+)$/', $value, $matches)) {
			// check if a language description is being set manually by description123 where 123 is language ID
			$languages = $this->wire('languages'); 
			if($languages) {
				$language = $languages->get((int) $matches[1]); 
				if($language && $language->id) return $this->setDescription($value, $language); 
			}
		}

		return parent::set($key, $value); 
	}

	/**
	 * Set a description, optionally parsing JSON language-specific descriptions to separate properties
	 *
	 * @param string $value
	 * @param Page|Language Langage to set it for. Omit to determine automatically. 
	 * @return this
	 *
	 */
	protected function setDescription($value, Page $language = null) {
		
		$field = $this->field; 
		$noLang = $field && $field->noLang; // noLang setting to disable multi-language from InputfieldFile

		if(!is_null($language) && $language->id) {
			$name = "description";
			if(!$language->isDefault() && !$noLang) {
				$name .= $language->id;
			}
			parent::set($name, $value); 
			return $this; 
		}

		// check if it contains JSON?
		$first = substr($value, 0, 1); 
		$last = substr($value, -1); 
		if(($first == '{' && $last == '}') || ($first == '[' && $last == ']')) {
			$values = json_decode($value, true); 
		} else {
			$values = array(); 
		}

		if($values && count($values)) {
			$n = 0; 
			foreach($values as $id => $v) {	
				// first item is always default language. this ensures description will still
				// work even if language support is later uninstalled. 
				if($noLang && $n > 0) continue;
				$name = $n > 0 ? "description$id" : "description"; 
				parent::set($name, $v); 
				$n++; 
			}
		} else {
			// no JSON values so assume regular language description
			$languages = $this->wire('languages');
			$language = $this->wire('user')->language; 

			if($languages && $language && !$noLang && !$language->isDefault()) {
				parent::set("description$language", $value); 
			} else {
				parent::set("description", $value); 
			}
		}

		return $this;
	}

	/**
	 * Get default description (no args), get description for a specific language (specify) or all languages (true)
	 *
	 * @param null|bool|Language
	 *	Omit arguments or specify null to return description for current user language
	 *	Specify a Language object to return description for that language, or to set the description for that language (if using 2nd argument)
	 * 	Specify boolean true to return JSON string with all languages (if langauges not applicable, regular string returned)
	 *	Specify boolean true to set JSON string (in $value argument) with all languages 
	 * @param null|string Specify only when you are setting rather than getting a value. Specify the string description value. 
	 * @return string
	 *
	 */
	public function description($language = null, $value = null) {

		if(!is_null($value)) {
			// set description mode
			if($language === true) {
				// set all language descriptions
				$this->setDescription($value); 
			} else {
				// set specific language description
				$this->setDescription($value, $language); 
			}
			return $value; 
		}

		if((is_string($language) || is_int($language)) && $this->wire('languages')) {
			// convert named or ID'd languages to Language object
			$language = $this->wire('languages')->get($language); 
		}

		if(is_null($language)) {	
			// return description for current user language, or inherit from default if not available
			$user = $this->wire('user'); 
			$value = null;
			if($user->language && $user->language->id) $value = parent::get("description{$user->language}"); 
			if(empty($value)) {
				// inherit default language value
				$value = parent::get("description"); 
			}

		} else if($language === true) {
			// return JSON string of all languages if applicable
			$languages = $this->wire('languages'); 
			if($languages && $languages->count() > 1) {
				$values = array(0 => parent::get("description"));
				foreach($languages as $lang) {
					if($lang->isDefault()) continue; 
					$v = parent::get("description$lang"); 
					if(empty($v)) continue; 
					$values[$lang->id] = $v; 
				}
				$flags = defined("JSON_UNESCAPED_UNICODE") ? JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES : 0; // more fulltext friendly
				$value = json_encode($values, $flags); 
				
			} else {
				// no languages present so just return string with description
				$value = parent::get("description"); 
			}
			
		} else if(is_object($language) && $language->id) {
			// return description for specific language or blank if not available
			if($language->isDefault()) $value = parent::get("description"); 
				else $value = parent::get("description$language"); 
		}

		// we only return strings, so return blank rather than null
		if(is_null($value)) $value = '';

		return $value; 
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
		if($key == 'pathname') $key = 'filename';

		switch($key) {
			case 'url':
			case 'httpUrl':
			case 'filename':
			case 'description':
			case 'tags':
			case 'ext':
			case 'hash': 
			case 'filesize':
			case 'filesizeStr':
				// 'basename' property intentionally excluded 
				$value = $this->$key();
				break;
			case 'URL':
				// nocache url
				$value = $this->url() . '?nc=' . @filemtime($this->filename());
				break;
			case 'pagefiles': 
				$value = $this->pagefiles; 
				break;
			case 'page': 
				$value = $this->pagefiles->getPage(); 
				break;
			case 'field': 
				$value = $this->pagefiles->getField(); 
				break;
			case 'modified':
			case 'created':
				$value = parent::get($key); 
				if(empty($value)) {
					$value = filemtime($this->filename()); 
					parent::set($key, $value); 
				}
				break;
			case 'mtime':
				$value = filemtime($this->filename()); 
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
	 * @return string
	 *
	 */
	public function url() {
		return self::isHooked('Pagefile::url()') ? $this->__call('url', array()) : $this->___url();
	}
	
	/**
	 * Hookable version of url() method
	 * 
	 * @return string
	 *
	 */
	protected function ___url() {
		return $this->pagefiles->url . $this->basename;
	}
	
	/**
	 * Return the web accessible URL (with schema and hostname) to this Pagefile
	 *
	 */
	public function ___httpUrl() {
		$page = $this->pagefiles->getPage();
		$url = substr($page->httpUrl(), 0, -1 * strlen($page->url())); 
		return $url . $this->url(); 
	}

	/**
	 * Returns the disk path to the Pagefile
	 *
	 */
	public function filename() {
		return self::isHooked('Pagefile::filename()') ? $this->__call('filename', array()) : $this->___filename();
	}

	/**
	 * Hookable version of filename() method
	 *
	 */
	protected function ___filename() {
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
	 * Returns the value of the tags field
	 *
	 * @return string
	 *
	 */
	public function tags() {
		return parent::get('tags'); 
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
	 * @return string
	 *
	 */
	public function filesizeStr() {
		return wireBytesStr($this->filesize()); 
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
		if(!strlen($this->basename) || !is_file($this->filename)) return true; 
		return unlink($this->filename); 	
	}

	/**
	 * Rename this file to $basename
	 *
 	 * @param string $basename
	 * @return string|bool Returns basename on success, or boolean false if rename failed
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

	/**
	 * Does this file have the given tag?
	 *
	 * @param string $tag one-word tag
	 * @return bool
	 *
	 */
	public function hasTag($tag) {

		$tags = $this->tags; 
		if(empty($tags)) return false;

		if(strpos($tags, ',') !== false) $tags = str_replace(',', ' ', $tags);
		$tags = explode(' ', strtolower($tags)); 

		if(strpos($tag, '|') !== false) $findTags = explode('|', strtolower($tag)); 
			else $findTags = array(strtolower($tag)); 

		$found = false; 
		foreach($findTags as $tag) {
			if(in_array($tag, $tags)) {
				$found = true; 
				break;
			}
		}

		return $found; 
	}

	/**
	 * Implement the hook that is called when a property changes (from Wire)
	 *
	 * Alert the $pagefiles of the change 
	 *
	 */
	public function ___changed($what, $old = null, $new = null) {
		if(in_array($what, array('description', 'tags', 'file'))) {
			$this->set('modified', time()); 
			$this->pagefiles->trackChange('item');
		}
		parent::___changed($what, $old, $new); 
	}

	/**
	 * Set the parent array container
	 *
	 */
	public function setPagefilesParent(Pagefiles $pagefiles) {
		$this->pagefiles = $pagefiles; 
		return $this;
	}

	/**
	 * Returns true if this Pagefile is temporary, not yet published. Or use this to set the temp status. 
	 * 
	 * @param bool $set Optionally set the temp status to true or false
	 * @return bool
	 * 
	 */
	public function isTemp($set = null) {
		return $this->pagefiles->isTemp($this, $set); 
	}


}

