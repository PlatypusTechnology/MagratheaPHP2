<?php

use Magrathea2\Admin\AdminElements;

AdminElements::Instance()->Header("Cache");

?>

<div class="container">
	<div class="row">
		<div class="col-sm-12">
			<? include(__DIR__."/details.php"); ?>
		</div>
	</div>

	<div class="row mt-4">
		<div class="col-sm-12" id="cache-files">
			<? include(__DIR__."/files.php"); ?>
		</div>
	</div>

	<div class="row mt-2">
		<div class="col-sm-12" id="view-container"></div>
	</div>
</div>


