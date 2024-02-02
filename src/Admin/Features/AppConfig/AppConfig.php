<?php

namespace Magrathea2\Admin\Features\AppConfig;

use Magrathea2\Exceptions\MagratheaModelException;
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
class AppConfig extends MagratheaModel implements iMagratheaModel { 

	public $id;
	public $name, $value, $is_system;
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

	public function Validate() {
		if($this->key == null || $this->key == "") {
			return ["valid" => false, "error" => "key should not be empty"];
		}
		return ["valid" => true];
	}

	public function Insert() {
		$valid = $this->Validate();
		if($valid["valid"] == false) throw new MagratheaModelException($valid["error"]);
		parent::Insert();
	}
	public function Update() {
		$valid = $this->Validate();
		if($valid["valid"] == false) throw new MagratheaModelException($valid["error"]);
		parent::Update();
	}

	public function Start() {
		$this->dbTable = "_magrathea_config";
		$this->dbPk = "id";
		$this->dbValues = [
			"id" => "int",
			"name" => "string",
			"value" => "string",
			"is_system" => "boolean",
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
		$str_n = $this->is_system ? "MAGRATHEACONFIG-system" : "MAGRATHEACONFIG";
		return "[".$str_n." (id: ".$this->id.")(key: ".$this->key.") = {".$this->value."}]";
	}

}

