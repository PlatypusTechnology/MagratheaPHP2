<?php

namespace Magrathea2\Admin\Models;

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
class AdminConfig extends MagratheaModel implements iMagratheaModel { 

	public $id;
	public $name, $value;
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
		$this->dbTable = "_magrathea_config";
		$this->dbPk = "id";
		$this->dbValues = [
			"id" => "int",
			"name" => "string",
			"value" => "string"
		];
		$this->dbAlias["key"] = "name";
	}

	public function GetKey() {
		return $this->name;
	}
	public function GetValue() {
		return $this->value;
	}

	public function __toString() {
		return "[MAGRATHEACONFIG (id: ".$this->id.")(key: ".$this->key.") = {".$this->value."}]";
	}

}

