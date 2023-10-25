<?php

use Magrathea2\Admin\ObjectManager;

$pageTitle = "Objects Config File";
\Magrathea2\Admin\AdminElements::Instance()->Header($pageTitle);

$objControl = ObjectManager::Instance();
$viewFile = $objControl->GetObjectFilePath();

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
