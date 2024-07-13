<?php

use Magrathea2\Admin\AdminElements;
use Magrathea2\Admin\ObjectManager;

$control = ObjectManager::Instance();
$objectName = $_GET["object"];
$relations = $control->GetRelationsByObject($objectName);

$rs = "";
$rs .= "Removing relations...<br/>";
foreach($relations as $rel) {
	$relName = $rel["rel_obj_base"]." ".$rel["rel_type"]." ".$rel["rel_object"];
	$rs .= " -- removing relation: [".$relName."]";
	$control->DeleteRelation($rel["rel_name"]);
}
$success = $control->RemoveObject($objectName);
$rs .= "<br/><br/>";
$rs .= "[".$objectName."] removed: [".$success."]";

?>


<div class="card">
	<div class="card-header">
		Removing... <?=$objectName?>
		<div class="card-close" aria-label="Close" onclick="closeCard(this);">&times;</div>
	</div>
	<div class="card-body">
		<div class="row"><div class="col-12">
		<?
		echo $rs;
		if($success) {
			AdminElements::Instance()->Alert($objectName." removed", "success");
		}
		?>
		</div></div>
	</div>
</div>
