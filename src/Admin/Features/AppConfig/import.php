<?

use Magrathea2\Admin\AdminElements;

?>

<div class="card">
	<div class="card-header">
		App Configuration Import
	</div>
	<div class="card-body config-form">
		<div class="row">
			<div class="col-12" id="app-config-list">
				<?
				AdminElements::Instance()->Textarea("config-import", "Import String");
				?>
			</div>
			<div class="col-12 right mt-3">
				<?
				AdminElements::Instance()->Button("Import", "importAppConfig()", ["btn-success", "no-margin"]);
				?>
			</div>
		</div>
	</div>
</div>
