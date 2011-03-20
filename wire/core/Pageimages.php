<?php

/**
 * ProcessWire Pageimages
 *
 * Pageimages are a collection of Pageimage objects.
 *
 * Typically a Pageimages object will be associated with a specific field attached to a Page. 
 * There may be multiple instances of Pageimages attached to a given Page (depending on what fields are in it's fieldgroup).
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2010 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://www.processwire.com
 * http://www.ryancramer.com
 *
 */

class Pageimages extends Pagefiles {

	/**
	 * Per the WireArray interface, items must be of type Pagefile
	 *
	 */
	public function isValidItem($item) {
		return $item instanceof Pageimage;
	}

	/**
	 * Add a new Pageimage item, or create one from it's filename and add it.
	 *
	 * @param Pageimage|string $item If item is a string (filename) then the Pageimage instance will be created automatically.
	 * @return this
	 *
	 */
	public function add($item) {
		if(is_string($item)) $item = new Pageimage($this, $item); 
		return parent::add($item); 
	}

	/**
	 * Per the WireArray interface, return a blank Pageimage
	 *
	 */
	public function makeBlankItem() {
		return new Pageimage($this, ''); 
	}
}
