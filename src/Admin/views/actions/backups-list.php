<?php

use Magrathea2\Admin\AdminDatabase;
use Magrathea2\Admin\AdminElements;

use function Magrathea2\p_r;

$adminDb = AdminDatabase::Instance();
$folder = $adminDb->GetBackupFolder();

/* as given by ChatGPT */
function getSqlFilesInFolder($folderPath) {
	$sqlFiles = glob($folderPath . '/*.sql');
	$result = array();

	foreach ($sqlFiles as $file) {
		$creationDate = date("Y-m-d h:i:s", filectime($file));
		$size = filesize($file);
		$result[] = array(
			'filename' => basename($file),
			'size' => $size,
			'created' => $creationDate
		);
	}

	return $result;
}

$files = getSqlFilesInFolder($folder);

?>

<div class="card">
	<div class="card-header">
		Backups
		<div class="card-close" aria-label="Close" onclick="closeCard(this);">&times;</div>
	</div>
	<div class="card-body">
		<div class="row">
			<div class="col-12">
				<b>Checking</b> <?=htmlspecialchars($folder)?>
			</div>
		</div>
		<?php
			AdminElements::Instance()->Table($files, [
				[
					"title" => "File",
					"key" => "filename"
				],
				[
					"title" => "Size",
					"key" => "size"
				],
				[
					"title" => "Date of creation",
					"key" => "created"
				],
				[
					"title" => "...",
					"key" => function($f) {
						// onclick argument: JSON for JS, then escaped for the HTML attribute
						$fileJs = htmlspecialchars(json_encode($f["filename"]));
						return '<a href="#" onclick="viewBackupFile('.$fileJs.'); return false;">view</a>';
					}
				],
			]);
		?>
	</div>
</div>
