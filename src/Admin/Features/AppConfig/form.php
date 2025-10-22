<?php

use Magrathea2\Admin\AdminElements;
use Magrathea2\Admin\AdminForm;
use Magrathea2\Admin\Features\AppConfig\AppConfig;
use Magrathea2\MagratheaCache;

$adminForm = new AdminForm();
$adminForm->SetName("data-form");
$crud = $adminForm->CRUDObject(new AppConfig(), true);

if(!$crud["success"]) {
	if($crud["rs"]) {
		$ex = $crud["rs"];
		if( $ex instanceof Exception) {
			AdminElements::Instance()->Alert($ex->getMessage(), 'danger');
		}
	}
} else {
	MagratheaCache::Instance()->DeleteFile("settings");
}

if(!empty($crud["action"])) {
	?>
	<script type="text/javascript">
		afterLoad().then(() => updateAppConfigList());
	</script>
	<?php
}

if(@$_POST["magrathea-submit"] === "delete") {
	return;
}
if(@$_GET["id"]) {
	$id = $_GET["id"];
	if ($id === "new") {
		$c = new AppConfig();
	} else {
		$c = new AppConfig($id);
	}
	?>
<div class="card card-form">
	<div class="card-header">
		Editing <b><?=$c->key?></b>
		<div class="card-close" aria-label="Close" onclick="closeCard(this);">&times;</div>
	</div>
	<div class="card-body">
		<?php
		$adminForm->Build(
			[
				[
					"name" => "#ID",
					"key" => "id",
					"type" => "disabled",
					"size" => "col-1",
				],
				[
					"name" => "Key",
					"key" => "name",
					"type" => "text",
					"size" => "col-3",
				],
				[
					"name" => "Value",
					"key" => "value",
					"type" => "text",
					"size" => "col-3",
				],
				[
					"type" => "delete-button",
					"size" => "col-2",
				],
				[
					"type" => "save-button",
					"size" => "col-3",
				],
			],
			$c
		)->Print();
		?>
	</div>
</div>
	<?php
}