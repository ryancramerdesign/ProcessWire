<?php

/**
 * ProcessWire Breadcrumbs
 *
 * Provides basic breadcrumb capability 
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2015 by Ryan Cramer 
 * This file licensed under Mozilla Public License v2.0 http://mozilla.org/MPL/2.0/
 * 
 * https://processwire.com
 * 
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


