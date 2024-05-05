<?php

namespace Magrathea2\Admin\Features\UserLogs;

use Admin;
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
class AdminLog extends MagratheaModel implements iMagratheaModel { 

	public $id;
	public $user_id, $action, $victim, $info;
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
		$this->dbTable = "_magrathea_logs";
		$this->dbPk = "id";
		$this->dbValues = [
			"id" => "int",
			"user_id" => "int",
			"action" => "string",
			"victim" => "string",
			"info" => "text",
		];
		$this->dbAlias["data"] = "info";
	}

	public function Simple() {
		unset($this->info);
		return $this;
	}

}

