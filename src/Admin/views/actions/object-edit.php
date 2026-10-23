<?php

use Magrathea2\Admin\AdminElements;
use Magrathea2\Admin\ObjectManager;

$objectName = @$_GET["object"];

$adminElements = AdminElements::Instance();
if(!is_string($objectName) || $objectName === "") {
	$adminElements->ErrorCard("invalid object");
	return;
}
$data = ObjectManager::Instance()->GetObjectData($objectName);

// GET parameters are links an admin can be sent: escaped, and onclose only as a JS function name
$objectHtml = htmlspecialchars($objectName);
$objectJs = htmlspecialchars(json_encode($objectName));
$closeFn = $adminElements->JsCallback(@$_GET["onclose"], "this");

?>

<div class="card" id="editCard-<?=$objectHtml?>">
	<div class="card-header">
		Edit <b><?=htmlspecialchars(ucfirst($objectName))?></b>
		<div class="card-close" aria-label="Close" onclick="closeCard(this); <?=$closeFn?>">&times;</div>
	</div>
	<div class="card-body">
		<div class="row">
			<div class="col-sm-12 col-md-6" id="object-view-<?=$objectHtml?>">
				<?php
				$adminElements->Button("View Object Details", "viewObject(".$objectJs.")", ["btn-primary", "w-100"], null, false, ["id" => "btn-view-".$objectHtml]);
				?>
			</div>
			<div class="col-sm-12 col-md-6 mt-xs-4">
				<?php include(__DIR__."/object-relations.php"); ?>
			</div>
			<div class="col-4 offset-8">
				<?php
				$adminElements->Button("Delete Object", "deleteObject(".$objectJs.")", ["btn-danger", "w-100"], null);
				?>
			</div>
		</div>
	</div>
</div>
