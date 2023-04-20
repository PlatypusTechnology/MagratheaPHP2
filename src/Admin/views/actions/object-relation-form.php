<?php

use Magrathea2\Admin\AdminForm;
use Magrathea2\Admin\ObjectManager;

$rel_types = [
	"belongs_to" => "belongs to", 
	"has_many" => "has many", 
	"has_and_belongs_to_many" => "has and belongs to many", 
];

$objects = ObjectManager::Instance()->GetObjectList();
$objects = array_combine(array_map('strtolower', $objects), $objects);

$formData = [
	[
		"name" => "Relation Type",
		"size" => "col-6",
		"key" => "relation_type",
		"type" => $rel_types,
		"attributes" => [
			"onchange" => "relationChange(this);"
		],
	],
	[
		"name" => "Related to",
		"size" => "col-6",
		"key" => "relation_object",
		"placeholder" => "Select the object to connect",
		"type" => $objects,
		"attributes" => [
			"onchange" => "relationChange(this);"
		],
	],
	[
		"type" => "hidden",
		"key" => "this_object",
		"size" => "hidden",
	],
	[
		"type" => "empty",
		"size" => "col-6 mt-4 right",
		"key" => "relation_property_description"
	],
	[
		"name" => false,
		"size" => "col-6 mt-2 relation-property-container",
		"key" => "relation_property",
		"placeholder" => "Select the object to connect",
		"type" => [],
	],
	[
		"type" => "button",
		"size" => "col-6",
		"class" => "btn-danger w-100",
		"name" => "Cancel",
		"key" => "toggleAddRelation('".$objectName."', false);",
	],
	[
		"type" => "button",
		"size" => "col-6",
		"class" => "btn-success w-100",
		"name" => "Add Relation",
		"key" => "addRelation(this);",
	],
];
$form = new AdminForm();
$form
	->SetName("form-relations-".$objectName)
	->Build($formData, [ "this_object" => $objectName ]);

?>

<div class="card">
	<div class="card-header">
		Add Relation
		<div class="card-close" aria-label="Close" onclick="toggleAddRelation('<?=$objectName?>', false);">&times;</div>
	</div>
	<div class="card-body">
		<?
			$form->Print();
		?>
		<div class="row" id="relation-<?=$objectName?>-response"></div>
	</div>
</div>
