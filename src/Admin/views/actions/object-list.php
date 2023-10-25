<?php

use Magrathea2\Admin\ObjectManager;

$objManager = ObjectManager::Instance();
if(!$objManager->DoesObjectFileExists()) {
	$msg = "magratha conf file (and objects) does not exist";
	\Magrathea2\Admin\AdminElements::Instance()->Alert($msg, "danger");
	return;
}
$objects = $objManager->GetObjectList();

?>

<style>
.obj-cell {
	text-align: center;
	padding: 5px;
}
.obj-cell span {
	border: 1px solid var(--primary);
	font-weight: bold;
	color: var(--primary);
	display: block;
	padding: 5px 10px;
	width: 100%;
}
.obj-cell span.active {
	background-color: var(--primary);
	color: var(--white);
}
</style>

<div class="card">
	<div class="card-header">
		Objects
	</div>
	<div class="card-body">
		<div class="row">
			<?php
				foreach($objects as $o) {
					$objClick = "objClick('".$o."')";
					echo '<div class="col-3 obj-cell pointer" onclick="'.$objClick.'"><span id="btn-'.$o.'">'.$o.'</span></div>';
				}
			?>
		</div>
	</div>
</div>
