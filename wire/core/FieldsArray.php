<?php

/**
 * ProcessWire Fields Array
 * 
 * WireArray of Field instances, as used by Fields class
 *
 * ProcessWire 2.x
 * Copyright (C) 2015 by Ryan Cramer
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 *
 * http://processwire.com
 *
 */

class FieldsArray extends WireArray {

	/**
	 * Per WireArray interface, only Field instances may be added
	 *
	 */
	public function isValidItem($item) {
		return $item instanceof Field;
	}

	/**
	 * Per WireArray interface, Field keys have to be integers
	 *
	 */
	public function isValidKey($key) {
		return is_int($key) || ctype_digit($key);
	}

	/**
	 * Per WireArray interface, Field instances are keyed by their ID
	 *
	 */
	public function getItemKey($item) {
		return $item->id;
	}

	/**
	 * Per WireArray interface, return a blank Field
	 *
	 */
	public function makeBlankItem() {
		return new Field();
	}
}