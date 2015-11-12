<?php

/**
 * ProcessWire NullPage
 *
 * Placeholder class for non-existant and non-saveable Page.
 * Many API functions return a NullPage to indicate no match. 
 *
 * ProcessWire 2.x 
 * Copyright (C) 2015 by Ryan Cramer 
 * This file licensed under Mozilla Public License v2.0 http://mozilla.org/MPL/2.0/
 * 
 * https://processwire.com
 *
 */

class NullPage extends Page { 
	public function path() { return ''; }
	public function url() { return ''; }
	public function set($key, $value) { return parent::setForced($key, $value); }
	public function parent($selector = '') { return null; }
	public function parents($selector = '') { return new PageArray(); } 
	public function __toString() { return ""; }
	public function isHidden() { return true; }
	public function filesManager() { return null; }
	public function ___rootParent() { return new NullPage(); }
	public function siblings($selector = '', $options = array()) { return new PageArray(); }
	public function children($selector = '', $options = array()) { return new PageArray(); }
	public function getAccessParent($type = 'view') { return new NullPage(); }
	public function getAccessRoles($type = 'view') { return new PageArray(); }
	public function hasAccessRole($role, $type = 'view') { return false; }
	public function isChanged($what = '') { return false; }
}

