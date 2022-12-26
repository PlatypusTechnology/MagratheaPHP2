<?php

namespace Magrathea2;

use Magrathea2\DB\Database;

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
	* Get Config Root
	* @return string		config root
	*/
	public function getConfigRoot() {
		return realpath($this->magRoot."/configs");
	}

	/**
	* Starts Magrathea
	* @return MagratheaPHP
	*/
	public function Load() {
		$configPath = $this->getConfigRoot();
		Config::Instance()->SetPath($configPath);
		return $this;
	}

	/**
	* Connect to Database
	* @return MagratheaPHP
	*/
	public function Connect() {
		$config = Config::Instance();
		$host = $config->Get("db_host");
		$name = $config->Get("db_name");
		$user = $config->Get("db_user");
		$pass = $config->Get("db_pass");

		$db = Database::Instance();
		$db->SetConnection($host, $name, $user, $pass);
		return $this;
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
?>