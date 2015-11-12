<?php

/**
 * HTML Markup Quality Assurance
 * 
 * Provides runtime quality assurance for markup stored in [textarea] field values. 
 * 
 * 1. Ensures URLs referenced in <a> and <img> tags are relative to actual site root.
 * 2. Identifies and logs <img> tags that point to non-existing files in PW's file system.
 * 3. Re-creates image variations that don't exist, when the original still exists. 
 * 4. Populates blank 'alt' attributes with actual file description. 
 * 
 * - For #1 use the wakeupUrls($value) and sleepUrls($value) methods. 
 * - For #2-4 use the checkImgTags($value) method. 
 * 
 * Runtime errors are logged to: /site/assets/logs/markup-qa-errors.txt
 * 
 * ProcessWire 2.x
 * Copyright 2015 by Ryan Cramer
 * This file licensed under Mozilla Public License v2.0 http://mozilla.org/MPL/2.0/
 *
 */ 

class MarkupQA extends Wire {
	
	const errorLogName = 'markup-qa-errors';
	
	protected $assetsURL = '';
	protected $page;
	protected $field;
	
	public function __construct(Page $page, Field $field) {
		$this->setPage($page);
		$this->setField($field);
		$this->assetsURL = $this->wire('config')->urls->assets;
	}
	
	public function setPage(Page $page) {
		$this->page = $page; 
	}
	
	public function setField(Field $field) {
		$this->field = $field; 
	}
	
	/**
	 * Wakeup URLs in href or src attributes for presentation
	 *
	 * @param $value
	 *
	 */
	public function wakeupUrls(&$value) {
		return $this->checkUrls($value, false);
	}

	/**
	 * Sleep URLs in href or src attributes for storage
	 *
	 * @param $value
	 *
	 */
	public function sleepUrls(&$value) {
		return $this->checkUrls($value, true);
	}

	/**
	 * Wake URLs for presentation or sleep for storage
	 * 
	 * @param string $value
	 * @param bool $sleep
	 * 
	 */
	protected function checkUrls(&$value, $sleep = false) {

		// see if quick exit possible
		if(stripos($value, 'href=') === false && stripos($value, 'src=') === false) return;

		$rootURL = $this->wire('config')->urls->root;

		$replacements = array(
			" href=\"$rootURL" => "\thref=\"/",
			" href='$rootURL" => "\thref='/",
			" src=\"$rootURL" => "\tsrc=\"/",
			" src='$rootURL" => "\tsrc='/",
		);

		if($sleep) {
			// sleep 
			$value = str_ireplace(array_keys($replacements), array_values($replacements), $value);

		} else if(strpos($value, "\t") === false) {
			// no wakeup necessary (quick exit)
			return;

		} else {
			// wakeup
			$value = str_ireplace(array_values($replacements), array_keys($replacements), $value);
		}
	}

	/**
	 * Quality assurance for <img> tags
	 *
	 * @param string $value
	 *
	 */
	public function checkImgTags(&$value) {
		if(strpos($value, '<img ') !== false && preg_match_all('{(<img [^>]+>)}', $value, $matches)) {
			foreach($matches[0] as $key => $img) {
				$this->checkImgTag($value, $img);
			}
		}
	}

	/**
	 * Format <img> tag
	 *
	 * @param string $value Entire text
	 * @param string $img Just the found <img> tag
	 *
	 */
	protected function checkImgTag(&$value, $img) {

		$replaceAlt = ''; // exact text to replace for blank alt attribute, i.e. alt=""
		$src = '';
		$user = $this->wire('user');
		$attrStrings = explode(' ', $img); // array of strings like "key=value"

		// determine current 'alt' and 'src' attributes
		foreach($attrStrings as $n => $attr) {

			if(!strpos($attr, '=')) continue;
			list($name, $val) = explode('=', $attr);

			$name = strtolower($name);
			$val = trim($val, "\"' ");

			if($name == 'alt' && !strlen($val)) {
				$replaceAlt = $attr;

			} else if($name == 'src') {
				$src = $val;
			}
		}

		// if <img> had no src attr, or if it was pointing to something outside of PW assets, skip it
		if(!$src || strpos($src, $this->assetsURL) === false) return;

		// recognized site image, make sure the file exists
		$pagefile = $this->page->filesManager()->getFile($src);

		// if this doesn't resolve to a known pagefile, stop now
		if(!$pagefile) {
			if(file_exists($this->page->filesManager()->path() . basename($src))) {
				// file exists, but we just don't know what it is - leave it alone
			} else {
				$this->error("Image file on page {$this->page->path} no longer exists (" . basename($src) . ")");
				if($this->page->of()) $value = str_replace($img, '', $value);
			}
			return;
		}

		if($pagefile->page->id != $this->page->id && !$user->hasPermission('page-view', $pagefile->page)) {
			// if the file resolves to another page that the user doesn't have access to view, 
			// then we will simply remove the image
			$this->error("Image referenced on page {$this->page->path} that user does not have view access to ($src)");
			if($this->page->of()) $value = str_replace($img, '', $value);
			return;
		}

		if($replaceAlt && $this->page->of()) {
			// image has a blank alt tag, meaning, we will auto-populate it with current file description, 
			// if output formatting is on
			$alt = $pagefile->description;
			if(strlen($alt)) {
				$alt = $this->wire('sanitizer')->entities1($alt);
				$_img = str_replace(" $replaceAlt", " alt=\"$alt\"", $img);
				$value = str_replace($img, $_img, $value);
			}
		}

		$this->checkImgExists($pagefile, $img, $src, $value);

	}

	/**
	 * Attempt to re-create images that don't exist, when possible
	 *
	 * @param Pagefile $pagefile
	 * @param $img
	 * @param $src
	 * @param $value
	 *
	 */
	protected function checkImgExists(Pagefile $pagefile, $img, $src, &$value) {

		$basename = basename($src);
		$pathname = $pagefile->pagefiles->path() . $basename;

		if(file_exists($pathname)) return; // no action necessary

		// file referenced in <img> tag does not exist, and it is not a variation we can re-create
		if($pagefile->basename == $basename) {
			// original file no longer exists
			$this->error("Original image file on {$this->page->path} no longer exists, unable to create new variation ($basename)");
			if($this->page->of()) $value = str_replace($img, '', $value); // remove reference to image, when output formatting is on
			return;
		}

		// check if this is a variation that we might be able to re-create
		$info = $pagefile->isVariation($basename);
		if(!$info) {
			// file is not a variation, so we apparently have no source to pull info from
			$this->error("Unrecognized image that does not exist ($basename)");
			if($this->page->of()) $value = str_replace($img, '', $value); // remove reference to image, when output formatting is on
			return;
		}

		$info['targetName'] = $basename; 
		$variations = array($info);
		while(!empty($info['parent'])) {
			$variations[] = $info['parent'];
			$info = $info['parent'];
		}
		
		foreach(array_reverse($variations) as $info) {
			// definitely a variation, attempt to re-create it
			$options = array();
			if($info['crop']) $options['cropping'] = $info['crop'];
			if($info['suffix']) {
				$options['suffix'] = $info['suffix'];
				if(in_array('hidpi', $options['suffix'])) $options['hidpi'] = true;
			}
			$newPagefile = $pagefile->size($info['width'], $info['height'], $options);
			// $this->wire('log')->message("size($info[width], $info[height], " . print_r($options, true) . ")");
			if($newPagefile && is_file($newPagefile->filename())) {
				if(!empty($info['targetName']) && $newPagefile->basename != $info['targetName']) {
					// new name differs from what is in text. Rename file to be consistent with text.
					rename($newPagefile->filename(), $pathname);
				}
				$this->wire('log')->message("Re-created image variation: $newPagefile->name");
				$pagefile = $newPagefile; // for next iteration
			} else {
				$this->wire('log')->error("Unable to re-create image variation ($newPagefile->name)");
			}
		}
	}

	/**
	 * Record error message to image-errors log
	 *
	 * @param string $text
	 * @param int $flags
	 * @return this
	 * 
	 */
	public function error($text, $flags = 0) {
		$this->wire('log')->save(self::errorLogName, $text);
		if($this->wire('modules')->isInstalled('SystemNotifications')) {
			$user = $this->wire('modules')->get('SystemNotifications')->getSystemUser();
			if($user && !$user->notifications->getBy('title', $text)) {
				$no = $user->notifications()->getNew('error');
				$no->title = $text; 
				$no->html = "<p>Field: {$this->field->name}\n<br />Page: <a href='{$this->page->url}'>{$this->page->title}</a></p>";
				$user->notifications->save(); 
			}
		}
		return $this;
	}
}