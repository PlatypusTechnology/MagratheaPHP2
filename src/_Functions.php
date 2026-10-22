<?php
namespace Magrathea2;

use Magrathea2\DB\Database;

#######################################################################################
####
####    MAGRATHEA PHP2
####    v. 2.0
####    Magrathea by Paulo Henrique Martins
####    Platypus technology
####	Magrathea1 created: 2012-12 by Paulo Martins
####    Magrathea2 created: 2022-11 by Paulo Martins
####
#######################################################################################

/**
 * Date of now - mySQL format
 * @return 	string 		Y-m-d H:i:s format of date
 */
function now(): string {
	return date("Y-m-d H:i:s");
}

function arrToStr($array): string {
	$result = [];
	foreach ($array as $key => $value) {
		$result[] = "[".$key."] = \"".$value."\"";
	}
	return implode(', ', $result);	
}

//.$trace[0]["file"].":".$trace[0]["line"]."\n"
/**
 * Prints easily and beautifully
 * @param 	object|array|string|int		 		$debugme	Object to be printed
 * @param  	boolean  											$beautyme	How beautifull do you want your object printed?
 */
function p_r($debugme){
	echo "<pre>";
	print_r($debugme);
	echo "</pre>";
}

/**
 * Gets an object and returns if it is a Magrathea Model
 */
function isMagratheaModel($object): bool {
	return is_a($object, "\Magrathea2\MagratheaModel");
}

/**
 * As asked to ChatGPT:
 * a function that gets a full class name, such as MagratheaContacts\Users\AdminUsers and return just the last part AdminUsers
 * @param string $fullClassName		full class name
 * @return 	string								class name
 */
function getClassNameOfClass($fullClassName) {
	$lastBackslash = strrpos($fullClassName, '\\');
	if ($lastBackslash === false) {
		return $fullClassName;
	}
	return substr($fullClassName, $lastBackslash + 1);
}

/**
 * Array of types available at Magrathea
 * @return  	array
 */
function magrathea_getTypesArr() : array{
	$types = array("int", "boolean", "string", "text", "float", "decimal", "datetime", "date");
	return $types;
}

/**
 * Function that will be executed after script is complete!
 * in Magrathea, will print debug, if available...
 * 	@todo  print debug in a beautifull way in the end of the page...
 */
register_shutdown_function(function(){
	Debugger::Instance()->ShowAtShutdown();
});

/**
 * Checks if a php file declares a namespace
 * @param 	string 	$file 			php file
 * @param 	string 	$namespace 	namespace ("" for the global one)
 * @return 	bool
 */
function fileDeclaresNamespace(string $file, string $namespace): bool {
	$code = @file_get_contents($file);
	if($code === false) return false;
	preg_match_all('/^\s*namespace\s+([A-Za-z0-9_\\\\]+)\s*[;{]/m', $code, $matches);
	if(empty($matches[1])) return $namespace === "";
	foreach ($matches[1] as $declared) {
		if(strcasecmp($declared, $namespace) == 0) return true;
	}
	return false;
}

/**
 * Autoload classes
 * Looks for <class basename>.php in every code folder.
 * If more than one folder has it, loads the file that declares the class' namespace (or the first one, if none does)
 */
spl_autoload_register(function ($class) {
	$folders = MagratheaPHP::Instance()->codeFolder;
	$class_name = getClassNameOfClass($class);
	if($class_name == "ClassLoader") return;
	$files = [];
	foreach ($folders as $dir) {
		$file = $dir."/".$class_name.'.php';
		if (!file_exists($file)) continue;
		// the same folder can be added twice (AddFeature + AddRootCodeFolder)
		$files[realpath($file)] ??= $file;
	}
	if (count($files) == 1) {
		require_once (reset($files));
		return;
	}
	if (count($files) > 1) {
		$lastBackslash = strrpos($class, '\\');
		$namespace = $lastBackslash === false ? "" : substr($class, 0, $lastBackslash);
		$load = reset($files);
		foreach ($files as $file) {
			if (fileDeclaresNamespace($file, $namespace)) {
				$load = $file;
				break;
			}
		}
		if (MagratheaPHP::Instance()->IsDev()) {
			Debugger::Instance()->Add("Autoloader: class [".$class."] has more than one file in the code folders [".implode(", ", $files)."]; loaded [".$load."]");
		}
		require_once ($load);
		return;
	}
	// not found: return, so class_exists() and the next autoloaders work
	$appNamespace = MagratheaPHP::Instance()->appNamespace;
	if ($appNamespace !== null && str_starts_with($class, $appNamespace . '\\') && MagratheaPHP::Instance()->IsDev()) {
		// a string: a MagratheaException would add itself to the Debugger (and the log) in every mode
		Debugger::Instance()->Add("Could not find class [".$class."]. Are the code folders correct? [".implode(", ", $folders)."]");
	}
});

