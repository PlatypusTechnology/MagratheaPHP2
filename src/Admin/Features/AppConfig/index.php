<?php

use Magrathea2\Admin\AdminElements;

AdminElements::Instance()->Header("App Configuration");

?>

<div class="container">

	<div class="card">
		<div class="card-header">
			App Configuration
		</div>
		<div class="card-body config-form">
			<div class="row">
				<div class="col-12" id="app-config-list">
					<?
					include(__DIR__."/list.php");
					?>
				</div>
			</div>
		</div>
	</div>

	<div class="row">
		<div class="col-6">
		<?
			$migrationUrl = $featureClass->GetSubpageUrl("Migration");
			AdminElements::Instance()->Button("Import/Export", "window.location.href='".$migrationUrl."'", ["btn-primary"]);
			?>
		</div>
		<div class="col-6 right">
			<?
			$newConfigUrl = $featureClass->GetSubpageUrl(null, ["id" => "new"]);
			AdminElements::Instance()->Button("Add Row", "window.location.href='".$newConfigUrl."'", ["btn-success"]);
			?>
		</div>
	</div>

	<div class="row mt-2">
		<div class="col-12">
			<?
			include(__DIR__."/form.php");
			?>
		</div>
	</div>

	<div class="row mt-2">
		<div class="col-12">
			<?
			include(__DIR__."/example.php");
			?>
		</div>
	</div>

</div>
