<?php

/**
 * The Users class serves as the $users API variable. 
 *
 * @method PageArray find() find($selectorString) Return the user(s) matching the the given selector query.
 * @method User get() get(mixed $selector) Return user by given name, numeric ID or a selector string.
 *
 */

class Users extends PagesType {

	protected $currentUser = null; 
	protected $guestUser = null;
	
	/**
	 * Like find() but returns only the first match as a Page object (not PageArray)
	 *
	 * This is an alias of the findOne() method for syntactic convenience and consistency.
	 *
	 * @param string $selectorString
	 * @return Page|null
	 */
	public function get($selectorString) {
		$user = parent::get($selectorString);
		return $user; 
	}

	/**
	 * Set the current system user (the $user API variable)
	 *
	 * @param User $user
	 *
	 */
	public function setCurrentUser(User $user) {
		if(!$user->roles->has("id=" . $this->fuel('config')->guestUserRolePageID)) {
			$guestRole = $this->fuel('roles')->getGuestRole();
			$user->roles->add($guestRole);
		}
		$this->currentUser = $user; 
		Wire::setFuel('user', $user); 
	}

	/**
	 * Ensure that every user loaded has at least the 'guest' role
	 *
	 */
	protected function loaded(Page $page) {
		static $guestID = null;
		if(is_null($guestID)) $guestID = $this->wire('config')->guestUserRolePageID; 
		$roles = $page->get('roles'); 
		if(!$roles->has("id=$guestID")) $page->get('roles')->add($this->wire('roles')->getGuestRole());
	}

	/**
	 * Returns the current user object
	 *
	 * @return User
	 *
	 */
	public function getCurrentUser() {
		if($this->currentUser) return $this->currentUser; 
		return $this->getGuestUser();
	}

	/**
	 * Get the 'guest' user account
	 *
	 * @return User
	 *
	 */
	public function getGuestUser() {
		if($this->guestUser) return $this->guestUser; 
		$this->guestUser = $this->get($this->config->guestUserPageID); 
		if(defined("PROCESSWIRE_UPGRADE") && !$this->guestUser || !$this->guestUser->id) $this->guestUser = new User(); // needed during upgrade
		return $this->guestUser; 
	}

}
