<?php namespace ProcessWire;

/**
 * Class CompiledFile
 *
 * @todo maintenance of compiled files, removal of ones no longer in use, etc.
 * @todo make storage in dedicated table rather than using wire('cache').
 * @todo handle race conditions
 * 
 */

class CompiledFile extends Wire {
	
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
	 * Also compile files include'd from source file?
	 * 
	 * @var bool
	 * 
	 */
	protected $compileIncludes = true;

	/**
	 * Compile to update for ProcessWire classes, interfaces and functin calls?
	 *
	 * @var bool
	 *
	 */
	protected $compileNamespace = true;

	/**
	 * Construct
	 * 
	 * @param $compiledPath Path where files will be compiled to
	 * @throws WireException if compiledPath doesn't exist and can't be created
	 * 
	 */
	public function __construct($sourcePath, $targetPath = null) {
		if(strpos($sourcePath, '..') !== false) $sourcePath = realpath($sourcePath);
		$this->sourcePath = rtrim($sourcePath, '/') . '/';
		$this->targetPath = $targetPath;
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
		if(empty($this->targetPath)) {
			$targetPath = $this->wire('config')->paths->cache . $this->className() . '/';
			if(strpos($this->sourcePath, $targetPath) === 0) {
				// sourcePath is inside the targetPath, correct this 
				$this->sourcePath = str_replace($targetPath, '', $this->sourcePath);
				$this->sourcePath = $this->wire('config')->paths->root . str_replace('_.', '/', $this->sourcePath);
			}
			$t = str_replace($this->wire('config')->paths->root, '', $this->sourcePath);
			//$t = str_replace(array('/', '\\'), '_.', trim($t, '/\\'));
			$this->targetPath = $targetPath . trim($t, '/') . '/';
		} else {
			$this->targetPath = rtrim($this->targetPath, '/') . '/';
		}
		if(!is_dir($this->targetPath)) {
			if(!$this->wire('files')->mkdir($this->targetPath, true)) {
				throw new WireException("Unable to create directory $this->targetPath");
			}
		}
		set_time_limit(120);
	}

	/**
	 * Compile given source file and return compiled destination file
	 * 
	 * @param $sourceFile Source file to compile
	 * @return string Full path and filename of compiledfile
	 * @throws WireException if given invalid sourceFile
	 * 
	 */
	public function ___compile($sourceFile) {
		
		if(strpos($this->sourcePath, $this->wire('config')->paths->wire) === 0) {
			// don't compile core stuff
			if(strpos($this->sourcePath, $sourceFile) === 0) return $sourceFile;
			return $this->sourcePath . ltrim($sourceFile, '/');
		}
		
		$this->init();
		
		if(strpos($sourceFile, $this->sourcePath) === 0) {
			$sourcePathname = $sourceFile;
			$sourceFile = str_replace($this->sourcePath, '/', $sourceFile);
		} else {
			$sourcePathname = $this->sourcePath . ltrim($sourceFile, '/');
		}
		
		if(!is_file($sourcePathname)) throw new WireException("$sourcePathname does not exist");
	
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
			$this->copyAllNewerFiles(dirname($sourcePathname), dirname($targetPathname)); 
			$targetDirname = dirname($targetPathname) . '/';
			if(!is_dir($targetDirname)) $this->wire('files')->mkdir($targetDirname, true);
			$targetData = $this->compileData(file_get_contents($sourcePathname), $sourcePathname, $targetPathname);
			if(false !== file_put_contents($targetPathname, $targetData, LOCK_EX)) {
				$this->wire('files')->chmod($targetPathname);	
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
		}
	
		return $targetPathname;
	}
	
	/**
	 * Compile the given string of data
	 * 
	 * @param string $data
	 * @param string $sourceFile
	 * @param string $targetFile
	 * @return string
	 * 
	 */
	protected function ___compileData($data, $sourceFile, $targetFile) {
		if($this->compileIncludes) $this->compileIncludes($data, $sourceFile, $targetFile);
		if($this->compileNamespace) $this->compileNamespace($data, $sourceFile, $targetFile);
		return $data;
	}

	/**
	 * Compile include(), require() (and variations) to refer to compiled files where possible
	 * 
	 * @param string $data
	 * @param string $sourceFile
	 * @param string $targetFile
	 * @return string Data updated with includes referring to compiled files
	 * 
	 */
	protected function compileIncludes(&$data, $sourceFile, $targetFile) {
		
		// other related to includes
		if(strpos($data, '__DIR__') !== false) {
			$data = str_replace('__DIR__', "'" . dirname($sourceFile) . "'", $data);
		}
		if(strpos($data, '__FILE__') !== false) {
			$data = str_replace('__FILE__', "'" . $sourceFile . "'", $data);
		}

		// main include regex
		$re = '/^' . 
			'(.*?)' . // 1: open
			'(include_once|include|require_once|require|wireIncludeFile|wireRenderFile)' . // 2:function
			'[\(\s]+' . // open parenthesis and/or space
			'(["\']?[^;\r\n]+);' . // 3:filename (may be quoted or end with closing parenthesis)
			'/m';
		
		if(!preg_match_all($re, $data, $matches)) return $data;
		
		foreach($matches[0] as $key => $fullMatch) {
		
			$open = $matches[1][$key];
			
			if(strlen($open)) {
				if(strpos($open, '"') !== false || strpos($open, "'") !== false) {
					// skip when words like "require" are in a string
					continue;
				}

				if(preg_match('/^[_a-zA-Z0-9]+$/', substr($open, -1))) {
					// skip things like: something_include(...
					continue;
				}
			}
				
			$funcMatch = $matches[2][$key];
			$fileMatch = $matches[3][$key];
			
			if(strpos($fileMatch, './') === 1) {
				// relative to current dir, convert to absolute
				$fileMatch = $fileMatch[0] . dirname($sourceFile) . substr($fileMatch, 2);
			}
		
			if(substr($fileMatch, -1) == ')') $fileMatch = substr($fileMatch, 0, -1);
			$fileMatch = str_replace(array(' ', "\t"), '', $fileMatch);
			$targetPath = dirname($targetFile);
			$newFullMatch = $open . ' ' . $funcMatch . "(\\ProcessWire\\wire('files')->compile($fileMatch));";
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
	 * @param string $sourceFile
	 * @param string $targetFile
	 * @return string
	 * 
	 */
	protected function compileNamespace(&$data, $sourceFile, $targetFile) {
		
		// first check if data already defines a namespace, in which case we'll skip over it
		$pos = strpos($data, 'namespace');
		if($pos !== false) { 
			if(preg_match('/(^.*)\s+namespace\s+[_a-zA-Z0-9\\\\]+\s*;/m', $data, $matches)) {
				if(strpos($matches[1], '//') === false && strpos($matches[1], '/*') === false) {
					// namespace already present, no need for namespace compilation
					return $data;
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
			list($ns, $class) = explode('\\', $class, 2); // reduce to just class without namespace
			if(strpos($data, $class) === false) continue; // quick exit if class name not referenced in data
			
			$patterns = array(
				// 1=open 2=close
				// all patterns match within 1 line only
				"new" => '(new\s+)' . $class . '\s*(\(|;)',  // 'new Page(' or 'new Page;'
				"function" => '([_a-zA-Z0-9]+\s*\([^)]*?)\b' . $class . '(\s+\$[_a-zA-Z0-9]+)', // 'function(Page $page' or 'function($a, Page $page'
				"::" => '(^|[^_a-zA-Z0-9"\'])' . $class . '(::)', // constant ' Page::foo' or '(Page::foo' or '=Page::foo' or bitwise open
				"extends" => '(\sextends\s+)' . $class . '(\s|\{|$)', // 'extends Page'
				"implements" => '(\simplements[^{]*?[\s,]+)' . $class . '([^_a-zA-Z0-9]|$)', // 'implements Module' or 'implements Foo, Module'
				"instanceof" => '(\sinstanceof\s+)' . $class . '([^_a-zA-Z0-9]|$)', // 'instanceof Page'
				"$class " => '(\(\s*|,\s*)' . $class . '(\s+\$)', // type hinted '(Page $something' or '($foo, Page $something'
			);
			
			foreach($patterns as $check => $regex) {
				
				if(strpos($data, $check) === false) continue;
				if(!preg_match_all('/' . $regex . '/m', $data, $matches)) continue;
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
			list($ns, $function) = explode('\\', $function, 2); // reduce to just function name
			if(stripos($data, $function) === false) continue; // if function name not mentioned in data, quick exit
		
			$n = 0;
			while(preg_match_all('/^(.*?[(!;\[=\s.])' . $function . '\s*\(/im', $data, $matches)) {
				foreach($matches[0] as $key => $fullMatch) {
					$open = $matches[1][$key];
					if(strpos($open, 'function') !== false) continue; // skip function defined with same name
					$functionName = '\\' . __NAMESPACE__ . '\\' . $function;
					$data = str_replace($fullMatch, $open . $functionName . '(', $data);
				}
				if(++$n > 5) break;
			}
		}
	}

	/**
	 * Recursively copy all files from $source to $target, but only if $source file is $newer
	 * 
	 * @param $source
	 * @param $target
	 * 
	 */
	protected function copyAllNewerFiles($source, $target, $recursive = true) {
		
		$source = rtrim($source, '/') . '/';
		$target = rtrim($target, '/') . '/';
	
		// don't perform full copies of some directories
		if($source === $this->wire('config')->paths->site) return;
		if($source === $this->wire('config')->paths->siteModules) return;
		if($source === $this->wire('config')->paths->templates) return;
		
		if(!is_dir($target)) $this->wire('files')->mkdir($target, true);
		
		$dir = new \DirectoryIterator($source);
		
		foreach($dir as $file) {
			
			if($file->isDot()) continue;
			
			$sourceFile = $file->getPathname();
			$targetFile = $target . $file->getBasename();
			
			if($file->isDir() && $recursive) {
				$this->copyAllNewerFiles($sourceFile, $targetFile, $recursive);
				continue;
			}
			
			if(is_file($targetFile)) {
				if(filemtime($targetFile) >= filemtime($sourceFile)) continue;
			}
			
			copy($sourceFile, $targetFile);
			$this->wire('files')->chmod($targetFile);
		}
	}
}

