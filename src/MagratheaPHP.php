<?php

namespace Magrathea2;

use Exception;
use Magrathea2\DB\Database;
use Magrathea2\Exceptions\MagratheaConfigException;

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
	public $appRoot = "";
		// Root of Magrathea Structure
	public $magRoot = "";

	/**
	* Sets App Root Path
	* @param    string  $path   Root path of project
	* @return MagratheaPHP
	*/
	public function AppPath($path) {
		$this->appRoot = $path;
		$this->magRoot = realpath($path."/../");
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
		Config::Instance()->SetPath($configPath);
		$timezone = Config::Instance()->Get("timezone");
		if($timezone) date_default_timezone_set($timezone);
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
		$vendorLoad = __DIR__."/../vendor/autoload.php";
		require($vendorLoad);
	}

	/**
	* Gets Magrathea Version
	* @return   string    version
	*/
	public static function Version(): string { 
		return "0.1";
	}

	/**
	* Test Magrathea
	* @return   void
	*/
	public static function Test(): void { 
		echo "MagratheaPHP2 is working!";
	}

}
