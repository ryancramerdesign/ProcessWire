<?php

/**
 * Controller for ProcessWire Admin
 *
 * This file is designed for inclusion by /site/templates/admin.php template and all the variables it references are from your template namespace. 
 *
 * Copyright 2011 by Ryan Cramer
 *
 */

if(!defined("PROCESSWIRE")) die("This file may not be accessed directly.");

// ensure core jQuery modules are loaded before others
$modules->get("JqueryCore"); 
$modules->get("JqueryUI"); 

// tell ProcessWire that any pages loaded from this point forward should have their outputFormatting turned off
$pages->setOutputFormatting(false); 

// setup breadcrumbs to current page, and the Process may modify, add to or replace them as needed
$breadcrumbs = new Breadcrumbs();
foreach($page->parents() as $p) {
	if($p->id > 1) $breadcrumbs->add(new Breadcrumb($p->url, $p->get("title|name"))); 
}
Wire::setFuel('breadcrumbs', $breadcrumbs); 
$controller = null;
$content = '';

// enable modules to output their own ajax responses if they choose to
if($config->ajax) ob_start();

if($page->process && $page->process != 'ProcessPageView') {
	try {

		if($config->demo && !in_array($page->process, array('ProcessLogin'))) {
			if(count($_POST)) $this->error("Saving is disabled in this demo"); 
			foreach($_POST as $k => $v) unset($_POST[$k]); 
			foreach($_FILES as $k => $v) unset($_FILES[$k]); 
			$input->post->removeAll();
		}

		$controller = new ProcessController(); 
		$controller->setProcessName($page->process); 
		$content = $controller->execute();

	} catch(Wire404Exception $e) {
		$this->error($e->getMessage()); 

	} catch(WirePermissionException $e) {

		if($controller && $controller->isAjax()) {
			$content = $controller->jsonMessage($e->getMessage(), true); 

		} else if($user->isGuest()) {
			$process = $modules->get("ProcessLogin"); 
			$content = $process->execute();
		} else {
			$this->error($e->getMessage()); 	
		}

	} catch(Exception $e) {
		$msg = $e->getMessage(); 
		if($config->debug) $msg .= "<pre>" . $e->getTraceAsString() . "</pre>";
		$this->error($msg); 
		if($controller && $controller->isAjax()) $content = $controller->jsonMessage($e->getMessage(), true); 
	}

} else {
	$content = "<p>This page has no Process assigned.</p>";
}

if($config->ajax) {
	// enable modules to output their own ajax responses if they choose to
	if(!$content) $content = ob_get_contents();
	ob_end_clean();
}

if($controller && $controller->isAjax()) {
	if(empty($content) && count($notices)) $content = $controller->jsonMessage($notices->last()->text); 
	echo $content; 
} else {
	require($config->paths->adminTemplates . 'default.php'); 
}

