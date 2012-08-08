<?php

/**
 * The Roles class serves as the $roles API variable. 
 *	
 * @method PageArray find() find($selectorString) Return the role(s) matching the the given selector query.
 * @method Role get() get(mixed $selector) Return role by given name, numeric ID or a selector string.
 * @method Role add() add(string $name) Add new Role with the given name and return it.
 * @method bool save() save(Role $role) Save given role. $role must be an instance of Role.
 * @method bool delete() delete(Role $role) Delete given role. $role must be an instance of Role.
 *
 */ 

class Roles extends PagesType {

	protected $guestRole = null;

	public function getGuestRole() {
		if($this->guestRole) return $this->guestRole; 
		$this->guestRole = $this->get($this->fuel('config')->guestUserRolePageID); 
		return $this->guestRole; 
	}

	/**
	 * Ensure that every role has at least 'page-view' permission
	 *
	 */
	protected function loaded(Page $page) {
		if(!$page->permissions->has("name=page-view")) {
			$page->permissions->add($this->fuel('permissions')->get("name=page-view")); 
		}
	}
}
