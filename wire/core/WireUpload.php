<?php

/**
 * ProcessWire WireUpload
 *
 * Saves uploads of single or multiple files, saving them to the destination path.
 * If the destination path does not exist, it will be created. 
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2013 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://processwire.com
 *
 */

class WireUpload extends Wire {
	

	protected $name;
	protected $destinationPath; 
	protected $maxFiles;
	protected $maxFileSize = 0;
	protected $completedFilenames = array(); 
	protected $overwrite; 
	protected $overwriteFilename = ''; // if specified, only this filename may be overwritten
	protected $lowercase = true; 
	protected $targetFilename = '';
	protected $extractArchives = false; 
	protected $validExtensions = array(); 
	protected $badExtensions = array('php', 'php3', 'phtml', 'exe', 'cfm', 'shtml', 'asp', 'pl', 'cgi', 'sh'); 
	protected $errors = array();
	protected $allowAjax = false;

	static protected $unzipCommand = 'unzip -j -qq -n /src/ -x __MACOSX .* -d /dst/';

	protected $errorInfo = array(); 

	public function __construct($name) {

		$this->errorInfo = array(
			UPLOAD_ERR_OK => $this->_('Successful Upload'),
			UPLOAD_ERR_INI_SIZE => $this->_('The uploaded file exceeds the upload_max_filesize directive in php.ini.'),
			UPLOAD_ERR_FORM_SIZE => $this->_('The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.'),
			UPLOAD_ERR_PARTIAL => $this->_('The uploaded file was only partially uploaded.'),
			UPLOAD_ERR_NO_FILE => $this->_('No file was uploaded.'),
			UPLOAD_ERR_NO_TMP_DIR => $this->_('Missing a temporary folder.'),
			UPLOAD_ERR_CANT_WRITE => $this->_('Failed to write file to disk.'),
			UPLOAD_ERR_EXTENSION => $this->_('File upload stopped by extension.')
			);

		$this->setName($name); 
		$this->maxFiles = 0; // no limit
		$this->overwrite = false; 
		$this->destinationPath = '';

		if($this->config->uploadBadExtensions) {
			$badExtensions = $this->config->uploadBadExtensions; 
			if(is_string($badExtensions) && $badExtensions) $badExtensions = explode(' ', $badExtensions); 
			if(is_array($badExtensions)) $this->badExtensions = $badExtensions; 			
		}	

		if($this->config->uploadUnzipCommand) {
			self::setUnzipCommand($this->config->uploadUnzipCommand); 
		}
	
	}

	public function execute() {

		// returns array of files (multi file upload)

		if(!$this->name) throw new WireException("You must set the name for WireUpload before executing it"); 
		if(!$this->destinationPath) throw new WireException("You must set the destination path for WireUpload before executing it");

		$files = array();

		$f = $this->getPhpFiles();
		if(!$f) return $files;

		if(is_array($f['name'])) {
			// multi file upload
			$cnt = 0;
			foreach($f['name'] as $key => $name) {
				if($this->maxFiles && ($cnt >= $this->maxFiles)) {
					$this->error($this->_('Max file upload limit reached')); 
					break;
				}
				if(!$this->isValidUpload($f['name'][$key], $f['size'][$key], $f['error'][$key])) continue; 
				if(!$this->saveUpload($f['tmp_name'][$key], $f['name'][$key])) continue; 
				$cnt++;
			}

			$files = $this->completedFilenames; 

		} else {
			// single file upload, including ajax
			if($this->isValidUpload($f['name'], $f['size'], $f['error'])) {
				$this->saveUpload($f['tmp_name'], $f['name'], !empty($f['ajax']));  // returns filename or false
				$files = $this->completedFilenames; 
			}
		}

		return $files; 
	}

	/**
	 * Returns PHP's $_FILES or one constructed from an ajax upload
	 *
	 */
	protected function getPhpFiles() {
		if(isset($_SERVER['HTTP_X_FILENAME']) && $this->allowAjax) return $this->getPhpFilesAjax();
		if(empty($_FILES) || !count($_FILES)) return false; 
		if(!isset($_FILES[$this->name]) || !is_array($_FILES[$this->name])) return false;
		return $_FILES[$this->name]; 	
	}

	/**
	 * Handles an ajax file upload and constructs a resulting $_FILES 
	 *
	 */
	protected function getPhpFilesAjax() {

		if(!$filename = $_SERVER['HTTP_X_FILENAME']) return false; 

		$dir = wire('config')->uploadTmpDir;
		if(!$dir || !is_writable($dir)) $dir = ini_get('upload_tmp_dir');
		if(!$dir || !is_writable($dir)) $dir = sys_get_temp_dir();
		if(!$dir || !is_writable($dir)) throw new WireException("Error writing to $dir. Please define \$config->uploadTmpDir and ensure it is writable."); 
		$tmpName = tempnam($dir, get_class($this));
		file_put_contents($tmpName, file_get_contents('php://input')); 
		$filesize = is_file($tmpName) ? filesize($tmpName) : 0;
		$error = $filesize ? UPLOAD_ERR_OK : UPLOAD_ERR_NO_FILE;

		$file = array(
			'name' => $filename, 
			'tmp_name' => $tmpName,
			'size' => $filesize,
			'error' => $error,
			'ajax' => true,
			);

		return $file;

	}

	protected function isValidExtension($name) {
		$pathInfo = pathinfo($name); 
		$extension = strtolower($pathInfo['extension']);

		if(in_array($extension, $this->badExtensions)) return false;
		if(in_array($extension, $this->validExtensions)) return true; 
		return false; 
	}

	protected function isValidUpload($name, $size, $error) { 
		$valid = false;

		if($error && $error != UPLOAD_ERR_NO_FILE) $this->error($this->errorInfo[$error]); 
			else if(!$size) $valid = false; // no data
			else if(!$this->isValidExtension($name)) {
				$fname = $this->validateFilename($name); 
				$this->error("$fname - " . $this->_('Invalid file extension, please use one of:') . ' ' . implode(', ', $this->validExtensions)); 
			} else if($this->maxFileSize > 0 && $size > $this->maxFileSize) {
				$fname = $this->validateFilename($name); 
				$this->error("$fname - " . $this->_('Exceeds max allowed file size')); 
			} else if($name[0] == '.') $valid = false; 
			else $valid = true; 

		return $valid; 
	}


	protected function checkDestinationPath() {
		if(!is_dir($this->destinationPath)) {
			$this->error("Destination path does not exist {$this->destinationPath}"); 
		}
		return true; 
	}

	protected function getUniqueFilename($destination) {

		$cnt = 0; 
		$p = pathinfo($destination); 
		$basename = basename($p['basename'], ".$p[extension]"); 

		while(file_exists($destination)) {
			$cnt++; 
			$filename = "$basename-$cnt.$p[extension]"; 
			$destination = "$p[dirname]/$filename"; 
		}
	
		return $destination; 	
	}

        public function validateFilename($value, $extensions = array()) {

                $value = basename($value);
		if($this->lowercase) $value = strtolower($value); 
                $value = preg_replace('/[^-a-zA-Z0-9_\.]/', '_', $value);
                $value = preg_replace('/__+/', '_', $value);
                $value = trim($value, "_");

		$p = pathinfo($value);
		$extension = strtolower($p['extension']);
		$basename = basename($p['basename'], ".$extension"); 
		// replace any dots in the basename with underscores
		$basename = trim(str_replace(".", "_", $basename), "_"); 
		$value = "$basename.$extension";

                if(count($extensions)) {
                        if(!in_array($extension, $extensions)) $value = false;
                }

                return $value;
        }


	protected function saveUpload($tmp_name, $filename, $ajax = false) {

		if(!$this->checkDestinationPath()) return false; 
		$filename = $this->getTargetFilename($filename); 
		$filename = $this->validateFilename($filename);
		if($this->lowercase) $filename = strtolower($filename); 
		$destination = $this->destinationPath . $filename;
		$p = pathinfo($destination); 

		if(!$this->overwrite && $filename != $this->overwriteFilename) {
			// overwrite not allowed, so find a new name for it
			$destination = $this->getUniqueFilename($destination); 
			$filename = basename($destination); 
		}

		if($ajax) $success = @rename($tmp_name, $destination);
			else $success = move_uploaded_file($tmp_name, $destination);

		if(!$success) {
			$this->error("Unable to move uploaded file to: $destination");
			if(is_file($tmp_name)) @unlink($tmp_name); 
			return false;
		}

		if($this->config->chmodFile) chmod($destination, octdec($this->config->chmodFile));

		if($p['extension'] == 'zip' && ($this->maxFiles == 0) && $this->extractArchives) {

			if($this->saveUploadZip($destination)) {
				if(count($this->completedFilenames) == 1) return $this->completedFilenames[0];
			}

			return $this->completedFilenames; 

		} else {
			$this->completedFilenames[] = $filename; 
			return $filename; 
		}


	}

	protected function saveUploadZip($zipFile) {

		// unzip with command line utility

		$files = array(); 
		if(!self::$unzipCommand) return false; 

		$dir = dirname($zipFile) . '/';
		$tmpDir = $dir . '.zip_tmp/'; 

		if(!mkdir($tmpDir)) return $files; 

		$unzipCommand = self::$unzipCommand;	
		$unzipCommand = str_replace('/src/', escapeshellarg($zipFile), $unzipCommand); 
		$unzipCommand = str_replace('/dst/', $tmpDir, $unzipCommand); 
		$str = exec($unzipCommand); 
		
		$files = new DirectoryIterator($tmpDir); 	
		$cnt = 0; 

		foreach($files as $file) {

			if($file->isDot() || $file->isDir()) continue; 

			if(!$this->isValidUpload($file->getFilename(), $file->getSize(), UPLOAD_ERR_OK)) {
				unlink($file->getPathname()); 
				continue; 
			}

			//$destination = $dir . $file->getFilename(); 
			$basename = $file->getFilename(); 
			$basename = $this->validateFilename($basename, $this->validExtensions); 

			if($basename) $destination = $this->getUniqueFilename($dir . $basename); 
				else $destination = '';

			if($destination && rename($file->getPathname(), $destination)) {
				$this->completedFilenames[] = basename($destination); 
				$cnt++; 
			} else {
				unlink($file->getPathname()); 
			}
		}

		rmdir($tmpDir); 
		unlink($zipFile); 

		if(!$cnt) return false; 
		return true; 	
	}

	public function getCompletedFilenames() {
		return $this->completedFilenames; 
	}

	public function setTargetFilename($filename) {
		// target filename as destination
		// only useful for single uploads
		$this->targetFilename = $filename; 
	}

	protected function getTargetFilename($filename) {
		// given a filename, takes it's extension and combines it with that
		// if the targetFilename (if set). Otherwise returns the filename you gave it
		if(!$this->targetFilename) return $filename; 
		$pathInfo = pathinfo($filename); 
		$targetPathInfo = pathinfo($this->targetFilename); 
		return rtrim(basename($this->targetFilename, $targetPathInfo['extension']), ".") . "." . $pathInfo['extension'];
	}

	public function setOverwriteFilename($filename) {
		// only this filename may be overwritten if specified, i.e. myphoto.jpg
		// only useful for single uploads
		$this->overwrite = false; // required
		if($this->lowercase) $filename = strtolower($filename); 
		$this->overwriteFilename = $filename; 
		return $this; 
	}

	static public function setUnzipCommand($unzipCommand) {
		if(strpos($unzipCommand, '/src/') && strpos($unzipCommand, '/dst/')) 
			self::$unzipCommand = $unzipCommand; 
	}
	
	static public function getUnzipCommand() {
		return self::$unzipCommand; 
	}

	public function setValidExtensions(array $extensions) {
		foreach($extensions as $ext) $this->validExtensions[] = strtolower($ext); 
		return $this; 
	}

	public function setMaxFiles($maxFiles) {
		$this->maxFiles = (int) $maxFiles; 
		return $this; 
	}

	public function setMaxFileSize($bytes) {
		$this->maxFileSize = (int) $bytes;
		return $this;
	}

	public function setOverwrite($overwrite) {
		$this->overwrite = $overwrite ? true : false; 
		return $this; 
	}

	public function setDestinationPath($destinationPath) {
		$this->destinationPath = $destinationPath; 
		return $this; 
	}

	public function setExtractArchives($extract = true) {
		$this->extractArchives = $extract; 
		$this->validExtensions[] = 'zip';
		return $this; 
	}

	public function setName($name) {
		$this->name = $this->fuel('sanitizer')->fieldName($name); 
		return $this; 
	}

	public function setLowercase($lowercase = true) {
		$this->lowercase = $lowercase ? true : false; 
		return $this; 
	}

	public function setAllowAjax($allowAjax = true) {
		$this->allowAjax = $allowAjax ? true : false; 
		return $this; 
	}

	public function error($text, $flags = 0) {
		$this->errors[] = $text; 
		parent::error($text, $flags); 
	}

	public function getErrors($clear = false) {
		$errors = $this->errors; 
		if($clear) $this->errors = array();
		return $errors;
	}


}


