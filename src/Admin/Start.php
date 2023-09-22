<?php

namespace Magrathea2\Admin;

use Exception;
use Magrathea2\Admin\Api\AdminApi;
use Magrathea2\Admin\Models\AdminConfigControl;
use Magrathea2\Debugger;
use Magrathea2\Singleton;
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
class Start extends Singleton { 

	protected $appPath;

	protected function Initialize() {
		MagratheaPHP::Instance()->StartSession();
		$this->appPath = MagratheaPHP::Instance()->appRoot;
		Debugger::Instance()->SetDebug();
		return $this;
	}

	/**
	 * Starts the database
	 * @return 	Start
	 */
	public function StartDb(): Start {
		MagratheaPHP::Instance()->Connect();
		return $this;
	}

	/**
	 * Check Admin API calls
	 */
	public function CheckApi(): void {
		if(@$_GET["magrathea_api"]) {
			$api = new AdminApi();
			$api->Call($_GET["magrathea_api"], @$_GET["magrathea_api_method"]);
			die;
		}
	}

	/**
	 * Checks if a feature is being loaded
	 */
	public function CheckFeature(): void {
		$featureName = @$_GET["magrathea_feature"];
		if(!$featureName) return;
		$action = @$_GET["magrathea_feature_action"];
		if(!$action) return;
		try { 
			$feature = AdminManager::Instance()->GetFeature($featureName);
			if (!$feature) {
				AdminElements::Instance()->ErrorCard("Feature [".$featureName."] not available!");
			} else {
				$feature->$action();
			}
			die;
		} catch(Exception $ex) {
			AdminElements::Instance()->ErrorCard($ex->getMessage());
		}
	}

	/**
	 * Loads Magrathea Admin
	 */
	public function Load(): void {
		if ($this->IsStarted()) {
			$this->ShouldLogout();
			if($this->IsLoggedIn()) {
				if(!AdminManager::Instance()->Auth()) {
					$message = "Access Denied: User is not an admin!";
					AdminManager::Instance()->ErrorPage($message);
				}
				$this->CheckApi();
				$this->CheckFeature();
				include ("views/index.php");
			} else {
				include ("views/login.php");
			}
		} else {
			include ("views/setup.php");
		}
	}

	public function ShouldLogout() {
		$logout = @$_GET["magrathea_logout"];
		if($logout == "true") {
			\Magrathea2\Admin\AdminUsers::Instance()->Logout();
			$url = strtok($_SERVER['REQUEST_URI'], '?');
			header('Location: ' . $url);
			exit;
		}
	}

	/**
	 * checks if any admin user is logged in
	 * @return 	bool 		$isLogged 			is it?
	 */
	public function IsLoggedIn(): bool {
		$user = \Magrathea2\Admin\AdminUsers::Instance()->GetLoggedUser();
		return ($user !== null);
	}

	/**
	 * Checks if MagratheaAdmin is already started
	 */
	public function IsStarted(): bool {
		$configContol = new AdminConfigControl();
		try {
			$config = $configContol->GetByKey("admin_install_date");
			$hasConfig = ($config !== null);
			$hasUsers = $this->HasUsers();
			return ($hasConfig && $hasUsers);
		} catch(\Magrathea2\Exceptions\MagratheaDBException $ex) {
			$title = "Error on Database. Is it installed?";
			AdminElements::Instance()->ErrorCard($ex->getMessage(), $title);
			return false;
		} catch(Exception $ex) {
			throw $ex;
		}
	}

	public function HasUsers(): bool {
		$control = new \Magrathea2\Admin\Features\User\AdminUserControl();
		return ($control->CountUsers() > 0);
	}

}

?>