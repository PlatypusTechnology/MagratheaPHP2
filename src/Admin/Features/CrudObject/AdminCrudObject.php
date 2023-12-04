<?php

namespace Magrathea2\Admin\Features\CrudObject;

use Magrathea2\Admin\AdminElements;
use Magrathea2\Admin\AdminFeature;
use Magrathea2\Admin\AdminManager;
use Magrathea2\Admin\iAdminFeature;
use Magrathea2\MagratheaModel;
use Magrathea2\MagratheaModelControl;

#######################################################################################
####
####    MAGRATHEA PHP2 Admin Object
####    v. 2.0
####    Magrathea by Paulo Henrique Martins
####    Platypus technology
####    Admin created: 2023-12 by Paulo Martins
####
#######################################################################################

/**
 * Class for Admin Object
 */
class AdminCrudObject extends AdminFeature implements iAdminFeature {

	public string $featureName;
	public string $featureId;
	public MagratheaModel $object;
	public MagratheaModelControl $control;
	public string $objectName;
	public string $fullObjectName; 

	public function __construct() {
		$this->Initialize();
		parent::__construct();
		$this->SetClassPath(__DIR__);
		$this->AddJs(__DIR__."/scripts.js");
	}


	public function Initialize() {}

	/**
	 * Sets the object for crud
	 * @param MagratheaModel $object		Magrathea Model for admin
	 * @return AdminCrudObject					itself
	 */
	public function SetObject(MagratheaModel $object): AdminCrudObject {
		$this->object = $object;
		$this->fullObjectName = get_class($object);
		$objName = $object->ModelName();
		$controlName = $this->fullObjectName."Control";
		$this->objectName = $objName;
		if(empty($this->featureName)) $this->featureName = $this->objectName." CRUD";
		if(empty($this->featureId)) $this->featureId = "CRUD".$this->objectName;
		$this->SetControl(new $controlName());
		return $this;
	}

	/**
	 * Sets the control for crud
	 * @param MagratheaModelControl $c		Magrathea Model Control for admin
	 * @return AdminCrudObject						itself
	 */
	public function SetControl(MagratheaModelControl $c): AdminCrudObject {
		$this->control = $c;
		return $this;
	}

	public function ReturnError($err) {
		echo json_encode(["success" => false, "error" => $err]);
		die;
	}

	/**
	 * Gets the columns for display the list
	 * @return array
	 */
	public function Columns(): array {
		$properties = $this->object->GetProperties();
		unset($properties["created_at"]);
		unset($properties["updated_at"]);
		return array_keys($properties);
	}
	/**
	 * Gets the column value for editing an object row
	 * @return array
	 */
	public function GetEditColumn(): array {
		return [
			"title" => "...",
			"key" => function ($item) {
				$action = "editCrudObject(".$item->GetID().")";
				return '<a href="#" onclick="'.$action.'">Edit</a>';
			}
		];
	}

	/**
	 * Gets the array of fields for a object form
	 * @return array
	 */
	public function Fields(): array {
		$properties = $this->object->GetFields();
		unset($properties["created_at"]);
		unset($properties["updated_at"]);
		return $this->BuildFields($properties);
	}
	/**
	 * Gets a list of fields => types and builds a form data
	 * @param array $properties		properties as ["field" => "type"]
	 * @return array
	 */
	public function BuildFields(array $properties): array {
		$fields = [];
		foreach($properties as $field => $type) {
			array_push($fields, $this->GetField($field, $type));
		}
		if(count($fields) % 2 != 0) {
			array_push($fields, ["type" => "empty"]);
		}
		array_push($fields, $this->GetSaveButton());
		return $fields;
	}
	public function GetField(string $key, string $type): array {
		if(str_starts_with($type, "\\")) {
			$base = new $type();
			$control = $base->GetControl();
			$relational = $control->GetAll();
			$selects = [];
			foreach($relational as $s) {
				$selects[$s->GetID()] = $s->Ref();
			}
			return [
				"name" => ucfirst($key)." (".$type.")",
				"key" => $key,
				"type" => $selects,
				"size" => "col-6",
			];
		}
		switch ($type) {
			case "pk":
				$fieldType = "disabled";
				break;
			case "int":
			case "text":
			default:
				$fieldType = "text";
		}
		return [
			"name" => ucfirst($key)." (".$type.")",
			"key" => $key,
			"type" => $fieldType,
			"size" => "col-6",
		];
	}
	/**
	 * Gets the button for saving an object
	 * @return array
	 */
	public function GetSaveButton(): array {
		return 	[
			"name" => "Save",
			"type" => "button",
			"class" => ["w-100", "btn-success"],
			"size" => "col-6 offset-6",
			"action" => "saveCrudObject(this)",
		];	
	}

	/**
	 * Gets the title of the page
	 * @return string 
	 */
	public function GetHeaderTitle(): string {
		return $this->objectName;
	}
	/**
	 * Prints a button for adding new object
	 */
	public function PrintButtonNew() {
		AdminElements::Instance()->Button(
			"New ".$this->objectName, "newCrudObject()", ["btn-success"]);
	}

	public function List() {
		$columns = $this->Columns();
		array_push($columns, $this->GetEditColumn());
		$list = $this->control->GetListPage();

		include(__DIR__."/list.php");
	}

	public function Form() {
		$formData = $this->Fields();
		$id = @$_GET["id"];
		$object = new $this->fullObjectName($id);
		include(__DIR__."/form.php");
	}

	public function Save() {
		$id = $_POST["id"];
		$u = new $this->fullObjectName($id);
		$u = $u->Assign($_POST);
		try {
			$success = $u->Save();
		} catch(\Magrathea2\Exceptions\MagratheaDBException $ex) {
			echo json_encode([
				"success" => false,
				"data" => $_POST,
				"error" => $ex->getMessage(),
			]);
			return;
		}
		echo json_encode([
			"success" => $success,
			"data" => $u,
			"type" => ($id ? "update" : "insert"),
		]);
	}

}

