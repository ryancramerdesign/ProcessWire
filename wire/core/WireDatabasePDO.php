<?php namespace ProcessWire;

/**
 * ProcessWire PDO Database
 *
 * Serves as a wrapper to PHP's PDO class
 * 
 * ProcessWire 3.x (development), Copyright 2015 by Ryan Cramer
 * https://processwire.com
 *
 */

/**
 * Database class provides a layer on top of mysqli
 *
 */
class WireDatabasePDO extends Wire implements WireDatabase {

	/**
	 * Log of all queries performed in this instance
	 *
	 */
	protected $queryLog = array();

	/**
	 * Whether queries will be logged
	 * 
	 */
	protected $debugMode = false;

	/**
	 * Instance of PDO
	 * 
	 */
	protected $pdo = null;

	/**
	 * PDO connection settings
	 * 
	 */
	private $pdoConfig = array(
		'dsn' => '', 
		'user' => '',
		'pass' => '', 	
		'options' => '',
		);

	/**
	 * Cached values from getVariable method
	 * 
	 * @var array associative of name => value
	 * 
	 */
	protected $variableCache = array();

	/**
	 * Create a new PDO instance from ProcessWire $config API variable
	 * 
	 * If you need to make other PDO connections, just instantiate a new WireDatabasePDO (or native PDO)
	 * rather than calling this getInstance method. 
	 * 
	 * @param Config $config
	 * @return WireDatabasePDO 
	 * @throws WireException
	 * 
	 */
	public static function getInstance(Config $config) {

		if(!class_exists('PDO')) {
			throw new WireException('Required PDO class (database) not found - please add PDO support to your PHP.'); 
		}

		$host = $config->dbHost;
		$username = $config->dbUser;
		$password = $config->dbPass;
		$name = $config->dbName;
		$socket = $config->dbSocket; 
		$charset = $config->dbCharset;
		if($socket) {
			// if socket is provided ignore $host and $port and use $socket instead:
			$dsn = "mysql:unix_socket=$socket;dbname=$name;";
		} else {
			$dsn = "mysql:dbname=$name;host=$host";
			$port = $config->dbPort;
			if($port) $dsn .= ";port=$port";
		}
		$driver_options = array(
			\PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES '$charset'",
			\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
			);
		$database = new WireDatabasePDO($dsn, $username, $password, $driver_options); 
		$database->setDebugMode($config->debug);
		return $database;
	}
	
	public function __construct($dsn, $username = null, $password = null, array $driver_options = array()) {
		$this->pdoConfig['dsn'] = $dsn; 
		$this->pdoConfig['user'] = $username;
		$this->pdoConfig['pass'] = $password; 
		$this->pdoConfig['options'] = $driver_options; 
		$this->pdo();
	}

	/**
	 * Return the current PDO connection instance
	 *
	 * Use this instead of $this->pdo because it restores a lost connection automatically. 
	 *
	 * @return \PDO
	 *
	 */
	public function pdo() {
		if(!$this->pdo) $this->pdo = new \PDO(
			$this->pdoConfig['dsn'], 
			$this->pdoConfig['user'], 
			$this->pdoConfig['pass'], 
			$this->pdoConfig['options']
			); 	
		return $this->pdo;
	}

	
	public function errorCode() {
		return $this->pdo()->errorCode();
	}
	
	public function errorInfo() {
		return $this->pdo()->errorInfo();
	}
	
	public function getAttribute($attribute) {
		return $this->pdo()->getAttribute($attribute); 
	}
	
	public function setAttribute($attribute, $value) {
		return $this->pdo()->setAttribute($attribute, $value); 
	}
	
	public function lastInsertId($name = null) {
		return $this->pdo()->lastInsertId($name); 
	}
	
	public function query($statement, $note = '') {
		if($this->debugMode) $this->queryLog($statement, $note); 
		return $this->pdo()->query($statement); 
	}
	
	public function beginTransaction() {
		return $this->pdo()->beginTransaction();
	}
	
	public function inTransaction() {
		return $this->pdo()->inTransaction();
	}
	
	public function commit() {
		return $this->pdo()->commit();
	}
	
	public function rollBack() {
		return $this->pdo()->rollBack();
	}

	/**
	 * Get an array of all queries that have been executed thus far
	 *
	 * Active in ProcessWire debug mode only
	 *
	 * @deprecated use queryLog() method instead
	 * @return array
	 *
	 */
	static public function getQueryLog() {
		/** @var WireDatabasePDO $database */
		$database = wire('database');
		$database->queryLog();
	}

	/**
	 * Prepare an SQL statement for accepting bound parameters
	 * 
	 * @param string $statement
	 * @param array|string $driver_options Driver options array or you may specify $note here
	 * @param string $note Debug notes to save with query in debug mode
	 * @return \PDOStatement
	 * 
	 */
	public function prepare($statement, $driver_options = array(), $note = '') {
		if(is_string($driver_options)) {
			$note = $driver_options; 
			$driver_options = array();
		}
		if($this->debugMode) $this->queryLog($statement, $note); 
		return $this->pdo()->prepare($statement, $driver_options);
	}

	/**
	 * Execute an SQL statement string
	 * 
	 * If given a PDOStatement, this method behaves the same as the execute() method. 
	 * 
	 * @param string|\PDOStatement $statement
	 * @param string $note
	 * @return bool|int
	 * @throws \PDOException
	 * 
	 */
	public function exec($statement, $note = '') {
		if(is_object($statement) && $statement instanceof \PDOStatement) {
			return $this->execute($statement);
		}
		$this->queryLog($statement, $note); 
		return $this->pdo()->exec($statement);
	}
	
	/**
	 * Execute a PDO statement, with retry and error handling
	 *
	 * @param \PDOStatement $query
	 * @param bool $throw Whether or not to throw exception on query error (default=true)
	 * @param int $maxTries Max number of times it will attempt to retry query on error
	 * @return bool
	 * @throws \PDOException
	 *
	 */
	public function execute(\PDOStatement $query, $throw = true, $maxTries = 3) {

		$tryAgain = 0;
		$_throw = $throw;

		do {
			try {
				$result = $query->execute();

			} catch(\PDOException $e) {

				$result = false;
				$error = $e->getMessage();
				$throw = false; // temporarily disable while we try more

				if($tryAgain === 0) {
					// setup retry loop
					$tryAgain = $maxTries;
				} else {
					// decrement retry loop
					$tryAgain--;
				}

				if(stripos($error, 'MySQL server has gone away') !== false) {
					// forces reconection on next query
					$this->wire('database')->closeConnection();

				} else if($query->errorCode() == '42S22') {
					// unknown column error
					$errorInfo = $query->errorInfo();
					if(preg_match('/[\'"]([_a-z0-9]+\.[_a-z0-9]+)[\'"]/i', $errorInfo[2], $matches)) {
						$this->unknownColumnError($matches[1]);
					}

				} else {
					// some other error that we don't have retry plans for
					// tryAgain=0 will force the loop to stop
					$tryAgain = 0;
				}

				if($tryAgain < 1) {
					// if at end of retry loop, restore original throw state
					$throw = $_throw;
				}

				if($throw) {
					throw $e;
				} else {
					$this->error($error);
				}
			}

		} while($tryAgain && !$result);

		return $result;
	}

	/**
	 * Hookable method called by execute() method when query encounters an unknown column
	 *
	 * @param string $column Column format tableName.columnName
	 *
	 */
	protected function ___unknownColumnError($column) { }
	
	public function queryLog($sql = '', $note = '') {
		if(empty($sql)) return $this->queryLog;
		$this->queryLog[] = $sql . ($note ? " -- $note" : "");
		return true;
	}

	/**
	 * Get array of all tables in this database.
	 *
	 * @return array
	 *
	 */
	public function getTables() {
		static $tables = array();

		if(!count($tables)) {
			$query = $this->query("SHOW TABLES");
			/** @noinspection PhpAssignmentInConditionInspection */
			while($col = $query->fetchColumn()) $tables[] = $col;
		} 

		return $tables; 
	}

	/**
	 * Is the given string a database comparison operator?
	 *
	 * @param string $str 1-2 character opreator to test
	 * @return bool 
	 *
	 */
	public function isOperator($str) {
		return in_array($str, array('=', '<', '>', '>=', '<=', '<>', '!=', '&', '~', '&~', '|', '^', '<<', '>>'), true);
	}

	/**
	 * Sanitize a table name for _a-zA-Z0-9
	 *
	 * @param string $table
	 * @return string
	 *
	 */
	public function escapeTable($table) {
		$table = (string) trim($table); 
		if(ctype_alnum($table)) return $table; 
		if(ctype_alnum(str_replace('_', '', $table))) return $table;
		return preg_replace('/[^_a-zA-Z0-9]/', '_', $table);
	}

	/**
	 * Sanitize a column name for _a-zA-Z0-9
	 *
	 * @param string $col
	 * @return string
	 *
	 */
	public function escapeCol($col) {
		return $this->escapeTable($col);
	}

	/**
	 * Sanitize a table.column string, where either part is optional
	 *
	 * @param string $str
	 * @return string
	 *
	 */
	public function escapeTableCol($str) {
		if(strpos($str, '.') === false) return $this->escapeTable($str); 
		list($table, $col) = explode('.', $str); 
		return $this->escapeTable($table) . '.' . $this->escapeCol($col);
	}

	/**
	 * Escape a string value, same as $db->quote() but without surrounding quotes
	 *
	 * @param string $str
	 * @return string
	 *
	 */
	public function escapeStr($str) {
		return substr($this->pdo()->quote($str), 1, -1);
	}

	/**
	 * Escape a string value, for backwards compatibility till PDO transition complete
	 *
	 * @deprecated
	 * @param string $str
	 * @return string
	 *
	 */
	public function escape_string($str) {
		return $this->escapeStr($str); 
	}

	/**
	 * Quote and escape a string value
	 *
	 * @param string $str
	 * @return string
	 *
	 */
	public function quote($str) {
		return $this->pdo()->quote($str);
	}

	/**
	 * Escape a string value, plus escape characters necessary for a MySQL 'LIKE' phrase
	 *
	 * @param string $like
	 * @return string
	 *
	 */
	public function escapeLike($like) {
		$like = $this->escapeStr($like); 
		return addcslashes($like, '%_'); 
	}
	
	public function setDebugMode($debugMode) {
		$this->debugMode = (bool) $debugMode; 
	}
	
	public function __get($key) {
		if($key == 'pdo') return $this->pdo();
		return parent::__get($key);
	}
	
	public function closeConnection() {
		$this->pdo = null;
	}

	/**
	 * Get the value of a MySQL variable
	 * 
	 * @param string $name
	 * @param bool $cache Allow use of cached values?
	 * @return string|int
	 * 
	 */
	public function getVariable($name, $cache = true) {
		if($cache && isset($this->variableCache[$name])) return $this->variableCache[$name];
		$query = $this->prepare('SHOW VARIABLES WHERE Variable_name=:name');
		$query->bindValue(':name', $name);
		$query->execute();
		/** @noinspection PhpUnusedLocalVariableInspection */
		list($varName, $value) = $query->fetch(\PDO::FETCH_NUM);
		$this->variableCache[$name] = $value;
		return $value;
	}

	/**
	 * Retrieve new instance of WireDatabaseBackups ready to use with this connection
	 * 
	 * See WireDatabaseBackup class for usage. 
	 * 
	 * @return WireDatabaseBackup
	 * @throws WireException|\Exception on fatal error
	 * 
	 */
	public function backups() {
	
		$path = $this->wire('config')->paths->assets . 'backups/database/';
		if(!is_dir($path)) {
			$this->wire('files')->mkdir($path, true); 
			if(!is_dir($path)) throw new WireException("Unable to create path for backups: $path"); 
		}

		$backups = new WireDatabaseBackup($path); 
		$backups->setWire($this->wire());
		$backups->setDatabase($this);
		$backups->setDatabaseConfig($this->wire('config'));
		$backups->setBackupOptions(array('user' => $this->wire('user')->name)); 
	
		return $backups; 
	}

}
