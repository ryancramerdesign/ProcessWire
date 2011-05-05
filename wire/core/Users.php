<?php

class Users extends PagesType {

	protected $currentUser = null; 
	protected $guestUser = null; 

	public function setCurrentUser(User $user) {
		if(!$user->roles->has("id=" . $this->fuel('config')->guestUserRolePageID)) {
			$guestRole = $this->fuel('roles')->getGuestRole();
			$user->roles->add($guestRole);
		}
		$this->currentUser = $user; 
	}

	/**
	 * Ensure that every user loaded has at least the 'guest' role
	 *
	 */
	protected function loaded(Page $page) {
		static $guestID  = null;
		if(is_null($guestID)) $guestID = $this->fuel('config')->guestUserRolePageID; 
		if(!$page->roles->has("id=$guestID")) $page->roles->add($this->fuel('roles')->getGuestRole());
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
