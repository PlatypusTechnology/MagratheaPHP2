<?php

namespace Magrathea2\Admin\Api;
use Magrathea2\Admin\Features\User\AdminUser;
use Magrathea2\Admin\Features\User\AdminUserControl;
use Magrathea2\MagratheaApi;
use Magrathea2\MagratheaApiAuth;

class AdminUserApi extends \Magrathea2\MagratheaApiControl {

	public function ChangePassword($params) {
		$user_id = $params["id"];
		$new_pwd = $params["new_password"];
		$user = new AdminUser($user_id);
		$control = new AdminUserControl();
		return $control->SetNewPassword($user, $new_pwd);
	}

	public function GetUserToken($params) {
		$userId = $params["id"];
		$user = new AdminUser($userId);
		$control = new MagratheaApiAuth();
		return $control->ResponseLogin($user);
	}
}

