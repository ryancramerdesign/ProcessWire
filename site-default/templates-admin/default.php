<?php

$searchForm = $user->hasPermission('page-edit') ? $modules->get('ProcessPageSearch')->renderSearchForm() : '';
$bodyClass = $input->get->modal ? 'modal ' : '';
$bodyClass .= "id-{$page->id} template-{$page->template->name}";
if(!isset($content)) $content = '';

//$defaultColorTheme = 'classic';
if($input->get->colors && !$user->isGuest()) $colors = $sanitizer->pageName($input->get->colors); 
	else if($user->isLoggedin() && $user->admin_colors && $user->admin_colors->id) $colors = $user->admin_colors->name; 
	else if($user->isLoggedin() && $session->adminThemeColors) $colors = $session->adminThemeColors; 
	else if($config->adminThemeColors) $colors = $sanitizer->pageName($config->adminThemeColors); 
	else $colors = '';
	//else $colors = $defaultColorTheme;

if(is_file(dirname(__FILE__) . "/styles/main-$colors.css")) $session->adminThemeColors = $colors;
	else $session->adminThemeColors = '';
$colorsFile = $session->adminThemeColors ? "main-" . $session->adminThemeColors : "main";

$config->styles->prepend($config->urls->adminTemplates . "styles/$colorsFile.css?v=6"); 
$config->styles->append($config->urls->root . "wire/templates-admin/styles/font-awesome/css/font-awesome.min.css"); 
$config->scripts->append($config->urls->root . "wire/templates-admin/scripts/inputfields.js?v=5"); 
$config->scripts->append($config->urls->adminTemplates . "scripts/main.js?v=5"); 

$browserTitle = wire('processBrowserTitle'); 
if(!$browserTitle) $browserTitle = __(strip_tags($page->get('title|name')), __FILE__) . ' &bull; ProcessWire';

/*
 * Dynamic phrases that we want to be automatically translated
 *
 * These are in a comment so that they register with the parser, in place of the dynamic __() function calls with page titles. 
 * 
 * __("Pages"); 
 * __("Setup"); 
 * __("Modules"); 
 * __("Access"); 
 * __("Admin"); 
 * __("Site"); 
 * __("Languages"); 
 * __("Users"); 
 * __("Roles"); 
 * __("Permissions"); 
 * __("Templates"); 
 * __("Fields"); 
 * __("Add New"); 
 * __("Not yet configured: See template family settings."); 
 * 
 */

?>
<!DOCTYPE html>
<html lang="<?php echo __('en', __FILE__); // HTML tag lang attribute
	/* this intentionally on a separate line */ ?>"> 
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<meta name="robots" content="noindex, nofollow" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">

	<title><?php echo $browserTitle; ?></title>

	<script type="text/javascript">
		<?php

		$jsConfig = $config->js();
		$jsConfig['debug'] = $config->debug;
		$jsConfig['urls'] = array(
			'root' => $config->urls->root, 
			'admin' => $config->urls->admin, 
			'modules' => $config->urls->modules, 
			'core' => $config->urls->core, 
			'files' => $config->urls->files, 
			'templates' => $config->urls->templates,
			'adminTemplates' => $config->urls->adminTemplates,
			); 

		if(!empty($jsConfig['JqueryWireTabs'])) $bodyClass .= ' hasWireTabs'; 
		?>

		var config = <?php echo json_encode($jsConfig); ?>;
	</script>

	<?php foreach($config->styles->unique() as $file) echo "\n\t<link type='text/css' href='$file' rel='stylesheet' />"; ?>


	<!--[if IE]>
	<link rel="stylesheet" type="text/css" href="<?php echo $config->urls->adminTemplates; ?>styles/ie.css" />
	<![endif]-->	

	<!--[if lt IE 8]>
	<link rel="stylesheet" type="text/css" href="<?php echo $config->urls->adminTemplates; ?>styles/ie7.css" />
	<![endif]-->

	<?php foreach($config->scripts->unique() as $file) echo "\n\t<script type='text/javascript' src='$file'></script>"; ?>

</head>
<body<?php if($bodyClass) echo " class='$bodyClass'"; ?>>

	<?php if(count($notices)) include($config->paths->adminTemplates . "notices.inc"); ?>

	<div id="masthead" class="masthead ui-helper-clearfix">
		<div class="container">

			<a id='logo' href='<?php echo $config->urls->admin?>'><img width='130' src="<?php echo $config->urls->adminTemplates?>styles/images/logo.png" alt="ProcessWire" /></a>

			<?php echo tabIndent($searchForm, 3); ?>

			<ul id='topnav'>
				<?php include($config->paths->adminTemplates . "topnav.inc"); ?>
				<?php if(!$user->isGuest()): ?>
				<li>
					<a class='dropdown-toggle' href='<?php echo $config->urls->admin?>profile/'><i class='icon-user'></i></a>
					<ul class='dropdown-menu topnav' data-my='left-1 top' data-at='left bottom'>
						<?php if($user->hasPermission('profile-edit')): ?>
						<li><a href='<?php echo $config->urls->admin?>profile/'><?php echo __('Profile', __FILE__); ?> <small><?php echo $user->name?></small></a></li>
						<?php endif; ?>
						<li><a href='<?php echo $config->urls->admin?>login/logout/'><?php echo __('Logout', __FILE__); ?> <i class='icon-signout'></i></a></li>
					</ul>
				</li>
				<?php endif; ?>
			</ul>

		</div>
	</div>


	<div id='breadcrumbs'>
		<div class='container'>
			<?php 
			if($user->isLoggedin() && in_array($page->id, array(2,3,8))) { // page-list
				echo "<div id='head_button'>";
				include($config->paths->adminTemplates . "shortcuts.inc"); 
				echo "</div>";
			}
			?>

			<ul class='nav'>

				<?php
				echo "<li><a class='sitelink' href='{$config->urls->root}'><i class='icon-home'></i></a><i class='icon-angle-right'></i></li>"; 
				foreach($this->fuel('breadcrumbs') as $breadcrumb) {
					$title = __($breadcrumb->title, __FILE__); 
					echo "<li><a href='{$breadcrumb->url}'>{$title}</a><i class='icon-angle-right'></i></li>";
				}
				unset($title);
				echo "<li class='title'>" . __(strip_tags($this->fuel->processHeadline ? $this->fuel->processHeadline : $page->get("title|name")), __FILE__) . "</li>";
				?>
			</ul>

		</div>
	</div>

	<div id="content" class="content fouc_fix">
		<div class="container">

			<?php if(trim($page->summary)) echo "<h2>{$page->summary}</h2>"; ?>

			<?php if($page->body) echo $page->body; ?>

			<?php echo $content?>

		</div>
	</div>


	<div id="footer" class="footer">
		<div class="container">
			<p>
			<?php if(!$user->isGuest()): ?>

			<span id='userinfo'>
				<i class='icon-user'></i> 
				<?php 
				if($user->hasPermission('profile-edit')): ?> <i class='icon-angle-right'></i>
				<a class='action' href='<?php echo $config->urls->admin; ?>profile/'><?php echo $user->name; ?></a> <i class='icon-angle-right'></i>
				<?php endif; ?>

				<a class='action' href='<?php echo $config->urls->admin; ?>login/logout/'><?php echo __('Logout', __FILE__); ?></a>
			</span>

			<?php endif; ?>

			ProcessWire <?php echo $config->version . ' <!--v' . $config->systemVersion; ?>--> &copy; <?php echo date("Y"); ?> Ryan Cramer 
			</p>

			<?php if($config->debug && $this->user->isSuperuser()) include($config->paths->adminTemplates . "debug.inc"); ?>
		</div>
	</div>

</body>
</html>
