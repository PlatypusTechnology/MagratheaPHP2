<div class="card">
	<div class="card-header">
		<?=\Magrathea2\Admin\AdminManager::Instance()->GetActiveFeature()->objectName?> List
	</div>
	<div class="card-body">
<?php
Magrathea2\Admin\AdminElements::Instance()->Table($list, $columns);
?>
	</div>
</div>

