<?php

/**
 * ProcessWire Breadcrumbs
 *
 * Provides basic breadcrumb capability 
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2010 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://www.processwire.com
 * http://www.ryancramer.com
 *
 */

/**
 * class Breadcrumb
 *
 * Holds a single breadcrumb item with URL and title
 *
 */
class Breadcrumb extends WireData {
	public function __construct($url = '', $title = '') {
		$this->set('url', $url); 
		$this->set('title', $title); 
	}
}

/**
 * class Breadcrumbs
 *
 * Holds multiple Breadcrumb items
 *
 */
class Breadcrumbs extends WireArray {

	public function isValidItem($item) {
		return $item instanceof Breadcrumb;
	}

	public function add($item) {

		if($item instanceof Page) {
			$page = $item; 
			$item = new Breadcrumb();
			$item->title = $page->get("title|name"); 
			$item->url = $page->url;
		} 

		return parent::add($item); 
	}

}


