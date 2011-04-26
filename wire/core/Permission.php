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
}


