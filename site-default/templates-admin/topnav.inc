<?php

function renderTopNavItem(Page $p, $level = 0) {

	$showItem = wire('user')->isSuperuser() ? true : false;
	$info = array();
	$children = $p->numChildren && !$level && $p->name != 'page' ? $p->children("check_access=0") : array();
	$out = '';

	if(!$level && !$showItem) { 
		$checkPages = count($children) ? $children : array($p); 
		foreach($checkPages as $child) {
			if($child->viewable()) {
				$showItem = true;
				break;
			}
		}
	}

	if(!$showItem) return '';

	$class = strpos(wire('page')->path, $p->path) === 0 ? 'on' : '';
	$title = strip_tags((string)$p->get('title|name')); 
	$title = __($title, dirname(__FILE__) . '/default.php'); // translate from context of default.php
	$out .= "<li>";

	if(!$level && count($children)) {
		$class = trim("$class dropdown-toggle"); 
		$out .= "<a href='$p->url' class='$class'>$title</a>"; 
		$out .= "<ul class='dropdown-menu topnav' data-my='right+1 top-2' data-at='right bottom'>";
		foreach($children as $c) $out .= renderTopNavItem($c, $level+1);
		$out .= "</ul>";
	} else {
		$class = $class ? " class='$class'" : '';
		$out .= "<a href='$p->url'$class>$title</a>"; 
	}

	$out .= "</li>";

	return $out; 
}

foreach($pages->get($wire->config->adminRootPageID)->children("check_access=0") as $p) {
	if(!$p->viewable()) continue; 
	echo renderTopNavItem($p);
}


