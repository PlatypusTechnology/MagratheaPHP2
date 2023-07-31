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
				<b>Checking</b> <?=$folder?>
			</div>
		</div>
		<?
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
						$html = "<a href='javascript:viewBackupFile(\"".$f["filename"]."\")'>view</a>";
						return $html;
					}
				],
			]);
		?>
	</div>
</div>
