<?php

use Magrathea2\Admin\AdminElements;
use Magrathea2\Admin\AdminForm;
use Magrathea2\Admin\ObjectManager;

if(empty($objectName)) {
	$objectName = @$_GET["object"];
}

if(empty($objectName)) {
	AdminElements::Instance()->Alert("Incorrect object", "danger");
}

$relations = ObjectManager::Instance()->GetRelationsByObject($objectName);

$relationForms = [];
foreach($relations as $relation) {
	$form = new AdminForm();
	$formData = [
		[
			"type" => "hidden",
			"key" => "object-name",
		],
		[
			"type" => "hidden",
			"key" => "relation-name",
		],
		[
			"type" => "empty",
			"key" => "relation-desc",
			"size" => "col-6",
		],
		[
			"type" => "empty",
			"key" => "relation-field",
			"size" => "col-6",
		],
		[
			"type" => "text",
			"name" => "Public Property",
			"key" => "relation-property",
			"size" => "col-12",
		],
		[
			"type" => "text",
			"name" => "Public Method",
			"key" => "relation-method",
			"size" => "col-12",
		],
		[
			"type" => "button",
			"key" => "removeRelation(this);",
			"class" => "btn-danger w-100",
			"name" => "Delete",
			"size" => "col-3",
		],
		[
			"type" => "checkbox",
			"key" => "relation-lazy",
			"name" => "Lazy Load",
			"size" => "col-3 mt-4",
		],
		[
			"type" => "checkbox",
			"key" => "relation-auto",
			"name" => "Auto Load",
			"size" => "col-3 mt-4",
		],
		[
			"type" => "button",
			"key" => "saveRelation(this);",
			"class" => "btn-success w-100",
			"name" => "Save",
			"size" => "col-3",
		],
	];
	$description = str_replace('_', ' ', $relation["rel_type"])." &raquo; ";
	$description .= "<pre class='inline'>".$relation["rel_object"]."</pre>";
	$field = "using <pre class='inline'>".$relation["rel_field"]."</pre>";
	$formValues = [
		"object-name" => $objectName,
		"relation-name" => $relation["rel_name"],
		"relation-desc" => $description,
		"relation-field" => $field,
		"relation-property" => $relation["rel_property"],
		"relation-method" => $relation["rel_method"]."()",
		"relation-lazy" => $relation["rel_lazyload"],
		"relation-auto" => $relation["rel_autoload"],
	];
	$form->Build($formData, $formValues);
	array_push($relationForms, $form);
}

?>

<div class="card">
	<div class="card-header">Relations for <?=$objectName?></div>
	<div class="card-body">
		<?
		foreach($relationForms as $rForm) { 
			echo '<div class="row relation-form">';
			$rForm->Print();
			echo '</div>';
		}
		?>
		</div>
	</div>
</div>