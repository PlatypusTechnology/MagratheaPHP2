<?php

use Magrathea2\Admin\AdminDatabase;
use Magrathea2\Admin\AdminElements;
use Magrathea2\Admin\AdminForm;

use function Magrathea2\p_r;

$adminElements = AdminElements::Instance();
$adminDb = AdminDatabase::Instance();

$adminElements->Header("Database backups");

$el = [
	[
		"type" => "disabled",
		"name" => "Backup Location",
		"key" => "location",
		"size" => "col-4",
	],
	[
		"type" => "text",
		"name" => "File",
		"key" => "filename",
		"size" => "col-4",
	],
	[
		"type" => "button",
		"name" => "Create",
		"class" => ["btn-primary", "w-100"],
		"key" => "btn-create",
		"action" => "createBackup()",
		"size" => "col-4",
	],
];
$val = [
	"location" => $adminDb->GetBackupFolder(),
	"filename" => $adminDb->GetDefaultFileName(),
];
$form = new AdminForm();
$form->SetName("bkp");
$form->Build($el, $val);

?>

<div class="container">
	<div class="card">
		<div class="card-header">
			Backup Location
		</div>
		<div class="card-body">
			<?php
				$form->Print();
			?>
		</div>
	</div>

	<div id="backup-list"></div>
	<div id="backup-rs"></div>

</div>

<script type="text/javascript">
afterLoad().then( loadList );
function loadList() {
	let container = $("#backup-list");
	callAction("backups-list")
		.then(rs => showOn(container, rs));
}

function createBackup() {
	let data = getFormDataFromElement($("#bkp"));
	callApi("Database", "CreateBackupFile", data)
		.then(rs => {
			if(!rs.success) {
				let error = (rs.data && rs.data.message) || "could not create backup file";
				showToast($("<span>").text(error).html(), "Error on backup", true);
			} else {
				let file = rs.data.file;
				showToast("file created: ["+$("<span>").text(file).html()+"]", "Success!");

				loadList();
			}
		});
}

function viewBackupFile(filename) {
	let container = $("#backup-rs");
	callAction("backup-file", "POST", { filename })
		.then(rs => showOn(container, rs));
}
</script>

