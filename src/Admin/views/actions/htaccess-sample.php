<?php

$magratheaFolder = \Magrathea2\MagratheaPHP::Instance()->GetMagratheaRoot();
$viewFile = $magratheaFolder."/Bootstrap/docs/htaccess.sample";

?>

<div class="card">
	<div class="card-header">
		sample htaccess
		<div class="card-close" aria-label="Close" onclick="closeCard(this);">&times;</div>
	</div>
	<div class="card-body">
	<? include(__DIR__."/../actions/file-view.php"); ?>
	</div>
</div>
