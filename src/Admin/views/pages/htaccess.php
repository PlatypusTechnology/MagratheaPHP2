<?php

use Magrathea2\Admin\AdminElements;
use Magrathea2\Admin\ObjectManager;

$pageTitle = ".htaccess file";
$elements = AdminElements::Instance();
$elements->Header($pageTitle);

?>

<div class="container">
	<div class="card">
		<div class="card-header">
			.htaccess
		</div>
		<div class="card-body">
			<div class="row">
				<div class="col-3 offset-6">
					<?
					$elements->Button("Show Sample", "viewHtaccessSample()", ["btn-primary", "w-100"]);
					?>
				</div>
				<div class="col-3">
					<?
					$elements->Button("Show App .htaccess", "viewHtaccessMyFile()", ["btn-success", "w-100"]);
					?>
				</div>
			</div>
		</div>
	</div>

	<div id="container-htaccess-sample"></div>
	<div id="container-htaccess-app"></div>
</div>

<script type="text/javascript">
function viewHtaccessSample() {
	callAction("htaccess-sample")
		.then(rs => showOn("#container-htaccess-sample", rs));
}

function viewHtaccessMyFile() {
	callAction("htaccess-view")
		.then(rs => showOn("#container-htaccess-app", rs));
}
</script>
