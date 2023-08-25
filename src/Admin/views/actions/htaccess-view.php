<?php

$appFolder = \Magrathea2\MagratheaPHP::Instance()->appRoot;

$htaccessFile = $appFolder."/.htaccess";
$viewFile = $htaccessFile;

?>

<div class="card">
	<div class="card-header">
		app htaccess: [<b><?=$htaccessFile?></b>]
		<div class="card-close" aria-label="Close" onclick="closeCard(this);">&times;</div>
	</div>
	<div class="card-body">
	<? include(__DIR__."/../actions/file-view.php"); ?>
	</div>
</div>
