<?php

namespace Magrathea2\Admin\Api;

use Magrathea2\Admin\Features\User\AdminUser;
use Magrathea2\Admin\Features\User\AdminUserControl;
use Magrathea2\MagratheaApiAuth;

class AdminUserApi extends \Magrathea2\MagratheaApiControl {
	public function __construct() {
		$this->model = get_class(new AdminUser());
		$this->service = new AdminUserControl();
	}

	public function GetRoles($params) {
		$user = new AdminUser();
		return $user->GetRoles();
	}

	public function Create($data=false) {
		$data = $this->GetPost();
		$user = new AdminUser();
		$user->Assign($data);
		$user->SetPassword($data["password"]);
		$user->Insert();
		return $user;
	}

	public function ChangePassword($params) {
		$user_id = $params["id"];
		$post = $this->GetPost();
		$new_pwd = $post["new_password"];
		$user = new AdminUser($user_id);
		return $this->service->SetNewPassword($user, $new_pwd);
	}

	public function GetUserToken($params) {
		$userId = $params["id"];
		$user = new AdminUser($userId);
		$control = new MagratheaApiAuth();
		return $control->ResponseLogin($user);
	}

}

