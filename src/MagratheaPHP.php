<?php

namespace Magrathea2;

use Exception;
use Magrathea2\DB\Database;
use Magrathea2\Errors\ErrorManager;
use Magrathea2\Exceptions\MagratheaConfigException;
use Magrathea2\Exceptions\MagratheaException;

#######################################################################################
####
####    MAGRATHEA PHP2
####    v. 2.0
####    Magrathea by Paulo Henrique Martins
####    Platypus technology
####    Magrathea2 created: 2022-11 by Paulo Martins
####
#######################################################################################

/**
 * Base class for a Magrathea project.
 * It handles paths, configurations, database connections, and environments.
 */
class MagratheaPHP extends Singleton {

	/** @var string Root of the application. */
	public string $appRoot = "";
	/** @var string Root of the Magrathea Structure (usually one level above appRoot). */
	public string $magRoot = "";
	/** @var array<string> Folders to be included in the autoloader. */
	public array $codeFolder = [];
	/** @var string|null Minimum version of Magrathea required. */
	public ?string $versionRequired = null;

	/**
	 * Sets the application's root path.
	 * Also defines the `magRoot` as the parent directory.
	 * @param string $path Root path of the project.
	 * @return MagratheaPHP
	 */
	public function AppPath(string $path): MagratheaPHP {
		$this->appRoot = $path;
		$this->magRoot = realpath($path."/../");
		return $this;
	}

	/**
	 * Returns the root path of the application.
	 * @return string Application root path.
	 */
	public function GetAppRoot(): string {
		return $this->appRoot;
	}

	/**
	 * Checks if the current Magrathea version meets a minimum requirement.
	 * @param string $version Minimum version required (e.g., "2.1.0").
	 * @param bool   $throwEx If true, throws a MagratheaException if the version is not met.
	 *                        Otherwise, it displays an error message.
	 * @return MagratheaPHP
	 * @throws MagratheaException (code 206) if the version is incompatible and `$throwEx` is true.
	 */
	public function MinVersion(string $version, bool $throwEx = false) {
		$this->versionRequired = $version;
		$magVersion = $this->Version();
		if(!version_compare($magVersion, $version, ">=")) {
			$errorMessage = "Magrathea version outdated [current: ".$magVersion." / required: ".$version."]";
			if($throwEx) throw new MagratheaException($errorMessage, 206);
			ErrorManager::Instance()->DisplayMesage($errorMessage);
		}
		return $this;
	}

	/**
	 * Gets the root directory of the MagratheaPHP library.
	 * @return string The path to the MagratheaPHP source directory.
	 */
	public function GetMagratheaRoot(): string {
		return __DIR__;
	}

	/**
	 * Adds one or more code folders for the autoloader.
	 * @param string ...$folder One or more folder paths to add.
	 * @return MagratheaPHP
	 */
	public function AddCodeFolder(...$folder): MagratheaPHP {
		array_push($this->codeFolder, ...$folder);
		return $this;
	}
	public function AddRootCodeFolder(...$folder): MagratheaPHP {
		return $this->AddCodeFolder(
			...array_map(function($f) { return $this->appRoot."/".$f; }, $folder)
		);
	}

	/**
	 * Adds feature folders based on the application root.
	 * For each feature, it adds `features/[name]` and `features/[name]/Base`.
	 * @param string ...$features One or more feature names.
	 * @return MagratheaPHP
	 */
	public function AddFeature(...$features): MagratheaPHP {
		$root = $this->appRoot;
		foreach($features as $f) {
			$this->AddCodeFolder(
				$root."/features/".$f,
				$root."/features/".$f."/Base"
			);
		}
		return $this;
	}

	/**
	 * Sets the environment to development mode.
	 * Enables error reporting and display.
	 */
	public function Dev(): MagratheaPHP {
		error_reporting(E_ALL);
		ini_set('display_errors', 1);
		ini_set("log_errors", 1);
		Debugger::Instance()->SetDev();
		return $this;
	}

	/**
	 * Enables the debugger.
	 * This will also log database queries.
	 */
	public function Debug(): MagratheaPHP {
		Debugger::Instance()->SetDebug()->LogQueries(true);
		return $this;
	}

	/**
	 * Sets the environment to production mode.
	 * Errors will be sent to the logger instead of being displayed.
	 */
	public function Prod(): MagratheaPHP {
		Debugger::Instance()->SetType(Debugger::LOG);
		return $this;
	}

	/**
	 * Gets the path to the configuration directory.
	 * @return string The path to the `/configs` directory.
	 * @throws MagratheaConfigException If `magRoot` is not set.
	 */
	public function GetConfigRoot() {
		return $this->magRoot."/configs";
	}

	/**
	* Starts Magrathea
	* @return MagratheaPHP
	*/
	public function Load() {
		$configPath = $this->getConfigRoot();
		if(!$configPath) throw new MagratheaConfigException("magrathea path is empty");
		try {
			Config::Instance()->SetPath($configPath);
			$timezone = Config::Instance()->Get("timezone");
			if($timezone) date_default_timezone_set($timezone);
		} catch(MagratheaConfigException $ex) {
			ErrorManager::Instance()->DisplayException($ex);
		} catch(\Exception $ex) {
			throw $ex;
		}
		return $this;
	}

	/**
	 * Connects to the database. Alias for `Connect()`.
	 * @return MagratheaPHP
	 */
	public function StartDB() {
		return $this->Connect();
	}

	/**
	 * Connects to the database using credentials from the configuration.
	 * @return MagratheaPHP
	 */
	public function Connect() {
		try {
			$config = Config::Instance();
			$host = $config->Get("db_host");
			$name = $config->Get("db_name");
			$user = $config->Get("db_user");
			$pass = $config->Get("db_pass");
	
			$db = Database::Instance();
			$db->SetConnection($host, $name, $user, $pass);
			return $this;
		} catch(Exception $ex) {
			throw $ex;
		}
	}

	/**
	 * Gets the singleton instance of the Database object.
	 * @return Database The database instance.
	 */
	public function GetDB(): Database {
		return Database::Instance();
	}

	/**
	 * Starts a PHP session if one is not already active.
	 * @return MagratheaPHP
	 */
	public function StartSession(): MagratheaPHP {
		if (session_status() == PHP_SESSION_NONE) {
			session_start();
		}
		return $this;
	}

	/**
	 * Includes the Composer autoloader.
	 * @return void
	 */
	public static function LoadVendor(): void {
		$vendorPath = realpath(__DIR__."/../../..");
		$vendorLoad = $vendorPath."/autoload.php";
		require($vendorLoad);
	}

	/**
	 * Returns the link to Magrathea's official documentation.
	 * @return string URL for the documentation.
	 */
	public static function GetDocumentationLink(): string {
		return "https://www.platypusweb.com.br/magratheaphp2";
	}

	/**
	* Gets Magrathea Version
	* @return   string    version
	*/
	public static function Version(): string { 
		return file_get_contents(__DIR__."/version");
	}

	/**
	 * Gets the version of the application from the `version` file in the project root.
	 * @return string Application version, or "???" if not found.
	 */
	public function AppVersion(): string {
		$file = MagratheaHelper::EnsureTrailingSlash($this->magRoot)."version";
		if(!file_exists($file)) return "???";
		return file_get_contents($file);
	}

	/**
	 * A simple test function to check if MagratheaPHP is working.
	 * @return void
	 */
	public static function Test(): void { 
		echo "MagratheaPHP2 is working!";
	}

}
