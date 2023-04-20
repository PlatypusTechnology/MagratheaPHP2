<?php

namespace Magrathea2\Admin;

use Magrathea2\Admin\Models\AdminUserControl;
use Magrathea2\Singleton;
use Magrathea2\DB\Database;

class AdminUsers extends Singleton {

	private $tableName = "MagratheaUsers";
	private $sessionName = "magrathea_user";

	public function IsUsersSet(): bool {
		$db = \Magrathea2\Config::Instance()->GetConfigFromDefault("db_name");
		$magdb = Database::Instance();
		$query = "SELECT COUNT(TABLE_NAME) FROM information_schema.TABLES WHERE ".
			"TABLE_SCHEMA = '".$db."' AND TABLE_NAME = '".$this->tableName."'";
		$rs = $magdb->QueryAll($query);
		return ($rs === 1);
	}

	public function Login($user, $password): array {
		$control = new AdminUserControl();
		$loginData = $control->Login($user, $password);
		if(!$loginData["success"]) return $loginData;
		$user = $loginData["user"];
		$_SESSION[$this->sessionName] = $user;
		return $loginData;
	}

	public function GetLoggedUser() {
		return @$_SESSION[$this->sessionName];
	}

	public function Logout() {
		unset($_SESSION[$this->sessionName]);
	}

}
