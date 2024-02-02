<?php

namespace Magrathea2\Tests;

use function Magrathea2\p_r;
use Magrathea2\MagratheaPHP;
use Exception;

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

	public function Initialize() {
		MagratheaPHP::LoadVendor();
		$this->AddMagrathaTests();
	}

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
		$pharCommand = "php ".$pharFolder."/phpunit.phar";
		if($debug) {
			$pharCommand .= " --testdox";
		}
		$pharCommand .= " --bootstrap ".__DIR__."/phpUnitBootstrap.php";
		$pharCommand .= $this->GetRunExtras();
		return $pharCommand;
	}

	public function GetRunExtras(): string {
		return " --display-deprecations --display-warnings --display-notices";
	}

	private function AddMagrathaTests() {
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
	 * Gets the array of tests
	 * @return 		array 		$tests
	 */
	public function GetTests(): array {
		return $this->tests;
	}

	/**
	 * Run all the tests
	 */
	public function RunAllTests() {
	}

	/**
	 * Run all the tests from a specific folder
	 * @param 	string 		$folder		full path of folder
	 * @param 	boolean		$debug		is this a full debug? (TODO)
	 * @return 	array			response
	 */
	public function RunFolder($folder, $debug=false): array {
		$response = [];
		$command = $this->GetPhar($debug)." ".$folder;
		if($debug) {
//			$command .= " --debug";
		}
		array_push($response, ">> $ phpunit.phar .../".self::TestFolderPrint($folder));
		exec($command, $response);
		return $response;
	}

	/**
	 * Runs a specific test file
	 * @param 	string 		$file			full path of file
	 * @param 	boolean		$debug		is this a full debug? (TODO)
	 * @return 	array			response
	 */
	public function RunFile($file, $debug=true): array {
		$response = [];
		$command = $this->GetPhar($debug)." ".$file;
		if ($debug) {
			$debugCommand = ">> $ ".$command;
		} else {
			$debugCommand = ">> $ phpunit.phar .../".self::TestFolderPrint($file);
		}
		array_push($response, $debugCommand);
		exec($command, $response);
		return $response;
	}

	/**
	 * Runs a specific test function from a file
	 * @param 	string 		$file			full path of file
	 * @param 	string 		$fn				full name of function
	 * @param 	boolean		$debug		is this a full debug? (TODO)
	 * @return 	array			response
	 */
	public function RunFunction($file, $fn, $debug=true): array {
		$response = [];
		$command = $this->GetPhar($debug);
		$command .= " --filter ".$fn." ".$file;
		if ($debug) {
			$debugCommand = ">> $ ".$command;
		} else {
			$debugCommand = ">> $ phpunit.phar .../".self::TestFolderPrint($file);
		}
		array_push($response, $debugCommand);
		exec($command, $response);
		return $response;
	}

	/**
	 * Gets a folder and returns the available test files
	 * @param 	string 		$folder
	 * @return 	array
	 */
	public function GetTestsFromFolder($folder): array {
		if(!is_dir($folder)) {
			throw new Exception("Invalid Folder!");
		}
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
	 * @param 		string  	$fileName
	 * @return 		array
	 */
	public function GetMethodsFromTest($fileName): array {
		$exclude = ["setUp", "tearDown"];
		$contents = file_get_contents($fileName);

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
