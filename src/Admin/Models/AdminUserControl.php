<?php

namespace Magrathea2\Admin\Models;

use Exception;
use Magrathea2\Exceptions\MagratheaException;
use Magrathea2\MagratheaModelControl;
use Magrathea2\DB\Query;
use Magrathea2\DB\Select;

class AdminUserControl extends MagratheaModelControl { 
	protected static $modelName = "Magrathea2\Admin\Models\AdminUser";
	protected static $dbTable = "_magrathea_users";

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
			if (!$user) {
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
			$query = Query::Update()
				->Table(static::$dbTable)
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

}
