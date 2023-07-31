<?php

namespace Magrathea2\Admin;

use Magrathea2\Admin\Models\AdminConfigControl;
use Magrathea2\ConfigFile;
use Magrathea2\MagratheaPHP;


#######################################################################################
####
####    MAGRATHEA PHP2
####    v. 2.0
####    Magrathea by Paulo Henrique Martins
####    Platypus technology
####    Object Manager created: 2023-04 by Paulo Martins
####
#######################################################################################

/**
* This class will help you deal with objects
*/
class ObjectManager extends \Magrathea2\Singleton {

	public $fileName = "magrathea_objects.conf";
	private $filePath;
	private $confObject;
	private $objData;

	/**
	 * sets the object file
	 */
	public function SetObjectFilePath($file): ObjectManager {
		$this->filePath = $file;
		return $this;
	}

	/**
	 * if object file path is not set, sets it to default config path
	 * returns full path with file name
	 */
	public function GetObjectFilePath(): string {
		if(empty($this->filePath)) {
			$this->filePath = MagratheaPHP::Instance()->getConfigRoot();
		}
		return $this->filePath;
	}

	/**
	 * returns a ConfigFile object with the object configuration
	 * @return 	ConfigFile;
	 */
	private function GetObjectConfigFile(): ConfigFile {
		if(!$this->confObject) {
			$fileControl = new ConfigFile();
			$fileControl->SetPath($this->GetObjectFilePath())->SetFile($this->fileName);
			$this->confObject = $fileControl;
		}
		return $this->confObject;
	}

	private function GetFullObjectData(): array {
		if(!$this->objData) {
			$this->objData = $this->GetObjectConfigFile()->GetConfig();
		}
		return $this->objData;
	}

	/**
	 * gets the object list
	 */
	public function GetObjectList(): array {
		try {
			$objects = $this->GetFullObjectData();
			unset($objects["relations"]);
			ksort($objects);
			$names = array_keys($objects);
			return array_map('ucfirst', $names);
		} catch(\Exception $ex) {
			throw $ex;
		}
	}

	/**
	 * Gets objects data by the name
	 * @param string $name		object name
	 * @return array					object data
	 */
	public function GetObjectData($name): array|null {
		$configData = $this->GetFullObjectData();
		return @$configData[strtolower($name)];
	}

	/**
	 * Gets objects details by the name
	 * @param string $name		object name
	 * @return array					object details
	 */
	public function GetObjectDetails($name): array|null {
		$data = $this->GetObjectData($name);
		if(!$data) return null;
		$data["name"] = ucfirst($name);
		$object = [];
		$object["name"] = $data["name"];
		$object["table"] = $data["table_name"];
		$object["public_properties"] = $this->GetPublicProperties($data);
		$object["public_methods"] = $this->GetPublicMethods($data);
		return $object;
	}

	/**
	 * Gets the public properties from object data
	 * @param array $obj_data		object data
	 * @return array		public properties in format ["name", "description", "type"]
	 */
	public function GetPublicProperties($obj_data): array {
		$props = [];
		foreach($obj_data as $key => $value){
			if( substr($key, -6) == "_alias" ){
				$name = substr($key, 0, -6);
				if(empty($props[$name])) $props[$name] = [];
				$desc = "";
				if($name == $obj_data["db_pk"]) $desc .= "PK";
				if(!empty($value)) $desc .= "(alias: ".$value.")";
				$props[$name]["name"] = $name;
				$props[$name]["description"] = $desc;
				continue;
			}
			if( substr($key, -5) == "_type" ){
				$name = substr($key, 0, -5);
				if(empty($props[$name])) $props[$name] = [];
				$props[$name]["type"] = $value;
			}
		}
		return $props;
	}

	/**
	 * gets a relation index and extracts the relation data
	 * @param int $index		relation index
	 * @return array				relation data
	 */
	private function ExtractRelFromRelArray($index): array {
		$rel_arr = $this->GetRawRelations();
		$relation = array();
		$relation["rel_name"] = $rel_arr["rel_name"][$index];
		$relation["rel_obj_base"] = $rel_arr["rel_obj_base"][$index];
		$relation["rel_type"] = $rel_arr["rel_type"][$index];
		$relation["rel_object"] = $rel_arr["rel_object"][$index];
		$relation["rel_field"] = $rel_arr["rel_field"][$index];
		$relation["rel_property"] = $rel_arr["rel_property"][$index];
		$relation["rel_method"] = $rel_arr["rel_method"][$index];
		$relation["rel_lazyload"] = (@$rel_arr["rel_lazyload"][$index] == true);
		$relation["rel_autoload"] = (@$rel_arr["rel_autoload"][$index] == true);
		return $relation;
	}
	
	/**
	 * Gets raw relations config
	 * @return	array|null			raw relations data
	 */
	public function GetRawRelations(): array|null {
		$configData = $this->GetFullObjectData();
		return @$configData["relations"];
	}

	/** Gets all relations
	 * @return array	relations;
	 */
	public function GetAllRelations(): array {
		$rels = $this->GetRawRelations();
		$relations = array();
		if( count($rels) == 0 ) return $relations;
		$index = 0;
		foreach( $rels["rel_obj_base"] as $object ){
			if(!isset($relations[$object])) $relations[$object] = [];
			array_push($relations[$object], $this->ExtractRelFromRelArray($index));
			$index++;
		}
		return $relations;
	}

	/**
	 * Gets all relations from an object
	 * @param string $obj 		object name
	 * @return array	relations
	 */
	public function GetRelationsByObject($obj): array {
		$rels = $this->GetRawRelations();
		$relations = array();
		if( !$rels || count($rels) == 0 ) return $relations;
		foreach( $rels["rel_obj_base"] as $index => $objbase ){
			if($objbase == $obj){
				array_push($relations, $this->ExtractRelFromRelArray($index));
			}
		}
		return $relations;
	}

	/**
	 * returns the index of a relation by its name
	 * @param string $name		relation name
	 * @return int		index inside magrathea_objects.conf
	 */
	private function GetRelationIndexByName($name): int {
		$rels = $this->GetRawRelations();
		if(!$rels) return -1;
		foreach($rels["rel_name"] as $i => $r) {
			if($r == $name)
				return $i;
		}
		return -1;
	}

	/**
	 * Returns a relation by the name
	 * @param string $name name of the string
	 * @return array		relation
	 */
	public function GetRelationByName($name): array {
		$index = $this->GetRelationIndexByName($name);
		if($index == -1) return [];
		return $this->ExtractRelFromRelArray($index);
	}

	private function SaveRelationArray($arr): bool {
		$data = $this->objData;
		$data["relations"] = $arr;
		$this->objData = null;
		return $this->confObject->SetConfig($data)->Save();
	}

	/**
	 * Adds object relation
	 * @param string $type  		Type, can be: "has_many", "belongs_to", "has_and_belongs_to_many"
	 * @param string $objBae		Object base
	 * @param string $otherObject		Object related
	 * @param string $field			relation field
	 * @param boolean $isMirror	is a mirror relation?
	 */
	public function AddRelation($type, $objBase, $otherObject, $field, $isMirror=false): array {
		$lazyLoad = true;
		$autoLoad = false;
		$otherObject = ucfirst($otherObject);
		$rel_name = "rel_".$objBase."_".$type."_".$otherObject."_with=".$field;
		switch($type){
			case "has_many":
				$property = $otherObject."s";
				$method = "Get".$otherObject."s";
			break;
			case "belongs_to":
			default:
				$property = $otherObject;
				$method = "Get".$otherObject;
			break;
		}

		$existingRelation = $this->GetRelationByName($rel_name);
		if(count($existingRelation) > 0) {
			return [
				"success" => false,
				"error" => "Relation [".$rel_name."] already exists",
			];
		}

		$relations = $this->GetRawRelations();
		if(!$relations) $rel_id = 0;
		else $rel_id = count($relations["rel_name"]);

		$relations["rel_name"][$rel_id] = $rel_name;
		$relations["rel_obj_base"][$rel_id] = $objBase;
		$relations["rel_type"][$rel_id] = $type;
		$relations["rel_object"][$rel_id] = $otherObject;
		$relations["rel_field"][$rel_id] = $field;
		$relations["rel_property"][$rel_id] = $property;
		$relations["rel_method"][$rel_id] = $method;
		$relations["rel_lazyload"][$rel_id] = $lazyLoad;
		$relations["rel_autoload"][$rel_id] = $autoLoad;

		$success = $this->SaveRelationArray($relations);
		$names = [];
		array_push($names, $rel_name);

		if(!$isMirror) {
			// create mirror relation:
			switch($type){
				case "belongs_to":
					$mirrorType = "has_many";
				break;
				case "has_many":
					$mirrorType = "belongs_to";
				break;
				default:
					$mirrorType = $type;
				break;
			}
			$mirrorSt = $this->AddRelation($mirrorType, $otherObject, $objBase, $field, true);
			array_push($names, $mirrorSt["name"]);
		} else {
			return [
				"success" => $success,
				"name" => $rel_name
			];
		}
		return [
			"success" => $success,
			"names" => $names
		];
	}

	/**
	 * Deletes a relation
	 * @param string $name		relation name
	 * @param bool $is_mirror	is a mirror relation>?
	 * @return bool	success?
	 */
	public function DeleteRelation($relation_name, $is_mirror=false): bool {
		$arr_relation = $this->GetRawRelations();
		$rel_index = $this->GetRelationIndexByName($relation_name);
		if( $rel_index >= 0 ){
			$type = @$arr_relation["rel_type"][$rel_index];
			$obj = @$arr_relation["rel_object"][$rel_index];
			$base_obj = @$arr_relation["rel_obj_base"][$rel_index];
			$field = @$arr_relation["rel_field"][$rel_index];
			unset($arr_relation["rel_name"][$rel_index]);
			unset($arr_relation["rel_obj_base"][$rel_index]);
			unset($arr_relation["rel_type"][$rel_index]);
			unset($arr_relation["rel_type_text"][$rel_index]);
			unset($arr_relation["rel_object"][$rel_index]);
			unset($arr_relation["rel_field"][$rel_index]);
			unset($arr_relation["rel_property"][$rel_index]);
			unset($arr_relation["rel_method"][$rel_index]);
			unset($arr_relation["rel_lazyload"][$rel_index]);
			unset($arr_relation["rel_autoload"][$rel_index]);
			$success = $this->SaveRelationArray($arr_relation);

			if( !$is_mirror ){
				$mirror_obj = $base_obj;
				$mirror_base_obj = $obj;
				switch($type){
					case "belongs_to":
						$mirror_type = "has_many";
					break;
					case "has_many":
						$mirror_type = "belongs_to";
					break;
					default:
						$mirror_type = $type;
					break;
				}
				$mirror_name = "rel_".$mirror_base_obj."_".$mirror_type."_".$mirror_obj."_with=".$field;
				return $this->DeleteRelation($mirror_name, true);
			} else {
				return $success;
			}
		} else {
			return false;
		}
	}

	/**
	 * Updates a relation
	 * @param string $relName		relation's name
	 * @param	array	$newData		new data to update (as from config file)
	 * @return bool		success?
	 */
	function UpdateRelation($relName, $newData): bool {
		$index = $this->GetRelationIndexByName($relName);
		if($index == -1) return false;
		$rels = $this->GetRawRelations();
		foreach($newData as $key => $value) {
			$rels[$key][$index] = $value;
		}
		return $this->SaveRelationArray($rels);
	}

	/**
	 * Gets the automatically generated methods
	 * @return array		public methods in format ["name", "description"]
	 */
	public function GetPublicMethods($obj_data): array {
		$name = $obj_data["name"];
		return [
			"insert" => [
				"name" => "Insert()",
				"description" => "Creates new ".$name,
			],
			"update" => [
				"name" => "Update()",
				"description" => "Updates the ".$name,
			],
			"save" => [
				"name" => "Save()",
				"description" => "Saves the ".$name.": updates if exists, inserts otherwise",
			],
			"delete" => [
				"name" => "Delete()",
				"description" => "Deletes the ".$name,
			],
			"get_id" => [
				"name" => "GetId()",
				"description" => "Gets ".$name."'s id",
			],
			"get_by_id" => [
				"name" => "GetById(\$id)",
				"description" => "Gets the ".$name." with the given id",
			],
		];
	}

}
