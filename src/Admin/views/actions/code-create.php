<?php

use Magrathea2\Admin\AdminElements;
use Magrathea2\Admin\CodeCreator;
use Magrathea2\Admin\CodeManager;

use function Magrathea2\p_r;

$codeManager = CodeManager::Instance();
$adminElements = AdminElements::Instance();
$object = @$_GET["object"];
if(empty($object) || !is_string($object)) {
	$adminElements->Alert("invalid object!", "danger");
	die;
}

// GET parameters are links an admin can be sent: escaped, and onclose only as a JS function name
$objectHtml = htmlspecialchars($object);
$objectJs = htmlspecialchars(json_encode($object));
$closeFn = $adminElements->JsCallback(@$_GET["onclose"], $objectJs);

$creationData = CodeCreator::Instance()->GetCodeCreationData();
?>

<div class="card">
	<div class="card-header">
		Code for <?=$objectHtml?>
		<div class="card-close" aria-label="Close" onclick="closeCard(this); <?=$closeFn?>">&times;</div>
	</div>
	<div class="card-body">
		<div class="row">
			<?php
			if(!$creationData["success"]) {
				$adminElements->Alert("Can't create code for ".$objectHtml.": ".htmlspecialchars(implode('; ', $creationData["errors"])).".", "danger", false);
			}
			$data = $codeManager->PrepareStructureForCodeGeneration($object);
			?>
			<div class="col-9 mt-3 folder_rs_<?=$objectHtml?>">
				<b>Code Destinations:</b> <?=htmlspecialchars((string)$data["code-destination"])?><br/>
				<b>Paths:</b><br/>
				<?=implode("<br/>", array_map("htmlspecialchars", $data["paths"]))?>
			</div>
			<div class="col-3">
			<?php
				if($data["ready"] === true) {
					$hideCreation = '';
					?>
					<div class="success pt-3">Destination folders are ok</div>
					<?php
				} else {
					$hideCreation = 'style="display: none;"';
					$adminElements->Button("Check Folders", "folderCreation(".$objectJs.", this);", ["btn-primary","mt-0"]);
				}
			?>
			</div>
		</div>
		<div class="row border-top code_create_<?=$objectHtml?>" <?=$hideCreation?>>
			<div class="col-6">
			<?php
				$adminElements->Button("View Code", "viewCodeGen(".$objectJs.");");
			?>
			</div>
			<div class="col-6">
			</div>
		</div>
		<div class="row code_create_rs_<?=$objectHtml?>">
			<div class="col-12 code_create_rs_<?=$objectHtml?> mt-3">

			</div>
		</div>
	</div>
</div>
