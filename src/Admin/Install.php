<?php

namespace Magrathea2\Admin;

use Exception;
use Magrathea2\Admin\Features\AppConfig\AppConfigControl;
use Magrathea2\Admin\Features\UserLogs\AdminLog;
use Magrathea2\Admin\Features\UserLogs\AdminLogControl;
use Magrathea2\Admin\Features\User\AdminUser;
use Magrathea2\DB\Database;
use Magrathea2\Errors\ErrorManager;
use Magrathea2\MagratheaPHP;

#######################################################################################
####
####    MAGRATHEA PHP2
####    v. 2.0
####    Magrathea by Paulo Henrique Martins
####    Platypus technology
####    Admin created: 2022-12 by Paulo Martins
####
#######################################################################################

/**
 * Class for installing Magrathea's Admin
 */
class Install { 

	public $appPath;
	public $adminFileName = "magrathea.php";

	/**
	 * @return	Install		itself
	 */
	public function Load() {
		$this->appPath = MagratheaPHP::Instance()->appRoot;
		return $this;
	}

	public function InstallDatabase() {
		$file = realpath(dirname(__FILE__)."/database.sql");
		$magdb = Database::Instance();
		return $magdb->ImportFile($file, false);
	}

	/**
	 * Gets the path for the admin file
	 * @return  	string		full path for file
	 */
	public function getAdminFileEntrance(): string {
		if (!$this->appPath) {
			$this->Load();
		}
		return $this->appPath."/".$this->adminFileName;
	}

	/**
	 * Checks if admin file exists
	 * @return 		bool 		true if it does exists
	 */
	public function CreateAdminEntranceFile(): bool {
		$adminFile = $this->getAdminFileEntrance();
		$file = @fopen($adminFile, "w");
		if (!$file) {
			ErrorManager::Instance()->DisplayMesage("Could not write file [".$adminFile."]");
		}
		fwrite($file, $this->GetAdminCode());
		fclose($file);
		return true;
	}

	public function CreateFirstUser($username, $password): AdminUser {
		$user = new AdminUser();
		$user->email = $username;
		$user->SetPassword($password);
		$user->role_id = 1;
		$user->Insert();
		$this->LogUserCreated($user);
		$this->SetBasicConfigs();
		return $user;
	}

	public function LogUserCreated($user): AdminLog {
		$logControl = new AdminLogControl();
		return $logControl->Log(
			$user->id, 
			"first user created",
			null,
			json_encode(["email" => $user->email])
		);
	}

	public function SetBasicConfigs(): void {
		$configControl = new AppConfigControl();
		$configControl->SaveSystem("admin_install_date", \Magrathea2\now(), false);
		$configControl->Save("app_name", "", false);
		$configControl->SaveSystem("code_path", MagratheaPHP::Instance()->appRoot, false);
		$configControl->SaveSystem("code_structure", "feature", false);
		$configControl->SaveSystem("code_namespace", "", false);
	}

	/**
	 * Checks if AdminConfig exists - therefore Admin is installed
	 * @return 		bool 		true if is installed
	 */
	public function CheckIfDBInstalled(): bool {
		MagratheaPHP::Instance()->StartDB();
		$query = "SHOW TABLES LIKE '_magrathea_config'";
		$rs = Database::Instance()->QueryRow($query);
		return (count($rs) > 0);
	}

	/**
	 * Checks if admin.php fil exists
	 * @return 		bool 		it does exists
	 */
	public function CheckIfAdminFileExists(): bool {
		$adminFile = $this->getAdminFileEntrance();
		return file_exists($adminFile);
	}

	public function GetAdminCode() {
		return <<<EOD
		<?php
		require "../vendor/autoload.php";

		Magrathea2\MagratheaPHP::Instance()->AppPath(realpath(dirname(__FILE__)))->Load();
		Magrathea2\Admin\AdminManager::Instance()->StartDefault();
		EOD;
	}



}
