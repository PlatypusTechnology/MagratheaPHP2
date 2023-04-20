<?php

use Magrathea2\Admin\AdminElements;
use Magrathea2\Admin\ObjectManager;

$objectName = $_GET["object"];

$adminElements = AdminElements::Instance();
$data = ObjectManager::Instance()->GetObjectData($objectName);
$details = ObjectManager::Instance()->GetObjectDetails($objectName);
$rels = ObjectManager::Instance()->GetRelationsByObject($objectName);

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

$closeFn = @$_GET["onclose"];
if($closeFn) {
	$closeFn = $closeFn."(this);";
}
?>

<div class="card" id="detailCard-<?=$objectName?>">
	<div class="card-header">
		<?=ucfirst($details["name"])?> Data
		<div class="card-close" aria-label="Close" onclick="closeCard(this); <?=$closeFn?>">&times;</div>
	</div>
	<div class="card-body">
		<div class="row">
			<div class="col-6">
				<?
				$adminElements->Input("disabled", null, "Table", $details["table"]);
				?>
			</div>
			<div class="col-6">
				&nbsp;
			</div>
			<div class="col-12 mt-2">
				<?
				$adminElements->Button("&darr;", "toggleCol(this, '.obj-raw-data');", ["btn-action", "btn-primary"])
				?>
				<b>Raw Data</b>
			</div>
			<div class="col-12 obj-raw-data" style="display: none;">
				<pre class="code"><?print_r($data)?></pre>
			</div>
			<div class="col-12 mt-2">
				<?
				$adminElements->Button("&darr;", "toggleCol(this, '.public-properties')", ["btn-action", "btn-primary"]);
				?>
				<b>Public Properties</b>
			</div>
			<div class="col-12 public-properties">
				<?
					$adminElements->Table($details["public_properties"], ["name" => "Name", "description" => "...", "type" => "Type"]);
				?>
			</div>
			<div class="col-12 mt-2">
				<?
				$adminElements->Button("&darr;", "toggleCol(this, '.public-methods')", ["btn-action", "btn-primary"]);
				?>
				<b>Public Methods</b>
			</div>
			<div class="col-12 public-methods">
				<?
					$adminElements->Table($details["public_methods"], ["name" => "Method", "description" => "Description"]);
				?>
			</div>
			<?
			if($relations) {
			?>
			<div class="col-12 mt-2">
				<?
				$adminElements->Button("&darr;", "toggleCol(this, '.public-methods')", ["btn-action", "btn-primary"]);
				?>
				<b>Relations</b>
			</div>
			<div class="col-12 public-methods">
				<?
					$adminElements->Table(
						$relations,
						["type" => "Type", "object" => "Object", "field" => "Field", "property" => "Property", "method" => "Method"]
					);
				?>
			</div>
			<?
			}
			?>
		</div>
	</div>
</div>

