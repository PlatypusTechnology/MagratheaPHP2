<?php

namespace Magrathea2\Admin;

use Exception;
use Magrathea2\Singleton;
use Magrathea2\MagratheaPHP;

use function Magrathea2\p_r;

#######################################################################################
####
####    MAGRATHEA PHP2 Admin elements
####    v. 2.0
####    Magrathea by Paulo Henrique Martins
####    Platypus technology
####    Admin created: 2022-12 by Paulo Martins
####
#######################################################################################

/**
 * Class for Admin Elements
 */
class AdminForm {

	public $formName;
	public $elements;
	public $values;
	public $defaultSize = "col-sm-12 col-md-6";

	/**
	 * Sets the form name/id
	 * @param 	string		$name
	 * @return 	AdminForm
	 */
	public function SetName($name): AdminForm {
		$this->formName = $name;
		return $this;
	}

	private function printAlert($msg, $type) {
		AdminElements::Instance()->Alert($msg, $type);
	}

	private function NormalizeFormArray($arr): array {
		$formArr = [];
		foreach($arr as $el) {
			if(empty(@$el['key']) && !empty(@$el['id'])) {
				$el['key'] = $el['id'];
			}
			if(empty($el['size'])) {
				$el['size'] = (string)$this->defaultSize;
			}
			array_push($formArr, $el);
		}
		return $formArr;
	}

	/**
	 * Prints a form for a object
	 * 		types: 
	 * 				hidden, empty (for a empty div),
	 * 				text, disabled, number, email, date,
	 * 				checkbox, switch,
	 * 				submit, button, save-button, delete-button,
	 * 				array (builds a select 'id' => 'name')
	 * 		key: object's property. can be a function (in case of a button)
	 * @param array $elements		arary with [ name, size, key, type, class, placeholder, attributes ]
	 * @param \Magrathea2\MagratheaModel|array 	$object  	Magrathea Model Object
	 */
	public function Build($elements, $object=[]): AdminForm {
		if (\Magrathea2\isMagratheaModel($object)) {
			array_push($elements,
				[
					"key" => "id",
					"type" => "hidden",
					"size" => "hidden",
				],
			);
		}
		$this->values = $object;
		$this->elements = $elements;
		return $this;
	}

	/**
	 * Prints the form
	 */
	public function Print() {
		$formElements = $this->NormalizeFormArray($this->elements);
		$formName = $this->formName;
		$values = $this->values;
		$view = __DIR__."/views/elements/form.php";
		include($view);
	}

	/**
	 * CRUD an object, automatically getting the action and the post
	 * -- to be used in combination with `FormObject`
	 * @param \Magrathea2\MagratheaModel $object		Magrathea Model Object
	 * @return 	array		operation success
	 */
	public function CRUDObject($object, $printsInfo=false): array {
		try {
			$objType = get_class($object);
			if(!@$_POST) {
				return [
					"success" => true,
					"action" => false,
				];
			}
			$pk = $object->GetPkName();
			if (isset($_POST[$pk])) {
				$id = $_POST[$pk];
			}
			$data = $_POST;
			$obj = new $objType($id);
			$action = @$_POST["magrathea-submit"];
			switch($action) {
				case "save":
					foreach($data as $key => $value) {
						if (property_exists($obj, $key)) {
							$obj->$key = $value;
						}
					}
					$rs = $obj->Save();
					$this->printAlert("Item saved", 'success');
					return [
						"success" => true,
						"action" => "save",
						"data" => $obj,
						"rs" => $rs,
					];
				case "delete":
					unset($_POST["id"]);
					$rs = $obj->Delete();
					$this->printAlert("Item removed", 'danger');
					return [
						"success" => true,
						"action" => "delete",
						"data" => $obj,
						"rs" => $rs,
					];
			}
			return [
				"success" => false,
				"action" => $action,
				"data" => $data,
				"rs" => new Exception("unkown action: ".$action),
			];
		} catch(Exception $ex) {
			return [
				"success" => false,
				"action" => $action,
				"data" => $data,
				"rs" => $ex,
			];
		}
	}

}

?>