<?php

use Magrathea2\Admin\AdminElements;
use Magrathea2\Admin\AdminManager;
use Magrathea2\Admin\Features\AppConfig\AppConfigControl;

$control = new AppConfigControl();
$data = $control->GetAll();
$featureClass = AdminManager::Instance()->GetActiveFeature();

AdminElements::Instance()->Table($data, [ 
	[
		"title" => "Key",
		"key" => function($c) {
			return $c->GetKey();
		}
	],
	[
		"title" => "Value",
		"key" => function($c) {
			return $c->GetValue();
		}
	],
	[
		"title" => "&nbsp;",
		"key" => function($c) use ($featureClass) {
			return '<a href="'.$featureClass->GetSubpageUrl(null, [ "id" => $c->id ]).'">Edit</a>';
		}
	]
]);

?>
