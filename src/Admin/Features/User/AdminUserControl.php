<?php

namespace Magrathea2\Admin\Features\User;

use Exception;
use Magrathea2\Admin\AdminManager;
use Magrathea2\MagratheaModelControl;
use Magrathea2\DB\Query;
use Magrathea2\DB\Select;
use Magrathea2\Debugger;

use function Magrathea2\p_r;

class AdminUserControl extends MagratheaModelControl { 

	protected static $modelNamespace = "Magrathea2\Admin\Features\User";
	protected static $modelName = "AdminUser";
	protected static $dbTable = "_magrathea_users";

	public function GetByEmail($email): AdminUser|null {
		$user = $this->GetWhere(["email" => $email]);
		if(count($user) == 0) return null;
		return $user[0];
	}

	/**
	 * Logs in
	 * @param string $user			user e-mail
	 * @param string $password	user password
	 * @return array		returns array with [ success, user, message ]
	 */
	public function Login($user, $password): array {
		try {
			$query = Query::Select()
				->Where(["email" => $user])
				->Obj(new AdminUser());
			$user = self::RunRow($query);
			if ($user == null) {
				return [ "success" => false, "user" => null, "message" => "User not found" ];
			}
			$pwdCorrect = $user->CheckPassword($password);
			if (!$pwdCorrect) {
				return [ "success" => false, "user" => $user, "message" => "Password incorrect" ];
			}
			$this->SetLoginAsNow($user);
			return [ "success" => true, "user" => $user ];
		} catch(Exception $ex) {
			throw $ex;
		}
	}

	public function SetLoginAsNow($user) {
		try {
			$query = Query::Update();
			$query->Table(static::$dbTable);
			$query
				->SetRaw("last_login = NOW()")
				->Where([ "id" => $user->id ]);
			return self::Run($query);
		} catch(Exception $ex) {
			throw $ex;
		}
	}

	public function CountUsers(): int {
		try {
			$query = Query::Select()
				->SelectStr("count(1) as c")
				->Table(static::$dbTable);
			$c = self::QueryOne($query->SQL());
			return intval($c);
		} catch(Exception $ex) {
			throw $ex;
		}
	}

	public function SetNewPassword($user, $pwd) {
		// Debugger::Instance()->SetDev();
		if(strlen($pwd) < 8) {
			return ["success" => false, "error" => "Password must be at least 8 chars long"];
		}
		$user->SetPassword($pwd);
		$saved = $user->Save();
		AdminManager::Instance()->Log("change-password", $user);
		return [ "success" => ($saved === true) ];
	}

	public function GetSelect() {
		return array_map(function($i) {
			return [
				"id" => $i->id,
				"name" => $i->email
			];
		}, $this->GetAll());
	}

	public function  GetSelectWithRoles() {
		$user = new AdminUser();
		$roles = $user->GetRoles();
		return array_map( function($i) use ($roles) {
			$role = @$roles[$i->role_id];
			return [
				"id" => $i->id,
				"name" => $i->email." (".$role.") "
			];
		}, $this->GetAll());		
	}

}
