<?php namespace ProcessWire;

/**
 * ProcessWire Page Traversal
 *
 * Provides implementation for Page traversal functions.
 * Based upon the jQuery traversal functions. 
 *
 * ProcessWire 3.x (development), Copyright 2015 by Ryan Cramer
 * https://processwire.com
 *
 */

class PageTraversal {

	/**
	 * Return number of children, optionally with conditions
	 *
	 * Use this over $page->numChildren property when you want to specify a selector or when you want the result to
	 * include only visible children. See the options for the $selector argument. 
	 *
	 * @param Page $page
	 * @param bool|string|int|array $selector 
	 *	When not specified, result includes all children without conditions, same as $page->numChildren property.
	 *	When a string or array, a selector is assumed and quantity will be counted based on selector.
	 * 	When boolean true, number includes only visible children (excludes unpublished, hidden, no-access, etc.)
	 *	When boolean false, number includes all children without conditions, including unpublished, hidden, no-access, etc.
	 * 	When integer 1 number includes viewable children (as opposed to visible, viewable includes hidden pages + it also includes unpublished pages if user has page-edit permission).
	 * @return int Number of children
	 *
	 */
	public function numChildren(Page $page, $selector = null) {
		if(is_bool($selector)) {
			// onlyVisible takes the place of selector
			$onlyVisible = $selector; 
			if(!$onlyVisible) return $page->get('numChildren');
			return $page->wire('pages')->count("parent_id=$page->id"); 
			
		} else if($selector === 1) { 
			// viewable pages only
			$numChildren = $page->get('numChildren');
			if(!$numChildren) return 0;
			if($page->wire('user')->isSuperuser()) return $numChildren;
			if($page->wire('user')->hasPermission('page-edit')) return $page->wire('pages')->count("parent_id=$page->id, include=unpublished");
			return $page->wire('pages')->count("parent_id=$page->id, include=hidden"); 

		} else if(empty($selector) || (!is_string($selector) && !is_array($selector))) {
			return $page->get('numChildren'); 

		} else {
			if(is_string($selector)) {
				$selector = "parent_id=$page->id, $selector";
			} else if(is_array($selector)) {
				$selector["parent_id"] = $page->id;
			}
			return $page->wire('pages')->count($selector);
		}
	}

	/**
	 * Return this page's children pages, optionally filtered by a selector
	 *
	 * @param Page $page
	 * @param string|array $selector Selector to use, or blank to return all children
	 * @param array $options
	 * @return PageArray
	 *
	 */
	public function children(Page $page, $selector = '', $options = array()) {
		if(!$page->numChildren) return $page->wire('pages')->newPageArray();
		$defaults = array('caller' => 'page.children'); 
		$options = array_merge($defaults, $options); 
		$sortfield = $page->template->sortfield;
		if(!$sortfield) $sortfield = $page->sortfield;
		if(is_array($selector)) {
			// selector is array
			$selector["parent_id"] = $page->id;
			if(isset($selector["sort"])) $sortfield = '';
			if($sortfield) $selector[] = array("sort", $sortfield);
		} else {
			// selector is string
			$selector = trim("parent_id=$page->id, $selector", ", ");
			if(strpos($selector, 'sort=') === false) $selector .= ", sort=$sortfield";
		}
		return $page->wire('pages')->find($selector, $options); 
	}

	/**
	 * Return the page's first single child that matches the given selector. 
	 *
	 * Same as children() but returns a Page object or NullPage (with id=0) rather than a PageArray
	 *
	 * @param Page $page
	 * @param string|array $selector Selector to use, or blank to return the first child. 
	 * @param array $options
	 * @return Page|NullPage
	 *
	 */
	public function child(Page $page, $selector = '', $options = array()) {
		if(!$page->numChildren) return $page->wire('pages')->newNullPage();
		$defaults = array('getTotal' => false, 'caller' => 'page.child'); 
		$options = array_merge($defaults, $options); 
		if(is_array($selector)) {
			$selector["limit"] = 1;
			$selector[] = array("start", "0");
		} else {
			$selector .= ($selector ? ', ' : '') . "limit=1";
			if(strpos($selector, 'start=') === false) $selector .= ", start=0"; // prevent pagination
		}
		$children = $this->children($page, $selector, $options); 
		return count($children) ? $children->first() : $page->wire('pages')->newNullPage();
	}

	/**
	 * Return this page's parent pages, or the parent pages matching the given selector.
	 *
	 * @param Page $page
	 * @param string|array $selector Optional selector string to filter parents by
	 * @return PageArray
	 *
	 */
	public function parents(Page $page, $selector = '') {
		$parents = $page->wire('pages')->newPageArray();
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
	 * @param Page $page
	 * @param string|Page|array $selector May either be a selector or Page to stop at. Results will not include this. 
	 * @param string|array $filter Optional selector to filter matched pages by
	 * @return PageArray
	 *
	 */
	public function parentsUntil(Page $page, $selector = '', $filter = '') {

		$parents = $this->parents($page); 
		$matches = $page->wire('pages')->newPageArray();
		$stop = false;

		foreach($parents->reverse() as $parent) {

			if(is_string($selector) && strlen($selector)) {
				if(ctype_digit("$selector") && $parent->id == $selector) {
					$stop = true;
				} else if($parent->matches($selector)) {
					$stop = true;
				}
				
			} else if(is_array($selector) && !empty($selector)) {
				if($parent->matches($selector)) $stop = true;

			} else if(is_int($selector)) {
				if($parent->id == $selector) $stop = true; 

			} else if($selector instanceof Page && $parent->id == $selector->id) {
				$stop = true; 
			}

			if($stop) break;
			$matches->prepend($parent);
		}

		if(!empty($filter)) $matches->filter($filter); 
		
		return $matches;
	}


	/**
	 * Get the lowest-level, non-homepage parent of this page
	 *
	 * rootParents typically comprise the first level of navigation on a site. 
	 *
	 * @param Page $page
	 * @return Page 
	 *
	 */
	public function rootParent(Page $page) {
		$parent = $page->parent;
		if(!$parent || !$parent->id || $parent->id === 1) return $page; 
		$parents = $this->parents($page);
		$parents->shift(); // shift off homepage
		return $parents->first();
	}

	/**
	 * Return this Page's sibling pages, optionally filtered by a selector. 
	 *
	 * Note that the siblings include the current page. To exclude the current page, specify "id!=$page". 
	 *
	 * @param Page $page
	 * @param string $selector Optional selector to filter siblings by.
	 * @return PageArray
	 *
	 */
	public function siblings(Page $page, $selector = '') {
		$parent = $page->parent();
		$sort = $parent && $parent->id ? $parent->sortfield : 'sort';
		if(is_array($selector)) {
			$selector["parent_id"] = $page->parent_id;	
			$selector[] = array('sort', $sort);
		} else {
			$selector = "parent_id=$page->parent_id, $selector";
			if(strpos($selector, 'sort=') === false) $selector .= ", sort=$sort";
			$selector = trim($selector, ", ");
		}
		$options = array('caller' => 'page.siblings'); 
		return $page->wire('pages')->find($selector, $options); 
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
	 * @param Page $page
	 * @param string|array $selector Optional selector. When specified, will find nearest next sibling that matches. 
	 * @param PageArray $siblings Optional siblings to use instead of the default. May also be specified as first argument when no selector needed.
	 * @return Page|NullPage Returns the next sibling page, or a NullPage if none found. 
	 *
	 */
	public function next(Page $page, $selector = '', PageArray $siblings = null) {
		if(is_object($selector) && $selector instanceof PageArray) {
			// backwards compatible to when $siblings was first argument
			$siblings = $selector;
			$selector = '';
		}
		if(is_null($siblings)) {
			$siblings = $page->parent->children();
		} else if(!$siblings->has($page)) {
			$siblings->prepend($page);
		}

		$next = $page;
		do {
			$next = $siblings->getNext($next, false); 
			if(empty($selector) || !$next || $next->matches($selector)) break;
		} while($next && $next->id); 
		if(is_null($next)) $next = $page->wire('pages')->newNullPage();
		return $next; 
	}

	/*
	public function nextFast(Page $page, $selector = '') {
		$sortfield = $page->sortfield;	
		if(!$sortfield) $sortfield = 'sort';
		$descending = strpos($sortfield, '-') === 0;
		if($descending) $sortfield = ltrim($sortfield, '-');
		$value = $page->wire('sanitizer')->selectorValue($page->getUnformatted($sortfield)); 
		$operator = $descending ? "<=" : ">=";
		$selector .= ", sortfield$operator$value, id!=$page->id";
		// experimental/work in progress
	}
	*/

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
	 * @param Page $page
	 * @param string|array $selector Optional selector. When specified, will find nearest previous sibling that matches. 
	 * @param PageArray $siblings Optional siblings to use instead of the default. May also be specified as first argument when no selector needed.
	 * @return Page|NullPage Returns the previous sibling page, or a NullPage if none found. 
	 *
	 */
	public function prev(Page $page, $selector = '', PageArray $siblings = null) {
		if(is_object($selector) && $selector instanceof PageArray) {
			// backwards compatible to when $siblings was first argument
			$siblings = $selector;
			$selector = '';
		}
		if(is_null($siblings)) {
			$siblings = $page->parent->children();
		} else if(!$siblings->has($page)) {
			$siblings->add($page);
		}

		$prev = $page;
		do {
			$prev = $siblings->getPrev($prev, false); 
			if(empty($selector) || !$prev || $prev->matches($selector)) break;
		} while($prev && $prev->id); 
		if(is_null($prev)) $prev = $page->wire('pages')->newNullPage();
		return $prev;
	}

	/**
	 * Return all sibling pages after this one, optionally matching a selector
	 *
	 * @param Page $page
	 * @param string|array $selector Optional selector. When specified, will filter the found siblings.
	 * @param PageArray $siblings Optional siblings to use instead of the default. 
	 * @return Page|NullPage Returns all matching pages after this one.
	 *
	 */
	public function nextAll(Page $page, $selector = '', PageArray $siblings = null) {

		if(is_null($siblings)) {
			$siblings = $page->parent()->children();
		} else if(!$siblings->has($page)) {
			$siblings->prepend($page);
		}

		$id = $page->id;
		$all = $page->wire('pages')->newPageArray();
		$rec = false;

		foreach($siblings as $sibling) {
			if($sibling->id == $id) {
				$rec = true;
				continue;
			}
			if($rec) $all->add($sibling);
		}

		if(!empty($selector)) $all->filter($selector); 
		
		return $all;
	}

	/**
	 * Return all sibling pages before this one, optionally matching a selector
	 *
	 * @param Page $page
	 * @param string|array $selector Optional selector. When specified, will filter the found siblings.
	 * @param PageArray $siblings Optional siblings to use instead of the default. 
	 * @return Page|NullPage Returns all matching pages before this one.
	 *
	 */
	public function prevAll(Page $page, $selector = '', PageArray $siblings = null) {

		if(is_null($siblings)) {
			$siblings = $page->parent()->children();
		} else if(!$siblings->has($page)) {
			$siblings->add($page);
		}

		$id = $page->id;
		$all = $page->wire('pages')->newPageArray();

		foreach($siblings as $sibling) {
			if($sibling->id == $id) break;
			$all->add($sibling);
		}

		if(!empty($selector)) $all->filter($selector); 
		
		return $all;
	}

	/**
	 * Return all sibling pages after this one until matching the one specified 
	 *
	 * @param Page $page
	 * @param string|Page|array $selector May either be a selector or Page to stop at. Results will not include this. 
	 * @param string|array $filter Optional selector to filter matched pages by
	 * @param PageArray|null $siblings Optional PageArray of siblings to use instead of all from the page.
	 * @return PageArray
	 *
	 */
	public function nextUntil(Page $page, $selector = '', $filter = '', PageArray $siblings = null) {

		if(is_null($siblings)) {
			$siblings = $page->parent()->children();
		} else if(!$siblings->has($page)) {
			$siblings->prepend($page);
		}

		$siblings = $this->nextAll($page, '', $siblings); 

		$all = $page->wire('pages')->newPageArray();
		$stop = false;

		foreach($siblings as $sibling) {

			if(is_string($selector) && strlen($selector)) {
				if(ctype_digit("$selector") && $sibling->id == $selector) {
					$stop = true;
				} else if($sibling->matches($selector)) {
					$stop = true;
				}
				
			} else if(is_array($selector) && count($selector)) {
				if($sibling->matches($selector)) $stop = true;

			} else if(is_int($selector)) {
				if($sibling->id == $selector) $stop = true; 

			} else if($selector instanceof Page && $sibling->id == $selector->id) {
				$stop = true; 
			}

			if($stop) break;
			
			$all->add($sibling);
		}

		if(!empty($filter)) $all->filter($filter); 
		
		return $all;
	}
	
	/**
	 * Return all sibling pages before this one until matching the one specified 
	 *
	 * @param Page $page
	 * @param string|Page|array $selector May either be a selector or Page to stop at. Results will not include this. 
	 * @param string|array $filter Optional selector string to filter matched pages by
	 * @param PageArray|null $siblings Optional PageArray of siblings to use instead of all from the page.
	 * @return PageArray
	 *
	 */
	public function prevUntil(Page $page, $selector = '', $filter = '', PageArray $siblings = null) {

		if(is_null($siblings)) {
			$siblings = $page->parent()->children();
		} else if(!$siblings->has($page)) {
			$siblings->add($page);
		}

		$siblings = $this->prevAll($page, '', $siblings); 

		$all = $page->wire('pages')->newPageArray();
		$stop = false;

		foreach($siblings->reverse() as $sibling) {

			if(is_string($selector) && strlen($selector)) {
				if(ctype_digit("$selector") && $sibling->id == $selector) {
					$stop = true;
				} else if($sibling->matches($selector)) {
					$stop = true;
				}
				
			} else if(is_array($selector) && count($selector)) {
				if($sibling->matches($selector)) $stop = true;

			} else if(is_int($selector)) {
				if($sibling->id == $selector) $stop = true; 

			} else if($selector instanceof Page && $sibling->id == $selector->id) {
				$stop = true; 
			}

			if($stop) break;
			
			$all->prepend($sibling);
		}

		if(!empty($filter)) $all->filter($filter); 
		
		return $all;
	}

}
