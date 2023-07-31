<?php

namespace Magrathea2\Admin\Features\User;

use Admin;
use Magrathea2\Admin\AdminElements;
use Magrathea2\Admin\AdminFeature;
use Magrathea2\Admin\AdminManager;
use Magrathea2\Admin\iAdminFeature;
use Magrathea2\iMagratheaModel;
use Magrathea2\MagratheaModel;

use function Magrathea2\p_r;

#######################################################################################
####
####    MAGRATHEA Admin Config PHP2
####    v. 2.0
####    Magrathea by Paulo Henrique Martins
####    Platypus technology
####    Admin created: 2023-02 by Paulo Martins
####
#######################################################################################

/**
 * Class for installing Magrathea's Admin
 */
class AdminFeatureUser extends AdminFeature implements iAdminFeature { 

	public $featureName = "Users";
	public $featureId = "AdminFeatureUser";

	public function __construct() {
		parent::__construct();
		$this->SetClassPath(__DIR__);
	}

	public function HasEditPermission($user): bool {
		$loggedUser = AdminManager::Instance()->GetLoggedUser();
		if($loggedUser->id == $user->id) return true;
		return $loggedUser->PermissionCanEdit();
	}

	private function GetUser(): AdminUser {
		$id = @$_GET["id"];
		if(!$id && !empty(@$_POST["id"])) {
			$id = $_POST["id"];
		} else if(!$id) {
			return AdminManager::Instance()->GetLoggedUser();
		}
		return new AdminUser($id);
	}

	public function ChangePassword() {
		$user = $this->GetUser();
		if(!$this->HasEditPermission($user)) {
			AdminManager::Instance()->PermissionDenied();
			die;
		}
		if(@$_POST["new_password"]) {
			$control = new AdminUserControl();
			$saved = $control->SetNewPassword($user, $_POST["new_password"]);
		}
		include(__DIR__."/password-edit.php");
	}

}
