<?php

use Magrathea2\Admin\AdminElements;
use Magrathea2\Admin\AdminManager;

AdminElements::Instance()->Header("App Configuration Migration");

?>

<div class="container">
	<div class="row">
		<div class="col-12">
		<?
			$backUrl = AdminManager::Instance()->GetActiveFeature()->GetSubpageUrl(null);
			AdminElements::Instance()->Button("Back", "window.location.href='".$backUrl."'", ["btn-primary"]);
		?>
		</div>
		<div class="col-6">
			<? include(__DIR__."/import.php"); ?>
		</div>
		<div class="col-6">
			<? include(__DIR__."/export.php"); ?>
		</div>
		<div class="col-12 mt-4" id="migration-rs"></div>
	</div>
</div>
