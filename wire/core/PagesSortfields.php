<?php

/**
 * ProcessWire PagesSortfields
 *
 * Manages the table for the sortfield property for Page children.
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2010 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://www.processwire.com
 * http://www.ryancramer.com
 *
 */

class PagesSortfields extends Wire {

	/**
	 * Save the sortfield for a given Page
	 *
	 * @param Page
	 * @return bool
	 *
	 */
	public function save(Page $page) {

		if(!$page->id) return; 
		if(!$page->isChanged('sortfield')) return; 

		$page_id = (int) $page->id; 
		$sortfield = $this->fuel('db')->escape_string($this->encode($page->sortfield)); 

		if($sortfield == 'sort' || !$sortfield) return $this->delete($page); 

		$sql = 	"INSERT INTO pages_sortfields (pages_id, sortfield) " . 
			"VALUES($page_id, '$sortfield') " . 
			"ON DUPLICATE KEY UPDATE sortfield=VALUES(sortfield)";

		return $this->fuel('db')->query($sql) != false; // QA

	}

	/**
	 * Delete the sortfield for a given Page
	 *
	 * @param Page
	 * @return bool
	 *
	 */
	public function delete(Page $page) {
		$this->fuel('db')->query("DELETE FROM pages_sortfields WHERE pages_id=" . (int) $page->id); // QA
	}

	/**
	 * Decodes a sortfield from a signed integer or string to a field name 
	 *
	 * The returned fieldname is preceded with a dash if the sortfield is reversed. 
	 *
	 * @param string|int $sortfield
	 * @return string
	 *
	 */
	public function decode($sortfield, $default = 'sort') {

		$reverse = false;

		if(substr($sortfield, 0, 1) == '-') {
			$sortfield = substr($sortfield, 1); 
			$reverse = true; 	
		}

		if(ctype_digit("$sortfield") || !Fields::isNativeName($sortfield)) {
			$field = $this->fuel('fields')->get($sortfield);
			if($field) $sortfield = $field->name; 
				else $sortfield = '';
		}

		if(!$sortfield) $sortfield = $default;
			else if($reverse) $sortfield = "-$sortfield";

		return $sortfield; 
	}

	/**
	 * Encodes a sortfield from a fieldname to a signed integer (ID) representing a custom field, or native field name
	 *
	 * The returned value will be a negative value (or string preceded by a dash) if the sortfield is reversed. 
	 *
	 * @param string $sortfield
	 * @return string|int
	 *
	 */

	public function encode($sortfield, $default = 'sort') {

		$reverse = false; 
	
		if(substr($sortfield, 0, 1) == '-') {	
			$reverse = true; 
			$sortfield = substr($sortfield, 1); 
		}

		if($sortfield && !Fields::isNativeName($sortfield)) { 
			if($field = $this->fuel('fields')->get($sortfield)) $sortfield = $field->id; 
				else $sortfield = '';
		}

		if($sortfield) {
			if($reverse) $sortfield = "-$sortfield";
		} else {
			$sortfield = $default;
		}

		return $sortfield; 
	}
}
