<?php

use Magrathea2\Admin\AdminElements;
use Magrathea2\Admin\AdminManager;

$feature = AdminManager::Instance()->GetActiveFeature();

$elements = AdminElements::Instance();
$elements->Header("Explore API: ".$feature->apiName);

echo '<div class="container">';

$feature->GetAuthentication();
$feature->GetEndpoints();

?>
	<div class="card">
		<div class="card-header">
			debugger
			<div class="card-close" aria-label="Close" onclick="closeCard(this);">&times;</div>
		</div>
		<div class="card-body">
			<div class="row">
				<div class="col-12 mb-2 right">
					<?
					$elements->Button("clear", "clearDebugAPI()", ["btn-danger", "no-margin"]);
					?>
				</div>
				<div class="col-12">
					<pre class="code" id="api-debug"></pre>
				</div>
			</div>
		</div>
	</div>
</div>
