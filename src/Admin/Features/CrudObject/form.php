<?php
//print_r($formData); die;
$form = new \Magrathea2\Admin\AdminForm();

?>

<div class="card">
	<div class="card-header">
		<?=($object->id ? "Edit" : "New")?> <b><?=$object->Ref()?></b>
		<div class="card-close" aria-label="Close" onclick="closeCard(this);">&times;</div>
	</div>
	<div class="card-body">
		<?
		$form->Build($formData, $object)->Print();
		?>
	</div>
</div>
