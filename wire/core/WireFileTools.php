<?php namespace ProcessWire;

/**
 * ProcessWire File Tools ($files API variable)
 *
 * ProcessWire 3.x (development), Copyright 2015 by Ryan Cramer
 * https://processwire.com
 *
 *
 */

class WireFileTools extends Wire {
	
	/**
	 * Create a directory that is writable to ProcessWire and uses the $config chmod settings
	 *
	 * @param string $path
	 * @param bool $recursive If set to true, all directories will be created as needed to reach the end.
	 * @param string $chmod Optional mode to set directory to (default: $config->chmodDir), format must be a string i.e. "0755"
	 * 	If omitted, then ProcessWire's $config->chmodDir setting is used instead.
	 * @return bool
	 *
	 */
	public function mkdir($path, $recursive = false, $chmod = null) {
		if(!strlen($path)) return false;
		if(!is_dir($path)) {
			if($recursive) {
				$parentPath = substr($path, 0, strrpos(rtrim($path, '/'), '/'));
				if(!is_dir($parentPath) && !$this->mkdir($parentPath, true, $chmod)) return false;
			}
			if(!@mkdir($path)) return false;
		}
		$this->chmod($path, false, $chmod);
		return true;
	}

	/**
	 * Remove a directory
	 *
	 * @param string $path
	 * @param bool $recursive If set to true, all files and directories in $path will be recursively removed as well.
	 * @return bool
	 *
	 */
	public function rmdir($path, $recursive = false) {
		if(!is_dir($path)) return false;
		if(!strlen(trim($path, '/.'))) return false; // just for safety, don't proceed with empty string
		if($recursive === true) {
			$files = scandir($path);
			if(is_array($files)) foreach($files as $file) {
				if($file == '.' || $file == '..') continue;
				$pathname = "$path/$file";
				if(is_dir($pathname)) {
					$this->rmdir($pathname, true);
				} else {
					@unlink($pathname);
				}
			}
		}
		return @rmdir($path);
	}


	/**
	 * Change the mode of a file or directory, consistent with PW's chmodFile/chmodDir settings
	 *
	 * @param string $path May be a directory or a filename
	 * @param bool $recursive If set to true, all files and directories in $path will be recursively set as well.
	 * @param string If you want to set the mode to something other than PW's chmodFile/chmodDir settings,
	 * you may override it by specifying it here. Ignored otherwise. Format should be a string, like "0755".
	 * @return bool Returns true if all changes were successful, or false if at least one chmod failed.
	 * @throws WireException when it receives incorrect chmod format
	 *
	 */
	public function chmod($path, $recursive = false, $chmod = null) {

		if(is_null($chmod)) {
			// default: pull values from PW config
			$chmodFile = $this->wire('config')->chmodFile;
			$chmodDir = $this->wire('config')->chmodDir;
		} else {
			// optional, manually specified string
			if(!is_string($chmod)) throw new WireException("chmod must be specified as a string like '0755'");
			$chmodFile = $chmod;
			$chmodDir = $chmod;
		}

		$numFails = 0;

		if(is_dir($path)) {
			// $path is a directory
			if($chmodDir) if(!@chmod($path, octdec($chmodDir))) $numFails++;

			// change mode of files in directory, if recursive
			if($recursive) foreach(new \DirectoryIterator($path) as $file) {
				if($file->isDot()) continue;
				$mod = $file->isDir() ? $chmodDir : $chmodFile;
				if($mod) if(!@chmod($file->getPathname(), octdec($mod))) $numFails++;
				if($file->isDir()) {
					if(!$this->chmod($file->getPathname(), true, $chmod)) $numFails++;
				}
			}
		} else {
			// $path is a file
			$mod = $chmodFile;
			if($mod) if(!@chmod($path, octdec($mod))) $numFails++;
		}

		return $numFails == 0;
	}

	/**
	 * Copy all files in directory $src to directory $dst
	 *
	 * The default behavior is to also copy directories recursively.
	 *
	 * @param string $src Path to copy files from
	 * @param string $dst Path to copy files to. Directory is created if it doesn't already exist.
	 * @param bool|array Array of options:
	 * 	- recursive (boolean): Whether to copy directories within recursively. (default=true)
	 * 	- allowEmptyDirs (boolean): Copy directories even if they are empty? (default=true)
	 * 	- If a boolean is specified for $options, it is assumed to be the 'recursive' option.
	 * @return bool True on success, false on failure.
	 *
	 */
	public function copy($src, $dst, $options = array()) {

		$defaults = array(
			'recursive' => true,
			'allowEmptyDirs' => true,
		);

		if(is_bool($options)) $options = array('recursive' => $options);
		$options = array_merge($defaults, $options);

		if(substr($src, -1) != '/') $src .= '/';
		if(substr($dst, -1) != '/') $dst .= '/';

		$dir = opendir($src);
		if(!$dir) return false;

		if(!$options['allowEmptyDirs']) {
			$isEmpty = true;
			while(false !== ($file = readdir($dir))) {
				if($file == '.' || $file == '..') continue;
				$isEmpty = false;
				break;
			}
			if($isEmpty) return true;
		}

		if(!$this->mkdir($dst)) return false;

		while(false !== ($file = readdir($dir))) {
			if($file == '.' || $file == '..') continue;
			$isDir = is_dir($src . $file);
			if($options['recursive'] && $isDir) {
				$this->copy($src . $file, $dst . $file, $options);
			} else if($isDir) {
				// skip it, because not recursive
			} else {
				copy($src . $file, $dst . $file);
				$chmodFile = $this->wire('config')->chmodFile;
				if($chmodFile) @chmod($dst . $file, octdec($chmodFile));
			}
		}

		closedir($dir);
		
		return true;
	}

	/**
	 * Return a new temporary directory/path ready to use for files
	 *
	 * @param object|string $name Provide the object that needs the temp dir, or name your own string
	 * @param array|int $options Options array:
	 * 	- maxAge: Maximum age of temp dir files in seconds (default=120)
	 * 	- basePath: Base path where temp dirs should be created. Omit to use default (recommended).
	 * 	Note: if you specify an integer for $options, then $maxAge is assumed.
	 * @return WireTempDir
	 *
	 */
	public function tempDir($name, $options = array()) {
		static $tempDirs = array();
		if(isset($tempDirs[$name])) return $tempDirs[$name];
		if(is_int($options)) $options = array('maxAge' => $options);
		$basePath = isset($options['basePath']) ? $options['basePath'] : '';
		$tempDir = new WireTempDir($name, $basePath);
		if(isset($options['maxAge'])) $tempDir->setMaxAge($options['maxAge']);
		$tempDirs[$name] = $tempDir;
		return $tempDir;
	}

	/**
	 * Unzips the given ZIP file to the destination directory
	 *
	 * @param string $file ZIP file to extract
	 * @param string $dst Directory where files should be unzipped into. Directory is created if it doesn't exist.
	 * @return array Returns an array of filenames (excluding $dst) that were unzipped.
	 * @throws WireException All error conditions result in WireException being thrown.
	 *
	 */
	public function unzip($file, $dst) {

		$dst = rtrim($dst, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

		if(!class_exists('\ZipArchive')) throw new WireException("PHP's ZipArchive class does not exist");
		if(!is_file($file)) throw new WireException("ZIP file does not exist");
		if(!is_dir($dst)) $this->mkdir($dst, true);

		$names = array();
		$chmodFile = $this->wire('config')->chmodFile;
		$chmodDir = $this->wire('config')->chmodDir;

		$zip = new \ZipArchive();
		$res = $zip->open($file);
		if($res !== true) throw new WireException("Unable to open ZIP file, error code: $res");

		for($i = 0; $i < $zip->numFiles; $i++) {
			$name = $zip->getNameIndex($i);
			if($zip->extractTo($dst, $name)) {
				$names[$i] = $name;
				$filename = $dst . ltrim($name, '/');
				if(is_dir($filename)) {
					if($chmodDir) chmod($filename, octdec($chmodDir));
				} else if(is_file($filename)) {
					if($chmodFile) chmod($filename, octdec($chmodFile));
				}
			}
		}

		$zip->close();

		return $names;
	}

	/**
	 * Creates a ZIP file
	 *
	 * @param string $zipfile Full path and filename to create or update (i.e. /path/to/myfile.zip)
	 * @param array|string $files Array of files to add (full path and filename), or directory (string) to add.
	 * 	If given a directory, it will recursively add everything in that directory.
	 * @param array $options Associative array of:
	 * 	- allowHidden (boolean or array): allow hidden files? May be boolean, or array of hidden files (basenames) you allow. (default=false)
	 * 		Note that if you actually specify a hidden file in your $files argument, then that overrides this.
	 * 	- allowEmptyDirs (boolean): allow empty directories in the ZIP file? (default=true)
	 * 	- overwrite (boolean): Replaces ZIP file if already present (rather than adding to it) (default=false)
	 * 	- exclude (array): Files or directories to exclude
	 * 	- dir (string): Directory name to prepend to added files in the ZIP
	 * @return Returns associative array of:
	 * 	- files => array(all files that were added),
	 * 	- errors => array(files that failed to add, if any)
	 * @throws WireException Original ZIP file creation error conditions result in WireException being thrown.
	 *
	 */
	public function zip($zipfile, $files, array $options = array()) {

		$defaults = array(
			'allowHidden' => false,
			'allowEmptyDirs' => true,
			'overwrite' => false,
			'exclude' => array(), // files or dirs to exclude
			'dir' => '',
			'zip' => null, // internal use: holds ZipArchive instance for recursive use
		);

		$return = array(
			'files' => array(),
			'errors' => array(),
		);

		if(!empty($options['zip']) && !empty($options['dir']) && $options['zip'] instanceof \ZipArchive) {
			// internal recursive call
			$recursive = true;
			$zip = $options['zip']; // ZipArchive instance

		} else if(is_string($zipfile)) {
			if(!class_exists('\ZipArchive')) throw new WireException("PHP's ZipArchive class does not exist");
			$options = array_merge($defaults, $options);
			$zippath = dirname($zipfile);
			if(!is_dir($zippath)) throw new WireException("Path for ZIP file ($zippath) does not exist");
			if(!is_writable($zippath)) throw new WireException("Path for ZIP file ($zippath) is not writable");
			if(empty($files)) throw new WireException("Nothing to add to ZIP file $zipfile");
			if(is_file($zipfile) && $options['overwrite'] && !unlink($zipfile)) throw new WireException("Unable to overwrite $zipfile");
			if(!is_array($files)) $files = array($files);
			if(!is_array($options['exclude'])) $options['exclude'] = array($options['exclude']);
			$recursive = false;
			$zip = new \ZipArchive();
			if($zip->open($zipfile, \ZipArchive::CREATE) !== true) throw new WireException("Unable to create ZIP: $zipfile");

		} else {
			throw new WireException("Invalid zipfile argument");
		}

		$dir = strlen($options['dir']) ? rtrim($options['dir'], '/') . '/' : '';

		foreach($files as $file) {
			$basename = basename($file);
			$name = $dir . $basename;
			if($basename[0] == '.' && $recursive) {
				if(!$options['allowHidden']) continue;
				if(is_array($options['allowHidden']) && !in_array($basename, $options['allowHidden'])) continue;
			}
			if(count($options['exclude']) && (in_array($name, $options['exclude']) || in_array("$name/", $options['exclude']))) continue;
			if(is_dir($file)) {
				$_files = array();
				foreach(new \DirectoryIterator($file) as $f) if(!$f->isDot()) $_files[] = $f->getPathname();
				if(count($_files)) {
					$zip->addEmptyDir($name);
					$options['dir'] = "$name/";
					$options['zip'] = $zip;
					$_return = $this->zip($zipfile, $_files, $options);
					foreach($_return['files'] as $s) $return['files'][] = $s;
					foreach($_return['errors'] as $s) $return['errors'][] = $s;
				} else if($options['allowEmptyDirs']) {
					$zip->addEmptyDir($name);
				}
			} else if(file_exists($file)) {
				if($zip->addFile($file, $name)) $return['files'][] = $name;
				else $return['errors'][] = $name;
			}
		}

		if(!$recursive) $zip->close();

		return $return;
	}

	/**
	 * Send the contents of the given filename to the current http connection
	 *
	 * This function utilizes the $content->fileContentTypes to match file extension
	 * to content type headers and force-download state.
	 *
	 * This function throws a WireException if the file can't be sent for some reason.
	 *
	 * @param string $filename Filename to send
	 * @param array $options Options that you may pass in, see $_options in function for details.
	 * @param array $headers Headers that are sent, see $_headers in function for details.
	 *	To remove a header completely, make its value NULL and it won't be sent.
	 * @throws WireException
	 *
	 */
	public function send($filename, array $options = array(), array $headers = array()) {
		$http = new WireHttp();
		$http->sendFile($filename, $options, $headers);
	}


	/**
	 * Given a filename, render it as a ProcessWire template file
	 *
	 * This is a shortcut to using the TemplateFile class.
	 *
	 * File is assumed relative to /site/templates/ (or a directory within there) unless you specify a full path.
	 * If you specify a full path, it will accept files in or below site/templates/, site/modules/, wire/modules/.
	 *
	 * Note this function returns the output for you to output wherever you want (delayed output).
	 * For direct output, use the wireInclude() function instead.
	 *
	 * @param string $filename Assumed relative to /site/templates/ unless you provide a full path name with the filename.
	 * 	If you provide a path, it must resolve somewhere in site/templates/, site/modules/ or wire/modules/.
	 * @param array $vars Optional associative array of variables to send to template file.
	 * 	Please note that all template files automatically receive all API variables already (you don't have to provide them)
	 * @param array $options Associative array of options to modify behavior:
	 * 	- defaultPath: Path where files are assumed to be when only filename or relative filename is specified (default=/site/templates/)
	 *  - autoExtension: Extension to assume when no ext in filename, make blank for no auto assumption (default=php)
	 * 	- allowedPaths: Array of paths that are allowed (default is templates, core modules and site modules)
	 * 	- allowDotDot: Allow use of ".." in paths? (default=false)
	 * 	- throwExceptions: Throw exceptions when fatal error occurs? (default=true)
	 * @return string|bool Rendered template file or boolean false on fatal error (and throwExceptions disabled)
	 * @throws WireException if template file doesn't exist
	 *
	 */
	public function render($filename, array $vars = array(), array $options = array()) {

		$paths = $this->wire('config')->paths;
		$defaults = array(
			'defaultPath' => $paths->templates,
			'autoExtension' => 'php',
			'allowedPaths' => array(
				$paths->templates,
				$paths->adminTemplates,
				$paths->modules,
				$paths->siteModules,
				$paths->cache
			),
			'allowDotDot' => false,
			'throwExceptions' => true,
		);

		$options = array_merge($defaults, $options);
		if(DIRECTORY_SEPARATOR != '/') $filename = str_replace(DIRECTORY_SEPARATOR, '/', $filename);

		// add .php extension if filename doesn't already have an extension
		if($options['autoExtension'] && !strrpos(basename($filename), '.')) {
			$filename .= "." . $options['autoExtension'];
		}

		if(!$options['allowDotDot'] && strpos($filename, '..')) {
			// make path relative to /site/templates/ if filename is not an absolute path
			$error = 'Filename may not have ".."';
			if($options['throwExceptions']) throw new WireException($error);
			$this->error($error);
			return false;
		}

		if($options['defaultPath'] && strpos($filename, './') === 0) {
			$filename = rtrim($options['defaultPath'], '/') . '/' . substr($filename, 2);

		} else if($options['defaultPath'] && strpos($filename, '/') !== 0 && strpos($filename, ':') !== 1) {
			// filename is relative to defaultPath (typically /site/templates/)
			$filename = rtrim($options['defaultPath'], '/') . '/' . $filename;

		} else if(strpos($filename, '/') !== false) {
			// filename is absolute, make sure it's in a location we consider safe
			$allowed = false;
			foreach($options['allowedPaths'] as $path) {
				if(strpos($filename, $path) === 0) $allowed = true;
			}
			if(!$allowed) {
				$error = "Filename $filename is not in an allowed path." ;
				$error .= ' Paths: ' . implode("\n", $options['allowedPaths']) . '';
				if($options['throwExceptions']) throw new WireException($error);
				$this->error($error);
				return false;
			}
		}

		// render file and return output
		$t = new TemplateFile();
		$t->setThrowExceptions($options['throwExceptions']);
		$t->setFilename($filename);

		foreach($vars as $key => $value) {
			$t->set($key, $value);
		}
		
		return $t->render();
	}


	/**
	 * Include a PHP file passing it all API variables and optionally your own specified variables
	 *
	 * This is the same as PHP's include() function except for the following:
	 * - It receives all API variables and optionally your custom variables
	 * - If your filename is not absolute, it doesn't look in PHP's include path, only in the current dir.
	 * - It only allows including files that are part of the PW installation: templates, core modules or site modules
	 * - It will assume a ".php" extension if filename has no extension.
	 *
	 * Note this function produced direct output. To retrieve output as a return value, use the
	 * wireTemplateFile function instead.
	 *
	 * @param $filename
	 * @param array $vars Optional variables you want to hand to the include (associative array)
	 * @param array $options Array of options to modify behavior:
	 * 	- func: Function to use: include, include_once, require or require_once (default=include)
	 *  - autoExtension: Extension to assume when no ext in filename, make blank for no auto assumption (default=php)
	 * 	- allowedPaths: Array of paths include files are allowed from. Note current dir is always allowed.
	 * @return bool Returns true
	 * @throws WireException if file doesn't exist or is not allowed
	 *
	 */
	function ___include($filename, array $vars = array(), array $options = array()) {

		$paths = $this->wire('config')->paths;
		$defaults = array(
			'func' => 'include',
			'autoExtension' => 'php',
			'allowedPaths' => array(
				$paths->templates,
				$paths->adminTemplates,
				$paths->modules,
				$paths->siteModules,
				$paths->cache
			)
		);

		$options = array_merge($defaults, $options);
		$filename = trim($filename);
		if(DIRECTORY_SEPARATOR != '/') $filename = str_replace(DIRECTORY_SEPARATOR, '/', $filename);

		// add .php extension if filename doesn't already have an extension
		if($options['autoExtension'] && !strrpos(basename($filename), '.')) {
			$filename .= "." . $options['autoExtension'];
		}

		if(strpos($filename, '..') !== false) {
			// if backtrack/relative components, convert to real path
			$_filename = $filename;
			$filename = realpath($filename);
			if($filename === false) throw new WireException("File does not exist: $_filename");
		}

		if(strpos($filename, '//') !== false) {
			throw new WireException("File is not allowed (double-slash): $filename");
		}

		if(strpos($filename, './') !== 0) {
			// file does not specify "current directory"
			$slashPos = strpos($filename, '/');
			// If no absolute path specified, ensure it only looks in current directory
			if($slashPos !== 0 && strpos($filename, ':/') === false) $filename = "./$filename";
		}

		if(strpos($filename, '/') === 0 || strpos($filename, ':/') !== false) {
			// absolute path, make sure it's part of PW's installation
			$allowed = false;
			foreach($options['allowedPaths'] as $path) {
				if(strpos($filename, $path) === 0) $allowed = true;
			}
			if(!$allowed) throw new WireException("File is not in an allowed path: $filename");
		}

		if(!file_exists($filename)) throw new WireException("File does not exist: $filename");

		// extract all API vars
		$fuel = array_merge($this->wire('fuel')->getArray(), $vars);
		extract($fuel);

		// include the file
		$func = $options['func'];
		if($func == 'require') require($filename);
			else if($func == 'require_once') require_once($filename);
			else if($func == 'include_once') include_once($filename);
			else include($filename);

		return true;
	}
	
	public function compile($file, array $options = array()) {
		$compiler = new FileCompiler(dirname($file), $options);
		return $compiler->compile(basename($file));
	}

	public function compileInclude($file, array $options = array()) {
		$file = $this->compile($file, $options);	
		include($file);	
	}
	
	public function compileIncludeOnce($file, array $options = array()) {
		$file = $this->compile($file, $options);
		include_once($file);
	}
	
	public function compileRequire($file, array $options = array()) {
		$file = $this->compile($file, $options);
		require($file);	
	}
	
	public function compileRequireOnce($file, array $options = array()) {
		$file = $this->compile($file, $options);
		require_once($file);
	}

}