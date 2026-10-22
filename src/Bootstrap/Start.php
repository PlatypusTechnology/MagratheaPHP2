<?php

namespace Magrathea2\Bootstrap;
use Exception;
use Magrathea2\Admin\AdminDatabase;
use Magrathea2\Config;
use Magrathea2\Debugger;
use Magrathea2\Logger;
use Magrathea2\MagratheaPHP;

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
	 * Actions that can be called through `?action=` (files in `views/actions/`)
	 */
	const ACTIONS = [
		"database-test",
		"install-admin",
		"install-admin-preview",
		"run-query",
		"save-config",
		"show-tables",
		"user-reset",
		"users-get",
	];

	/**
	* Shows Bootstrap UI
	* Only available when the active environment has `bootstrap_unlocked = true`;
	* otherwise, a "locked" page is shown with a 403 status.
	* @return 	void
	*/
	public function Load() {
		if (!$this->IsUnlocked()) {
			http_response_code(403);
			$environment = $this->GetEnvironmentName();
			include("views/locked.php");
			return;
		}
		Debugger::Instance()->SetDev();
		if (!$this->appPath) {
			$this->appPath = MagratheaPHP::Instance()->appRoot;
		}
		include("views/index.php");
	}

	/**
	 * Checks if bootstrap is unlocked:
	 * the active environment section must have `bootstrap_unlocked = true`.
	 * A missing key, a false value or an unreadable config means locked.
	 * @return bool		is bootstrap unlocked?
	 */
	public function IsUnlocked(): bool {
		try {
			$unlocked = Config::Instance()->Get("bootstrap_unlocked");
		} catch(\Exception $ex) {
			return false;
		}
		return filter_var($unlocked, FILTER_VALIDATE_BOOLEAN);
	}

	/**
	 * Gets the active environment's name, or null if config can't be read
	 * @return string|null	environment name
	 */
	private function GetEnvironmentName(): ?string {
		try {
			return Config::Instance()->GetEnvironment();
		} catch(\Throwable $ex) {
			return null;
		}
	}

	/**
	 * Gets the file for an action, if it is a valid one
	 * @param mixed $action		action name (from `$_GET["action"]`)
	 * @return string|null		file path, or null for an unknown action
	 */
	public function GetActionFile($action): ?string {
		if (!is_string($action) || !in_array($action, self::ACTIONS, true)) {
			return null;
		}
		return __DIR__."/views/actions/".$action.".php";
	}

	/**
	 * Gets link to step
	 * @param int $step		step to go
	 * @return string			step url
	 */
	public function GetStepLink($step) {
		$url = self::GetBootstrapURL();
		return $url."?step=".$step;
	}

	public static function GetBootstrapURL() {
		return strtok($_SERVER["REQUEST_URI"], '?');
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
	private function CheckFolder($path, $retry=false) {
		$realPath = realpath($path);
		if(!$realPath) {
			echo "folder does not exist!<br/>";
			echo "creating folder [".$path."]<br/>";
			try {
				$success = @mkdir($path);
			} catch(Exception $ex) {
				echo "<span class='error'>ERROR: Could not create [".$path."]".$ex->getMessage()."</span>";
			}
			if(!$success) {
				echo "... ERROR!<br/>";
				if($retry) {
					echo " Could not create folder.<br/>";
					return false;
				}
			} else {
				echo "... DONE!<br/>";
			}
			return $this->CheckFolder($path, true);
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
			'Config' => $this->GetConfigPath(),
			'Logs' => Logger::Instance()->GetLogPath(),
			// 'Models' => $this->appPath."/../Models",
			// 'Models-Base' => $this->appPath."/../Models/Base",
			'Backups' => AdminDatabase::Instance()->GetBackupFolder(),
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
	public function GetConfigPath() {
		$configPath = realpath($this->appPath."/../configs");
		if(empty($configPath)) {
			throw new Exception("invalid config path: ".$configPath);
		}
		return $configPath;
	}
	/**
	* Get config file path
	* @return 	string		path for config file
	*/
	public function GetConfigFilePath() {
		$this->Structurate();
		$configPath = $this->GetConfigPath();
		$configFile = $configPath."/magrathea.conf";
		if(empty($configPath)) {
			throw new Exception("invalid config path: ".$configPath);
		}
		return $configFile;
	}

	/**
	* Create default config file
	* @return 	boolean		true if file was created, false if don't
	*/
	public function CreateDefaultConfigFile() {
		$configFile = $this->GetConfigFilePath();
		if(file_exists($configFile)) {
			echo "file [".$configFile."] already exists";
			return false;
		}

		$configDefaultPath = realpath(dirname(__FILE__)."/docs");
		$configDefaultFile = $configDefaultPath."/magrathea.conf.sample";
		echo "creating config file [".$configFile."] from [".$configDefaultFile."]";
		try {
			$success = @copy($configDefaultFile, $configFile);
		} catch(Exception $ex) {
			throw new Exception("could not copy file [".$configDefaultFile."] to [".$configFile."]");
		}
		if(!$success) {
			echo "<br/><br/>could not create config file!<br/>base file:<br/>";
			echo "<pre>".file_get_contents($configDefaultFile)."</pre>";
			echo "<br/><hr/>";
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

	public function PreLoad($appRoot) {
		$this->appPath = $appRoot;
		$this->CreateDefaultConfigFile();
	}

}
