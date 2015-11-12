<?php

/**
 * PageAction
 *
 * Base class for Page actions in ProcessWire
 * 
 * ProcessWire 2.x
 * Copyright 2015 by Ryan Cramer
 * This file licensed under Mozilla Public License v2.0 http://mozilla.org/MPL/2.0/
 *
 */

abstract class PageAction extends WireAction implements Module {

	/**
	 * Return array of module information
	 *
	 * @return array
	 *
	public static function getModuleInfo() {
		return array(
			'title' => 'PageAction (abstract)', 
			'summary' => 'Base class for PageActions',
			'version' => 0
			);
	}
	 */

	/**
	 * Return the string type (class name) of items that this action operates upon
	 *
	 * @return string
	 *
	 */
	public function getItemType() {
		return 'Page';
	}

	/**
	 * Perform the action on the given item
	 *
	 * @param Page $item Page item to operate upon
	 * @return bool True if the item was successfully operated upon, false if not. 
	 *
	abstract protected function ___action($item);
	 */
}
