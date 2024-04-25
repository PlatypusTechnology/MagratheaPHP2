<?php

include(__DIR__."/prettyfi.php");

use Magrathea2\Admin\AdminElements;
use Magrathea2\MagratheaCache;
use Magrathea2\MagratheaHelper;

$elements = AdminElements::Instance();
$path = MagratheaCache::Instance()->GetCachePath();
$filePath = MagratheaHelper::EnsureTrailingSlash($path).$file;

$content = file_get_contents($filePath);
$pretty = prettyPrint($content);

?>

<div class="card">
	<div class="card-header">
		<?=$file?>
		<div class="card-close" aria-label="Close" onclick="closeCard(this);">&times;</div>
	</div>
	<div class="card-body">
		<div class="row">
			<div class="col-sm-6">
				<?
				$switchAction = ["onchange" => "switchCacheView(this);"];
				$elements
					->Checkbox(null, "View Pretty", true, true, [], true, $switchAction);
				?>
			</div>
			<div class="col-sm-6 right">
				<?
				$elements
					->Button("edit", "editCacheFile('".$file."')", ["btn-success", "label-margin", "mt-0", "mb-1"]);
				$elements
					->Button("delete", "deleteCache('".$file."')", ["btn-danger", "label-margin", "mt-0", "mb-1"]);
				?>
			</div>
		</div>
		<div class="row">
			<div class="col-sm-12">
				<pre class="cache-view pretty-cache"><?=$pretty?></pre>
				<pre class="cache-view raw-cache" style="display: none;"><?=$content?></pre>
			</div>
		</div>
	</div>
</div>
