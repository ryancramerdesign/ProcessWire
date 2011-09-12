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
 * Copyright (C) 2010 by Ryan Cramer 
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

	const forceCopy = false; 
	const replaceDB = true; 
	const chmodDir = "0777";
	const chmodFile = "0666"; 

	protected $numErrors = 0; 

	protected function err($str) {
		$this->numErrors++;
		echo "\n<li class='ui-state-error'><span class='ui-icon ui-icon-alert'></span>$str</li>";
		return false;
	}

	protected function li($str) {
		echo "\n<li class='ui-state-highlight'><span class='ui-icon ui-icon-check'></span>$str</li>";
		return true; 
	}

	protected function btn($label, $value) {
		echo "\n<p><button name='step' type='submit' class='ui-button ui-widget ui-state-default ui-corner-all' value='$value'><span class='ui-button-text'><span class='ui-icon ui-icon-carat-1-e'></span>$label</span></a></button></p>";
	}

	protected function mkdir($path, $showNote = true) {
		if(mkdir($path)) {
			chmod($path, octdec(self::chmodDir));
			if($showNote) $this->li("Created directory: $path"); 
			return true; 
		} else {
			if($showNote) $this->err("Error creating directory: $path"); 
			return false; 
		}
	}

	protected function compatibilityCheckPHPInfo() {

		ob_start();
		phpinfo();
		$phpinfo = ob_get_contents();
		ob_end_clean();

		$info = array(); 

		preg_match_all('{<tr><td class="e">(?>(.+?)</td>)<td class="v">(?>(.+?)</td>)</tr>}', $phpinfo, $matches); 
		foreach($matches[1] as $key => $label) $info[trim(strtolower($label))] = trim(strtolower($matches[2][$key])); 

		preg_match_all('{<tr class="h"><th>(?>(.+?)</th>)<th>(?>(.+?)</th>)</tr>}', $phpinfo, $matches); 
		foreach($matches[1] as $key => $label) $info[trim(strtolower($label))] = trim(strtolower($matches[2][$key])); 

		if(isset($info['server_software'])) {
			if(strpos($info['server_software'], 'apache') === false) {
				$this->err("Apache web server server software is required. You are running \"$info[server_software]\". Continue at your own risk, ProcessWire is not currently tested with any other server software."); 
			} else {
				$this->li("Server Software: $info[server_software]"); 
			}
		} else {
			$this->err("Unable to determine server software. It may be okay to continue, but note that ProcessWire is only developed for Apache at present."); 
		}

		$tests = array(
			'mysqli support', 
			'spl support', 
			'ctype functions', 
			'gd support',
		//	'jpg support',
			'iconv support',
			'hash support', 
			'json support',
			'pcre (perl compatible regular expressions) support', 
			'session support',
			);

		foreach($tests as $test) {
			if(!isset($info[$test]) || $info[$test] != 'enabled') $this->err("Failed test for: $test"); 
				else $this->li("$test: OK"); 
		}
	}

	protected function compatibilityCheck() { 

		echo "\n<h2>1. Compatibility Check</h2>\n";

		if(is_file("./site/install/install.sql")) {
			$this->li("Found installation profile in /site"); 

		} else if(is_file("./site/")) {
			$this->li("A site already appears to be installed. You do not need to run this intaller to upgrade. Instead, please remove it (/install.php). "); 

		} else if(@rename("./site-default", "./site")) {
			$this->li("Renamed /site-default => /site"); 

		} else {
			$this->err("Before continuing, please rename '/site-default' to '/site' -- this is the default installation profile."); 
			$this->li("If you prefer, you may download an alternate installation profile at processwire.com/download, which you should unzip to /site");
			$this->btn("Continue", 1); 
			return;
		}

		$v = phpversion();
		$va = explode(".", phpversion()); 
		if($va[0] < 5 || ($va[0] == 5 && $va[1] < 2) || ($va[0] == 5 && $va[1] == 2 && $va[2] < 3)) $this->err("ProcessWire requires PHP version 5.2.3 or newer. You are running PHP v$v");
			else $this->li("PHP version v$v");

		if(function_exists('mysqli_connect')) $this->li("Found MySQLi"); 
			else $this->err("MySQLi not found and it is required"); 

		if(function_exists("imagecreatetruecolor")) $this->li("Found GD2"); 
			else $this->err("GD version 2.x (GD2) or newer required"); 

		//if(strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') $this->li("Platform appears to be Windows-based."); 
		//	else $this->li("Platform: " . PHP_OS); 

		if(function_exists('apache_get_modules')) {
			if(in_array('mod_rewrite', apache_get_modules())) $this->li("Found Apache module: mod_rewrite"); 
				else $this->err("Apache mod_rewrite does not appear to be installed and is required by ProcessWire."); 
		} else {
			// apache_get_modules doesn't work on a cgi installation.
			// check for environment var set in htaccess file, as submitted by jmarjie. 
			$mod_rewrite = getenv('HTTP_MOD_REWRITE') == 'On' ? true : false;
			if($mod_rewrite) {
				$this->li("Found Apache module (cgi): mod_rewrite");
			} else {
				$this->err("Unable to determine if Apache mod_rewrite (required by ProcessWire) is installed. On some servers, we may not be able to detect it until your .htaccess file is place. Please click the 'check again' button at the bottom of this screen, if you haven't already."); 
			}
		}

		if(is_writable("./site/assets/")) $this->li("./site/assets/ is writable"); 
			else $this->err("Error: Directory ./site/assets/ must be writable. Please adjust the permissions before continuing."); 

		if(is_writable("./site/config.php")) $this->li("./site/config.php is writable"); 
			else $this->err("Error: File ./site/config.php must be writable. Please adjust the permissions before continuing."); 

		if(!is_file("./.htaccess") || !is_readable("./.htaccess")) {
			if(@rename("./htaccess.txt", "./.htaccess")) $this->li("Installed .htaccess"); 
				else $this->err("/.htaccess doesn't exist. Before continuing, you should rename the included htaccess.txt file to be .htaccess (with the period in front of it, and no '.txt' at the end)."); 

		} else if(!strpos(file_get_contents("./.htaccess"), "PROCESSWIRE")) {
			$this->err("/.htaccess file exists, but is not for ProcessWire. Please overwrite or combine it with the provided /htaccess.txt file (i.e. rename /htaccess.txt to /.htaccess, with the period in front)."); 

		} else {
			$this->li(".htaccess looks good"); 
		}

		$this->compatibilityCheckPHPInfo();


		if($this->numErrors) {
			echo 	"\n<p>One or more errors were found above. Please correct these issues before proceeding or " . 
				"<a href='http://www.ryancramer.com/contact/'>Contact Ryan</a> if you have questions or think the error is incorrect.</p>";

			$this->btn("Check Again", 1); 
		}

		$this->btn("Continue to Next Step", 2); 
	}

	protected function dbConfig($values = array()) {

		if(!is_file("./site/install/install.sql")) die("There is no installation profile in /site/. Please place one there before continuing. You can get it at processwire.com/download"); 

		echo 	"\n<h2>3. MySQL Database Configuration</h2>" . 
			"\n<p>" . 
			"Please create a MySQL 5.x database and user account on your server. " . 
			"The user account should have full read, write and delete permissions on the database.* " . 
			"Once created, please enter the information for this database and account below:" . 
			"</p>" . 
			"\n<p class='detail'>*Recommended permissions are select, insert, update, delete, create, alter, index, drop, create temporary tables, and lock tables.</p>"; 

		if(!isset($values['dbName'])) $values['dbName'] = '';
		if(!isset($values['dbHost'])) $values['dbHost'] = ini_get("mysqli.default_host"); 
		if(!isset($values['dbPort'])) $values['dbPort'] = ini_get("mysqli.default_port"); 
		if(!isset($values['dbUser'])) $values['dbUser'] = ini_get("mysqli.default_user"); 
		if(!isset($values['dbPass'])) $values['dbPass'] = ini_get("mysqli.default_pw"); 

		if(!$values['dbHost']) $values['dbHost'] = 'localhost';
		if(!$values['dbPort']) $values['dbPort'] = 3306; 

		foreach($values as $key => $value) $values[$key] = htmlspecialchars($value, ENT_QUOTES); 

		echo "<p><label>Database Name<br /><input type='text' name='dbName' value='$values[dbName]'></label></p>";
		echo "<p><label>Database User<br /><input type='text' name='dbUser' value='$values[dbUser]'></label></p>";
		echo "<p><label>Database Password<br /><input type='text' name='dbPass' value='$values[dbPass]'></label></p>"; 
		echo "<p><label>Database Host<br /><input type='text' name='dbHost' value='$values[dbHost]'></label></p>"; 
		echo "<p><label>Database Port<br /><input type='text' name='dbPort' value='$values[dbPort]'></label></p>"; 

		$this->btn("Continue", 4); 
		echo "\n<p>Note: After you click the button above, be patient &hellip; it may take a minute. </p>";

	}

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

		echo "<h2>3. Test Database and Save Configuration</h2>\n";

		$this->li("Database connection successful to " . htmlspecialchars($values['dbName'])); 

		$salt = md5(mt_rand() . microtime(true)); 

		$cfg = 	"\n\$config->dbHost = '$values[dbHost]';" . 
			"\n\$config->dbName = '$values[dbName]';" . 
			"\n\$config->dbUser = '$values[dbUser]';" . 
			"\n\$config->dbPass = '$values[dbPass]';" . 
			"\n\$config->dbPort = '$values[dbPort]';" . 
			"\n\$config->userAuthSalt = '$salt';" . 
			"\n\n";

		if(($fp = fopen("./site/config.php", "a")) && fwrite($fp, $cfg)) {
			fclose($fp); 
			$this->li("Saved database configuration to ./site/config.php"); 
			$this->profileImport($mysqli);
		} else {
			$this->err("Error saving database configuration to ./site/config.php. Please make sure it is writable."); 
		}

	}

	protected function profileImport($mysqli) {

		$profile = "./site/install/";
		if(!is_file("{$profile}install.sql")) die("No installation profile found in {$profile}"); 

		// checks to see if the database exists using an arbitrary query (could just as easily be something else)
		$result = $mysqli->query("SHOW COLUMNS FROM pages"); 

		if(self::replaceDB || !$result || $result->num_rows == 0) {

			$this->profileImportSQL($mysqli, $profile . "install.sql"); 
			$this->li("Imported: {$profile}install.sql"); 

			if(!is_dir("./site/assets/files/") && is_dir($profile . "files")) {
				$this->mkdir("./site/assets/cache/"); 
				$this->mkdir("./site/assets/logs/"); 
				$this->mkdir("./site/assets/sessions/"); 
				$this->profileImportFiles($profile);
			}

		} else {
			$this->li("A profile is already imported, skipping..."); 
		}

		$this->adminAccount();
	}

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
				chmod($dst . $file, octdec(self::chmodFile));
			}
		}

		closedir($dir);
		return true; 
	} 

	protected function profileImportFiles($fromPath) {

		$dir = new DirectoryIterator($fromPath);

		foreach($dir as $file) {

			if($file->isDot()) continue; 
			if(!$file->isDir()) continue; 

			$dirname = $file->getFilename();
			$pathname = $file->getPathname();

			if(is_writable($pathname) && self::forceCopy == false) {
				// if it's writable, then we know all the files are likely writable too, so we can just rename it
				$result = rename($pathname, "./site/assets/$dirname/"); 
			} else {
				// if it's not writable, then we will make a copy instead, and that copy should be writable by the server
				$result = $this->copyRecursive($pathname, "./site/assets/$dirname/"); 
			}

			if($result) $this->li("Imported: $pathname => ./site/assets/$dirname/"); 
				else $this->error("Error Importing: $pathname => ./site/assets/$dirname/"); 
			
		}
	}


	protected function profileImportSQL($mysqli, $sqlDumpFile) {

		$fp = fopen($sqlDumpFile, "rb"); 	
		while(!feof($fp)) {
			$line = trim(fgets($fp, 8192)); 
			if(empty($line) || substr($line, 0, 2) == '--') continue; 
			if(strpos($line, 'CREATE TABLE') === 0) {
				preg_match('/CREATE TABLE ([^(]+)/', $line, $matches); 
				//$this->li("Creating table: $matches[1]"); 
				do { $line .= fgets($fp, 1024); } while(substr(trim($line), -1) != ';'); 
			}

			$mysqli->query($line); 	
			if($mysqli->error) $this->err($mysqli->error); 

			// echo "<p>\$mysqli->query('" . htmlspecialchars($line, ENT_QUOTES) . "');</p>"; 
		}
	}

	protected function adminAccount() {

		echo 	"<h2>4. Create Admin Account</h2>" . 
			"<p>The account you create here will have superuser access, so please make sure to create a strong password.</p>" . 
			"<p><label>Username<br /><input type='text' name='username' value='admin' /></label></p>" . 
			"<p><label>Password<br /><input type='text' name='userpass' value='' /></label></p>" . 
			"<p><label>E-Mail Address<br /><input type='text' name='useremail' value='' /></label></p>";

		$this->btn("Create Account", 5); 
	}

	protected function adminAccountSave($wire) {

		if(!$wire->input->post->username || !$wire->input->post->userpass) {
			$this->err("Missing account information"); 
			return $this->adminAccount();
		}

		$superuser = $wire->roles->get("name=superuser");

		$user = $wire->users->get($wire->config->superUserPageID); 
		if(!$user->id) {
			$user = new User(); 
			$user->id = $wire->config->superUserPageID; 
		}
		$user->name = $wire->input->post->username; 
		$user->pass = $wire->input->post->userpass; 
		$user->email = $wire->input->post->useremail;
		$pass = htmlentities($wire->input->post->userpass); 

		if($user->name != $wire->input->post->username) {
			$this->err("Your username contained characters that aren't accepted at this time. Please try another."); 
			return $this->adminAccount();
		} 

		if(!$user->roles->has("superuser")) $user->roles->add($superuser); 

		try {
			$wire->users->save($user); 

		} catch(Exception $e) {
			$this->err("Error: " . $e->getMessage()); 
			return $this->adminAccount(); 
		}

		echo "<h2>5. Admin Account Saved</h2>";

		$this->li("User account saved. Please make note of this login information, as you will not be able to retrieve it again:"); 
		$this->li("Username: <strong>{$user->name}</strong>"); 
		$this->li("Password: <strong>{$pass}</strong>"); 

		echo "\n<h2>6. Complete &amp; Secure Your Installation</h2>";

		$this->li("Now that the installer is complete, it is highly recommended that you make ./site/config.php non-writable! This is important for security."); 

		if(@unlink("./install.php")) {
			$this->li("Deleted this installer (./install.php) for security."); 
		} else {
			$this->li("Please delete this installer! The file is located in your web root at: ./install.php"); 
		}
		if(is_file("./upgrade.php")) { 
			if(@unlink("./upgrade.php")) {
				$this->li("Deleted this upgrade installer (./upgrade.php) for security."); 
			} else {
				$this->li("Please delete the upgrade installer! The file is located in your web root at: ./upgrade.php"); 
			}
		}
		$this->li("There are additional configuration options available in this file that you may want to review: ./site/config.php"); 

		$this->li("To save space, you may delete this directory (and everything in it): ./site/install/ - it's no longer needed"); 

		$this->li("Note that future runtime errors are logged to: /site/assets/logs/errors.txt (not web accessible)"); 

		echo "\n<h2>7. Use The Site!</h2>"; 

		echo "<p><a target='_blank' href='./'>View the Web Site</a> or <a href='./processwire/'>Login to ProcessWire</a></p>";


		// set a define that indicates installation is completed so that this script no longer runs
		file_put_contents("./site/assets/installed.php", "<?php // The existence of this file prevents the installer from running. Don't delete it unless you want to re-run the install or you have deleted ./install.php."); 

	}

	protected function welcome() {
		echo "<h2>Welcome. This tool will guide you through the installation process.</h2>";
		$this->btn("Get Started", 1); 
	}

	public function execute() {

		$title = "ProcessWire 2.1 Installation";
		require("./wire/templates-admin/install-head.inc"); 

		if(isset($_POST['step'])) switch($_POST['step']) {

			case 1: 
				$this->compatibilityCheck(); 
				break;
			case 2: 
				$this->dbConfig(); 
				break;
			case 4: 
				$this->dbSaveConfig(); 
				break;
			case 5: 
				require("./index.php"); 
				$this->adminAccountSave($wire); 
				break;
			default: 
				$this->welcome();

		} else $this->welcome();

		require("./wire/templates-admin/install-foot.inc"); 
	}
}

if(is_file("./site/assets/installed.php")) die("This installer has already run. Please delete it."); 
error_reporting(E_ALL | E_STRICT); 
$installer = new Installer();
$installer->execute();

