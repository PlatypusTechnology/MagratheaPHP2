<?php

use Magrathea2\Admin\AdminElements;

// $objectName comes from object-edit.php: escaped
$relObjectHtml = htmlspecialchars((string)($objectName ?? ""));
$relObjectJs = htmlspecialchars(json_encode((string)($objectName ?? "")));

?>

<div class="row">
	<div class="col-12" id="relation-<?=$relObjectHtml?>-form-button">
		<?php
		AdminElements::Instance()
			->Button(
				"+ Add Relation",
				"toggleAddRelation(".$relObjectJs.", true)",
				['btn-success', 'w-100'],
			);
		?>
	</div>
	<div class="col-12" id="relation-<?=$relObjectHtml?>-form" style="display: none;">
		<?php include(__DIR__."/object-relation-form.php"); ?>
	</div>
	<div class="col-12 mt-2" id="relation-<?=$relObjectHtml?>-rs" style="display: none;"></div>
	<div class="col-12 mt-2" id="edit-relations-for-<?=$relObjectHtml?>">
		<?php
			include(__DIR__."/object-relation-edit.php");
		?>
	</div>
</div>
