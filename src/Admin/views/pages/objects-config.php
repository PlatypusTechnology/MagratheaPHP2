<?php

use Magrathea2\Admin\ObjectManager;

$pageTitle = "Objects Config File";
include(__DIR__."/../sections/header.php");

$objControl = ObjectManager::Instance();
$viewFile = $objControl->GetObjectFilePath()."/".$objControl->fileName;

?>

<div class="container">
	<div class="card">
		<div class="card-header">
			<?=$viewFile?>
		</div>
		<div class="card-body">
		<? include(__DIR__."/../actions/file-view.php"); ?>
		</div>
	</div>

</div>
