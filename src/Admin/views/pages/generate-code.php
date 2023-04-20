<?php

use Magrathea2\Admin\AdminElements;
use Magrathea2\Admin\AdminForm;
use Magrathea2\Admin\AdminUrls;
use Magrathea2\Admin\ObjectManager;

$pageTitle = "Generate Code";
include(__DIR__."/../sections/header.php");

$data = ObjectManager::Instance()->GetCodeCreaationData();
$codeData = $data["data"];
$path = $codeData["code-path"];
$structure = $codeData["structure"];
if(empty($structure)) {
	$structure = "feature";
}
$namespace = $codeData["namespace"];

$structureForm = new AdminForm();
$structureForm
	->SetName("structure")
	->Build([
		[
			"type" => "text",
			"id" => "code-path",
			"name" => "Code Path",
			"size" => "col-4",
			"placeholder" => "Path where code will be located"
		],
		[
			"type" => [
				"mvc" => "Model-View-Control",
				"feature" => "Features"
			],
			"id" => "code-structure",
			"name" => "Structure Type",
			"size" => "col-4",
		],
		[
			"type" => "text",
			"id" => "code-namespace",
			"name" => "Namespace",
			"size" => "col-4",
			"placeholder" => "namespace"
		],
		[
			"type" => "button",
			"name" => "Validate Structure",
			"key" => "window.location.href='".AdminUrls::Instance()->GetPageUrl("structure")."'",
			"class" => "w-100 btn-primary",
			"size" => "col-4",
		],
		[
			"type" => "empty",
			"key" => "structure-explanation",
			"size" => "col-4 mt-2",
		],
		[
			"type" => "button",
			"name" => "Save",
			"class" => "w-100 btn-success",
			"size" => "col-4",
		],
	],
	[
		"code-path" => $path,
		"code-structure" => $structure,
		"code-namespace" => $namespace,
		"structure-explanation" => function() {
			$html = "";
			$html .= "<b>Model-View-Control</b><br/>";
			$html .= "Will create a folder with all the Models.<br/><br/>";
			$html .= "<b>Features</b><br/>";
			$html .= "Will create a folder for each Object.";
			return $html;
		}
	]);

?>

<style>
#file-view {
	max-height: 750px;
	overflow-y: scroll;
}
</style>

<div class="container">
	<div class="card">
		<div class="card-header">
			Generate Code
		</div>
		<div class="card-body">
			<? $structureForm->Print(); ?>
		</div>
	</div>
	<?
	if(!$data["success"]) {
		echo "<br/>";
		AdminElements::Instance()->Alert("Can't create code: ".implode('; ', $data["errors"]).".", "danger");
		echo "<br/>";
	}
	?>

	<? include(__DIR__."/../actions/object-list.php"); ?>
	<div id="obj-responses"></div>
</div>


<script type="text/javascript">
let cards = [];
function objClick(obj) {
	console.info('openingn ' +obj);
	if(cards[obj]) return;
	$("#btn-"+obj).addClass("active");
	cards[obj] = true;
	callAction("code-create&onclose=closeCodeCard&object=" + obj)
		.then(rs => {
			$("#obj-responses").prepend(rs)
		});
}
function closeCodeCard(obj) {
	cards[obj] = false;
	$("#btn-"+obj).removeClass("active");
}

</script>