<?php

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
