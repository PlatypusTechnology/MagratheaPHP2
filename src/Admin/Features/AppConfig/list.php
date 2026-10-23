<?php

use Magrathea2\Admin\AdminElements;
use Magrathea2\Admin\AdminManager;
use Magrathea2\Admin\Features\AppConfig\AppConfigControl;

$control = new AppConfigControl();
/** @var \Magrathea2\Admin\Features\AppConfig\AdminFeatureAppConfig $featureClass */
$featureClass = AdminManager::Instance()->GetActiveFeature();

$hideSystem = $featureClass->onlyApp;

$tableData = [];
if($hideSystem) {
	$data = $control->GetOnlyApp();
} else {
	$data = $control->GetAll();
	array_push($tableData, [
		"title" => "System",
		"key" => function($c) {
			return $c->is_system ? "&check;" : "";
		},
	]);
}

// callable columns are printed as HTML: escape the data inside them
array_push($tableData,
	[
		"title" => "Key",
		"key" => function($c) {
			return htmlspecialchars((string)$c->GetKey());
		}
	],
	[
		"title" => "Value",
		"key" => function($c) {
			return htmlspecialchars((string)$c->GetValue());
		}
	],
	[
		"title" => "&nbsp;",
		"key" => function($c) use ($featureClass) {
			return '<a href="'.$featureClass->GetSubpageUrl(null, [ "id" => $c->id ]).'">Edit</a>';
		}
	]);

AdminElements::Instance()->Table($data, $tableData);
