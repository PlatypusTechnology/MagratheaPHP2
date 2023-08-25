<?php

use Magrathea2\Admin\AdminElements;
use Magrathea2\Admin\AdminForm;
use Magrathea2\Admin\Models\AdminConfig;
use Magrathea2\Admin\Models\AdminConfigControl;

AdminElements::Instance()->Header("App Configuration");

?>
<div class="container">
<?
$adminForm = new AdminForm();
$adminForm->SetName("data-form");
$crud = $adminForm->CRUDObject(new AdminConfig(), true);

$newDataUrl = \Magrathea2\Admin\AdminUrls::Instance()
	->GetPageUrl("config-data", null, [ "id" => "new" ]);

$control = new AdminConfigControl();
$data = $control->GetAll();

?>

	<div class="card">
		<div class="card-header">
			Saved Data
		</div>
		<div class="card-body config-form">
			<div class="row">
				<div class="col-12">
					<?
					AdminElements::Instance()->Table($data, [ 
						[
							"title" => "Key",
							"key" => function($c) {
								return $c->GetKey();
							}
						],
						[
							"title" => "Value",
							"key" => function($c) {
								return $c->GetValue();
							}
						],
						[
							"title" => "&nbsp;",
							"key" => function($c) {
								$editUrl = \Magrathea2\Admin\AdminUrls::Instance()
								->GetPageUrl("config-data", null, [ "id" => $c->id ]);
								return '<a href="'.$editUrl.'">Edit</a>';
							}
						]
					]);
					?>
				</div>
			</div>
		</div>
	</div>

	<div class="row">
		<div class="col-12 right">
			<?
			AdminElements::Instance()->Button("New Data", "newData();", ["btn-primary"]);
			?>
		</div>
	</div>

<?
	if(@$_POST["magrathea-submit"] === "delete") {
		die;
	}
	if(@$_GET["id"]) {
		$id = $_GET["id"];
		if ($id === "new") {
			$c = new AdminConfig();
		} else {
			$c = new AdminConfig($id);
		}
		?>
	<div class="card card-form">
		<div class="card-header">
			Editing <b><?=$c->key?></b>
			<div class="card-close" aria-label="Close" onclick="closeCard(this);">&times;</div>
		</div>
		<div class="card-body">
			<?
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
		<?
	}
?>

</div>

<script type="text/javascript">
	function newData() {
		window.location.href='<?=$newDataUrl?>';
	}
</script>

