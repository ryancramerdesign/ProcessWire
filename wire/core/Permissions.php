<?php

/**
 * The Permissions class serves as the $permissions API variable. 
 * 
 * @method PageArray find() find($selectorString) Return the permissions(s) matching the the given selector query.
 * @method Role get() get(mixed $selector) Return permission by given name, numeric ID or a selector string.
 * @method array getOptionalPermissions($omitInstalled = true)
 * 
 * ProcessWire 2.x
 * Copyright 2015 by Ryan Cramer
 * This file licensed under Mozilla Public License v2.0 http://mozilla.org/MPL/2.0/
 *
 */
class Permissions extends PagesType {
	
	const cacheName = 'Permissions.names';

	/**
	 * Array of permissions name => id, for runtime caching purposes
	 * 
	 * @var array
	 * 
	 */
	protected $permissionNames = array();

	/**
	 * Optional permission names that when not installed, are delegated to another
	 * 
	 * Does not include runtime-only permissions (page-add, page-create) which are delegated to page-edit
	 * 
	 * @var array of permission name => delegated permission name
	 * 
	 */
	protected $delegatedPermissions = array(
		'page-publish' => 'page-edit',
		'page-hide' => 'page-edit',
		'page-lock' => 'page-edit',
		'page-edit-created' => 'page-edit',
		'page-edit-images' => 'page-edit',
		'page-rename' => 'page-edit',
		'user-admin-all' => 'user-admin',
	);

	/**
	 * Returns true/false as to whether the system has a permission with given $name installed
	 * 
	 * Useful in quickly checking for presence of optional permissions. 
	 * 
	 * @param string $name Name of permission
	 * @return bool
	 * 
	 */
	public function has($name) {
		
		if($name == 'page-add' || $name == 'page-create') return true; // runtime only permissions
		
		if(empty($this->permissionNames)) {

			$cache = $this->wire('cache');
			$names = $cache->get(self::cacheName);

			if(empty($names)) {
				$names = array();
				foreach($this as $permission) {
					$names[$permission->name] = $permission->id;
				}
				$cache->save(self::cacheName, $names, WireCache::expireNever);
			}

			$this->permissionNames = $names;
		}
			
		return isset($this->permissionNames[$name]);
	}


	/**
	 * Get an array of all optional permissions in format: name => label
	 *
	 * @param bool $omitInstalled Specify false to include all optional permissions, whether already installed or not.
	 * @return array
	 *
	 */
	public function ___getOptionalPermissions($omitInstalled = true) {

		$a = array(
			'page-hide' => $this->_('Hide/unhide pages'),
			'page-publish' => $this->_('Publish/unpublish pages or edit already published pages'),
			'page-edit-created' => $this->_('Edit only pages user has created'),
			'page-edit-images' => $this->_('Use the image editor to manipulate (crop, resize, etc.) images'), 
			'page-rename' => $this->_('Change the name of published pages they are allowed to edit'),
			'user-admin-all' => $this->_('Administer users in any role (except superuser)'),
		);
	
		foreach($this->wire('roles') as $role) {
			if($role->name == 'guest' || $role->name == 'superuser') continue;
			$a["user-admin-$role->name"] = sprintf($this->_('Administer users in role: %s'), $role->name);
		}

		$languages = $this->wire('languages');
		if($languages) {
			$label = $this->_('Edit fields on a page in language: %s');
			$a["page-edit-lang-default"] = sprintf($label, 'default') . ' ' . $this->_('(also required to create or delete pages)');
			foreach($languages as $language) {
				if($language->isDefault()) continue;
				$a["page-edit-lang-$language->name"] = sprintf($label, $language->name);
			}
			if(!$this->has('lang-edit')) {
				$a["lang-edit"] = $this->_('Administer languages and static translation files');
			}
		}

		if($omitInstalled) {
			// remove permissions that are already in the system
			foreach($a as $name => $label) {
				if($this->has($name)) unset($a[$name]);
			}
		}

		ksort($a);

		return $a;
	}

	/**
	 * Return array of permission names that are delegated to another when not installed
	 * 
	 * @return array of permission name => delegated permission name
	 * 
	 */
	public function getDelegatedPermissions() {
		return $this->delegatedPermissions;
	}

	public function ___saved(Page $page, array $changes = array(), $values = array()) {
		$this->wire('cache')->delete(self::cacheName);
		parent::___saved($page, $changes, $values);
	}

	public function ___deleted(Page $page) {
		$this->wire('cache')->delete(self::cacheName);
		parent::___deleted($page);
	}
}
