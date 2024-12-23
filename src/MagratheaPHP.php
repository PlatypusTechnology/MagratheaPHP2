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
* Base class for Magrathea project
* 
*/
class MagratheaPHP extends Singleton {

		// Root of application
	public string $appRoot = "";
		// Root of Magrathea Structure
	public string $magRoot = "";
	public $codeFolder = [];
	public $versionRequired = null;

	/**
	* Sets App Root Path
	* @param    string  $path   Root path of project
	* @return 	MagratheaPHP
	*/
	public function AppPath($path): MagratheaPHP {
		$this->appRoot = $path;
		$this->magRoot = realpath($path."/../");
		return $this;
	}

	/**
	 * Returns the root path (app) of project
	 * @return 	string	app path
	 */
	public function GetAppRoot(): string {
		return $this->appRoot;
	}

	/**
	 * Check if current Magrathea Version is acceptable
	 * @param		string	$version		min version for Magrathea
	 * @param		boolean	$throwEx		if true, throws a 206 Magrathea Exception if version is under (default: false)
	 * @return 	MagratheaPHP
	 * @throws	MagratheaException		code 206: incompatible version
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
	 * @return 	string		$magratheaRoot
	 */
	public function GetMagratheaRoot(): string {
		return __DIR__;
	}

	/**
	 * Adds a code folder for autoload
	 * @param string $folder	foldername
	 * @return MagratheaPHP		itself
	 */
	public function AddCodeFolder(...$folder): MagratheaPHP {
		array_push($this->codeFolder, ...$folder);
		return $this;
	}

	/**
	 * Adds features (and its folders)
	 * @param string	$root			__DIR__ of the base app
	 * @param string 	$features	features names
	 * @return MagratheaPHP		itself
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
	 * Set dev display errors
	 * @return MagratheaPHP
	 */
	public function Dev(): MagratheaPHP {
		error_reporting(E_ALL);
		ini_set('display_errors', 1);
		ini_set("log_errors", 1);
		return $this;
	}

	/**
	 * Set debug development
	 * @return MagratheaPHP
	 */
	public function Debug(): MagratheaPHP {
		Debugger::Instance()->SetDebug();
		return $this;
	}

	/**
	 * Set prod environment
	 * @return MagratheaPHP
	 */
	public function Prod(): MagratheaPHP {
		Debugger::Instance()->SetType(Debugger::LOG);
		return $this;
	}



	/**
	* Get Config Root
	* @return string		config root
	*/
	public function getConfigRoot() {
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
	* Connect to Database (other name for Connect)
	* @return MagratheaPHP
	*/
	public function StartDB() {
		return $this->Connect();
	}

	/**
	* Connect to Database
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
	 * Gets Database object
	 * @return Database
	 */
	public function GetDB(): Database {
		return Database::Instance();
	}

	/** 
	 * starts session if needed
	 */
	public function StartSession(): MagratheaPHP {
		if (session_status() == PHP_SESSION_NONE) {
			session_start();
		}
		return $this;
	}

	public static function LoadVendor(): void {
		$vendorPath = realpath(__DIR__."/../../..");
		$vendorLoad = $vendorPath."/autoload.php";
		require($vendorLoad);
	}

	/**
	 * Return link to Magrathea's documentation
	 * @return		string 		link
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
	 * Gets the version of the app (inside the src folder)
	 * @return	string 		app version
	 */
	public function AppVersion(): string {
		$file = MagratheaHelper::EnsureTrailingSlash($this->magRoot)."version";
		if(!file_exists($file)) return "???";
		return file_get_contents($file);
	}

	/**
	* Test Magrathea
	* @return   void
	*/
	public static function Test(): void { 
		echo "MagratheaPHP2 is working!";
	}

}
