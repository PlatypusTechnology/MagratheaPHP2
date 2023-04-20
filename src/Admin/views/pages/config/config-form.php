<?php

use Magrathea2\Admin\AdminForm;

use function Magrathea2\p_r;

$formData = [];
foreach ($configFormData as $item => $value) {
	array_push($formData,
		[
			"name" => $item,
			"key" => $item,
			"type" => "text",
			"size" => "col-sm-12 col-xs-12 inline-form",
		]
	);
}

array_push(
	$formData,
	[
		"name" => "magrathea_use_environment",
		"key" => "magrathea_use_environment",
		"type" => "hidden",
	],
	[
		"name" => "magrathea-action",
		"key" => "magrathea-action",
		"type" => "hidden",
	]
);
$configFormData["magrathea-action"] = "config-save";
$configFormData["magrathea_use_environment"] = $activeEnv;

array_push(
	$formData,
	[
		"name" => "Save",
		"type" => "submit",
		"key" => "config-save",
		"size" => "col-6 offset-6",
		"class" => "btn-success w-100"
	]
);
$configForm = new AdminForm();
$configForm->defaultSize = "col-6";
$configForm->Build($formData, $configFormData)->Print();


