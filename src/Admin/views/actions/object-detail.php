<?php

use Magrathea2\Admin\AdminElements;
use Magrathea2\Admin\ObjectManager;

$objectName = @$_GET["object"];

$adminElements = AdminElements::Instance();
if(!is_string($objectName) || $objectName === "") {
	$adminElements->ErrorCard("invalid object");
	return;
}
// GET parameters are links an admin can be sent: escaped, and onclose only as a JS function name
$objectHtml = htmlspecialchars($objectName);
$manager = ObjectManager::Instance();
$data = $manager->GetObjectData($objectName);
$details = $manager->GetObjectDetails($objectName);
if(!$details) {
	$adminElements->ErrorCard("object not found");
	return;
}
$rels = $manager->GetRelationsByObject($objectName);

$relations = false;
if($rels) {
	$relations = array_map(function($r) {
		return [
			"type" => str_replace('_', ' ', $r["rel_type"]),
			"object" => ucfirst($r["rel_object"]),
			"field" => $r["rel_field"],
			"property" => $r["rel_property"],
			"method" => $r["rel_method"]."()",
		];
	}, $rels);
}

$closeFn = $adminElements->JsCallback(@$_GET["onclose"], "this");
?>

<div class="card" id="detailCard-<?=$objectHtml?>">
	<div class="card-header">
		<?=htmlspecialchars(ucfirst((string)@$details["name"]))?> Data
		<div class="card-close" aria-label="Close" onclick="closeCard(this); <?=$closeFn?>">&times;</div>
	</div>
	<div class="card-body">
		<div class="row">
			<div class="col-6">
				<?php
				$adminElements->Input("disabled", null, "Table", $details["table"]);
				?>
			</div>
			<div class="col-6">
				&nbsp;
			</div>
			<div class="col-12 mt-2">
				<?php
				$adminElements->Button("&darr;", "toggleCol(this, '.obj-raw-data');", ["btn-action", "btn-primary"])
				?>
				<b>Raw Data</b>
			</div>
			<div class="col-12 obj-raw-data" style="display: none;">
				<pre class="code"><?=htmlspecialchars(print_r($data, true))?></pre>
			</div>
			<div class="col-12 mt-2">
				<?php
				$adminElements->Button("&darr;", "toggleCol(this, '.public-properties')", ["btn-action", "btn-primary"]);
				?>
				<b>Public Properties</b>
			</div>
			<div class="col-12 public-properties">
				<?php
					$adminElements->Table($details["public_properties"], ["name" => "Name", "description" => "...", "type" => "Type"]);
				?>
			</div>
			<div class="col-12 mt-2">
				<?php
				$adminElements->Button("&darr;", "toggleCol(this, '.public-methods')", ["btn-action", "btn-primary"]);
				?>
				<b>Public Methods</b>
			</div>
			<div class="col-12 public-methods">
				<?php
					$adminElements->Table($details["public_methods"], ["name" => "Method", "description" => "Description"]);
				?>
			</div>
			<?php
			if($relations) {
			?>
			<div class="col-12 mt-2">
				<?php
				$adminElements->Button("&darr;", "toggleCol(this, '.public-relations')", ["btn-action", "btn-primary"]);
				?>
				<b>Relations</b>
			</div>
			<div class="col-12 public-relations">
				<?php
					$adminElements->Table(
						$relations,
						["type" => "Type", "object" => "Object", "field" => "Field", "property" => "Property", "method" => "Method"]
					);
				?>
			</div>
			<?php
			}
			?>
			<div class="col-12 mt-2">
				<?php
				$adminElements->Button("&darr;", "toggleCol(this, '.database-query')", ["btn-action", "btn-primary"]);
				?>
				<b>Database Query</b>
			</div>
			<div class="col-12 database-query" style="display: none;">
				<?php
				$query = $manager->GenerateQueryForObject($objectName);
				?>
				<pre class="code mt-2" id="create-<?=$objectHtml?>"><?=htmlspecialchars((string)$query)?></pre>
			</div>
		</div>
	</div>
</div>

