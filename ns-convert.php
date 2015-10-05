<?php

/*
 * spl_autoload_register('ProcessWire\ProcessWireClassLoader'); autoload.php
 * register_shutdown_function('ProcessWire\ProcessWireShutdown'); shutdown.php
 * 
 * $className = str_replace('ProcessWire\\', '', $className); add this to autoload.php
 * 
 * Exclude textformatters: markdown/parsedown
 * 
 * Add this to functions.php:
 * 
 * function wireClassExists($className, $autoload = true, $namespace = 'ProcessWire') {
 *    if(strpos($className, "\\") === false) $className = "$namespace\\$className";
 *    return class_exists($className, $autoload);
 * }
 * 
 * MANUAL UPDATES
 * ==============
 * 1. is_callable($), call_user_func, class_exists($), new $className, method_exists(), function_exists(), class_implements(), class_parents()
 * 2. all get_class() calls will include the ProcessWire namespace in them, so must be updated. 
 * 3. Add define("PROCESSWIRE_NAMESPACE", "ProcessWire"); to top of core/ProcessWire.php
 */

define("DEBUG", true);
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1);

function findFiles($path, $exts = array('php', 'module', 'inc'), $skipBasenames = array()) {
	
	$files = array();
	
	foreach(new DirectoryIterator($path) as $file) {
		if($file->isDot()) continue;
		
		$basename = $file->getBasename();
		if(strpos($basename, '.') === 0) continue; // skip hidden
		
		$skip = false;
		foreach($skipBasenames as $skipName) {
			if(strpos($skipName, '/') === 0) {
				if(preg_match($skipName, $basename)) $skip = true;
			} else {
				if($basename == $skipName) $skip = true;
			}
		}
		if($skip) continue; 
		
		if($file->isDir()) {
			$files = array_merge($files, findFiles($file->getPathname(), $exts, $skipBasenames));
			continue;
		}
		
		foreach($exts as $ext) {
			if($file->getExtension() == $ext) {
				$files[] = $file->getPathname();
				break;
			}
		}
	}
	return $files;
}

function updateFile($file) {
	
	$data = file_get_contents($file);
	$classes = get_declared_classes();
	// $interfaces = get_declared_interfaces();
	echo "$file\n";
	
	if(strpos($data, 'namespace') && preg_match('/^(?:<\?php)?\s*namespace\s+([^;]+);/m', $data, $matches)) {
		// already has namespace
		echo "\tAlready has namespace: $matches[1]\n";
		
	} else {
		if(strpos($data, '<?php') === 0) {
			$data = preg_replace('!^<\?php!', '<' . '?php namespace ProcessWire;', $data);
			echo "\tAdded namespace\n";
		} else {
			// php tag not at beginning of file
			$data = "<" . "?php namespace ProcessWire;\n?" . ">" . $data;
		}
	}

	// class interfaces
	if(strpos($data, ' implements ') && preg_match_all('!\s*(class\s+.+?) implements ([^{]+)\s*{!s', $data, $matches)) {
		foreach($matches[2] as $key => $implements) {
			$_implements = $implements;
			$implementsArray = explode(',', trim($implements)); 
			foreach($implementsArray as $interface) {
				$interface = trim($interface);
				if(strpos($interface, '\\') !== false) continue;
				if(interface_exists($interface)) {
					$implements = str_replace($interface, "\\$interface", $implements);
				}
			}
			if($implements != $_implements) {
				$data = str_replace(" implements $_implements", " implements $implements", $data);
				echo "\tUpdated implements: $_implements => $implements\n";
			}
		}
	}
	
	// classes
	foreach($classes as $class) {
		if(strpos($data, "new $class") !== false) {
			$data = str_replace("new $class(", "new \\$class(", $data);
			echo "\tUpdated 'new $class' => 'new \\$class'\n";
			// echo htmlentities($data);
		}
		if(strpos($data, " extends $class ") !== false) {
			$data = str_replace(" extends $class ", " extends \\$class ", $data);
			echo "\tUpdated 'extends $class' => 'extends \\$class'\n";
		}
	}

	// catch Exception
	if(strpos($data, '(Exception ') !== false) {
		$data = str_replace('(Exception ', '(\Exception ', $data);
		echo "\tUpdated 'Exception' => '\\Exception'\n";
	}

	// PDO constants
	if(strpos($data, '(PDO::') || strpos($data, ' PDO::')) {
		$data = str_replace(' PDO::', ' \\PDO::', $data);
		$data = str_replace('(PDO::', '(\\PDO::', $data);
		echo "\tUpdated 'PDO::' => '\\PDO::'\n";
	}
	if(strpos($data, '(PDOStatement') || strpos($data, ' PDOStatement')) {
		$data = str_replace(array('(PDOStatement', ' PDOStatement'), array('(\\PDOStatement', ' \\PDOStatement'), $data);
	}

	/*
	// class_exists with variable argument
	if(strpos($data, 'class_exists($')) {
		$data = str_replace('class_exists($', 'class_exists("ProcessWire\\\\" . $', $data);
		echo "\tUpdated 'class_exists($[className])' => 'class_exists(\"ProcessWire\\\" . $[className])'\n";
	}

	// new $className
	if(strpos($data, 'new $')) {
		$data = preg_replace('/(\s+new)\s+\$/', '$1 "ProcessWire\\\\" . $', $data);
		echo "\tUpdated 'new $[className]' => 'new \"ProcessWire\\\" . $[className]'\n";
	}
	*/

	if(!DEBUG) {
		$fp = fopen($file, "w");
		fwrite($fp, $data);
		fclose($fp);
	}
}

/***************************************************************************/

$skipBasenames = array(
	'parsedown',
	'parsedown-extra',
	'markdown.php',
	'htmlpurifier',
	'/^ckeditor-\.*/', 
);

$extensions = array(
	'php',
	'module',
	'inc',
);

$path = dirname(__FILE__) . '/';
$files = array($path . 'index.php');
$files = array_merge($files, findFiles($path . 'wire/', $extensions, $skipBasenames));

echo "<pre>";

foreach($files as $file) {
	updateFile($file);
}

//echo implode("\n", findFiles($wirePath));

