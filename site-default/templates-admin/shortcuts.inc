<?php

function renderShortcuts() {

	$user = wire('user');
	if($user->isGuest() || !$user->hasPermission('page-edit')) return '';
	$language = $user->language && $user->language->id && !$user->language->isDefault() ? $user->language : null;
	$url = wire('config')->urls->admin . 'page/add/';
	$out = '';

	foreach(wire('templates') as $template) {
		$parent = $template->getParentPage(true); 
		if(!$parent) continue; 
		$label = $template->label;
		if($language) $label = $template->get("label$language");
		if(empty($label)) $label = $template->name; 
		$out .= "<li><a href='$url?parent_id=$parent->id'>$label</a></li>";
	}

	if(empty($out)) {
		$out .= "<li>" . __('Not yet configured: See template family settings.', dirname(__FILE__) . "/default.php") . "</li>";
	}

	$label = __('Add New', dirname(__FILE__) . "/default.php"); 

	$out = 	"<button class='dropdown-toggle'><i class='icon-angle-down'></i> $label</button>" . 
		"<ul class='dropdown-menu'>$out</ul>";

	return $out; 
}

echo renderShortcuts();

