<?php

namespace Magrathea2\Admin;

use Magrathea2\Admin\Features\User\AdminUserControl;
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
		$_SESSION[$this->sessionName] = serialize($user);
		AdminManager::Instance()->Log("login", $user, $user->id);
		return $loginData;
	}

	public function GetLoggedUser() {
		$u = @$_SESSION[$this->sessionName];
		if($u) {
			return unserialize($u);
		} else {
			return null;
		}
	}

	public function Logout() {
		unset($_SESSION[$this->sessionName]);
	}

}
