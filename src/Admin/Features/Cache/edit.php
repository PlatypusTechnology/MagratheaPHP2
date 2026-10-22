<?php

use Magrathea2\Admin\AdminElements;

// $file (name) and $filePath (validated) come from AdminFeatureCache::Edit()
$elements = AdminElements::Instance();

$content = file_get_contents($filePath);
// the textarea's id is read back by saveCache() with getElementById(file + "-txt")
$textId = htmlspecialchars($file."-txt");
$fileHtml = htmlspecialchars($file);
$fileJs = htmlspecialchars(json_encode($file));

?>

<div class="card">
	<div class="card-header">
		edit <?=$fileHtml?>
		<div class="card-close" aria-label="Close" onclick="closeCard(this);">&times;</div>
	</div>
	<div class="card-body">
		<div class="row">
			<div class="col-sm-12">
				<?php
				AdminElements::Instance()
					->Textarea($textId, $fileHtml, htmlspecialchars($content), "cache-txt");
				?>
			</div>
		</div>
		<div class="row">
			<div class="col-sm-12">
				<?php
				$elements
					->Button("save", "saveCache(".$fileJs.")", ["btn-success", "label-margin", "mt-1"]);
				$elements
					->Button("delete", "deleteCache(".$fileJs.")", ["btn-danger", "label-margin", "mt-1"]);
				?>
			</div>
		</div>
	</div>
</div>
