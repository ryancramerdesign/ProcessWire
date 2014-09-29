<?php

/**
 * Shared functions or variables for all template files 
 *
 * This file is automatically prepended to all template files as a result of:
 * $config->prependTemplateFile = '_init.php'; in /site/config.php. 
 *
 * If you want to disable this automatic inclusion for any given template, 
 * go in your admin to Setup > Templates > [some-template] and click on the 
 * "Files" tab. Check the box to "Disable automatic prepend file". 
 */

/** 
 * Given a group of pages render a tree of navigation
 *
 * @param Page|PageArray $items Page to start the navigation tree from or pages to render
 * @param int $depth How many levels of navigation below current should it go?
 * @return string
 *
 */
function renderNavTree($items, $maxDepth = 3) {

	if($items instanceof Page) {
		// if we've been given a single page rather than a group of them, 
		// convert it to a group (PageArray rather than Page)
		$page = $items; 
		$items = new PageArray();
		$items->add($page);
	}

	// if no items for nav, return nothing
	if(!count($items)) return '';

	// $out is where we store the markup we are creating in this function
	// start our <ul> markup
	$out = "<ul class='nav nav-tree'>";

	// cycle through all the items
	foreach($items as $item) {

		// markup for the list item...
		// if current item is the same as the page being viewed, add a "current" class to it
		if($item->id == wire('page')->id) {
			$out .= "<li class='current'>";
		} else {
			$out .= "<li>";
		}

		// markup for the link
		$out .= "<a href='$item->url'>$item->title</a>";

		// if the item has children and we're allowed to output tree navigation (maxDepth)
		// then call this same function again for the item's children 
		if($item->hasChildren() && $maxDepth) {
			$out .= renderNavTree($item->children, $maxDepth-1); 
		}

		// close the list item
		$out .= "</li>";
	}

	// end our <ul> markup
	$out .= "</ul>";

	// return the markup we generated above
	return $out; 
}


