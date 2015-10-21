<?php namespace ProcessWire;

/**
 * ProcessWire Breadcrumbs
 *
 * Provides basic breadcrumb capability 
 * 
 * This file is licensed under the MIT license.
 * https://processwire.com/about/license/mit/
 * 
 * ProcessWire 3.x (development), Copyright 2015 by Ryan Cramer
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
			$item = $this->wire(new Breadcrumb());
			$item->title = $page->get("title|name"); 
			$item->url = $page->url;
		} else if($item instanceof Breadcrumb) {
			$this->wire($item);
		}
		
		return parent::add($item); 
	}

}


