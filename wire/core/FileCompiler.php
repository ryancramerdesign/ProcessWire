<?php namespace ProcessWire;

/**
 * Class FileCompiler
 *
 * @todo determine whether we should make storage in dedicated table rather than using wire('cache').
 * @todo handle race conditions for multiple requests attempting to compile the same file(s).
 * 
 * @method string compile($sourceFile)
 * @method string compileData($data, $sourceFile)
 * 
 */

class FileCompiler extends Wire {

	/**
	 * Compilation options
	 * 
	 * @var array
	 * 
	 */
	protected $options = array(
		'includes' => true,	// compile include()'d files too?
		'namespace' => true, // compile to make compatible with PW namespace when necessary?
		'modules' => false, // compile using installed FileCompiler modules
	);
	
	/**
	 * Path to source files directory
	 *
	 * @var string
	 *
	 */
	protected $sourcePath;

	/**
	 * Path to compiled files directory
	 * 
	 * @var string
	 * 
	 */
	protected $targetPath = null;

	/**
	 * Files or directories that should be excluded from compilation
	 * 
	 * @var array
	 * 
	 */
	protected $exclusions = array();

	/**
	 * File extensions that we compile and copy
	 * 
	 * @var array
	 * 
	 */
	protected $extensions = array(
		'php',
		'module',
		'inc',
	);

	/**
	 * Construct
	 * 
	 * @param string $sourcePath Path where source files are located
	 * @param array $options Indicate which compilations should be performed (default='includes' and 'namespace')
	 * 
	 */
	public function __construct($sourcePath, array $options = array()) {
		$this->options = array_merge($this->options, $options);
		if(strpos($sourcePath, '..') !== false) $sourcePath = realpath($sourcePath);
		$this->sourcePath = rtrim($sourcePath, '/') . '/';
	}

	/**
	 * Initialize paths
	 * 
	 * @throws WireException
	 * 
	 */
	protected function init() {
		
		static $preloaded = false;
		
		if(!$preloaded) {
			$this->wire('cache')->preloadFor($this);
			$preloaded = true;
		}
		
		$targetPath = $this->wire('config')->paths->cache . $this->className() . '/';
		
		if(strpos($this->sourcePath, $targetPath) === 0) {
			// sourcePath is inside the targetPath, correct this 
			$this->sourcePath = str_replace($targetPath, '', $this->sourcePath);
			$this->sourcePath = $this->wire('config')->paths->root . $this->sourcePath;
		}
		
		$t = str_replace($this->wire('config')->paths->root, '', $this->sourcePath);
		$this->targetPath = $targetPath . trim($t, '/') . '/';
	
		// @todo move this somewhere outside of this class
		$this->addExclusion($this->wire('config')->paths->wire);
		// $this->addExclusion($this->wire('config')->paths->templates . 'admin.php');
		
	}

	/**
	 * Initialize the target path, making sure that it exists and creating it if not
	 * 
	 * @throws WireException
	 * 
	 */
	protected function initTargetPath() {
		if(!is_dir($this->targetPath)) {
			if(!$this->wire('files')->mkdir($this->targetPath, true)) {
				throw new WireException("Unable to create directory $this->targetPath");
			}
		}
	}

	/**
	 * Allow the given filename to be compiled?
	 * 
	 * @param string $filename This property can be modified by the function
	 * @param string $basename This property can be modified by the function
	 * @return bool 
	 * 
	 */
	protected function allowCompile(&$filename, &$basename) {
		
		$ext = pathinfo($filename, PATHINFO_EXTENSION);
		if(!in_array(strtolower($ext), $this->extensions)) {
			if(!strlen($ext) && !is_file($filename) && is_file("$filename.php")) {
				// assume PHP file extension if none given, for cases like wireIncludeFile
				$filename .= '.php';
				$basename .= '.php';
			} else {
				return false;
			}
		}

		if(!is_file($filename)) {
			return false;
		}
		
		$allow = true;
		foreach($this->exclusions as $pathname) {
			if(strpos($filename, $pathname) === 0) {
				$allow = false;
				break;
			}
		}

		return $allow; 
	}

	/**
	 * Compile given source file and return compiled destination file
	 * 
	 * @param string $sourceFile Source file to compile (relative to sourcePath given in constructor)
	 * @return string Full path and filename of compiled file. Returns sourceFile is compilation is not necessary.
	 * @throws WireException if given invalid sourceFile
	 * 
	 */
	public function ___compile($sourceFile) {
		
		$this->init();
		
		if(strpos($sourceFile, $this->sourcePath) === 0) {
			$sourcePathname = $sourceFile;
			$sourceFile = str_replace($this->sourcePath, '/', $sourceFile);
		} else {
			$sourcePathname = $this->sourcePath . ltrim($sourceFile, '/');
		}

		if(!$this->allowCompile($sourcePathname, $sourceFile)) return $sourcePathname;

		$this->initTargetPath();

		$cacheName = md5($sourcePathname);
		$sourceHash = md5_file($sourcePathname);
		
		$targetPathname = $this->targetPath . ltrim($sourceFile, '/');
		$compileNow = true;
		
		if(is_file($targetPathname)) {
			// target file already exists, check if it is up-to-date
			// $targetData = file_get_contents($targetPathname);
			$targetHash = md5_file($targetPathname);
			$cache = $this->wire('cache')->getFor($this, $cacheName);
			if($cache && is_array($cache)) {
				if($cache['target']['hash'] == $targetHash && $cache['source']['hash'] == $sourceHash) {
					// target file is up-to-date 
					$compileNow = false;
				} else {
					// target file changed somewhere else, needs to be re-compiled
					$this->wire('cache')->deleteFor($this, $cacheName);	
				}
			}
		}
		
		if($compileNow) {
			$sourcePath = dirname($sourcePathname);
			$targetPath = dirname($targetPathname);
			$targetData = file_get_contents($sourcePathname);
			if(stripos($targetData, 'FileCompiler=0')) return $sourcePathname; // bypass if it contains this string
			set_time_limit(120);
			$this->copyAllNewerFiles($sourcePath, $targetPath); 
			$targetDirname = dirname($targetPathname) . '/';
			if(!is_dir($targetDirname)) $this->wire('files')->mkdir($targetDirname, true);
			$targetData = $this->compileData($targetData, $sourcePathname);
			if(false !== file_put_contents($targetPathname, $targetData, LOCK_EX)) {
				$this->wire('files')->chmod($targetPathname);
				touch($targetPathname, filemtime($sourcePathname));
				$cacheData = array(
					'source' => array(
						'file' => $sourcePathname,
						'hash' => $sourceHash,
						'size' => filesize($sourcePathname), 
						'time' => filemtime($sourcePathname)
					),
					'target' => array(
						'file' => $targetPathname,
						'hash' => md5_file($targetPathname),
						'size' => filesize($targetPathname),
						'time' => filemtime($targetPathname),
					)
				);
				$this->wire('cache')->saveFor($this, $cacheName, $cacheData, WireCache::expireNever);
			}
			$u = $this->wire('user');
			if($this->wire('config')->debug || ($u && $u->isSuperuser())) {
				$this->message($this->_('Compiled file:') . ' ' . str_replace($this->wire('config')->paths->root, '/', $sourcePathname));
			}
		}
		
	
		return $targetPathname;
	}
	
	/**
	 * Compile the given string of data
	 * 
	 * @param string $data
	 * @param string $sourceFile
	 * @return string
	 * 
	 */
	protected function ___compileData($data, $sourceFile) {
		
		if($this->options['includes']) $this->compileIncludes($data, $sourceFile);
		if($this->options['namespace']) $this->compileNamespace($data);
		
		if($this->options['modules']) {
			foreach($this->wire('modules')->findByPrefix('FileCompiler', true) as $module) {
				if(!$module instanceof FileCompilerModule) continue;
				$module->setSourceFile($sourceFile);
				$data = $module->compile($data);
			}	
		}
		
		return $data;
	}

	/**
	 * Compile include(), require() (and variations) to refer to compiled files where possible
	 * 
	 * @param string $data
	 * @param string $sourceFile
	 * 
	 */
	protected function compileIncludes(&$data, $sourceFile) {
		
		// other related to includes
		if(strpos($data, '__DIR__') !== false) {
			$data = str_replace('__DIR__', "'" . dirname($sourceFile) . "'", $data);
		}
		if(strpos($data, '__FILE__') !== false) {
			$data = str_replace('__FILE__', "'" . $sourceFile . "'", $data);
		}
		
		$optionsStr = $this->optionsToString($this->options);
		
		$funcs = array(
			'include_once',
			'include', 
			'require_once',
			'require',
			'wireIncludeFile',
			'wireRenderFile',
			'TemplateFile',
		);

		// main include regex
		$re = '/^' . 
			'(.*?)' . // 1: open
			'(' . implode('|', $funcs) . ')' . // 2:function
			'[\(\s]+' . // open parenthesis and/or space
			'(["\']?[^;\r\n]+);' . // 3:filename, and the rest of the statement (filename may be quoted or end with closing parenthesis)
			'/m';
		
		if(!preg_match_all($re, $data, $matches)) return;
		
		foreach($matches[0] as $key => $fullMatch) {
		
			$open = $matches[1][$key];
			
			if(strlen($open)) {
				
				if(strpos($open, '"') !== false || strpos($open, "'") !== false) {
					// skip when words like "require" are in a string
					continue;
				}

				if(preg_match('/^[$_a-zA-Z0-9]+$/', substr($open, -1))) {
					// skip things like: something_include(... and $include
					continue;
				}
			}
				
			$funcMatch = $matches[2][$key];
			$fileMatch = $matches[3][$key];
			$argsMatch = '';

			if(substr($fileMatch, -1) == ')') $fileMatch = substr($fileMatch, 0, -1);
			if(empty($fileMatch)) continue;
			
			$commaPos = strpos($fileMatch, ',');
			if($commaPos) {
				// fileMatch contains additional function arguments
				$argsMatch = substr($fileMatch, $commaPos);
				$fileMatch = substr($fileMatch, 0, $commaPos);
			}
			
			if(strpos($fileMatch, './') === 1) {
				// relative to current dir, convert to absolute
				$fileMatch = $fileMatch[0] . dirname($sourceFile) . substr($fileMatch, 2);
			} else if(strpos($fileMatch, '/') === false 
				&& strpos($fileMatch, '$') === false 
				&& strpos($fileMatch, '(') === false
				&& strpos($fileMatch, '\\') === false) {
				// i.e. include("file.php")
				$fileMatch = $fileMatch[0] . dirname($sourceFile) . '/' . substr($fileMatch, 1);
			}
		
			$fileMatch = str_replace("\t", '', $fileMatch);
			if(strlen($open)) $open .= ' ';
			$newFullMatch = $open . $funcMatch . "(\\ProcessWire\\wire('files')->compile($fileMatch,$optionsStr)$argsMatch);";
			$data = str_replace($fullMatch, $newFullMatch, $data);
		}
		
		// replace absolute root path references with runtime generated versions
		$rootPath = $this->wire('config')->paths->root; 
		if(strpos($data, $rootPath)) {
			/*
			$data = preg_replace('%([\'"])' . preg_quote($rootPath) . '([^\'"\s\r\n]*[\'"])%', 
				'(isset($this) && $this instanceof \\ProcessWire\\Wire ? ' . 
					'$this->wire("config")->paths->root : ' . 
					'\\ProcessWire\\wire("config")->paths->root' . 
				') . $1$2', 
				$data);
			*/
			$data = preg_replace('%([\'"])' . preg_quote($rootPath) . '([^\'"\s\r\n]*[\'"])%',
				'\\ProcessWire\\wire("config")->paths->root . $1$2',
				$data);
		}

	}

	/**
	 * Compile global class/interface/function references to namespaced versions
	 * 
	 * @param string $data
	 * @return bool Whether or not namespace changes were compiled
	 * 
	 */
	protected function compileNamespace(&$data) {
		
		// first check if data already defines a namespace, in which case we'll skip over it
		$pos = strpos($data, 'namespace');
		if($pos !== false) { 
			if(preg_match('/(^.*)\s+namespace\s+[_a-zA-Z0-9\\\\]+\s*;/m', $data, $matches)) {
				if(strpos($matches[1], '//') === false && strpos($matches[1], '/*') === false) {
					// namespace already present, no need for namespace compilation
					return false;
				}
			}
		}
		
		$classes = get_declared_classes();
		$classes = array_merge($classes, get_declared_interfaces());
	
		// also add in all core classes, in case the have not yet been autoloaded
		static $files = null;
		if(is_null($files)) {
			$files = array();
			foreach(new \DirectoryIterator($this->wire('config')->paths->core) as $file) {
				if($file->isDot() || $file->isDir()) continue;
				$basename = $file->getBasename('.php');
				if(strtoupper($basename[0]) == $basename[0]) {
					$name = __NAMESPACE__ . "\\$basename";	
					if(!in_array($name, $classes)) $files[] = $name;
				}
			}
		}
		
		// also add in all modules
		foreach($this->wire('modules') as $module) {
			$name = $module->className(true);
			if(!in_array($name, $classes)) $classes[] = $name;
		}
		$classes = array_merge($classes, $files);
		
		// update classes and interfaces
		foreach($classes as $class) {
			
			if(strpos($class, __NAMESPACE__ . '\\') !== 0) continue; // limit only to ProcessWire classes/interfaces
			/** @noinspection PhpUnusedLocalVariableInspection */
			list($ns, $class) = explode('\\', $class, 2); // reduce to just class without namespace
			if(stripos($data, $class) === false) continue; // quick exit if class name not referenced in data
			
			$patterns = array(
				// 1=open 2=close
				// all patterns match within 1 line only
				"new" => '(new\s+)' . $class . '\s*(\(|;|\))',  // 'new Page(' or 'new Page;' or 'new Page)'
				"function" => '([_a-zA-Z0-9]+\s*\([^)]*?)\b' . $class . '(\s+\$[_a-zA-Z0-9]+)', // 'function(Page $page' or 'function($a, Page $page'
				"::" => '(^|[^_a-zA-Z0-9"\'])' . $class . '(::)', // constant ' Page::foo' or '(Page::foo' or '=Page::foo' or bitwise open
				"extends" => '(\sextends\s+)' . $class . '(\s|\{|$)', // 'extends Page'
				"implements" => '(\simplements[^{]*?[\s,]+)' . $class . '([^_a-zA-Z0-9]|$)', // 'implements Module' or 'implements Foo, Module'
				"instanceof" => '(\sinstanceof\s+)' . $class . '([^_a-zA-Z0-9]|$)', // 'instanceof Page'
				"$class " => '(\(\s*|,\s*)' . $class . '(\s+\$)', // type hinted '(Page $something' or '($foo, Page $something'
			);
			
			foreach($patterns as $check => $regex) {
				
				if(stripos($data, $check) === false) continue;
				if(!preg_match_all('/' . $regex . '/im', $data, $matches)) continue;
				//echo "<pre>" . print_r($matches, true) . "</pre>";
				
				foreach($matches[0] as $key => $fullMatch) {
					$open = $matches[1][$key];
					$close = $matches[2][$key];
					if(substr($open, -1) == '\\') continue; // if last character in open is '\' then skip the replacement
					$className = '\\' . __NAMESPACE__ . '\\' . $class;
					$data = str_replace($fullMatch, $open . $className . $close, $data);
				}
			}
		}
	
		// update PW procedural function calls
		$functions = get_defined_functions();
		
		foreach($functions['user'] as $function) {
			
			if(stripos($function, __NAMESPACE__ . '\\') !== 0) continue; // limit only to ProcessWire functions
			/** @noinspection PhpUnusedLocalVariableInspection */
			list($ns, $function) = explode('\\', $function, 2); // reduce to just function name
			if(stripos($data, $function) === false) continue; // if function name not mentioned in data, quick exit
		
			$n = 0;
			while(preg_match_all('/^(.*?[()!;,@\[=\s.])' . $function . '\s*\(/im', $data, $matches)) {
				foreach($matches[0] as $key => $fullMatch) {
					$open = $matches[1][$key];
					if(strpos($open, 'function') !== false) continue; // skip function defined with same name
					$functionName = '\\' . __NAMESPACE__ . '\\' . $function;
					$data = str_replace($fullMatch, $open . $functionName . '(', $data);
				}
				if(++$n > 5) break;
			}
		}
		
		// update other function calls
		if(strpos($data, 'class_parents(') !== false) {
			$data = preg_replace('/\bclass_parents\(/', '\\ProcessWire\\wireClassParents(', $data);
		}
		if(strpos($data, 'class_implements(') !== false) {
			$data = preg_replace('/\bclass_implements\(/', '\\ProcessWire\\wireClassImplements(', $data);
		}
		
		return true; 
	}

	/**
	 * Recursively copy all files from $source to $target, but only if $source file is $newer
	 * 
	 * @param string $source
	 * @param string $target
	 * @param bool $recursive
	 * @return int Number of files copied
	 * 
	 */
	protected function copyAllNewerFiles($source, $target, $recursive = true) {
		
		$source = rtrim($source, '/') . '/';
		$target = rtrim($target, '/') . '/';
	
		// don't perform full copies of some directories
		// @todo convert this to use the user definable exclusions list
		if($source === $this->wire('config')->paths->site) return 0;
		if($source === $this->wire('config')->paths->siteModules) return 0;
		if($source === $this->wire('config')->paths->templates) return 0;
		
		if(!is_dir($target)) $this->wire('files')->mkdir($target, true);
		
		$dir = new \DirectoryIterator($source);
		$numCopied = 0;
		
		foreach($dir as $file) {
			
			if($file->isDot()) continue;
			
			$sourceFile = $file->getPathname();
			$targetFile = $target . $file->getBasename();
			
			if($file->isDir()) {
				if($recursive) {
					$numCopied += $this->copyAllNewerFiles($sourceFile, $targetFile, $recursive);
				}
				continue;
			}
			
			$ext = strtolower($file->getExtension());
			if(!in_array($ext, $this->extensions)) continue;
			
			if(is_file($targetFile)) {
				if(filemtime($targetFile) >= filemtime($sourceFile)) {
					$numCopied++;
					continue;
				}
			}
			
			copy($sourceFile, $targetFile);
			$this->wire('files')->chmod($targetFile);
			touch($targetFile, filemtime($sourceFile));
			$numCopied++;
		}
		
		if(!$numCopied) {
			$this->wire('files')->rmdir($target, true);
		}
		
		return $numCopied;
	}

	/**
	 * Get a count of how many files are in the cache
	 * 
	 * @param bool $all Specify true to get a count for all file compiler caches
	 * @param string $targetPath for internal recursion use, public calls should omit this
	 * @return int
	 * 
	 */
	public function getNumCacheFiles($all = false, $targetPath = null) {
		
		if(!is_null($targetPath)) {
			// use it
		} else if($all) {
			$targetPath = $this->wire('config')->paths->cache . $this->className() . '/';
		} else {
			$this->init();
			$targetPath = $this->targetPath;
		}
		
		if(!is_dir($targetPath)) return 0;
		
		$numFiles = 0;
		
		foreach(new \DirectoryIterator($targetPath) as $file) {
			if($file->isDot()) continue;
			if($file->isDir()) {
				$numFiles += $this->getNumCacheFiles($all, $file->getPathname());
			} else {
				$numFiles++;
			}
		}
	
		return $numFiles;
	}

	/**
	 * Clear all file compiler caches
	 * 
	 * @param bool $all Specify true to clear for all FileCompiler caches
	 * @return bool
	 * 
	 */
	public function clearCache($all = false) {
		if($all) {
			$targetPath = $this->wire('config')->paths->cache . $this->className() . '/';
			$this->wire('cache')->deleteFor($this);
		} else {
			$this->init();
			$targetPath = $this->targetPath;
		}
		if(!is_dir($targetPath)) return true;
		return $this->wire('files')->rmdir($targetPath, true);
	}

	/**
	 * Run maintenance on the FileCompiler cache
	 * 
	 * This should be called at the end of each request. 
	 * 
	 * @param int $interval Number of seconds between maintenance runs (default=86400)
	 * @return bool Whether or not it was necessary to run maintenance
	 * 
	 */
	public function maintenance($interval = 86400) {
		
		$this->init();
		$this->initTargetPath();
		$lastRunFile = $this->targetPath . 'maint.last';
		if(file_exists($lastRunFile) && filemtime($lastRunFile) > time() - $interval) {
			// maintenance already run today
			return false;
		}
		touch($lastRunFile);
		$this->wire('files')->chmod($lastRunFile);
		clearstatcache();

		return $this->_maintenance($this->sourcePath, $this->targetPath);
	}

	/**
	 * Implementation for maintenance on a given path
	 * 
	 * Logs maintenance actions to logs/file-compiler.txt
	 * 
	 * @param $sourcePath
	 * @param $targetPath
	 * @return bool
	 * 
	 */
	protected function _maintenance($sourcePath, $targetPath) {

		$sourcePath = rtrim($sourcePath, '/') . '/';
		$targetPath = rtrim($targetPath, '/') . '/';
		$sourceURL = str_replace($this->wire('config')->paths->root, '/', $sourcePath);
		$targetURL = str_replace($this->wire('config')->paths->root, '/', $targetPath);
		
		//$this->log("Running maintenance for $targetURL (source: $sourceURL)");
	
		if(!is_dir($targetPath)) return false;
		$dir = new \DirectoryIterator($targetPath);

		foreach($dir as $file) {

			if($file->isDot()) continue;
			$basename = $file->getBasename();
			if($basename == 'maint.last') continue; 
			$targetFile = $file->getPathname();
			$sourceFile = $sourcePath . $basename;

			if($file->isDir()) {
				if(!is_dir($sourceFile)) {
					$this->wire('files')->rmdir($targetFile, true);
					$this->log("Remove directory: $targetURL$basename");
				} else {
					$this->_maintenance($sourceFile, $targetFile);
				}
				continue;
			}

			if(!file_exists($sourceFile)) {
				// source file has been deleted
				unlink($targetFile);
				$this->log("Remove target file: $targetURL$basename");
				
			} else if(filemtime($sourceFile) != filemtime($targetFile)) {
				// source file has changed
				copy($sourceFile, $targetFile);
				$this->wire('files')->chmod($targetFile);
				touch($targetFile, filemtime($sourceFile));
				$this->log("Copy new version of source file to target file: $sourceURL$basename => $targetURL$basename");
			}
		}
	
		return true; 
	}

	/**
	 * Given an array of $options convert to an PHP-code array() string
	 * 
	 * @param array $options
	 * @return string
	 * 
	 */
	protected function optionsToString(array $options) {
		$str = "array(";
		foreach($options as $key => $value) {
			if(is_bool($value)) {
				$value = $value ? "true" : "false";
			} else if(is_string($value)) {
				$value = '"' . str_replace('"', '\\"', $value) . '"';
			} else if(is_array($value)) {
				if(count($value)) {
					$value = "array('" . implode("',", $value) . "')";
				} else {
					$value = "array()";
				}
			}
			$str .= "'$key'=>$value,";
		}
		$str = rtrim($str, ",") . ")";
		return $str;
	}
	
	/**
	 * Exclude a file or path from compilation
	 *
	 * @param string $pathname
	 *
	 */
	public function addExclusion($pathname) {
		$this->exclusions[] = $pathname;
	}

}

