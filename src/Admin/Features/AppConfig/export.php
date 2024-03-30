<?php

use Magrathea2\Admin\Features\AppConfig\AppConfigControl;
use Magrathea2\Admin\AdminManager;
use Magrathea2\Admin\AdminElements;

/** @var \Magrathea2\Admin\Features\AppConfig\AdminFeatureAppConfig $featureClass */
$featureClass = AdminManager::Instance()->GetActiveFeature();

$hideSystem = $featureClass->onlyApp;

$control = new AppConfigControl();
$exportStr = $control->ExportData($hideSystem);
$exportStr = str_replace('\n', "\n", $exportStr);


?>

<div class="card">
	<div class="card-header">
		App Configuration Export
	</div>
	<div class="card-body config-form">
		<div class="row">
			<div class="col-12" id="app-config-list">
				<div class="form-group">
					<label>Export String</label>
					<pre class="light grow" id="exportStr"><?=$exportStr?></pre>
					<?
					AdminElements::Instance()->Button("Copy", "copyExport()", ["btn-primary", "no-margin"]);
					?>
				</div>
			</div>
		</div>
	</div>
</div>
