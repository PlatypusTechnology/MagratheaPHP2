<?php

use Magrathea2\Admin\AdminElements;
use Magrathea2\Admin\ObjectManager;

?>

<div class="row">
	<div class="col-12" id="relation-<?=$objectName?>-form-button">
		<?
		AdminElements::Instance()
			->Button(
				"+ Add Relation",
				"toggleAddRelation('".$objectName."', true)",
				['btn-success', 'w-100'],
			);
		?>
	</div>
	<div class="col-12" id="relation-<?=$objectName?>-form" style="display: none;">
		<? include(__DIR__."/object-relation-form.php"); ?>
	</div>
	<div class="col-12 mt-2" id="relation-<?=$objectName?>-rs" style="display: none;"></div>
	<div class="col-12 mt-2" id="edit-relations-for-<?=$objectName?>">
		<?
			include(__DIR__."/object-relation-edit.php");
		?>
	</div>
</div>
