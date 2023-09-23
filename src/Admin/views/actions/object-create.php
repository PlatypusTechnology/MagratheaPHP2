<?php

use Magrathea2\Admin\AdminDatabase;
use Magrathea2\Admin\AdminElements;
use Magrathea2\Admin\AdminUrls;
use Magrathea2\Admin\ObjectManager;

use function Magrathea2\p_r;

$table = $_GET["table"];

$objManager = ObjectManager::Instance();

$objectDataConfig = $objManager->GetObjectDataByTable($table);
$objectData = [];
$alreadyExists = false;
$name = ucfirst($table);
if($objectDataConfig) {
	$objectData = $objectDataConfig["data"];
	$name = ucfirst($objectDataConfig["object"]);
	$alreadyExists = true;
}

$closeFn = @$_GET["onclose"];
if($closeFn) {
	$closeFn = $closeFn."(this);";
}

$elements = AdminElements::Instance();
$fields = AdminDatabase::Instance()->GetTableColumns($table);

// solution given by chatGPT
function hasColumn($arr, $field) {
	$names = array_column($arr, 'name');
	return in_array($field, $names);
}
$canCreate = true;
$errors = [];
if (!hasColumn($fields, "created_at")) {
	$canCreate = false;
	array_push($errors, [
		"msg" => "Field `created_at` is missing",
		"query" => $objManager->GetCreatedAtQuery($table)
	]);
}
if (!hasColumn($fields, "updated_at")) {
	$canCreate = false;
	array_push($errors, [
		"msg" => "Field `updated_at` is missing",
		"query" => $objManager->GetUpdatedAtQuery($table)
	]);
}

?>

<div class="card" id="tableCard-<?=$table?>">
	<div class="card-header">
		Table: <?=$table?>
		<div class="card-close" aria-label="Close" onclick="closeCard(this); <?=$closeFn?>">&times;</div>
	</div>
	<div class="card-body">
		<form id="create-from-table-<?=$table?>">
		<div class="row">
			<div class="col-6">
				<?
				$elements->Input("hidden", "table_name", false, $table);
				if($alreadyExists)
					$elements->Alert("Object already exists! [".$name."]", "warning");
				?>
			</div>
			<div class="col-6">
				<?
				$elements->Input("text", "object_name", "Object Name", $name);
				?>
			</div>
		</div>
		<div class="row">
			<div class="col-12">
		<?
			$elements->Table($fields,[
				[
					"title" => "Name",
					"key" => "name"
				],
				[
					"title" => "SQL Type",
					"key" => "type"
				],
				[
					"title" => "Type",
					"key" => function($i) {
						$objManager = ObjectManager::Instance();
						$value = $objManager->GetType($i["type"]);
						return AdminElements::Instance()->Buffer()
							->Select($i["name"]."_type", false, $objManager->GetTypesArr(), $value)
							->Get();
					}
				],
				[
					"title" => "Alias",
					"key" => function($i) use ($objectData) {
						$alias = @$objectData[$i["name"]."_alias"];
						return AdminElements::Instance()->Buffer()
							->Input("text", $i["name"]."_alias", false, $alias, "w-50")
							->Get();
					}
				],
				[
					"title" => "&nbsp;",
					"key" => function($i) {
						$rs = "";
						if($i["nullable"] == 1) $rs .= "(allow null) ";
						if($i["default"]) $rs .= "[default: (".$i["default"].")] ";
						if($i["pk"] == 1 ) {
							$rs .= AdminElements::Instance()->Buffer()
								->Input("hidden", "db_pk", false, $i["name"])
								->Get();
							$rs .= "PK &#9872;";
						}
						return $rs;
					}
				],
			],[ ]);
			?>
			</div>
		</div>
		<div class="row">
			<?
				if($canCreate) {
					echo '<div class="col-6 offset-6">';
					$saveCaption = $alreadyExists ? "Save" : "Create";
					$elements->Button($saveCaption, "saveObjectInfo(this)", ["btn-success", "w-100"]);
					echo '</div>';
				} else {
					echo '<div class="col-12">';
					$msg = "Object data is incomplete for creation!";
					if(count($errors) > 0) {
						$msg .= "<br/><br/>".join("<br/>", array_column($errors, "msg"));
						$sql = "Run this queries to solve the issue:";
						$sql .= "<br/>".join("<br/>", array_column($errors, "query"));
						$sql .= "<br/><br/>To access the query manager <a href='".AdminUrls::Instance()->GetPageUrl("db-tables")."'>click here</a>.";
					}
					$elements->Alert($msg, "danger", false);
					if (@$sql) {
						echo "<br/>";
						$elements->Alert($sql, "warning");
					}
					echo '</div>';
				}
			?>
		</div>
		<div class="row"><div class="col-12 mt-2" id="rs-for-<?=$table?>"></div></div>
		</form>
	</div>
</div>

