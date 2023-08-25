<?php

$pageTitle = "Admin Scripts";
\Magrathea2\Admin\AdminElements::Instance()->Header($pageTitle);

?>

<style>
</style>

<div class="container">

	<div class="card">
		<div class="card-header">
			Javascript File - javascript/scripts.js
			<div class="card-close" aria-label="Close" onclick="closeCard(this);">&times;</div>
		</div>
		<div class="card-body">
			<div class="row">
				<div class="col-12">
					<?
					$viewFile = __DIR__."/../javascript/scripts.js";
					include(__DIR__."/../actions/file-view.php");
					?>
				</div>
			</div>
		</div>
	</div>

</div>
