<?php

use Magrathea2\Admin\AdminElements;
use Magrathea2\Admin\ObjectManager;

$pageTitle = "site.caddy file";
$elements = AdminElements::Instance();
$elements->Header($pageTitle);

?>

<div class="container">
	<div class="card">
		<div class="card-header">
			site.caddy
		</div>
		<div class="card-body">
			<div class="row">
				<div class="col-3 offset-6">
					<?
					$elements->Button("Show Sample", "viewCaddySample()", ["btn-primary", "w-100"]);
					?>
				</div>
				<div class="col-3">
					<?
					$elements->Button("Show App site.caddy", "viewCaddyMyFile()", ["btn-success", "w-100"]);
					?>
				</div>
			</div>
		</div>
	</div>

	<div id="container-caddy-sample"></div>
	<div id="container-caddy-app"></div>
</div>

<script type="text/javascript">
function viewCaddySample() {
	callAction("caddy-sample")
		.then(rs => showOn("#container-caddy-sample", rs));
}

function viewCaddyMyFile() {
	callAction("caddy-view")
		.then(rs => showOn("#container-caddy-app", rs));
}
</script>
