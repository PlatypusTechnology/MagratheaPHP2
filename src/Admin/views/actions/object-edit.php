<?php

use Magrathea2\Admin\AdminElements;
use Magrathea2\Admin\ObjectManager;

$objectName = $_GET["object"];

$adminElements = AdminElements::Instance();
$data = ObjectManager::Instance()->GetObjectData($objectName);

$closeFn = @$_GET["onclose"];
if($closeFn) {
	$closeFn = $closeFn."(this);";
}

?>

<div class="card" id="editCard-<?=$objectName?>">
	<div class="card-header">
		Edit <b><?=ucfirst($objectName)?></b>
		<div class="card-close" aria-label="Close" onclick="closeCard(this); <?=$closeFn?>">&times;</div>
	</div>
	<div class="card-body">
		<div class="row">
			<div class="col-sm-12 col-md-6" id="object-view-<?=$objectName?>">
				<?
				$adminElements->Button("View Object Details", "viewObject('".$objectName."')", ["btn-primary", "w-100"], null, false, ["id" => "btn-view-".$objectName]);
				?>
			</div>
			<div class="col-sm-12 col-md-6 mt-xs-4">
				<? include(__DIR__."/object-relations.php"); ?>
			</div>
		</div>
	</div>
</div>

