<?php

/**
 * ProcessWire Permission Page
 *
 * A type of Page used for storing an individual User
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2011 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://www.processwire.com
 * http://www.ryancramer.com
 *
 */

class Permission extends Page {

	/**
	 * Static relations between permissions
	 * 
	 * All other relations follow the name format (i.e. page-edit-created assumes page-edit as parent)
	 * 
	 * @var array
	 * 
	 */
	static protected $parentPermissions = array(
		'page-view' => 'none',
		'page-edit' => 'none', 
		'user-admin' => 'page-edit',
		'page' => 'page-edit', // all page-* permissions
		);

	/**
	 * Create a new Permission page in memory. 
	 *
	 * @param Template $tpl Template object this page should use. 
	 *
	 */
	public function __construct(Template $tpl = null) {
		if(is_null($tpl)) $tpl = $this->fuel('templates')->get('permission'); 
		$this->parent = $this->fuel('pages')->get($this->fuel('config')->permissionsPageID); 
		parent::__construct($tpl); 
	}

	/**
	 * Return the immediate parent permission of this permission or NullPage if no parent permission
	 * 
	 * For permissions, parents relations are typically by name. For instance, page-edit is the parent of page-edit-created.
	 * But all page-* permissions are assumed to have page-edit as parent, except for page-view. 
	 * 
	 * @return Permission|NullPage
	 * 
	 */
	public function getParentPermission() {
		
		$name = $this->name; 
		$permissions = $this->wire('permissions');
		$permission = null;
		
		do {
			// first check if we have a static definition for this permission
			if(isset(self::$parentPermissions[$name])) {
				$parentName = self::$parentPermissions[$name];
				if($parentName == 'none') break; // NullPage
				$permission = $permissions->get($parentName); 
				if($permission->id) break;
			} 
			// reduce permission by one part, to a potential parent name
			$parts = explode('-', $name); 
			array_pop($parts); 
			if(!count($parts)) break;
			$name = implode('-', $parts);
			$permission = $permissions->get($name); 
		} while(!$permission->id); 
		
		if(is_null($permission)) $permission = new NullPage();
		
		return $permission;
	}
}


