<?php

/**
 * ProcessWire Page Traversal
 *
 * Provides implementation for Page traversal functions.
 * Based upon the jQuery traversal functions. 
 *
 * ProcessWire 2.x 
 * Copyright (C) 2013 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://processwire.com
 *
 */

class PageTraversal {

	/**
	 * Return this page's children pages, optionally filtered by a selector
	 *
	 * @param string $selector Selector to use, or blank to return all children
	 * @return PageArray
	 *
	 */
	public static function children(page $page, $selector = '', $options = array()) {
		if(!$page->numChildren) return new PageArray();
		if($selector) $selector .= ", ";
		$selector = "parent_id={$page->id}, $selector"; 
		if(strpos($selector, 'sort=') === false) {
			$sortfield = $page->template->sortfield;
			if(!$sortfield) $sortfield = $page->sortfield;
			$selector .= "sort=$sortfield";
		}
		return wire('pages')->find(trim($selector, ", "), $options); 
	}

	/**
	 * Return the page's first single child that matches the given selector. 
	 *
	 * Same as children() but returns a Page object or NullPage (with id=0) rather than a PageArray
	 *
	 * @param string $selector Selector to use, or blank to return the first child. 
	 * @return Page|NullPage
	 *
	 */
	public static function child(Page $page, $selector = '', $options = array()) {
		if(!$page->numChildren) return new NullPage();
		$selector .= ($selector ? ', ' : '') . "limit=1";
		if(strpos($selector, 'start=') === false) $selector .= ", start=0"; // prevent pagination
		$children = self::children($page, $selector, $options); 
		return count($children) ? $children->first() : new NullPage();
	}

	/**
	 * Return this page's parent pages, or the parent pages matching the given selector.
	 *
	 * @param sting $selector Optional selector string to filter parents by
	 * @return PageArray
	 *
	 */
	static public function parents(Page $page, $selector = '') {
		$parents = new PageArray();
		$parent = $page->parent();
		while($parent && $parent->id) {
			$parents->prepend($parent); 	
			$parent = $parent->parent();
		}
		return strlen($selector) ? $parents->filter($selector) : $parents; 
	}

	/**
	 * Return all parent from current till the one matched by $selector
	 *
	 * @param string|Page $selector May either be a selector string or Page to stop at. Results will not include this. 
	 * @param string $filter Optional selector string to filter matched pages by
	 * @return PageArray
	 *
	 */
	static public function parentsUntil(Page $page, $selector = '', $filter = '') {

		$parents = self::parents($page); 
		$matches = new PageArray();
		$stop = false;

		foreach($parents->reverse() as $parent) {

			if(is_string($selector) && strlen($selector)) {
				if(ctype_digit("$selector") && $parent->id == $selector) $stop = true; 
					else if($parent->matches($selector)) $stop = true; 

			} else if(is_int($selector)) {
				if($parent->id == $selector) $stop = true; 

			} else if($selector instanceof Page && $parnet->id == $selector->id) {
				$stop = true; 
			}

			if($stop) break;
			$matches->prepend($parent);
		}

		if(strlen($filter)) $matches->filter($filter); 
		return $matches;
	}


	/**
	 * Get the lowest-level, non-homepage parent of this page
	 *
	 * rootParents typically comprise the first level of navigation on a site. 
	 *
	 * @return Page 
	 *
	 */
	static public function rootParent(Page $page) {
		$parent = $page->parent;
		if(!$parent || !$parent->id || $parent->id === 1) return $page; 
		$parents = self::parents($page);
		$parents->shift(); // shift off homepage
		return $parents->first();
	}

	/**
	 * Return this Page's sibling pages, optionally filtered by a selector. 
	 *
	 * Note that the siblings include the current page. To exclude the current page, specify "id!=$page". 
	 *
	 * @param string $selector Optional selector to filter siblings by.
	 * @return PageArray
	 *
	 */
	static public function siblings(Page $page, $selector = '') {
		if($selector) $selector .= ", ";
		$selector = "parent_id={$page->parent_id}, $selector";
		if(strpos($selector, 'sort=') === false) {
			$parent = $page->parent();
			$selector .= "sort=" . ($parent ? $parent->sortfield : 'sort'); 
		}
		return wire('pages')->find(trim($selector, ", ")); 
	}

	/**
	 * Return the next sibling page
	 *
	 * If given a PageArray of siblings (containing the current) it will return the next sibling relative to the provided PageArray.
	 *
	 * Be careful with this function when the page has a lot of siblings. It has to load them all, so this function is best
	 * avoided at large scale, unless you provide your own already-reduced siblings list (like from pagination)
	 *
	 * When using a selector, note that this method operates only on visible children. If you want something like "include=all"
	 * or "include=hidden", they will not work in the selector. Instead, you should provide the siblings already retrieved with
	 * one of those modifiers, and provide those siblings as the second argument to this function.
	 *
	 * @param string $selector Optional selector string. When specified, will find nearest next sibling that matches. 
	 * @param PageArray $siblings Optional siblings to use instead of the default. May also be specified as first argument when no selector needed.
	 * @return Page|NullPage Returns the next sibling page, or a NullPage if none found. 
	 *
	 */
	static public function next(Page $page, $selector = '', PageArray $siblings = null) {
		if(is_object($selector) && $selector instanceof PageArray) {
			// backwards compatible to when $siblings was first argument
			$siblings = $selector;
			$selector = '';
		}
		if(is_null($siblings)) $siblings = $page->parent->children();
			else if(!$siblings->has($page)) $siblings->prepend($page); 

		$next = $page;
		do {
			$next = $siblings->getNext($next); 
			if(!strlen($selector) || !$next || $next->matches($selector)) break;
		} while($next && $next->id); 
		if(is_null($next)) $next = new NullPage();
		return $next; 
	}

	/**
	 * Return the previous sibling page
	 *
	 * If given a PageArray of siblings (containing the current) it will return the previous sibling relative to the provided PageArray.
	 *
	 * Be careful with this function when the page has a lot of siblings. It has to load them all, so this function is best
	 * avoided at large scale, unless you provide your own already-reduced siblings list (like from pagination)
	 *
	 * When using a selector, note that this method operates only on visible children. If you want something like "include=all"
	 * or "include=hidden", they will not work in the selector. Instead, you should provide the siblings already retrieved with
	 * one of those modifiers, and provide those siblings as the second argument to this function.
	 *
	 * @param string $selector Optional selector string. When specified, will find nearest previous sibling that matches. 
	 * @param PageArray $siblings Optional siblings to use instead of the default. May also be specified as first argument when no selector needed.
	 * @return Page|NullPage Returns the previous sibling page, or a NullPage if none found. 
	 *
	 */
	static public function prev(Page $page, $selector = '', PageArray $siblings = null) {
		if(is_object($selector) && $selector instanceof PageArray) {
			// backwards compatible to when $siblings was first argument
			$siblings = $selector;
			$selector = '';
		}
		if(is_null($siblings)) $siblings = $page->parent->children();
			else if(!$siblings->has($page)) $siblings->add($page);

		$prev = $page;
		do {
			$prev = $siblings->getPrev($prev); 
			if(!strlen($selector) || !$prev || $prev->matches($selector)) break;
		} while($prev && $prev->id); 
		if(is_null($prev)) $prev = new NullPage();
		return $prev;
	}

	/**
	 * Return all sibling pages after this one, optionally matching a selector
	 *
	 * @param string $selector Optional selector string. When specified, will filter the found siblings.
	 * @param PageArray $siblings Optional siblings to use instead of the default. 
	 * @return Page|NullPage Returns all matching pages after this one.
	 *
	 */
	static public function nextAll(Page $page, $selector = '', PageArray $siblings = null) {

		if(is_null($siblings)) $siblings = $page->parent()->children();
			else if(!$siblings->has($page)) $siblings->prepend($page);

		$id = $page->id;
		$all = new PageArray();
		$rec = false;

		foreach($siblings as $sibling) {
			if($sibling->id == $id) {
				$rec = true;
				continue;
			}
			if($rec) $all->add($sibling);
		}

		if(strlen($selector)) $all->filter($selector); 
		return $all;
	}

	/**
	 * Return all sibling pages before this one, optionally matching a selector
	 *
	 * @param string $selector Optional selector string. When specified, will filter the found siblings.
	 * @param PageArray $siblings Optional siblings to use instead of the default. 
	 * @return Page|NullPage Returns all matching pages before this one.
	 *
	 */
	static public function prevAll(Page $page, $selector = '', PageArray $siblings = null) {

		if(is_null($siblings)) $siblings = $page->parent()->children();
			else if(!$siblings->has($page)) $siblings->add($page);

		$id = $page->id;
		$all = new PageArray();

		foreach($siblings as $sibling) {
			if($sibling->id == $id) break;
			$all->add($sibling);
		}

		if(strlen($selector)) $all->filter($selector); 
		return $all;
	}

	/**
	 * Return all sibling pages after this one until matching the one specified 
	 *
	 * @param string|Page $selector May either be a selector string or Page to stop at. Results will not include this. 
	 * @param string $filter Optional selector string to filter matched pages by
	 * @param PageArray Optional PageArray of siblings to use instead of all from the page.
	 * @return PageArray
	 *
	 */
	static public function nextUntil(Page $page, $selector = '', $filter = '', PageArray $siblings = null) {

		if(is_null($siblings)) $siblings = $page->parent()->children();
			else if(!$siblings->has($page)) $siblings->prepend($page);

		$siblings = self::nextAll($page, '', $siblings); 

		$id = $page->id;
		$all = new PageArray();
		$stop = false;

		foreach($siblings as $sibling) {

			if(is_string($selector) && strlen($selector)) { 
				if(ctype_digit("$selector") && $sibling->id == $selector) $stop = true; 
					else if($sibling->matches($selector)) $stop = true; 

			} else if(is_int($selector)) {
				if($sibling->id == $selector) $stop = true; 

			} else if($selector instanceof Page && $sibling->id == $selector->id) {
				$stop = true; 
			}

			if($stop) break;
			$all->add($sibling);
		}

		if(strlen($filter)) $all->filter($filter); 
		return $all;
	}
	
	/**
	 * Return all sibling pages before this one until matching the one specified 
	 *
	 * @param string|Page $selector May either be a selector string or Page to stop at. Results will not include this. 
	 * @param string $filter Optional selector string to filter matched pages by
	 * @param PageArray Optional PageArray of siblings to use instead of all from the page.
	 * @return PageArray
	 *
	 */
	static public function prevUntil(Page $page, $selector = '', $filter = '', PageArray $siblings = null) {

		if(is_null($siblings)) $siblings = $page->parent()->children();
			else if(!$siblings->has($page)) $siblings->add($page);

		$siblings = self::prevAll($page, '', $siblings); 

		$id = $page->id;
		$all = new PageArray();
		$stop = false;

		foreach($siblings->reverse() as $sibling) {

			if(is_string($selector) && strlen($selector)) {
				if(ctype_digit("$selector") && $sibling->id == $selector) $stop = true; 
					else if($sibling->matches($selector)) $stop = true; 

			} else if(is_int($selector)) {
				if($sibling->id == $selector) $stop = true; 

			} else if($selector instanceof Page && $sibling->id == $selector->id) {
				$stop = true; 
			}

			if($stop) break;
			$all->prepend($sibling);
		}

		if(strlen($filter)) $all->filter($filter); 
		return $all;
	}

}
