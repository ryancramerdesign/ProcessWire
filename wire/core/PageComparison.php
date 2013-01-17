<?php

/**
 * ProcessWire Page Comparison
 *
 * Provides implementation for Page comparison functions.
 *
 * ProcessWire 2.x 
 * Copyright (C) 2013 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://processwire.com
 *
 */

class PageComparison {

	/** 
	 * Does this page have the specified status number or template name? 
 	 *
 	 * See status flag constants at top of Page class
	 *
	 * @param int|string|Selectors $status Status number or Template name or selector string/object
	 * @return bool
	 *
	 */
	public static function is(Page $page, $status) {

		if(is_int($status)) {
			return ((bool) ($page->status & $status)); 

		} else if(is_string($status) && wire('sanitizer')->name($status) == $status) {
			// valid template name
			if($page->template->name == $status) return true; 

		} else if($page->matches($status)) { 
			// Selectors object or selector string
			return true; 
		}

		return false;
	}

	/**
	 * Given a Selectors object or a selector string, return whether this Page matches it
	 *
	 * @param string|Selectors $s
	 * @return bool
	 *
	 */
	public static function matches(Page $page, $s) {

		if(is_string($s)) {
			if(substr($s, 0, 1) == '/' && $page->path() == (rtrim($s, '/') . '/')) return true; 
			if(!Selectors::stringHasOperator($s)) return false;
			$selectors = new Selectors($s); 

		} else if($s instanceof Selectors) {
			$selectors = $s; 

		} else { 
			return false;
		}

		$matches = false;

		foreach($selectors as $selector) {
			$name = $selector->field;
			if(in_array($name, array('limit', 'start', 'sort', 'include'))) continue; 
			$matches = true; 
			$value = $page->get($name); 
			if(!$selector->matches("$value")) {
				$matches = false; 
				break;
			}
		}

		return $matches; 
	}

}

