<?php namespace ProcessWire;

/**
 * ProcessWire NullPage
 *
 * Placeholder class for non-existant and non-saveable Page.
 * Many API functions return a NullPage to indicate no match. 
 *
 * ProcessWire 3.x (development), Copyright 2015 by Ryan Cramer
 * https://processwire.com
 *
 */

class NullPage extends Page { 
	public function path() { return ''; }
	public function url() { return ''; }
	public function set($key, $value) { return parent::setForced($key, $value); }
	public function parent($selector = '') { return null; }
	public function parents($selector = '') { return $this->wire('pages')->newPageArray(); } 
	public function __toString() { return ""; }
	public function isHidden() { return true; }
	public function filesManager() { return null; }
	public function ___rootParent() { return $this->wire('pages')->newNullPage(); }
	public function siblings($selector = '', $options = array()) { return $this->wire('pages')->newPageArray(); }
	public function children($selector = '', $options = array()) { return $this->wire('pages')->newPageArray(); }
	public function getAccessParent($type = 'view') { return $this->wire('pages')->newNullPage(); }
	public function getAccessRoles($type = 'view') { return $this->wire('pages')->newPageArray(); }
	public function hasAccessRole($role, $type = 'view') { return false; }
	public function isChanged($what = '') { return false; }
}

