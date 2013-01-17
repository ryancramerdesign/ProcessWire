<?php

/**
 * ProcessWire NullPage
 *
 * Placeholder class for non-existant and non-saveable Page.
 * Many API functions return a NullPage to indicate no match. 
 *
 * ProcessWire 2.x 
 * Copyright (C) 2013 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://processwire.com
 *
 */

class NullPage extends Page { 
	public function path() { return ''; }
	public function url() { return ''; }
	public function set($key, $value) { return $this; }
	public function parent($selector = '') { return null; }
	public function parents($selector = '') { return new PageArray(); } 
	public function __toString() { return ""; }
	public function isHidden() { return true; }
	public function filesManager() { return null; }
	public function ___rootParent() { return new NullPage(); }
	public function siblings($selector = '', $options = array()) { return new PageArray(); }
	public function children($selector = '', $options = array()) { return new PageArray(); }
	public function getAccessParent() { return new NullPage(); }
	public function getAccessRoles() { return new PageArray(); }
	public function hasAccessRole($role) { return false; }
}

