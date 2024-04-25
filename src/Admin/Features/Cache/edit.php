<?php

include(__DIR__."/prettyfi.php");

use Magrathea2\Admin\AdminElements;
use Magrathea2\MagratheaCache;
use Magrathea2\MagratheaHelper;

$elements = AdminElements::Instance();

$path = MagratheaCache::Instance()->GetCachePath();
$filePath = MagratheaHelper::EnsureTrailingSlash($path).$file;

$content = file_get_contents($filePath);
$textId = $file."-txt";

?>

<div class="card">
	<div class="card-header">
		edit <?=$file?>
		<div class="card-close" aria-label="Close" onclick="closeCard(this);">&times;</div>
	</div>
	<div class="card-body">
		<div class="row">
			<div class="col-sm-12">
				<?
				AdminElements::Instance()
					->Textarea($textId, $file, $content, "cache-txt");
				?>
			</div>
		</div>
		<div class="row">
			<div class="col-sm-12">
				<?
				$elements
					->Button("save", "saveCache('".$file."')", ["btn-success", "label-margin", "mt-1"]);
				$elements
					->Button("delete", "deleteCache('".$file."')", ["btn-danger", "label-margin", "mt-1"]);
				?>
			</div>
		</div>
	</div>
</div>
