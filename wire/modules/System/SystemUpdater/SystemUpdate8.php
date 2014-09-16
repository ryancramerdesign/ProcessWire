<?php

/**
 * Add modified and created dates to modules table
 *
 */
class SystemUpdate8 extends SystemUpdate {
	
	public function execute() {
		wire('modules')->resetCache();
		wire('modules')->get('AdminThemeReno'); 
		$this->message("Installed AdminThemeReno (accessible from user profile)"); 
		return true; 
	}
}

