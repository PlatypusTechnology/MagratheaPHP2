<?php

namespace Magrathea2\Admin\Api;

use Magrathea2\Exceptions\MagratheaApiException;
use Magrathea2\Admin\ObjectManager;

#######################################################################################
####
####    MAGRATHEA PHP2
####    v. 2.0
####    Magrathea by Paulo Henrique Martins
####    Platypus technology
####    Admin Api created: 2023-04 by Paulo Martins
####
#######################################################################################
class ObjectsApi extends \Magrathea2\MagratheaApiControl {

	public function ListObjects() {
		$objects = ObjectManager::Instance()->GetObjectList();
		return $objects;
	}

	public function GetProperties($params) {
		$object = @$params["object"];
		if(!$object) {
			throw new MagratheaApiException("invalid object: [".$object."]", false);
		}
		$details = ObjectManager::Instance()->GetObjectDetails($object);
		if(!$details) {
			throw new MagratheaApiException("could not find data for object [".$object."]", false);
		}
		return [
			"object" => $object,
			"fields" => array_values($details["public_properties"]),
		];
	}

	public function AddRelation($params) {
		$allGood = true;
		if(!$params["relation_type"]) $allGood = false;
		if(!$params["relation_object"]) $allGood = false;
		if(!$params["this_object"]) $allGood = false;
		if(!$params["relation_property"]) $allGood = false;
		
		if(!$allGood) {
			throw new MagratheaApiException("invalid data", false, 400, $params);
		}
		$rel = ObjectManager::Instance()->AddRelation(
			$params["relation_type"],
			$params["this_object"],
			$params["relation_object"],
			$params["relation_property"]
		);
		return $rel;
	}

	public function DeleteRelation($params) {
		$relName = $params["name"];
		if(!$relName) {
			throw new MagratheaApiException("invalid data", false, 400, $params);
		}
		return ObjectManager::Instance()->DeleteRelation($relName);
	}

	public function UpdateRelation($params) {
		$relName = @$params["name"];
		unset($params["name"]);
		unset($params["magrathea_api"]);
		unset($params["magrathea_api_method"]);
		if(!$relName) {
			throw new MagratheaApiException("invalid data", false, 400, $params);
		}
		return ObjectManager::Instance()->UpdateRelation($relName, $params);
	}

}

