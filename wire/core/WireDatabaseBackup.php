<?php

/**
 * ProcessWire Database Backup and Restore
 * 
 * This class intentionally does not have any external dependencies (other than PDO)
 * so that it can be included by outside tools for restoring/exporting, with the main
 * example of that being the ProcessWire installer. 
 *
 * ProcessWire 2.x
 * Copyright (C) 2014 by Ryan Cramer
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 *
 * http://processwire.com
 * 
 * USAGE
 * 
 * Initialization
 * ==============
 * $backup = new WireDataseBackup('/path/to/backups/');
 * $backup->setDatabase($this->database); // optional, if ommitted it will attempt it's own connection
 * $backup->setDatabaseConfig($this->config); // optional, only if setDatabase() was called
 * 
 * Backup
 * ======
 * $file = $backup->backup([$options]);
 * if($file) print_r($backup->notes()); 
 *   else print_r($backup->errors()); 
 * 
 * 
 * Restore
 * =======
 * $success = $backup->restore($file, [$options]); 
 * if($success) print_r($backup->notes()); 
 *   else print_r($backup->errors()); 
 *
 */

class WireDatabaseBackup {
	
	const fileHeader = '--- WireDatabaseBackup';
	const fileFooter = '--- /WireDatabaseBackup';

	/**
	 * Options available for the $options argument to backup() method
	 * 
	 * @var array
	 * 
	 */
	protected $backupOptions = array(
		
		'filename' => '', // default is to make a dated filename, but this can also be used (basename only, no path)
		'description' => '', // optional description of this backup
		'tables' => array(), // if specified, export will only include these tables
		'user' => '', // username to associate with the backup file, optional
		'excludeTables' => array(), // exclude creating or inserting into these tables
		'excludeCreateTables' => array(), // exclude creating these tables, but still export data (not supported by mysqldump)
		'excludeExportTables' => array(), // exclude exporting data, but still create tables (not supported by mysqldump)
		'whereSQL' => array(), // SQL conditions for export of individual tables (table => SQL conditions)
		'maxSeconds' => 1200, // max number of seconds allowed for execution 
		'allowDrop' => true, // use DROP TABLES statements before CREATE TABLE statements?
		'findReplace' => array(
			// find and replace in row data during backup (not supported by exec/mysql method)
			// Example: 'databass' => 'database'
			), 
		'findReplaceCreateTable' => array( 
			// find and replace in create table statements (not supported by exec/mysqldump)
			// Example: 'DEFAULT CHARSET=latin1;' => 'DEFAULT CHARSET=utf8;', 
			), 
		'exec' => false, // if true, we will try to use mysqldump (exec) first. if false, we won't attempt mysqldump.
		'execCommand' => '[dbPath]mysqldump 
			--complete-insert=TRUE 
			--add-locks=FALSE 
			--disable-keys=FALSE 
			--extended-insert=FALSE 
			--default-character-set=utf8 
			--comments=FALSE 
			--compact 
			--skip-disable-keys 
			--skip-add-locks 
			--add-drop-table=TRUE 
			--result-file=[dbFile]
			--port=[dbPort] 
			-u[dbUser] 
			-p[dbPass] 
			-h[dbHost] 
			[dbName]
			[tables]' 
		);

	/**
	 * Options available for the $options argument to restore() method
	 * 
	 * @var array
	 * 
	 */
	protected $restoreOptions = array(
		'tables' => array(), // table names to restore (empty=all)
		'allowDrop' => true, // allow DROP TABLE statements?
		'exec' => false, // if true, we will try to use mysql via exec first (faster). if false, we won't attempt that.
		'execCommand' => '[dbPath]mysql --port=[dbPort] -u[dbUser] -p[dbPass] -h[dbHost] [dbName] < [dbFile]',
		'haltOnError' => false, 
		'maxSeconds' => 1200, // max number of seconds allowed for execution
		'findReplace' => array( 
			// find and replace in row data (not supported by exec/mysql method)
			// Example: 'databass' => 'database'
			),
		'findReplaceCreateTable' => array( 
			// find and replace in create table statements (not supported by exec/mysql)
			// Example: 'DEFAULT CHARSET=latin1;' => 'DEFAULT CHARSET=utf8;', 
			), 
		);
	
	/**
	 * @var null|PDO
	 * 
	 */
	protected $database = null;

	/**
	 * @var array
	 * 
	 */
	protected $databaseConfig = array(
		'dbUser' => '',
		'dbPass' => '', // optional (if password is blank)
		'dbHost' => '', 
		'dbPort' => '', 
		'dbName' => '', 
		'dbPath' => '', // optional mysql/mysqldump path on file system
		'dbSocket' => '', 
		'dbCharset' => 'utf8',
		);

	/**
	 * Array of text indicating details about what methods were used (primarily for debugging)
	 * 
	 * @var array
	 * 
	 */
	protected $notes = array();
	
	/**
	 * Array of text error messages
	 *
	 * @var array
	 *
	 */
	protected $errors = array();

	/**
	 * Database files path
	 * 
	 * @var string|null
	 * 
	 */
	protected $path = null;

	/**
	 * Construct
	 * 
	 * You should follow-up the construct call with one or both of the following:
	 * 
	 * 	- $backups->setDatabase(PDO|WireDatabasePDO);
	 * 	- $backups->setDatabaseConfig(array|object); 
	 * 
	 * @param string $path Path where database files are stored
	 * @throws Exception
	 * 
	 */
	public function __construct($path = '') {
		if(strlen($path)) $this->setPath($path);
	}

	/**
	 * Set the database configuration information
	 * 
	 * @param array|object $config Containing these properties: dbUser, dbHost, dbPort, dbName,
	 * 	and optionally: dbPass, dbPath, dbCharset
	 * @return this
	 * @throws Exception if missing required config settings
	 * 
	 */
	public function setDatabaseConfig($config) {
		
		foreach($this->databaseConfig as $key => $_value) {
			$value = is_object($config) ? $config->$key : $config[$key];
			if(empty($value) && !empty($_value)) $value = $_value; // i.e. dbCharset
			if($key == 'dbPath' && $value) {
				$value = rtrim($value, '/') . '/';
				if(!is_dir($value)) $value = '';
			}
			$this->databaseConfig[$key] = $value;
		}

		$missing = array();
		$optional = array('dbPass', 'dbPath', 'dbSocket', 'dbPort'); 
		foreach($this->databaseConfig as $key => $value) {
			if(empty($value) && !in_array($key, $optional)) $missing[] = $key;
		}

		if(count($missing)) {
			throw new Exception("Missing required config for: " . implode(', ', $missing));
		}
		
		// $charset = $this->databaseConfig['dbCharset'];
		// $this->backupOptions['findReplaceCreateTable']['DEFAULT CHARSET=latin1;'] = "DEFAULT CHARSET=$charset;";

		return $this;
	}

	/**
	 * Set the database connection
	 * 
	 * @param PDO|WireDatabasePDO $database
	 * @throws PDOException on invalid connection
	 * 
	 */
	public function setDatabase($database) {
		$query = $database->prepare('SELECT DATABASE()'); 
		$query->execute();
		list($dbName) = $query->fetch(PDO::FETCH_NUM); 
		if($dbName) $this->databaseConfig['dbName'] = $dbName; 
		$this->database = $database;
	}

	/**
	 * Get current database connection, initiating the connection if not yet active
	 * 
	 * @return null|PDO|WireDatabasePDO
	 * @throws Exception
	 * 
	 */
	public function getDatabase() {
		
		if($this->database) return $this->database; 
		
		$config = $this->databaseConfig; 
		if(empty($config['dbUser'])) throw new Exception("Please call setDatabaseConfig(config) to supply config information so we can connect."); 
		
		if($config['dbSocket']) {
			$dsn = "mysql:unix_socket=$config[dbSocket];dbname=$config[dbName];";
		} else {
			$dsn = "mysql:dbname=$config[dbName];host=$config[dbHost]";
			if($config['dbPort']) $dsn .= ";port=$config[dbPort]";
		}
		
		$options = array(
			PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES '$config[dbCharset]'",
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
			);
		
		$database = new PDO($dsn, $config['dbUser'], $config['dbPass'], $options);
		$this->setDatabase($database); 
	}

	/**
	 * Add an error and return last error
	 * 
	 * @param string $str If omitted, no error is added
	 * @return string
	 * 
	 */
	public function error($str = '') {
		if(strlen($str)) $this->errors[] = $str; // append error message
		return count($this->errors) ? end($this->errors) : ''; // return last error
	}

	/**
	 * Return all error messages that occurred
	 * 
	 * @param bool $reset Specify true to clear out existing errors or omit just to return error messages
	 * @return array
	 * 
	 */
	public function errors($reset = false) {
		$errors = $this->errors; 	
		if($reset) $this->errors = array();
		return $errors;
	}
	
	/**
	 * Record a note
	 *
	 * @param $key
	 * @param $value
	 *
	 */
	protected function note($key, $value) {
		if(!empty($this->notes[$key])) $this->notes[$key] .= ", $value";
			else $this->notes[$key] = $value;
	}

	/**
	 * Get all notes
	 *
	 * @param bool $reset
	 * @return array
	 *
	 */
	public function notes($reset = false) {
		$notes = $this->notes;
		if($reset) $this->notes = array();
		return $notes;
	}

	/**
	 * Set path where database files are stored
	 * 
	 * @param string $path
	 * @return this
	 * @throws Exception if path has a problem
	 * 
	 */
	public function setPath($path) {
		if(!is_dir($path)) throw new Exception("Path doesn't exist: $path");
		if(!is_writable($path)) throw new Exception("Path isn't writable: $path");
		if(DIRECTORY_SEPARATOR != '/') $path = str_replace(DIRECTORY_SEPARATOR, '/', $path); 
		$path = rtrim($path, '/') . '/';
		$this->path = $path;
		return $this;
	}
	
	public function getPath() {
		return $this->path; 
	}
	
	/**
	 * Return array of all backup files
	 *
	 * To get additional info on any of them, call getFileInfo($basename) method
	 *
	 * @return array of strings (basenames)
	 *
	 */
	public function getFiles() {
		$dir = new DirectoryIterator($this->path);
		$files = array();
		foreach($dir as $file) {
			if($file->isDot() || $file->isDir()) continue;
			$key = $file->getMTime();
			while(isset($files[$key])) $key++;
			$files[$key] = $file->getBasename();
		}
		krsort($files); // sort by date, newest to oldest
		return array_values($files); 
	}
	
	/**
	 * Get information about a backup file
	 *
	 * @param $filename
	 * @return array Returns associative array of information on success, empty array on failure
	 *
	 */
	public function getFileInfo($filename) {
		
		// all possible info (null values become integers when populated)
		$info = array(
			'description' => '',
			'valid' => false,
			'time' => '', // ISO-8601
			'mtime' => null, // timestamp
			'user' => '',
			'size' => null,
			'basename' => '',
			'pathname' => '',
			'dbName' => '',
			'tables' => array(),
			'excludeTables' => array(),
			'excludeCreateTables' => array(),
			'excludeExportTables' => array(),
			'numTables' => null, 
			'numCreateTables' => null, 
			'numInserts' => null, 
			'numSeconds' => null,
			);
		
		$filename = $this->path . basename($filename);
		if(!file_exists($filename)) return array();

		$fp = fopen($filename, "r+");
		$line = fgets($fp);
		if(strpos($line, self::fileHeader) === 0) {
			$pos = strpos($line, '{');
			if($pos !== false) {
				$json = substr($line, $pos);
				$info2 = json_decode($json, true);
				if(!$info2) $info2 = array();
				foreach($info2 as $key => $value) $info[$key] = $value;
			}
		}

		$bytes = strlen(self::fileFooter) + 255; // some extra bytes in case something gets added at the end
		fseek($fp, $bytes * -1, SEEK_END); 
		$foot = fread($fp, $bytes); 
		$info['valid'] = strpos($foot, self::fileFooter) !== false; 
		fclose($fp);
	
		// footer summary
		$pos = strpos($foot, '{'); 
		if($info['valid'] && $pos !== false) {
			$json = substr($foot, $pos); 
			$summary = json_decode($json, true); 
			if(is_array($summary)) $info = array_merge($info, $summary); 
		}
		
		$info['size'] = filesize($filename); 
		$info['mtime'] = filemtime($filename); 
		$info['pathname'] = $filename; 
		$info['basename'] = basename($filename); 
		
		return $info;
	}

	/**
	 * Get array of all table names
	 *
	 * @param bool $count If true, returns array will be indexed by name and include count of records as value
	 * @param bool $cache Allow use of cache?
	 * @return array
	 *
	 */
	public function getAllTables($count = false, $cache = true) {

		static $tables = array();
		static $counts = array();

		if($cache) {
			if($count && count($counts)) return $counts;
			if(count($tables)) return $tables;
		} else {
			$tables = array();
			$counts = array();
		}

		$query = $this->database->prepare('SHOW TABLES');
		$query->execute();
		while($row = $query->fetch(PDO::FETCH_NUM)) $tables[$row[0]] = $row[0];
		$query->closeCursor();

		if($count) foreach($tables as $table) {
			$query = $this->database->prepare("SELECT COUNT(*) FROM `$table`");
			$query->execute();
			$row = query_fetch(PDO::FETCH_NUM);
			$counts[$table] = (int) $row[0];
			$query->closeCursor();
			return $counts;
			
		} else {
			return $tables;
		}
	}

	////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Perform a database export/dump
	 * 
	 * @param array $options See $backupOptions
	 * @return string Full path and filename of database export file, or false on failure. 
	 * @throws Exception on fatal error
	 * 
	 */
	public function backup(array $options = array()) {

		if(!$this->path) throw new Exception("Please call setPath('/backup/files/path/') first"); 
		$this->errors(true); 
		$options = array_merge($this->backupOptions, $options); 
	
		if(empty($options['filename'])) {
			// generate unique filename
			$tail = ((count($options['tables']) || count($options['excludeTables']) || count($options['excludeExportTables'])) ? '-part' : '');
			$n = 0;
			do {
				$options['filename'] = $this->databaseConfig['dbName'] . '_' . date('Y-m-d') . $tail . ($n ? "-$n" : "") . ".sql";
				$n++;
			} while(file_exists($this->path . $options['filename'])); 
		} else {
			$options['filename'] = basename($options['filename']); 
		}
	
		set_time_limit($options['maxSeconds']); 
		$file = false;
		
		if($this->supportsExec($options)) {
			$file = $this->backupExec($this->path . $options['filename'], $options);
			$this->note('method', 'exec_mysqldump'); 
		}
		
		if(!$file) {
			$file = $this->backupPDO($this->path . $options['filename'], $options);
			$this->note('method', 'pdo'); 
		}
		
		$success = false;
		if($file && file_exists($file)) {
			if(!filesize($file)) {
				unlink($file);
			} else {
				$success = true; 
			}
		}
	
		return $success ? $file : false;
	}
	
	public function setBackupOptions(array $options) {
		$this->backupOptions = array_merge($this->backupOptions, $options); 
		return $this;
	}

	/**
	 * Start a new backup file, adding our info header to the top
	 * 
	 * @param string $file
	 * @param array $options
	 * @return bool
	 * 
	 */
	protected function backupStartFile($file, array $options) {

		$fp = fopen($file, 'w+'); 
		
		if(!$fp) {
			$this->error("Unable to write header to file: $file"); 
			return false;
		}
		
		$info = array(
			'time' => date('Y-m-d H:i:s'), 
			'user' => $options['user'],
			'dbName' => $this->databaseConfig['dbName'],
			'description' => $options['description'], 
			'tables' => $options['tables'], 
			'excludeTables' => $options['excludeTables'],
			'excludeCreateTables' => $options['excludeCreateTables'], 
			'excludeExportTables' => $options['excludeExportTables'],
			);
		
		$json = json_encode($info); 
		$json = str_replace(array("\r", "\n"), " ", $json);
		
		fwrite($fp, self::fileHeader . " $json\n"); 
		fclose($fp); 
		wireChmod($fp); 
		return true; 
	}

	/**
	 * End a new backup file, adding our footer to the bottom
	 *
	 * @param string|resources $file
	 * @param array $summary
	 * @return bool
	 *
	 */
	protected function backupEndFile($file, array $summary = array()) {

		$fp = is_resource($file) ? $file : fopen($file, 'a+'); 
		
		if(!$fp) {
			$this->error("Unable to write footer to file: $file");
			return false;
		}
		
		$footer = self::fileFooter;
		if(count($summary)) {
			$json = json_encode($summary); 
			$json = str_replace(array("\r", "\n"), " ", $json); 
			$footer .= " $json";
		}

		fwrite($fp, "\n$footer"); 
		fclose($fp); 
		return true; 
	}

	/**
	 * Create a mysql dump file using PDO
	 *
	 * @param string $file Path + filename to create
	 * @param array $options
	 * @return string|bool Returns the created file on success or false on error
	 *
	 */
	protected function backupPDO($file, array $options = array()) {

		$database = $this->getDatabase();
		$options = array_merge($this->backupOptions, $options);
		if(!$this->backupStartFile($file, $options)) return false;
		$startTime = time();
		$fp = fopen($file, "a+"); 
		$tables = $this->getAllTables();
		$numCreateTables = 0;
		$numTables = 0;
		$numInserts = 0;

		foreach($tables as $table) {

			if(in_array($table, $options['excludeTables'])) continue;
			if(count($options['tables']) && !in_array($table, $options['tables'])) continue;

			if(in_array($table, $options['excludeCreateTables'])) {
				$excludeCreate = true;
			} else {
				$excludeCreate = false;
				if($options['allowDrop']) fwrite($fp, "\nDROP TABLE IF EXISTS `$table`;");
				$query = $database->prepare("SHOW CREATE TABLE `$table`");
				$query->execute();
				$row = $query->fetch(PDO::FETCH_NUM);
				$createTable = $row[1]; 
				foreach($options['findReplaceCreateTable'] as $find => $replace) {
					$createTable = str_replace($find, $replace, $createTable); 
				}
				$numCreateTables++;
				fwrite($fp, "\n$createTable;\n");
			}
			
			if(in_array($table, $options['excludeExportTables'])) continue; 
			$numTables++;
			$columns = array();
			$query = $database->prepare("SHOW COLUMNS FROM `$table`");
			$query->execute();
			while($row = $query->fetch(PDO::FETCH_NUM)) $columns[] = $row[0];
			$query->closeCursor();
			$columnsStr = '`' . implode('`, `', $columns) . '`';

			$sql = "SELECT $columnsStr FROM `$table` ";
			if(isset($options['whereSQL'][$table])) $sql .= "WHERE (" . $options['whereSQL'][$table] . ") ";
			$query = $database->prepare($sql);
			$query->execute();
			
			$hasReplace = count($options['findReplace']); 

			while($row = $query->fetch(PDO::FETCH_NUM)) {
				$numInserts++;
				$out = "\nINSERT INTO `$table` ($columnsStr) VALUES(";
				foreach($row as $value) {
					if(is_null($value)) {
						$value = 'NULL';
					} else {
						if($hasReplace) foreach($options['findReplace'] as $find => $replace) {
							if(strpos($value, $find)) $value = str_replace($find, $replace, $value);
						}
						$value = $database->quote($value);
					}
					$out .= "$value, ";
				}
				$out = rtrim($out, ", ") . ") ";
				if($excludeCreate) {
					$out .= "ON DUPLICATE KEY UPDATE ";
					foreach($columns as $c) $out .= "`$c`=VALUES(`$c`), ";
				}

				$out = rtrim($out, ", ") . ";";
				fwrite($fp, $out);
			}

			$query->closeCursor();
			fwrite($fp, "\n");
		}

		$summary = array(
			'numTables' => $numTables, 
			'numCreateTables' => $numCreateTables, 
			'numInserts' => $numInserts,
			'numSeconds' => time() - $startTime, 
			);
		$this->backupEndFile($fp, $summary); // this does the fclose
		
		return file_exists($file) ? $file : false;
	}

	/**
	 * Create a mysql dump file using exec(mysqldump)
	 *
	 * @param string $file Path + filename to create
	 * @param array $options
	 * @return string|bool Returns the created file on success or false on error
	 * 
	 * @todo add backupStartFile/backupEndFile support
	 *
	 */
	protected function backupExec($file, array $options) {

		$cmd = $options['execCommand'];
		$cmd = str_replace(array("\n", "\t"), ' ', $cmd); 
		$cmd = str_replace('[tables]', implode(' ', $options['tables']), $cmd); 
		
		foreach($options['excludeTables'] as $table) {
			$cmd .= " --ignore-table=$table";
		}
		
		if(strpos($cmd, '[dbFile]')) {
			$cmd = str_replace('[dbFile]', $file, $cmd); 
		} else {
			$cmd .= " > $file";
		}
		
		foreach($this->databaseConfig as $key => $value) {
			$cmd = str_replace("[$key]", $value, $cmd); 
		}
		
		exec($cmd); 
		
		if(file_exists($file)) {
			if(filesize($file) > 0) return $file; 
			unlink($file); 
		}
		
		return false;
	}
	
	////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	
	/**
	 * Import a database SQL file
	 *
	 * @param $filename Filename to restore, optionally including path (if no path, then path set to construct is assumed)
	 * @param array $options See WireDatabaseBackup::$restoreOptions
	 * @return true on success, false on failure. Call the errors() method to retrieve errors.
	 * @throws Exception on fatal error
	 *
	 */
	public function restore($filename, array $options = array()) {

		if(DIRECTORY_SEPARATOR == '\\') $filename = str_replace('\\', '/', $filename); 
		if(strpos($filename, '/') === false) {
			if(!$this->path) throw new Exception("Please supply full path to file, or call setPath('/backup/files/path/') first"); 
			$filename = $this->path . $filename;
		}
		if(!file_exists($filename)) throw new Exception("Restore file does not exist: $filename");
		$options = array_merge($this->restoreOptions, $options);
		set_time_limit($options['maxSeconds']);
		$success = false;
		
		$this->errors(true);
		$this->notes(true); 

		if($this->supportsExec($options)) {
			$this->note('method', 'exec_mysql');
			$success = $this->restoreExec($filename, $options);
			if(!$success) $this->error("Exec mysql failed, attempting PDO...");
		}

		if(!$success) {
			$this->note('method', 'pdo');
			$success = $this->restorePDO($filename, $options);
		}

		return $success;
	}
	
	public function setRestoreOptions(array $options) {
		$this->restoreOptions = array_merge($this->restoreOptions, $options);
		return $this;
	}

	/**
	 * Import a database SQL file using PDO
	 *
	 * @param $filename Filename to restore (must be SQL file exported by this class)
	 * @param array $options See $restoreOptions
	 * @return true on success, false on failure. Call the errors() method to retrieve errors.
	 *
	 */
	protected function restorePDO($filename, array $options = array()) {

		$database = $this->getDatabase();
		$fp = fopen($filename, "rb");
		$numInserts = 0;
		$numTables = 0; 
		$numQueries = 0;
	
		$tables = array(); // selective tables to restore, optional
		foreach($options['tables'] as $table) $tables[$table] = $table; 
		if(!count($tables)) $tables = null;

		while(!feof($fp)) {

			$line = trim(fgets($fp));
			if(!$this->restoreUseLine($line)) continue;
			
			if(preg_match('/^(INSERT|CREATE|DROP)\s+(?:INTO|TABLE IF EXISTS|TABLE IF NOT EXISTS|TABLE)\s+`?([^\s`]+)/i', $line, $matches)) {
				$command = strtoupper($matches[1]); 
				$table = $matches[2];
				wire()->message("restorePDO table='$table'", Notice::debug); 
			} else {
				$command = '';
				$table = '';
			}
			
			if($command === 'CREATE') { 
				if(!$options['allowDrop'] && stripos($line, 'CREATE TABLE IF NOT EXISTS') === false) {
					$line = str_ireplace('CREATE TABLE', 'CREATE TABLE IF NOT EXISTS', $line);
				}
			} else if($command === 'DROP') {
				if(!$options['allowDrop']) continue; 
				
			} else if($command === 'INSERT' && $tables) {
				if(!isset($tables[$table])) continue; // skip tables not selected for import
			}
			
			while(substr($line, -1) != ';' && !feof($fp)) {
				// get the rest of the lines in the query (if multi-line)
				$_line = trim(fgets($fp));
				if($this->restoreUseLine($_line)) $line .= $_line;
			}
		
			$replacements = $command === 'CREATE' ? $options['findReplaceCreateTable'] : $options['findReplace'];
			if(count($replacements)) foreach($replacements as $find => $replace) {
				if(strpos($line, $find) === false) continue;
				$line = str_replace($find, $replace, $line);
			}

			try {
				$database->exec($line);
				if($command === 'INSERT') $numInserts++;
				if($command === 'CREATE') $numTables++;
				$numQueries++;
				
			} catch(Exception $e) {
				$this->error($e->getMessage());
				if($options['haltOnError']) break;
			}
		}

		fclose($fp);
		
		$this->note('queries', $numQueries); 
		$this->note('inserts', $numInserts);
		$this->note('tables', $numTables); 

		if(count($this->errors) > 0) {
			$this->error(count($this->errors) . " queries generated errors ($numQueries queries and $numInserts inserts for $numTables were successful)");
			return false;
		} else {
			return $numQueries;
		}
	}

	/**
	 * Import a database SQL file using exec(mysql)
	 *
	 * @param $filename Filename to restore (must be SQL file exported by this class)
	 * @param array $options See $restoreOptions
	 * @return true on success, false on failure. Call the errors() method to retrieve errors.
	 *
	 */
	protected function restoreExec($filename, array $options = array()) {

		$cmd = $options['execCommand'];
		$cmd = str_replace(array("\n", "\t"), ' ', $cmd);
		$cmd = str_replace('[dbFile]', $filename, $cmd);

		foreach($this->databaseConfig as $key => $value) {
			$cmd = str_replace("[$key]", $value, $cmd);
		}

		$o = array();
		$r = 0;
		exec($cmd, $o, $r);

		if($r > 0) {
			// 0=success, 1=warning, 2=not found
			$this->error("mysql reported error code $r");
			foreach($o as $e) $this->error($e);
			return false;
		}

		return true;
	}

	/**
	 * Returns true or false if a line should be used for restore
	 *
	 * @param $line
	 * @return bool
	 *
	 */
	protected function restoreUseLine($line) {
		if(empty($line) || substr($line, 0, 2) == '--') return false;
		return true;
	}

	////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	

	/**
	 * Determine if exec is available for the given command
	 * 
	 * @param array $options
	 * @return bool
	 * @throws Exception on unknown exec type
	 * 
	 */
	protected function supportsExec(array $options = array()) {
		
		if(!$options['exec']) return false;
		if(empty($this->databaseConfig['dbUser'])) return false; // no db config options provided
		
		if(preg_match('{^(?:\[dbPath\])?([_a-zA-Z0-9]+)\s}', $options['execCommand'], $matches)) {
			$type = $matches[1]; 
		} else {
			throw new Exception("Unable to determine command for exec"); 
		}

		if($type == 'mysqldump') {
			// these options are not supported by mysqldump
			if(	count($options['excludeCreateTables']) || 
				count($options['excludeExportTables']) ||
				count($options['findReplace']) ||
				count($options['findReplaceCreateTable'])) {
				return false;
			}
			
		} else if($type == 'mysql') {
			
		} else {
			throw new Exception("Unrecognized exec command: $type"); 
		}
		
		// first check if exec is available (http://stackoverflow.com/questions/3938120/check-if-exec-is-disabled)
		if(ini_get('safe_mode')) return false; 
		$d = ini_get('disable_functions');
		$s = ini_get('suhosin.executor.func.blacklist');
		if("$d$s") {
			$a = preg_split('/,\s*/', "$d,$s");
			if(in_array('exec', $a)) return false;
		}
	
		// now check if mysqldump is available
		$o = array();
		$r = 0; 
		$path = $this->databaseConfig['dbPath'];
		exec("{$path}$type --version", $o, $r); 
		if(!$r && count($o) && stripos($o[0], $type) !== false && stripos($o[0], 'Ver') !== false) {
			// i.e. mysqldump  Ver 10.13 Distrib 5.5.34, for osx10.6 (i386)
			return true; 
		}
		
		return false;
	}
	
}
