<?php

namespace Magrathea2\Bootstrap;
use Exception;
use Magrathea2\Debugger;

#######################################################################################
####
####    MAGRATHEA PHP2
####    v. 2.0
####    Magrathea by Paulo Henrique Martins
####    Platypus technology
####    Bootstrap Start created: 2022-12 by Paulo Martins
####
#######################################################################################

/**
 * Class for handling and loading Magrathea's Bootstrap system
 */
class Start extends \Magrathea2\Singleton { 

	protected $appPath;
	private $structure = [];

	/**
	* Shows Bootstrap UI
	* @return 	void
	*/
	public function Load() {
		Debugger::Instance()->SetDev();
		include("views/index.php");
	}

	/**
	* Sets project's path
	* @param 	string 	$path 	main path of the project
	* @return 	Start
	*/
	public function setPath($path) {
		$this->appPath = $path;
		return $this;
	}

	/**
	* Check if the class has everything it needs to survive
	* @return 	boolean
	*/
	public function CheckSelf() {
		if(empty($this->appPath)) {
			return false;
		}
		return true;
	}

	/**
	* Check folders (printing in the way)
	* @return	boolean 	Folder OK or not
	*/
	private function CheckFolder($path) {
		$realPath = realpath($path);
		if(!$realPath) {
			echo "folder does not exist!<br/>";
			echo "creating folder [".$path."]<br/>";
			try {
				mkdir($path);
			} catch(Exception $ex) {
				echo "<span class='error'>ERROR: Could not create [".$path."]".$ex->getMessage()."</span>";
			}
			echo "... DONE!<br/>";
			return $this->CheckFolder($path);
		} else {
			echo "&gt;&gt; directory path: [".$realPath."]<br/>";
			return true;
		}
	}

	/**
	* Create folders path
	* @return 	void
	*/
	private function Structurate() {
		$path = $this->appPath;
		if(empty($path)) {
			throw new Exception("invalid path: ".$path);
		}
		$this->structure = array(
			'Config' => $this->appPath."/../configs",
			'Logs' => $this->appPath."/../logs",
			'Models' => $this->appPath."/../Models",
			'Models-Base' => $this->appPath."/../Models/Base",
		);
	}

	/**
	* Validate project's structure
	* @return 	void
	*/
	public function ValidateStructure() {
		$this->Structurate();
		echo "<ul>";
		foreach($this->structure as $name => $value) {
			echo "<li>";
			echo "<b>".$name."</b><br/>";
			$this->CheckFolder($value);
			echo "<br/>";
			echo "</li>";
		}
		echo "</ul>";
	}

	/**
	* Get config file path
	* @return 	string		path for config file
	*/
	public function getConfigFilePath() {
		$this->Structurate();
		$configPath = realpath($this->structure["Config"]);
		$configFile = $configPath."/magrathea.conf";
		if(empty($configPath)) {
			throw new Exception("invalid config path: ".$this->structure["Config"]);
		}
		return $configFile;
	}

	/**
	* Create default config file
	* @return 	boolean		true if file was created, false if don't
	*/
	public function CreateDefaultConfigFile() {
		$configFile = $this->getConfigFilePath();
		if(file_exists($configFile)) {
			echo "file [".$configFile."] already exists";
			return false;
		}

		$configDefaultPath = realpath(dirname(__FILE__)."/docs");
		$configDefaultFile = $configDefaultPath."/magrathea.conf.sample";
		echo "creating config file [".$configFile."]";
		try {
			copy($configDefaultFile, $configFile);
		} catch(Exception $ex) {
			throw new Exception("could not copy file [".$configDefaultFile."] to [".$configFile."]");
		}
		return true;
	}

	/**
	* Prints config file
	* @return 	void
	*/
	public function ViewConfigFile() {
		$this->Structurate();
		$configPath = realpath($this->structure["Config"]);
		$configFile = $configPath."/magrathea.conf";
		echo file_get_contents($configFile);
	}

}

?>