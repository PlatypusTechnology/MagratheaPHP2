<?php

namespace Magrathea2\Admin\Features\CrudObject;

use Magrathea2\Admin\AdminElements;
use Magrathea2\Admin\AdminFeature;
use Magrathea2\Admin\AdminManager;
use Magrathea2\Admin\iAdminFeature;
use Magrathea2\Exceptions\MagratheaModelException;
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
	public function ReturnException($ex, $data=null) {
		if(empty($data)) $data = $ex;
		echo json_encode([
			"success" => false,
			"data" => $data,
			"error" => $ex->getMessage(),
		]);
		die;
	}
	public function ShowObjectNotFound($id) {
		$errorMsg = "Can't find a ".$this->objectName." with ID [".$id."]";
		return AdminElements::Instance()->ErrorCard($errorMsg);
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
	 * @param MagratheaModel $object		object for the form
	 * @return array
	 */
	public function Fields(MagratheaModel $object): array {
		$properties = $object->GetFields();
		unset($properties["created_at"]);
		unset($properties["updated_at"]);
		$fields = [];
		foreach($properties as $field => $type) {
			array_push($fields, $this->GetField($field, $type));
		}
		if(count($fields) % 2 != 0) {
			array_push($fields, ["type" => "empty"]);
		}
		if($object->id) {
			array_push($fields,
				["type" => "empty", "size" => "col-6"],
				$this->GetDeleteButton(),
				$this->GetSaveButton()
			);
		} else {
			array_push($fields,
				["type" => "empty", "size" => "col-9"],
				$this->GetSaveButton()
			);
		}
		return $fields;
	}
	/**
	 * Get a field related to type
	 * @param string $key 			Object property
	 * @param string $type			Object type
	 * @return array
	 */
	public function GetField(string $key, string $type): array {
		if(str_starts_with($type, "\\")) {
			$base = new $type();
			$control = $base->GetControl();
			$relational = $control->GetSelectArray();
			return [
				"name" => ucfirst($key)." (".$type.")",
				"key" => $key,
				"type" => $relational,
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
			"size" => "col-3",
			"action" => "saveCrudObject(this)",
		];	
	}
	/**
	 * Gets the button for deleting an object
	 * @return array
	 */
	public function GetDeleteButton(): array {
		return 	[
			"name" => "Delete",
			"type" => "button",
			"class" => ["w-100", "btn-danger"],
			"size" => "col-3",
			"action" => "deleteCrudObject(this)",
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

	/**
	 * Display the list of objects
	 */
	public function List() {
		$columns = $this->Columns();
		array_push($columns, $this->GetEditColumn());
		$list = $this->control->GetListPage();
		include(__DIR__."/list.php");
	}

	/**
	 * Display a form for the object
	 */
	public function Form() {
		$id = @$_GET["id"];
		try {
			$object = new $this->fullObjectName($id);
			if(!$object) {
				return $this->ShowObjectNotFound($id);
			}
			$formData = $this->Fields($object);
		} catch(MagratheaModelException $ex) {
			return $this->ShowObjectNotFound($id);
		} catch(\Exception $ex) {
			return $this->ReturnException($ex, $_POST);
		}
		include(__DIR__."/form.php");
	}

	/**
	 * Saves the object
	 */
	public function Save() {
		$id = $_POST["id"];
		$o = new $this->fullObjectName($id);
		$o = $o->Assign($_POST);
		try {
			$success = $o->Save();
		} catch(\Magrathea2\Exceptions\MagratheaDBException $ex) {
			return $this->ReturnException($ex, $_POST);
		}
		echo json_encode([
			"success" => true,
			"data" => $o,
			"type" => ($id ? "update" : "insert"),
		]);
	}

	/**
	 * Deletes the object
	 */
	public function Delete() {
		$id = $_POST["id"];
		$o = new $this->fullObjectName($id);
		try {
			$success = $o->Delete();
		} catch(\Magrathea2\Exceptions\MagratheaDBException $ex) {
			return $this->ReturnException($ex);
		}
		echo json_encode([
			"success" => $success,
			"data" => $o,
			"type" => "delete",
		]);
	}

}

