<?php

namespace Magrathea2\Tests;

use function Magrathea2\p_r;
use Magrathea2\Exceptions\MagratheaException;

#######################################################################################
####
####    MAGRATHEA PHP2
####    v. 2.0
####    Magrathea by Paulo Henrique Martins
####    Platypus technology
####    Tests Manager created: 2023-03 by Paulo Martins
####
#######################################################################################

/**
 * Class for handling and code generations
 */
class TestsManager extends \Magrathea2\Singleton { 

	/**
	 * tests format:
	 * [ "name", "folders", "success", "result" ]
	 */
	private $tests = [];
	private $phpunit;

	public static function TestFolderPrint($path) {
		$folders = explode('/', $path);
		$lastTwo = array_slice($folders, -2);
		return implode('/', $lastTwo);
	}

	/**
	 * Gets a string with the location of the PHPUnit phar for running test
	 * @param boolean $debug	should include debug param
	 * @return string 		phar code to be executed
	 */
	private function GetPhar($debug=false): string {
		$pharFolder = realpath(__DIR__."/../../");
		$pharCommand = "php -d display_errors=on ".escapeshellarg($pharFolder."/phpunit.phar");
		if($debug) {
			$pharCommand .= " --testdox";
		}
		$pharCommand .= " --bootstrap ".escapeshellarg(__DIR__."/phpUnitBootstrap.php");
		$pharCommand .= $this->GetRunExtras();
		return $pharCommand;
	}

	public function GetCacheDestination(): string {
		$destination = realpath(__DIR__."/../../../cache/")."/.phpunit.result.cache";
		return "--cache-result-file=".escapeshellarg($destination);
	}

	public function GetRunExtras(): string {
		return " --display-deprecations --display-warnings --display-notices --display-errors ".$this->GetCacheDestination();
	}

	public function AddMagrathaTests() {
		$thisFolder = __DIR__;
		array_push($this->tests, [
			'name' => "Magrathea Tests",
			'folders' => [$thisFolder."/MagratheaTests"]
		], [
			'name' => "Magrathea Admin",
			'folders' => [$thisFolder."/MagratheaAdminTests"]
		]);
	}

	/**
	 * Add custom test
	 * @param 	array		$folders 	folders
	 * @param 	string 	$name			name (default: the first folder's name)
	 * @return 	TestsManager  		itself
	 */
	public function AddTest(array $folders, $name=null): TestsManager {
		if(empty($name)) {
			$name = basename(rtrim($folders[0] ?? "", "/"));
		}
		array_push($this->tests, [
			'name' => $name,
			'folders' => $folders,
		]);
		return $this;
	}

	/**
	 * Gets the array of tests
	 * @return 		array 		$tests
	 */
	public function GetTests(): array {
		return $this->tests;
	}

	/**
	 * Run all the tests: every folder of every registered test, in the order they were added
	 * @param 	boolean		$debug		is this a full debug? (TODO)
	 * @return 	array			response (every folder's response, one after the other)
	 */
	public function RunAllTests($debug=false): array {
		$response = [];
		foreach($this->tests as $test) {
			foreach($test["folders"] as $folder) {
				array_push($response, ...$this->RunFolder($folder, $debug));
			}
		}
		return $response;
	}

	/**
	 * Gets the real path of a path inside a registered test folder (or of the folder itself).
	 * Anything else (`../`, a symlink pointing out, a missing path) throws, so the admin's Tests page
	 * can't run or read files outside the test folders.
	 * @param 	string 		$path		path to check
	 * @return 	string		real path
	 * @throws 	MagratheaException	invalid test path
	 */
	private function GetPathInTestFolders($path): string {
		$realPath = (is_string($path) && $path !== "" && !str_contains($path, "\0")) ? realpath($path) : false;
		if($realPath) {
			foreach($this->tests as $test) {
				foreach($test["folders"] as $folder) {
					$root = realpath($folder);
					if(!$root) continue;
					if($realPath === $root || str_starts_with($realPath, rtrim($root, "/")."/")) return $realPath;
				}
			}
		}
		throw new MagratheaException("invalid test path");
	}
	/**
	 * Gets the real path of a registered test folder, or of a folder inside one
	 * @param 	string 		$folder		folder path
	 * @return 	string		real path
	 * @throws 	MagratheaException	invalid test path
	 */
	private function GetTestFolder($folder): string {
		$path = $this->GetPathInTestFolders($folder);
		if(!is_dir($path)) throw new MagratheaException("invalid test path");
		return $path;
	}
	/**
	 * Gets the real path of a test file: a `*Test.php` file inside a registered test folder
	 * @param 	string 		$file		file path
	 * @return 	string		real path
	 * @throws 	MagratheaException	invalid test path
	 */
	private function GetTestFile($file): string {
		$path = $this->GetPathInTestFolders($file);
		if(!is_file($path) || substr($path, -8) !== 'Test.php') throw new MagratheaException("invalid test path");
		return $path;
	}

	/**
	 * Runs a shell command
	 * @param 	string 		$command	command, arguments already escaped
	 * @return 	array			output lines
	 */
	protected function Execute(string $command): array {
		$output = [];
		exec($command, $output);
		return $output;
	}

	/**
	 * Run all the tests from a specific folder
	 * @param 	string 		$folder		full path of folder: a registered test folder, or a folder inside one
	 * @param 	boolean		$debug		is this a full debug? (TODO)
	 * @return 	array			response
	 * @throws 	MagratheaException	invalid test path
	 */
	public function RunFolder($folder, $debug=false): array {
		$folder = $this->GetTestFolder($folder);
		$command = $this->GetPhar($debug)." ".escapeshellarg($folder);
		if($debug) {
//			$command .= " --debug";
		}
		return [">> $ ".$command, ...$this->Execute($command)];
	}

	/**
	 * Runs a specific test file
	 * @param 	string 		$file			full path of file: a `*Test.php` file inside a registered test folder
	 * @param 	boolean		$debug		is this a full debug? (TODO)
	 * @return 	array			response
	 * @throws 	MagratheaException	invalid test path
	 */
	public function RunFile($file, $debug=true): array {
		$file = $this->GetTestFile($file);
		$command = $this->GetPhar($debug)." ".escapeshellarg($file);
		return [">> $ ".$command, ...$this->Execute($command)];
	}

	/**
	 * Runs a specific test function from a file
	 * @param 	string 		$file			full path of file: a `*Test.php` file inside a registered test folder
	 * @param 	string 		$fn				full name of function (letters, digits and `_` only)
	 * @param 	boolean		$debug		is this a full debug? (TODO)
	 * @return 	array			response
	 * @throws 	MagratheaException	invalid test path or function name
	 */
	public function RunFunction($file, $fn, $debug=true): array {
		$file = $this->GetTestFile($file);
		if(!is_string($fn) || !preg_match('/^\w+$/', $fn)) {
			throw new MagratheaException("invalid test function");
		}
		$command = $this->GetPhar($debug);
		$command .= " --filter ".escapeshellarg($fn)." ".escapeshellarg($file);
		if ($debug) {
			$debugCommand = ">> $ ".$command;
		} else {
			$debugCommand = ">> $ phpunit.phar .../".self::TestFolderPrint($file);
		}
		return [$debugCommand, ...$this->Execute($command)];
	}

	/**
	 * Gets a folder and returns the available test files
	 * @param 	string 		$folder		a registered test folder, or a folder inside one
	 * @return 	array
	 * @throws 	MagratheaException	invalid test path
	 */
	public function GetTestsFromFolder($folder): array {
		$folder = $this->GetTestFolder($folder);
		$dirData = scandir($folder);
		$files = array();
		foreach ($dirData as $d) {
			if ($d === '.' or $d === '..') continue;
			array_push($files, $d);
		}
		$files = array_filter($files, function($file) {
			return substr($file, -8) === 'Test.php';
		});
		return $files;
	}
	/**
	 * Gets a file name and return the avaiable methods
	 * @param 		string  	$fileName		a `*Test.php` file inside a registered test folder
	 * @return 		array
	 * @throws 		MagratheaException	invalid test path
	 */
	public function GetMethodsFromTest($fileName): array {
		$exclude = ["setUp", "tearDown"];
		$contents = file_get_contents($this->GetTestFile($fileName));

		// Define the regular expression pattern
		$pattern = '/function\s+(\w+)\s*\(/';

		// Match the pattern against the contents of the file
		preg_match_all($pattern, $contents, $matches);
		$functions = array_diff($matches[1], $exclude);
		$functions = preg_grep('/^test/', $functions);

		// Return the array of function names
		return $functions;
	}



	public function RunTestSuite() {
		$file = __DIR__."/MagratheaTests/coreTest.php";

		$conf = $this->createConfiguration();
		$suiteBuilder = new \PHPUnit\TextUI\Configuration\TestSuiteBuilder();
		$suite = $suiteBuilder->build($conf);
		$suite->addTestFile($file);

		$cache = $this->getCache();

		$runner = new \PHPUnit\TextUI\TestRunner();
		$runner->run($conf, $cache, $suite);
	}

	private function createConfiguration () {
		$confBuilder = new \PHPUnit\TextUI\Configuration\Builder();
		$conf = $confBuilder->build($_SERVER['argv']);
		return $conf;
	}

	private function getCache() {
		$cache = new \PHPUnit\Runner\ResultCache\NullResultCache();
		return $cache;
	}

}
