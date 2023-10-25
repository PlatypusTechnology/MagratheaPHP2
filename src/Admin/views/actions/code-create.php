<?php

use Magrathea2\Admin\AdminElements;
use Magrathea2\Admin\CodeCreator;
use Magrathea2\Admin\CodeManager;

use function Magrathea2\p_r;

$codeManager = CodeManager::Instance();
$adminElements = AdminElements::Instance();
$object = $_GET["object"];
if(empty($object)) {
	$adminElements->Alert("invalid object!", "danger");
	die;
}

$closeFn = @$_GET["onclose"];
if($closeFn) {
	$closeFn = $closeFn."('".$object."');";
}

$creationData = CodeCreator::Instance()->GetCodeCreationData();
?>

<div class="card">
	<div class="card-header">
		Code for <?=$object?>
		<div class="card-close" aria-label="Close" onclick="closeCard(this); <?=$closeFn?>">&times;</div>
	</div>
	<div class="card-body">
		<div class="row">
			<?
			if(!$creationData["success"]) {
				$adminElements->Alert("Can't create code for ".$object.": ".implode('; ', $data["errors"]).".", "danger", false);
			}
			$data = $codeManager->PrepareStructureForCodeGeneration($object);
			?>
			<div class="col-9 mt-3 folder_rs_<?=$object?>">
				<b>Code Destinations:</b> <?=$data["code-destination"]?><br/>
				<b>Paths:</b><br/>
				<?=implode("<br/>", $data["paths"])?>
			</div>
			<div class="col-3">
			<?
				if($data["ready"] === true) {
					$hideCreation = '';
					?>
					<div class="success pt-3">Destination folders are ok</div>
					<?
				} else {
					$hideCreation = 'style="display: none;"';
					$adminElements->Button("Check Folders", "folderCreation('".$object."', this);", ["btn-primary","mt-0"]);
				}
			?>
			</div>
		</div>
		<div class="row border-top code_create_<?=$object?>" <?=$hideCreation?>>
			<div class="col-6">
			<?
				$adminElements->Button("View Code", "viewCodeGen('".$object."');");
			?>
			</div>
			<div class="col-6">
			</div>
		</div>
		<div class="row code_create_rs_<?=$object?>">
			<div class="col-12 code_create_rs_<?=$object?> mt-3">

			</div>
		</div>
	</div>
</div>
