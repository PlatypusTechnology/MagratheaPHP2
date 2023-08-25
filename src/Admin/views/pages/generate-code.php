<?php

use Magrathea2\Admin\AdminElements;
use Magrathea2\Admin\AdminForm;
use Magrathea2\Admin\AdminUrls;
use Magrathea2\Admin\ObjectManager;
use Magrathea2\Admin\CodeManager;

use function Magrathea2\p_r;

$pageTitle = "Generate Code";
AdminElements::Instance()->Header($pageTitle);

$data = CodeManager::Instance()->GetCodeCreationData();
$codeData = @$data["data"];
$path = @$codeData["code-path"];
$structure = @$codeData["code-structure"];
if(empty($structure)) {
	$structure = "feature";
}
$namespace = @$codeData["code-namespace"];

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
			"key" => "saveCodeGenerationConfig(this);",
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
		echo "<br/><div class='message-container'>";
		AdminElements::Instance()->Alert("Can't create code: ".implode('; ', $data["errors"]).".", "danger", false);
		echo "<br/></div>";
	}
	?>

	<? include(__DIR__."/../actions/object-list.php"); ?>
	<div id="obj-responses"></div>
</div>


<script type="text/javascript">
let cards = [];
function objClick(obj) {
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

<?php include(__DIR__."/../javascript/code-creation.js"); ?>
</script>
