<?php namespace ProcessWire;

/**
 * ProcessWire Database Backup and Restore
 * 
 * This class intentionally does not have any external dependencies (other than PDO)
 * so that it can be included by outside tools for restoring/exporting, with the main
 * example of that being the ProcessWire installer. 
 *
 * ProcessWire 3.x, Copyright 2016 by Ryan Cramer
 * https://processwire.com
 * 
 * USAGE
 * 
 * Initialization
 * ==============
 * $backup = new WireDatabaseBackup('/path/to/backups/');
 * $backup->setDatabase($this->database); // optional, if omitted it will attempt it's own connection
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
	 * ProcessWire instance, when applicable
	 * 
	 * @var ProcessWire
	 * 
	 */
	protected $wire = null;

	/**
	 * Options available for the $options argument to backup() method
	 * 
	 * @var array
	 * 
	 */
	protected $backupOptions = array(

		// filename for backup: default is to make a dated filename, but this can also be used (basename only, no path)
		'filename' => '',
		
		// optional description of this backup
		'description' => '',
		
		// if specified, export will only include these tables
		'tables' => array(),
		
		// username to associate with the backup file (string), optional
		'user' => '',
		
		// exclude creating or inserting into these tables
		'excludeTables' => array(),
		
		// exclude creating these tables, but still export data (not supported by mysqldump)
		'excludeCreateTables' => array(),
		
		// exclude exporting data, but still create tables (not supported by mysqldump)
		'excludeExportTables' => array(),
		
		// SQL conditions for export of individual tables (table => array(SQL conditions))
		// The 'table' portion (index) may also be a full PCRE regexp, must start with '/' to be recognized as regex
		'whereSQL' => array(),
		
		// max number of seconds allowed for execution 
		'maxSeconds' => 1200,
		
		// use DROP TABLES statements before CREATE TABLE statements?
		'allowDrop' => true,
		
		// use UPDATE ON DUPLICATE KEY so that INSERT statements can UPDATE when rows already present (all tables)
		'allowUpdate' => false,
		
		// table names that will use UPDATE ON DUPLICATE KEY (does NOT require allowUpdate=true)
		'allowUpdateTables' => array(),
		
		// find and replace in row data during backup (not supported by exec/mysql method)
		'findReplace' => array(
			// Example: 'databass' => 'database'
			),
		
		// find and replace in create table statements (not supported by exec/mysqldump)
		'findReplaceCreateTable' => array( 
			// Example: 'DEFAULT CHARSET=latin1;' => 'DEFAULT CHARSET=utf8;', 
			),
	
		// additional SQL queries to append at the bottom
		'extraSQL' => array(
			// Example: UPDATE pages SET CREATED=NOW	
			),
		
		// EXEC MODE IS CURRRENTLY EXPERIMENTAL AND NOT RECOMMEND FOR USE YET
		// if true, we will try to use mysqldump (exec) first. if false, we won't attempt mysqldump.
		'exec' => false, 
		
		// exec command to use for mysqldump (when in use)
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
		
		// table names to restore (empty=all)
		'tables' => array(),
		
		// allow DROP TABLE statements?
		'allowDrop' => true, 
		
		// halt execution when an error occurs?
		'haltOnError' => false,
		
		// max number of seconds allowed for execution
		'maxSeconds' => 1200,
		
		// find and replace in row data (not supported by exec/mysql method)
		'findReplace' => array( 
			// Example: 'databass' => 'database'
			),
		
		// find and replace in create table statements (not supported by exec/mysql)
		'findReplaceCreateTable' => array( 
			// Example: 'DEFAULT CHARSET=latin1;' => 'DEFAULT CHARSET=utf8;', 
			),

		// EXEC MODE IS CURRRENTLY EXPERIMENTAL AND NOT RECOMMEND FOR USE YET
		// if true, we will try to use mysql via exec first (faster). if false, we won't attempt that.
		'exec' => false, 
		
		// command to use for mysql exec
		'execCommand' => '[dbPath]mysql 
			--port=[dbPort] 
			-u[dbUser] 
			-p[dbPass] 
			-h[dbHost] 
			[dbName] < [dbFile]',
		);
	
	/**
	 * @var null|\PDO
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
	 * @throws \Exception
	 * 
	 */
	public function __construct($path = '') {
		if(strlen($path)) $this->setPath($path);
	}

	/**
	 * Set the current ProcessWire instance
	 * 
	 * @param ProcessWire $wire
	 * 
	 */
	public function setWire($wire) {
		if(is_object($wire) && $wire->className() == 'ProcessWire') $this->wire = $wire;
	}

	/**
	 * Set the database configuration information
	 * 
	 * @param array|object $config Containing these properties: dbUser, dbHost, dbPort, dbName,
	 * 	and optionally: dbPass, dbPath, dbCharset
	 * @return $this
	 * @throws \Exception if missing required config settings
	 * 
	 */
	public function setDatabaseConfig($config) {
		
		foreach($this->databaseConfig as $key => $_value) {
			if(is_object($config) && isset($config->$key)) $value = $config->$key;
				else if(is_array($config) && isset($config[$key])) $value = $config[$key]; 
				else $value = '';
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
			throw new \Exception("Missing required config for: " . implode(', ', $missing));
		}
		
		// $charset = $this->databaseConfig['dbCharset'];
		// $this->backupOptions['findReplaceCreateTable']['DEFAULT CHARSET=latin1;'] = "DEFAULT CHARSET=$charset;";

		return $this;
	}

	/**
	 * Set the database connection
	 * 
	 * @param \PDO|WireDatabasePDO $database
	 * @throws \PDOException on invalid connection
	 * 
	 */
	public function setDatabase($database) {
		$query = $database->prepare('SELECT DATABASE()'); 
		$query->execute();
		list($dbName) = $query->fetch(\PDO::FETCH_NUM); 
		if($dbName) $this->databaseConfig['dbName'] = $dbName; 
		$this->database = $database;
	}

	/**
	 * Get current database connection, initiating the connection if not yet active
	 * 
	 * @return null|\PDO|WireDatabasePDO
	 * @throws \Exception
	 * 
	 */
	public function getDatabase() {
		
		if($this->database) return $this->database; 
		
		$config = $this->databaseConfig; 
		if(empty($config['dbUser'])) throw new \Exception("Please call setDatabaseConfig(config) to supply config information so we can connect."); 
		
		if($config['dbSocket']) {
			$dsn = "mysql:unix_socket=$config[dbSocket];dbname=$config[dbName];";
		} else {
			$dsn = "mysql:dbname=$config[dbName];host=$config[dbHost]";
			if($config['dbPort']) $dsn .= ";port=$config[dbPort]";
		}
		
		$options = array(
			\PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES '$config[dbCharset]'",
			\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
			);
		
		$database = new \PDO($dsn, $config['dbUser'], $config['dbPass'], $options);
		$this->setDatabase($database); 
		return $database;
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
	 * @return $this
	 * @throws \Exception if path has a problem
	 * 
	 */
	public function setPath($path) {
		$path = $this->sanitizePath($path); 
		if(!is_dir($path)) throw new \Exception("Path doesn't exist: $path");
		if(!is_writable($path)) throw new \Exception("Path isn't writable: $path");
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
		$dir = new \DirectoryIterator($this->path);
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
		
		$filename = $this->sanitizeFilename($filename); 
		if(!file_exists($filename)) return array();

		$fp = fopen($filename, "r+");
		$line = fgets($fp);
		if(strpos($line, self::fileHeader) === 0 || strpos($line, "# " . self::fileHeader) === 0) {
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
		$pos = strpos($foot, self::fileFooter) + strlen(self::fileFooter);
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
		/** @noinspection PhpAssignmentInConditionInspection */
		while($row = $query->fetch(\PDO::FETCH_NUM)) $tables[$row[0]] = $row[0];
		$query->closeCursor();

		if($count) {
			foreach($tables as $table) {
				$query = $this->database->prepare("SELECT COUNT(*) FROM `$table`");
				$query->execute();
				$row = $query->fetch(\PDO::FETCH_NUM);
				$counts[$table] = (int) $row[0];
			}
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
	 * @throws \Exception on fatal error
	 * 
	 */
	public function backup(array $options = array()) {

		if(!$this->path) throw new \Exception("Please call setPath('/backup/files/path/') first"); 
		$this->errors(true); 
		$options = array_merge($this->backupOptions, $options); 
	
		if(empty($options['filename'])) {
			// generate unique filename
			$tail = ((count($options['tables']) || count($options['excludeTables']) || count($options['excludeExportTables'])) ? '-part' : '');
			$n = 0;
			do {
				$options['filename'] = $this->databaseConfig['dbName'] . '_' . date('Y-m-d_H-i-s') . $tail . ($n ? "-$n" : "") . ".sql";
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
		
		fwrite($fp, "# " . self::fileHeader . " $json\n"); 
		fclose($fp);
		if($this->wire) $this->wire->files->chmod($file);
		return true; 
	}

	/**
	 * End a new backup file, adding our footer to the bottom
	 *
	 * @param string|resource $file
	 * @param array $summary
	 * @param array $options
	 * @return bool
	 *
	 */
	protected function backupEndFile($file, array $summary = array(), array $options) {

		$fp = is_resource($file) ? $file : fopen($file, 'a+'); 
		
		if(!$fp) {
			$this->error("Unable to write footer to file: $file");
			return false;
		}
		
		foreach($options['extraSQL'] as $sql) {
			fwrite($fp, "\n" . rtrim($sql, '; ') . ";\n"); 
		}
		
		$footer = "# " . self::fileFooter;
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
		$hasReplace = count($options['findReplace']); 

		foreach($tables as $table) {

			if(in_array($table, $options['excludeTables'])) continue;
			if(count($options['tables']) && !in_array($table, $options['tables'])) continue;

			if(in_array($table, $options['excludeCreateTables'])) {
				// skip
			} else {
				if($options['allowDrop']) fwrite($fp, "\nDROP TABLE IF EXISTS `$table`;");
				$query = $database->prepare("SHOW CREATE TABLE `$table`");
				$query->execute();
				$row = $query->fetch(\PDO::FETCH_NUM);
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
			/** @noinspection PhpAssignmentInConditionInspection */
			while($row = $query->fetch(\PDO::FETCH_NUM)) $columns[] = $row[0];
			$query->closeCursor();
			$columnsStr = '`' . implode('`, `', $columns) . '`';

			$sql = "SELECT $columnsStr FROM `$table` ";

			$conditions = array();
			foreach($options['whereSQL'] as $_table => $_conditions) {
				if($_table === $table || ($_table[0] == '/' && preg_match($_table, $table))) $conditions = array_merge($conditions, $_conditions); 
			}
			if(count($conditions)) {
				$sql .= "WHERE ";
				foreach(array_values($conditions) as $n => $condition) {
					if($n) $sql .= "AND ";	
					$sql .= "($condition) ";
				}
			}
			
			$query = $database->prepare($sql);
			$this->executeQuery($query);

			/** @noinspection PhpAssignmentInConditionInspection */
			while($row = $query->fetch(\PDO::FETCH_NUM)) {
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
				if($options['allowUpdate']) {
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
		$this->backupEndFile($fp, $summary, $options); // this does the fclose
		
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
	 * Import a database SQL file that was created by this class
	 * 
	 * @param string $filename Filename to restore, optionally including path (if no path, then path set to construct is assumed)
	 * @param array $options See WireDatabaseBackup::$restoreOptions
	 * @return true on success, false on failure. Call the errors() method to retrieve errors.
	 * @throws \Exception on fatal error
	 *
	 */
	public function restore($filename, array $options = array()) {

		$filename = $this->sanitizeFilename($filename); 
		if(!file_exists($filename)) throw new \Exception("Restore file does not exist: $filename");
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
	 * @param string $filename Filename to restore (must be SQL file exported by this class)
	 * @param array $options See $restoreOptions
	 * @return true on success, false on failure. Call the errors() method to retrieve errors.
	 *
	 */
	protected function restorePDO($filename, array $options = array()) {

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
				$this->executeQuery($line, $options); 
				if($command === 'INSERT') $numInserts++;
				if($command === 'CREATE') $numTables++;
				$numQueries++;
				
			} catch(\Exception $e) {
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
	 * @param string $filename Filename to restore (must be SQL file exported by this class)
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
		if(empty($line) || substr($line, 0, 2) == '--' || substr($line, 0, 1) == '#') return false;
		return true;
	}

	/**
	 * Restore from 2 SQL files while resolving table differences (think of it as array_merge for a DB restore)
	 * 
	 * The CREATE TABLE and INSERT statements in filename2 take precedence of those in filename1.
	 * INSERT statements from both will be executed, with filename2 INSERTs updating those of filename1.
	 * CREATE TABLE statements in filename1 won't be executed if they also exist in filename2.
	 * 
	 * This method assumes both files follow the SQL dump format created by this class. 
	 * 
	 * @param string $filename1 Original filename
	 * @param string $filename2 Filename that may have statements that will update/override those in filename1
	 * @param array $options
	 * @return bool True on success, false on fail. 
	 * @throws \Exception|WireException if $options['haltOnErrors'] == true. 
	 * 
	 */
	public function restoreMerge($filename1, $filename2, $options) {
		
		$options = array_merge($this->restoreOptions, $options); 
		$creates1 = $this->findCreateTables($filename1, $options); 
		$creates2 = $this->findCreateTables($filename2, $options); 
		$creates = array_merge($creates1, $creates2); // CREATE TABLE statements in filename2 override those in filename1
		$numErrors = 0;
		
		foreach($creates as $table => $create) {
			if($options['allowDrop']) {
				if(!$this->executeQuery("DROP TABLE IF EXISTS `$table`", $options)) $numErrors++;
			}
			if(!$this->executeQuery($create, $options)) $numErrors++;
		}
		
		$inserts = $this->findInserts($filename1); 
		foreach($inserts as $table => $tableInserts) {
			foreach($tableInserts as $insert) {
				if(!$this->executeQuery($insert, $options)) $numErrors++;
			}
		}
	
		// Convert line 1 to line 2:
		// 1. INSERT INTO `field_process` (pages_id, data) VALUES('6', '17'); 
		// 2. INSERT INTO `field_process` (pages_id, data) VALUES('6', '17') ON DUPLICATE KEY UPDATE pages_id=VALUES(pages_id), data=VALUES(data);
		
		$inserts = $this->findInserts($filename2);
		foreach($inserts as $table => $tableInserts) {
			foreach($tableInserts as $insert) {
				// check if table existed in both dump files, and has no duplicate update statement
				$regex = '/\s+ON\s+DUPLICATE\s+KEY\s+UPDATE\s+[^\'";]+;$/i';
				if(isset($creates1[$table]) && !preg_match($regex, $insert)) {
					// line doesn't already contain an ON DUPLICATE section, so we need to add it
					$pos1 = strpos($insert, '(') + 1; 
					$pos2 = strpos($insert, ')') - $pos1;
					$fields = substr($insert, $pos1, $pos2);
					$insert = rtrim($insert, '; ') . " ON DUPLICATE KEY UPDATE ";
					foreach(explode(',', $fields) as $name) {
						$name = trim($name); 
						$insert .= "$name=VALUES($name), ";
					}
					$insert = rtrim($insert, ", ") . ";";
				}
				if(!$this->executeQuery($insert, $options)) $numErrors++;
			}
		}
		
		return $numErrors === 0;
	}

	////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	/**
	 * Returns array of all create table statements, indexed by table name
	 *
	 * @param string $filename to extract all CREATE TABLE statements from
	 * @param string $regex Regex (PCRE) to match for statement to be returned, must stuff table name into first match
	 * @param bool $multi Whether there can be multiple matches per table
	 * @return array of statements, indexed by table name. If $multi is true, it will be array of arrays.
	 * @throws \Exception if unable to open specified file
	 *
	 */
	protected function findStatements($filename, $regex, $multi = true) {
		$filename = $this->sanitizeFilename($filename); 
		$fp = fopen($filename, 'r');
		if(!$fp) throw new \Exception("Unable to open: $filename"); 
		$statements = array();
		while(!feof($fp)) {
			$line = trim(fgets($fp));
			if(!preg_match($regex, $line, $matches)) continue;
			if(empty($matches[1])) continue; 
			$table = $matches[1];
			while(substr($line, -1) != ';' && !feof($fp)) $line .= " " . rtrim(fgets($fp));
			if($multi) {
				if(!isset($statements[$table])) $statements[$table] = array();
				$statements[$table][] = $line;
			} else {
				$statements[$table] = $line;
			}
		}
		fclose($fp);
		return $statements;
	}

	/**
	 * Returns array of all create table statements, indexed by table name
	 * 
	 * @param string $filename to extract all CREATE TABLE statements from
	 * @param array $options
	 * @return bool|array of CREATE TABLE statements, associative: indexed by table name
	 * @throws \Exception if unable to open specified file
	 *
	 */
	public function findCreateTables($filename, array $options) {
		$regex = '/^CREATE\s+TABLE\s+`?([^`\s]+)/i';
		$statements = $this->findStatements($filename, $regex, false);
		if(!empty($options['findReplaceCreateTable'])) {
			foreach($options['findReplaceCreateTable'] as $find => $replace) {
				foreach($statements as $key => $line) {
					if(strpos($line, $find) === false) continue;
					$line = str_replace($find, $replace, $line);
					$statements[$key] = $line;
				}
			}
		}
		return $statements;
	}

	/**
	 * Returns array of all INSERT statements, indexed by table name
	 *
	 * @param string $filename to extract all CREATE TABLE statements from
	 * @return array of arrays of INSERT statements. Base array is associative indexed by table name. 
	 * 	Inside arrays are numerically indexed by order of appearance. 
	 *
	 */
	public function findInserts($filename) {
		$regex = '/^INSERT\s+INTO\s+`?([^`\s]+)/i';
		return $this->findStatements($filename, $regex, true); 
	}

	/**
	 * Execute an SQL query, either a string or PDOStatement
	 * 
	 * @param string $query
	 * @param bool|array $options May be boolean (for haltOnError), or array containing the property (i.e. $options array)
	 * @return bool Query result
	 * @throws \Exception if haltOnError, otherwise it populates $this->errors
	 * 
	 */
	protected function executeQuery($query, $options = array()) {
		$defaults = array(
			'haltOnError' => false
		);
		if(is_bool($options)) {
			$defaults['haltOnError'] = $options;
			$options = array();
		}
		$options = array_merge($defaults, $options);
		$result = false;
		try {
			if(is_string($query)) {
				$result = $this->getDatabase()->exec($query); 
			} else if($query instanceof \PDOStatement) {
				$result = $query->execute();
			}
		} catch(\Exception $e) {
			if(empty($options['haltOnError'])) {
				$this->error($e->getMessage());
			} else {
				throw $e;
			}
		}
		return $result === false ? false : true;
	}

	/**
	 * For path: Normalizes slashes and ensures it ends with a slash
	 *
	 * @param $path
	 * @return string
	 *
	 */
	protected function sanitizePath($path) {
		if(DIRECTORY_SEPARATOR != '/') $path = str_replace(DIRECTORY_SEPARATOR, '/', $path); 
		$path = rtrim($path, '/') . '/'; // ensure it ends with trailing slash
		return $path; 
	}

	/**
	 * For filename: Normalizes slashes and ensures it starts with a path
	 * 
	 * @param $filename
	 * @return string
	 * @throws \Exception if path has not yet been set
	 * 
	 */
	protected function sanitizeFilename($filename) {
		if(DIRECTORY_SEPARATOR != '/') $filename = str_replace(DIRECTORY_SEPARATOR, '/', $filename);
		if(strpos($filename, '/') === false) {
			$filename = $this->path . $filename;
		}
		if(strpos($filename, '/') === false) {
			$path = $this->getPath();
			if(!strlen($path)) throw new \Exception("Please supply full path to file, or call setPath('/backup/files/path/') first");
			$filename = $path . $filename; 
		}
		return $filename; 
	}
	
	////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Determine if exec is available for the given command
	 * 
	 * Note that WireDatabaseBackup does not currently use exec() mode so this is here for future use. 
	 * 
	 * @param array $options
	 * @return bool
	 * @throws \Exception on unknown exec type
	 * 
	 */
	protected function supportsExec(array $options = array()) {

		if(!$options['exec']) return false;
		
		if(empty($this->databaseConfig['dbUser'])) return false; // no db config options provided
		
		if(preg_match('{^(?:\[dbPath\])?([_a-zA-Z0-9]+)\s}', $options['execCommand'], $matches)) {
			$type = $matches[1]; 
		} else {
			throw new \Exception("Unable to determine command for exec"); 
		}

		if($type == 'mysqldump') {
			// these options are not supported by mysqldump via exec
			if(	!empty($options['excludeCreateTables']) || 
				!empty($options['excludeExportTables']) ||
				!empty($options['findReplace']) ||
				!empty($options['findReplaceCreateTable']) ||
				!empty($options['allowUpdateTables']) || 
				!empty($options['allowUpdate'])) {
				return false;
			}
			
		} else if($type == 'mysql') {
			// these options are not supported by mysql via exec
			if(	!empty($options['tables']) ||
				!empty($options['allowDrop']) ||
				!empty($options['findReplace']) ||
				!empty($options['findReplaceCreateTable'])) {
				return false;
			}
			
		} else {
			throw new \Exception("Unrecognized exec command: $type"); 
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
