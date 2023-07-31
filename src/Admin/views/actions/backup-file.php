<?php

use Magrathea2\Admin\AdminDatabase;

$filename = $_REQUEST["filename"];
$viewFile = AdminDatabase::Instance()->GetBackupFolder()."/".$filename;

?>

<div class="card">
	<div class="card-header">
		View File: <?=$filename?>
		<div class="card-close" aria-label="Close" onclick="closeCard(this);">&times;</div>
	</div>
	<div class="card-body">
		<? include(__DIR__."/../actions/file-view.php"); ?>
	</div>
</div>
