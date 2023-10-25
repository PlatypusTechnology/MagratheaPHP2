<?php

use Magrathea2\Admin\AdminElements;
use Magrathea2\Admin\ObjectManager;

$elements = AdminElements::Instance();
$control = ObjectManager::Instance();

$data = $_POST;

if(!$control->DoesObjectFileExists()) {
	$control->CreateObjectConfigFile();
}

$object = $data["object_name"];
if(!$object) {
	$elements->Alert("Could not find object name", "danger");
	die;
}
$objName = strtolower($object);
if(!$control->ValidateName($objName)) {
	$elements->Alert("Object name [".$object."] is not a valid name", "danger");
	die;
}

$table = $data["table_name"];

$success = $control->SaveObject($objName, $data);
if($success) {
	$elements->Alert("Data Saved!", "success");
} else {
	$elements->Alert("error saving data!", "danger");
}
