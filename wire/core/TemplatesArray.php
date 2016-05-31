<?php namespace ProcessWire;

/**
 * ProcessWire Templates
 *
 * Manages and provides access to all the Template instances
 *
 * ProcessWire 3.x (development), Copyright 2015 by Ryan Cramer
 * https://processwire.com
 *
 */

/**
 * WireArray of Template instances
 *
 */
class TemplatesArray extends WireArray {

	public function isValidItem($item) {
		return $item instanceof Template;
	}

	public function isValidKey($key) {
		return is_int($key) || ctype_digit($key);
	}

	public function getItemKey($item) {
		return $item->id;
	}

	public function makeBlankItem() {
		return $this->wire(new Template());
	}

}
