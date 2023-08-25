<?php

use Magrathea2\Admin\AdminElements;
use Magrathea2\Admin\AdminForm;
use Magrathea2\Admin\AdminManager;

$elements = AdminElements::Instance();

$form = new AdminForm();
$formData = [
	[
		"name" => "User",
		"type" => $users,
		"id" => "user-selector",
		"size" => "col-5",
	],
	[
		"name" => " => ",
		"type" => "button",
		"action" => "tokenUser();",
		"class" => ["w-100", "btn-success"],
		"size" => "col-2",
	],
	[
		"name" => "Token",
		"type" => "text",
		"id" => "token",
		"size" => "col-5",

	]
];
$form->Build($formData, null);

?>

<div class="card">
	<div class="card-header">
		Authentication
		<div class="card-close" aria-label="Close" onclick="closeCard(this);">&times;</div>
	</div>
	<div class="card-body">
	<?
		$form->Print();
	?>
	</div>
</div>
