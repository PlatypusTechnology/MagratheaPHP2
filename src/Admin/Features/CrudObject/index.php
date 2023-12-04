<?php

use Magrathea2\Admin\AdminElements;
use Magrathea2\Admin\AdminManager;

$feature = AdminManager::Instance()->GetActiveFeature();

$elements = AdminElements::Instance();
$elements->Header($feature->GetHeaderTitle());

?>

<input type="hidden" value="<?=$feature->featureId?>" id="crud-feature-id" />
<div class="container">

<div class="row">
	<div class="col-12" id="container-crudobject-list">
	<?
		$feature->List();
	?>
	</div>
</div>
<div class="row">
	<div class="col-12 right">
		<?
		$feature->PrintButtonNew();
		?>
	</div>
</div>

<div class="row">
	<div class="col-12 mt-4" id="container-crudobject-rs"></div>
	<div class="col-12" id="container-crudobject-form"></div>
</div>

</div>