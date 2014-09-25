<?php

/**
 * Default.php
 * 
 * Main markup file for AdminThemeReno
 * Copyright (C) 2014 by Tom Reno (Renobird)
 * http://www.tomrenodesign.com
 *
 * ProcessWire 2.x
 * Copyright (C) 2014 by Ryan Cramer
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 *
 * http://processwire.com
 * 
 */

if(!defined("PROCESSWIRE")) die();

if(!isset($content)) $content = '';
$version = $adminTheme->version;
$searchForm = $user->hasPermission('page-edit') ? $modules->get('ProcessPageSearch')->renderSearchForm($adminTheme->getSearchPlaceholder()) : '';

$config->styles->prepend($config->urls->adminTemplates . "styles/" . ($adminTheme->colors ? "$adminTheme->colors" : "main") . ".css?v=$version"); 
$config->styles->append($config->urls->root . "wire/templates-admin/styles/font-awesome/css/font-awesome.min.css?v=$version");
$config->scripts->append($config->urls->root . "wire/templates-admin/scripts/inputfields.js?v=$version"); 
$config->scripts->append($config->urls->adminTemplates . "scripts/main.js?v=$version");

require_once(dirname(__FILE__) . "/AdminThemeRenoHelpers.php");
$helpers = new AdminThemeRenoHelpers();
$extras = $adminTheme->getExtraMarkup();

?>
<!DOCTYPE html>
<html class="<?php echo $helpers->renderBodyClass(); ?>" lang="<?php echo $helpers->_('en'); 
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

<body class="<?php echo $helpers->renderBodyClass(); ?>">

	<div id="wrap">
		<div id="masthead" class="masthead ui-helper-clearfix">

				<a href="" class='main-nav-toggle'><i class="fa fa-bars"></i></a>
				<a id="logo" href="<?php echo $config->urls->admin?>">
					<img src="<?php echo $config->urls->adminTemplates?>styles/images/logo.png" alt="ProcessWire" />
					<img src="<?php echo $config->urls->adminTemplates?>styles/images/logo-sm.png" class='sm' alt="ProcessWire" />
				</a>

				<?php echo tabIndent($searchForm, 3); ?>

				<ul id="topnav">
					<?php echo $helpers->renderTopNavItems(); ?>
				</ul>

				<?php echo $extras['masthead']; ?>

		</div>

		<div id="sidebar" class="mobile">
			
			<ul id="main-nav">
				<?php echo $helpers->renderSideNavItems($page); ?>
			</ul>
			
			<?php echo $extras['sidebar']; ?>

		</div>

		<div id="main">

			<?php 
			echo $helpers->renderAdminNotices($notices);
			echo $extras['notices'];
			?>
		
			<div id="breadcrumbs">
				<ul class="nav"><?php echo $helpers->renderBreadcrumbs(false); ?></ul>
			</div>

			<div id="headline">
				<?php if(in_array($page->id, array(2,3,8))) echo $helpers->renderAdminShortcuts(); /* 2,3,8=page-list admin page IDs */ ?>
				<h1 id="title"><?php echo $helpers->getHeadline() ?></h1>
			</div>

			<div id="content" class="content fouc_fix">

				<?php
				if(trim($page->summary)) echo "<h2>$page->summary</h2>";
				if($page->body) echo $page->body;
				echo $content;
				echo $extras['content'];
				?>

			</div>

			<div id="footer" class="footer">
				<p>
					<?php if(!$user->isGuest()): ?>
						<span id="userinfo">
						<?php if($user->hasPermission('profile-edit')): ?> 
							<a class="action" href="<?php echo $config->urls->admin; ?>profile/"><i class="fa fa-user"></i> <?php echo $user->name; ?></a>  
						<?php endif; ?>
							<a class="action" href="<?php echo $config->urls->admin; ?>login/logout/"><i class="fa fa-times"></i> <?php echo $helpers->_('Logout'); ?></a>
						</span>
					<?php endif; ?>
					ProcessWire <?php echo $config->versionName . ' <!--v' . $config->systemVersion; ?>--> &copy; <?php echo date("Y"); ?> 
				</p>
				
				<?php
				echo $extras['footer'];
				if($config->debug && $user->isSuperuser()) include($config->paths->root . "wire/templates-admin/debug.inc"); 
				?>

			</div><!--/#footer-->
		</div> <!-- /#main -->
	</div> <!-- /#wrap -->
	<?php echo $extras['body']; ?>
</body>
</html>
