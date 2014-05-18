<?php

/**
 * default.php
 * 
 * Main markup template file for AdminThemeDefault
 * 
 * __('FOR TRANSLATORS: please translate the file /wire/templates-admin/default.php rather than this one.');
 * 
 * 
 */

if(!defined("PROCESSWIRE")) die();

if(!isset($content)) $content = '';
	
$searchForm = $user->hasPermission('page-edit') ? $modules->get('ProcessPageSearch')->renderSearchForm() : '';

$config->styles->prepend($config->urls->adminTemplates . "styles/" . ($adminTheme->colors ? "main-$adminTheme->colors" : "main") . ".css?v=7"); 
$config->styles->append($config->urls->root . "wire/templates-admin/styles/font-awesome/css/font-awesome.min.css"); 
$config->scripts->append($config->urls->root . "wire/templates-admin/scripts/inputfields.js?v=5"); 
$config->scripts->append($config->urls->adminTemplates . "scripts/main.js?v=5");
	
require_once(dirname(__FILE__) . "/AdminThemeDefaultHelpers.php");
$helpers = new AdminThemeDefaultHelpers();

?><!DOCTYPE html>
<html lang="<?php echo $helpers->_('en'); 
	/* this intentionally on a separate line */ ?>">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<meta name="robots" content="noindex, nofollow" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">

	<title><?php echo $helpers->renderBrowserTitle(); ?></title>

	<script type="text/javascript"><?php echo $helpers->renderJSConfig(); ?></script>

	<?php foreach($config->styles as $file) echo "\n\t<link type='text/css' href='$file' rel='stylesheet' />"; ?>

	<?php foreach($config->scripts as $file) echo "\n\t<script type='text/javascript' src='$file'></script>"; ?>

</head>
<body class='<?php echo $helpers->renderBodyClass(); ?>'>

	<?php echo $helpers->renderAdminNotices($notices); ?>

	<div id="masthead" class="masthead ui-helper-clearfix">
		<div class="container">

			<a id='logo' href='<?php echo $config->urls->admin?>'><img width='130' src="<?php echo $config->urls->adminTemplates?>styles/images/logo.png" alt="ProcessWire" /></a>

			<?php 
			if($user->isLoggedin()) {
				echo $searchForm;
				echo "\n\n<ul id='topnav'>" . $helpers->renderTopNavItems() . "</ul>";
			}
			?>

		</div>
	</div><!--/#masthead-->

	<div id='breadcrumbs'>
		<div class='container'>

			<?php if(in_array($page->id, array(2,3,8))) echo $helpers->renderAdminShortcuts(); /* 2,3,8=page-list admin page IDs */ ?>

			<ul class='nav'><?php echo $helpers->renderBreadcrumbs(); ?></ul>

		</div>
	</div><!--/#breadcrumbs-->

	<div id="content" class="content fouc_fix">
		<div class="container">

			<?php 
			if(trim($page->summary)) echo "<h2>{$page->summary}</h2>"; 
			if($page->body) echo $page->body; 
			echo $content; 
			?>

		</div>
	</div><!--/#content-->

	<div id="footer" class="footer">
		<div class="container">
			<p>
			<?php if($user->isLoggedin()): ?>
			<span id='userinfo'>
				<i class='fa fa-user'></i> 
				<?php 
				if($user->hasPermission('profile-edit')): ?> 
				<i class='fa fa-angle-right'></i> <a class='action' href='<?php echo $config->urls->admin; ?>profile/'><?php echo $user->name; ?></a> <i class='fa fa-angle-right'></i>
				<?php endif; ?>
				<a class='action' href='<?php echo $config->urls->admin; ?>login/logout/'><?php echo $helpers->_('Logout'); ?></a>
			</span>
			<?php endif; ?>
			ProcessWire <?php echo $config->version . ' <!--v' . $config->systemVersion; ?>--> &copy; <?php echo date("Y"); ?> 
			</p>

			<?php if($config->debug && $this->user->isSuperuser()) include($config->paths->root . '/wire/templates-admin/debug.inc'); ?>
		</div>
	</div><!--/#footer-->

</body>
</html>
