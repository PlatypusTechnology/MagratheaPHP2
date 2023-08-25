<?php

namespace Magrathea2\Admin\Features\User;

use Magrathea2\Admin\AdminManager;
use Magrathea2\iMagratheaModel;
use Magrathea2\MagratheaModel;

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
class AdminUser extends MagratheaModel implements iMagratheaModel { 

	public $id;
	public $email, $password, $last_login, $role_id, $active;
	public $created_at, $updated_at;
	protected $autoload = null;

	public function __construct($id=null) {
		$this->Start();
		if( !empty($id) ){
			$pk = $this->dbPk;
			$this->$pk = $id;
			$this->GetById($id);
		}
	}

	public function Start() {
		$this->dbTable = "_magrathea_users";
		$this->dbPk = "id";
		$this->dbValues = [
			"id" => "int",
			"email" => "string",
			"password" => "string",
			"last_login" => "datetime",
			"role_id" => "int",
			"active" => "bool"
		];
	}

	public function Insert() {
		$this->active = true;
		$id = parent::Insert();
		AdminManager::Instance()->Log("user-created", $this);
		return $id;
	}
	public function Update() {
		$up = parent::Update();
		AdminManager::Instance()->Log("user-updated", $this);
		return $up;
	}
	public function Delete() {
		$del = parent::Delete();
		AdminManager::Instance()->Log("user-deleted", $this);
		return $del;
	}

	public function SetPassword($pwd): AdminUser {
		$this->password = password_hash($pwd, PASSWORD_BCRYPT);
		return $this;
	}

	public function CheckPassword($pwd): bool {
		return password_verify($pwd, $this->password);
	}

	public function __toString() {
		$this->password = "-hidden-";
		return parent::__toString();
	}
	public function ToArray() {
		$this->password = "-hidden-";
		return parent::ToArray();
	}

	/**
	 * checks if the user can edit another user
	 */
	public function PermissionCanEdit(): bool {
		return $this->IsAdmin();
	}

	/**
	 * checks if it's admin
	 * @return bool
	 */
	public function IsAdmin(): bool {
		return ($this->role_id == 2 || $this->role_id == 1);
	}

	public function GetRoles() {
		return array(
			1 => "Super Admin",
			2 => "Admin",
			3 => "Basic",
		);
	}

	public function GetRoleName() {
		return $this->GetRoles()[$this->role_id];
	}

}

