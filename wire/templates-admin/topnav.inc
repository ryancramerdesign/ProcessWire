<?php

foreach($pages->get($wire->config->adminRootPageID)->children() as $p) {

	if(!$p->viewable()) continue; 

	$showItem = $user->isSuperuser() ? true : false;
	$info = array();

	if(!$showItem) { 
		$checkPages = $p->numChildren ? $p->children : array($p); 
		foreach($checkPages as $child) {
			$process = $child->getUnformatted('process'); 
			if($process) {
				$info = $modules->getModuleInfo($process); 
				$permission = empty($info['permission']) ? '' : $info['permission']; 
				if($permission && $user->hasPermission($permission)) {
					$showItem = true; 
					break;
				}
			}
		}
	}

	if($showItem) { 
		$class = strpos($page->path, $p->path) === 0 ? " class='on'" : '';
		echo "\n\t\t\t<li><a href='{$p->url}'$class>" . strip_tags($p->get('title|name')) . "</a></li>"; 
	}
}
