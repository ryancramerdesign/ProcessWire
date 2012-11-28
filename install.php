<?php

/**
 * ProcessWire Installer
 *
 * Because this installer runs before PW2 is installed, it is largely self contained.
 * It's a quick-n-simple single purpose script that's designed to run once, and it should be deleted after installation.
 * This file self-executes using code found at the bottom of the file, under the Installer class. 
 *
 * Note that it creates this file once installation is completed: /site/assets/installed.php
 * If that file exists, the installer will not run. So if you need to re-run this installer for any
 * reason, then you'll want to delete that file. This was implemented just in case someone doesn't delete the installer.
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2012 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://www.processwire.com
 * http://www.ryancramer.com
 *
 */

define("PROCESSWIRE_INSTALL", 2); 

/**
 * class Installer
 *
 * Self contained class to install ProcessWire 2.x
 *
 */
class Installer {

	/**
	 * Whether or not we force installed files to be copied. 
	 *
	 * If false, we attempt a faster rename of directories instead.
	 *
	 */
	const FORCE_COPY = true; 

	/**
	 * Replace existing database tables if already present?
	 *
	 */
	const REPLACE_DB = true; 

	/**
	 * Minimum required PHP version to install ProcessWire
	 *
	 */
	const MIN_REQUIRED_PHP_VERSION = '5.2.4';

	/**
	 * File permissions, determined in the dbConfig function
	 *
	 * Below are last resort defaults
	 *
	 */
	protected $chmodDir = "0777";
	protected $chmodFile = "0666";

	/**
	 * Number of errors that occurred during the request
	 *
	 */
	protected $numErrors = 0; 

	/**
	 * Execution controller
	 *
	 */
	public function execute() {

		$title = "ProcessWire 2.3 Installation";

		require("./wire/templates-admin/install-head.inc"); 

		if(isset($_POST['step'])) switch($_POST['step']) {

			case 1: $this->compatibilityCheck(); 
				break;

			case 2: $this->dbConfig(); 
				break;

			case 4: $this->dbSaveConfig(); 
				break;

			case 5: require("./index.php"); 
				$this->adminAccountSave($wire); 
				break;

			default: 
				$this->welcome();

		} else $this->welcome();

		require("./wire/templates-admin/install-foot.inc"); 
	}


	/**
	 * Welcome/Intro screen
	 *
	 */
	protected function welcome() {
		$this->h("Welcome. This tool will guide you through the installation process."); 
		$this->p("Thanks for choosing ProcessWire! If you downloaded this copy of ProcessWire from somewhere other than <a href='http://processwire.com/'>processwire.com</a> or <a href='https://github.com/ryancramerdesign/ProcessWire' target='_blank'>our GitHub page</a>, please download a fresh copy before installing.");
		$this->p("Need help or have questions during installation? Please stop by the <a href='http://processwire.com/talk/' target='_blank'>ProcessWire support forums</a> and we'll be glad to help.");
		$this->btn("Get Started", 1); 
	}


	/**
	 * Check if the given function $name exists and report OK or fail with $label
	 *
	 */
	protected function checkFunction($name, $label) {
		if(function_exists($name)) $this->ok("OK: $label"); 
			else $this->err("Fail: $label"); 
	}

	/**
	 * Step 1: Check for ProcessWire compatibility
	 *
	 */
	protected function compatibilityCheck() { 

		$this->h("Compatibility Check"); 
		
		if(is_file("./site/install/install.sql")) {
			$this->ok("Found installation profile in /site/install/"); 

		} else if(is_dir("./site/")) {
			$this->ok("Found /site/ -- already installed? ");

		} else if(@rename("./site-default", "./site")) {
			$this->ok("Renamed /site-default => /site"); 

		} else {
			$this->err("Before continuing, please rename '/site-default' to '/site' -- this is the default installation profile."); 
			$this->ok("If you prefer, you may download an alternate installation profile at processwire.com/download, which you should unzip to /site");
			$this->btn("Continue", 1); 
			return;
		}

		if(version_compare(PHP_VERSION, self::MIN_REQUIRED_PHP_VERSION) >= 0) {
			$this->ok("PHP version " . PHP_VERSION);
		} else {
			$this->err("ProcessWire requires PHP version 5.2.4 or newer. You are running PHP " . PHP_VERSION);
		}

		$this->checkFunction("filter_var", "Filter functions (filter_var)");
		$this->checkFunction("mysqli_connect", "MySQLi");
		$this->checkFunction("imagecreatetruecolor", "GD 2.0 or newer"); 
		$this->checkFunction("json_encode", "JSON support");
		$this->checkFunction("preg_match", "PCRE support"); 
		$this->checkFunction("ctype_digit", "CTYPE support");
		$this->checkFunction("iconv", "ICONV support"); 
		$this->checkFunction("session_save_path", "SESSION support"); 
		$this->checkFunction("hash", "HASH support"); 
		$this->checkFunction("spl_autoload_register", "SPL support"); 

		if(function_exists('apache_get_modules')) {
			if(in_array('mod_rewrite', apache_get_modules())) $this->ok("Found Apache module: mod_rewrite"); 
				else $this->err("Apache mod_rewrite does not appear to be installed and is required by ProcessWire."); 
		} else {
			// apache_get_modules doesn't work on a cgi installation.
			// check for environment var set in htaccess file, as submitted by jmarjie. 
			$mod_rewrite = getenv('HTTP_MOD_REWRITE') == 'On' ? true : false;
			if($mod_rewrite) {
				$this->ok("Found Apache module (cgi): mod_rewrite");
			} else {
				$this->err("Unable to determine if Apache mod_rewrite (required by ProcessWire) is installed. On some servers, we may not be able to detect it until your .htaccess file is place. Please click the 'check again' button at the bottom of this screen, if you haven't already."); 
			}
		}

		if(is_writable("./site/assets/")) $this->ok("./site/assets/ is writable"); 
			else $this->err("Error: Directory ./site/assets/ must be writable. Please adjust the permissions before continuing."); 

		if(is_writable("./site/config.php")) $this->ok("./site/config.php is writable"); 
			else $this->err("Error: File ./site/config.php must be writable. Please adjust the permissions before continuing."); 

		if(!is_file("./.htaccess") || !is_readable("./.htaccess")) {
			if(@rename("./htaccess.txt", "./.htaccess")) $this->ok("Installed .htaccess"); 
				else $this->err("/.htaccess doesn't exist. Before continuing, you should rename the included htaccess.txt file to be .htaccess (with the period in front of it, and no '.txt' at the end)."); 

		} else if(!strpos(file_get_contents("./.htaccess"), "PROCESSWIRE")) {
			$this->err("/.htaccess file exists, but is not for ProcessWire. Please overwrite or combine it with the provided /htaccess.txt file (i.e. rename /htaccess.txt to /.htaccess, with the period in front)."); 

		} else {
			$this->ok(".htaccess looks good"); 
		}

		if($this->numErrors) {
			$this->p("One or more errors were found above. Please correct these issues before proceeding or <a href='http://processwire.com/talk/'>contact ProcessWire support</a> if you have questions or think the error is incorrect.");
			$this->btn("Check Again", 1); 
		}

		$this->btn("Continue to Next Step", 2); 
	}

	/**
	 * Step 2: Configure the database and file permission settings
	 *
	 */
	protected function dbConfig($values = array()) {

		if(!is_file("./site/install/install.sql")) die("There is no installation profile in /site/. Please place one there before continuing. You can get it at processwire.com/download"); 

		$this->h("MySQL Database"); 
		$this->p("Please create a MySQL 5.x database and user account on your server. The user account should have full read, write and delete permissions on the database.* Once created, please enter the information for this database and account below:"); 
		$this->p("*Recommended permissions are select, insert, update, delete, create, alter, index, drop, create temporary tables, and lock tables.", "detail"); 

		if(!isset($values['dbName'])) $values['dbName'] = '';
		if(!isset($values['dbHost'])) $values['dbHost'] = ini_get("mysqli.default_host"); 
		if(!isset($values['dbPort'])) $values['dbPort'] = ini_get("mysqli.default_port"); 
		if(!isset($values['dbUser'])) $values['dbUser'] = ini_get("mysqli.default_user"); 
		if(!isset($values['dbPass'])) $values['dbPass'] = ini_get("mysqli.default_pw"); 

		if(!$values['dbHost']) $values['dbHost'] = 'localhost';
		if(!$values['dbPort']) $values['dbPort'] = 3306; 

		foreach($values as $key => $value) {
			if(strpos($key, 'chmod') === 0) $values[$key] = (int) $value;
				else $values[$key] = htmlspecialchars($value, ENT_QUOTES); 
		}

		$this->input('dbName', 'DB Name', $values['dbName']); 
		$this->input('dbUser', 'DB User', $values['dbUser']);
		$this->input('dbPass', 'DB Pass', $values['dbPass'], false, 'password'); 
		$this->input('dbHost', 'DB Host', $values['dbHost']); 
		$this->input('dbPort', 'DB Port', $values['dbPort'], true); 

		$cgi = false;
		if(is_writable(__FILE__)) {
			$defaults['chmodDir'] = "755";
			$defaults['chmodFile'] = "644";
			$cgi = true;
		} else {
			$defaults['chmodDir'] = "777";
			$defaults['chmodFile'] = "666";
		}
		$values = array_merge($defaults, $values); 

		$this->h("File Permissions"); 
		$this->p(
			"When ProcessWire creates directories or files, it assigns permissions to them. " . 
			"Enter the most restrictive permissions possible that allow full read and write access to the web server (Apache) and yourself. " . 
			"The safest setting to use varies from server to server. " . 
			"If you are not on a dedicated server or private server, you may want to contact your web host to advise on what are the best permissions to use in your environment. " . 
			"Should you opt to use the defaults provided, you can also adjust these permissions later (if necessary) by editing /site/config.php (near the bottom of the file). "
			);

		$this->p("Permissions must be 3 digits each.", "detail");

		$this->input('chmodDir', 'Directories', $values['chmodDir']); 
		$this->input('chmodFile', 'Files', $values['chmodFile'], true); 

		if($cgi) $this->p("We detected that this file (install.php) is writable. That means Apache may be running as your user account. Given that, we populated the permissions above (755 &amp; 644) as possible good starting point.");

		$this->btn("Continue", 4); 

		$this->p("Note: After you click the button above, be patient &hellip; it may take a minute.");
	}

	/**
	 * Step 3: Save database configuration, then begin profile import
	 *
	 */
	protected function dbSaveConfig() {

		$fields = array('dbUser', 'dbName', 'dbPass', 'dbHost', 'dbPort'); 	
		$values = array();	

		foreach($fields as $field) {
			$value = get_magic_quotes_gpc() ? stripslashes($_POST[$field]) : $_POST[$field]; 
			$value = substr($value, 0, 128); 
			$values[$field] = $value; 
		}

		if(!$values['dbUser'] || !$values['dbName'] || !$values['dbPort']) {
			$this->err("Missing database configuration fields"); 
			return $this->dbConfig();
		}

		error_reporting(0); 
		$mysqli = new mysqli($values['dbHost'], $values['dbUser'], $values['dbPass'], $values['dbName'], $values['dbPort']); 	
		error_reporting(E_ALL); 
		if(!$mysqli || mysqli_connect_error()) {
			$this->err(mysqli_connect_error()); 
			$this->err("Database connection information did not work."); 
			$this->dbConfig($values);
			return;
		}
		$mysqli->set_charset("utf8"); 

		// file permissions
		$fields = array('chmodDir', 'chmodFile');
		foreach($fields as $field) {
			$value = (int) $_POST[$field]; 
			if(strlen("$value") !== 3) $this->err("Value for '$field' is invalid");
				else $this->$field = "0$value"; 
			$values[$field] = $value;
		}

		if($this->numErrors) {
			$this->dbConfig($values);
			return;
		}

		$this->h("Test Database and Save Configuration");
		$this->ok("Database connection successful to " . htmlspecialchars($values['dbName'])); 

		if($this->dbSaveConfigFile($values)) $this->profileImport($mysqli);
			else $this->dbConfig($values);
	}

	/**
	 * Save configuration to /site/config.php
	 *
	 */
	protected function dbSaveConfigFile(array $values) {

		$salt = md5(mt_rand() . microtime(true)); 

		$cfg = 	"\n/**" . 
			"\n * Installer: Database Configuration" . 
			"\n * " . 
			"\n */" . 
			"\n\$config->dbHost = '$values[dbHost]';" . 
			"\n\$config->dbName = '$values[dbName]';" . 
			"\n\$config->dbUser = '$values[dbUser]';" . 
			"\n\$config->dbPass = '$values[dbPass]';" . 
			"\n\$config->dbPort = '$values[dbPort]';" . 
			"\n" . 
			"\n/**" . 
			"\n * Installer: User Authentication Salt " . 
			"\n * " . 
			"\n * Must be retained if you migrate your site from one server to another" . 
			"\n * " . 
			"\n */" . 
			"\n\$config->userAuthSalt = '$salt'; " . 
			"\n" . 
			"\n/**" . 
			"\n * Installer: File Permission Configuration" . 
			"\n * " . 
			"\n */" . 
			"\n\$config->chmodDir = '0$values[chmodDir]'; // permission for directories created by ProcessWire" . 	
			"\n\$config->chmodFile = '0$values[chmodFile]'; // permission for files created by ProcessWire " . 	
			"\n\n";

		if(($fp = fopen("./site/config.php", "a")) && fwrite($fp, $cfg)) {
			fclose($fp); 
			$this->ok("Saved database configuration to ./site/config.php"); 
			return true; 
		} else {
			$this->err("Error saving database configuration to ./site/config.php. Please make sure it is writable."); 
			return false;
		}
	}

	/**
	 * Step 3b: Import profile
	 *
	 */
	protected function profileImport($mysqli) {

		$profile = "./site/install/";
		if(!is_file("{$profile}install.sql")) die("No installation profile found in {$profile}"); 

		// checks to see if the database exists using an arbitrary query (could just as easily be something else)
		$result = $mysqli->query("SHOW COLUMNS FROM pages"); 

		if(self::REPLACE_DB || !$result || $result->num_rows == 0) {

			$this->profileImportSQL($mysqli, "./wire/core/install.sql"); 
			$this->ok("Imported: ./wire/core/install.sql"); 
			$this->profileImportSQL($mysqli, $profile . "install.sql"); 
			$this->ok("Imported: {$profile}install.sql"); 

			if(!is_dir("./site/assets/files/") && is_dir($profile . "files")) {
				$this->mkdir("./site/assets/cache/"); 
				$this->mkdir("./site/assets/logs/"); 
				$this->mkdir("./site/assets/sessions/"); 
				$this->profileImportFiles($profile);
			}

		} else {
			$this->ok("A profile is already imported, skipping..."); 
		}

		$this->adminAccount();
	}


	/**
	 * Import files to profile
	 *
	 */
	protected function profileImportFiles($fromPath) {

		$dir = new DirectoryIterator($fromPath);

		foreach($dir as $file) {

			if($file->isDot()) continue; 
			if(!$file->isDir()) continue; 

			$dirname = $file->getFilename();
			$pathname = $file->getPathname();

			if(is_writable($pathname) && self::FORCE_COPY == false) {
				// if it's writable, then we know all the files are likely writable too, so we can just rename it
				$result = rename($pathname, "./site/assets/$dirname/"); 

			} else {
				// if it's not writable, then we will make a copy instead, and that copy should be writable by the server
				$result = $this->copyRecursive($pathname, "./site/assets/$dirname/"); 
			}

			if($result) $this->ok("Imported: $pathname => ./site/assets/$dirname/"); 
				else $this->err("Error Importing: $pathname => ./site/assets/$dirname/"); 
			
		}
	}

	/**
	 * Import profile SQL dump
	 *
	 */
	protected function profileImportSQL($mysqli, $sqlDumpFile) {

		$fp = fopen($sqlDumpFile, "rb"); 	
		while(!feof($fp)) {
			$line = trim(fgets($fp)); 
			if(empty($line) || substr($line, 0, 2) == '--') continue; 
			if(strpos($line, 'CREATE TABLE') === 0) {
				preg_match('/CREATE TABLE ([^(]+)/', $line, $matches); 
				//$this->ok("Creating table: $matches[1]"); 
				do { $line .= fgets($fp); } while(substr(trim($line), -1) != ';'); 
			}

			$mysqli->query($line); 	
			if($mysqli->error) $this->err($mysqli->error); 
		}
	}

	/**
	 * Present form to create admin account
	 *
	 */
	protected function adminAccount($wire = null) {

		$values = array(
			'username' => 'admin',
			'userpass' => '',
			'userpass_confirm' => '',
			'useremail' => ''
			);

		$clean = array();

		foreach($values as $key => $value) {
			if($wire && $wire->input->post->$key) $value = $wire->input->post->$key;
			$value = htmlentities($value, ENT_QUOTES, "UTF-8"); 
			$clean[$key] = $value;
		}

		$this->h("Create Admin Account");
		$this->p("The account you create here will have superuser access, so please make sure to create a strong password.");
		$this->input("username", "Username", $clean['username']); 
		$this->input("userpass", "Password", $clean['userpass'], false, "password"); 
		$this->input("userpass_confirm", "Password (again)", $clean['userpass_confirm'], true, "password"); 
		$this->input("useremail", "Email Address", $clean['useremail'], true, "email"); 
		$this->p("Please remember the password you enter above as you will not be able to retrieve it again.");
		$this->btn("Create Account", 5); 
	}

	/**
	 * Save submitted admin account form
	 *
	 */
	protected function adminAccountSave($wire) {

		if(!$wire->input->post->username || !$wire->input->post->userpass) $this->err("Missing account information"); 
		if($wire->input->post->userpass !== $wire->input->post->userpass_confirm) $this->err("Passwords do not match");
		if(strtolower($wire->input->post->username) != strtolower($wire->sanitizer->pageName($wire->input->post->username))) $this->err("Username must be only a-z 0-9");
		if(strtolower($wire->input->post->useremail) != strtolower($wire->sanitizer->email($wire->input->post->useremail))) $this->err("Email address did not validate");
		if($this->numErrors) return $this->adminAccount($wire);
	
		$superuserRole = $wire->roles->get("name=superuser");
		$user = $wire->users->get($wire->config->superUserPageID); 

		if(!$user->id) {
			$user = new User(); 
			$user->id = $wire->config->superUserPageID; 
		}

		$user->name = $wire->input->post->username; 
		$user->pass = $wire->input->post->userpass; 
		$user->email = $wire->input->post->useremail;
		$pass = htmlentities($wire->input->post->userpass); 

		if(!$user->roles->has("superuser")) $user->roles->add($superuserRole); 

		try {
			$wire->users->save($user); 

		} catch(Exception $e) {
			$this->err($e->getMessage()); 
			return $this->adminAccount($wire); 
		}

		$this->h("Admin Account Saved");
		$this->ok("User account saved: <b>{$user->name}</b>"); 
		$this->h("Complete &amp; Secure Your Installation");
		$this->ok("Now that the installer is complete, it is highly recommended that you make ./site/config.php non-writable, for security."); 

		if(@unlink("./install.php")) $this->ok("Deleted this installer (./install.php) for security."); 
			else $this->ok("Please delete this installer! The file is located in your web root at: ./install.php"); 

		$this->ok("There are additional configuration options available in this file that you may want to review: ./site/config.php"); 
		$this->ok("To save space, you may delete this directory (and everything in it): ./site/install/ - it's no longer needed"); 
		$this->ok("Note that future runtime errors are logged to: /site/assets/logs/errors.txt (not web accessible)"); 
		$this->h("Use The Site!");
		$this->p("Your admin URL is <a href='./processwire/'>/processwire/</a>. If you'd like, you may change this by editing the admin page and changing the name."); 
		$this->p("<a target='_blank' href='./'>View the Web Site</a> or <a href='./processwire/'>Login to ProcessWire admin</a>");

		// set a define that indicates installation is completed so that this script no longer runs
		file_put_contents("./site/assets/installed.php", "<?php // The existence of this file prevents the installer from running. Don't delete it unless you want to re-run the install or you have deleted ./install.php."); 

	}


	/******************************************************************************************************************
	 * OUTPUT FUNCTIONS
	 *
	 */
	
	/**
	 * Report and log an error
	 *
	 */
	protected function err($str) {
		$this->numErrors++;
		echo "\n<li class='ui-state-error'><span class='ui-icon ui-icon-alert'></span>$str</li>";
		return false;
	}

	/**
	 * Report success
	 *
	 */
	protected function ok($str) {
		echo "\n<li class='ui-state-highlight'><span class='ui-icon ui-icon-check'></span>$str</li>";
		return true; 
	}

	/**
	 * Output a button 
	 *
	 */
	protected function btn($label, $value) {
		echo "\n<p><button name='step' type='submit' class='ui-button ui-widget ui-state-default ui-corner-all' value='$value'><span class='ui-button-text'><span class='ui-icon ui-icon-carat-1-e'></span>$label</span></a></button></p>";
	}

	/**
	 * Output a headline
	 *
	 */
	protected function h($label) {
		echo "\n<h2>$label</h2>";
	}

	/**
	 * Output a paragraph 
	 *
	 */
	protected function p($text, $class = '') {
		if($class) echo "\n<p class='$class'>$text</p>";
			else echo "\n<p>$text</p>";
	}

	/**
	 * Output an <input type='text'>
	 *
	 */
	protected function input($name, $label, $value, $clear = false, $type = "text") {
		$width = 135; 
		$required = "required='required'";
		if($type == 'email') {
			$width = ($width*2); 
			$required = '';
		}
		$inputWidth = $width - 15; 
		$value = htmlentities($value, ENT_QUOTES, "UTF-8"); 
		echo "\n<p style='width: {$width}px; float: left; margin-top: 0;'><label>$label<br /><input type='$type' name='$name' value='$value' $required style='width: {$inputWidth}px;' /></label></p>";
		if($clear) echo "\n<br style='clear: both;' />";
	}


	/******************************************************************************************************************
	 * FILE FUNCTIONS
	 *
	 */

	/**
	 * Create a directory and assign permission
	 *
	 */
	protected function mkdir($path, $showNote = true) {
		if(mkdir($path)) {
			chmod($path, octdec($this->chmodDir));
			if($showNote) $this->ok("Created directory: $path"); 
			return true; 
		} else {
			if($showNote) $this->err("Error creating directory: $path"); 
			return false; 
		}
	}

	/**
	 * Copy directories recursively
	 *
	 */
	protected function copyRecursive($src, $dst) {

		if(substr($src, -1) != '/') $src .= '/';
		if(substr($dst, -1) != '/') $dst .= '/';

		$dir = opendir($src);
		$this->mkdir($dst, false);

		while(false !== ($file = readdir($dir))) {
			if($file == '.' || $file == '..') continue; 
			if(is_dir($src . $file)) {
				$this->copyRecursive($src . $file, $dst . $file);
			} else {
				copy($src . $file, $dst . $file);
				chmod($dst . $file, octdec($this->chmodFile));
			}
		}

		closedir($dir);
		return true; 
	} 


}

/****************************************************************************************************/

if(is_file("./site/assets/installed.php")) die("This installer has already run. Please delete it."); 
error_reporting(E_ALL | E_STRICT); 
$installer = new Installer();
$installer->execute();

