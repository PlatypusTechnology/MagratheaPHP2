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
	<?php
		$feature->List();
	?>
	</div>
</div>
<div class="row">
	<div class="col-12 right">
		<?php
		$feature->PrintButtonNew();
		?>
	</div>
</div>

<div class="row">
	<div class="col-12 mt-4" id="container-crudobject-rs"></div>
	<div class="col-12" id="container-crudobject-form"></div>
</div>

</div>

<?php
$openId = @$_GET["id"];
if($openId && is_scalar($openId)) {
	// from the query string, inside a <script>: a JSON string, with <, >, &, ' and " as \u escapes
	$openIdJs = json_encode((string)$openId, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
	?>
<script type="text/javascript">
	afterLoad().then(() => editCrudObject(<?=$openIdJs?>));
</script>
	<?php
}
?>