<?php

/**
 * Adds a 'data' column to the fieldgroups_fields table
 *
 */
class SystemUpdate4 extends Wire implements SystemUpdateInterface {

	public function execute() {
		$this->modules->resetCache();
		$this->modules->install('AdminThemeDefault'); 
		$this->message("Added new default admin theme. To configure or remove this theme, see Modules &gt; Core &gt; Admin &gt; Default Admin Theme."); 
	}
}
