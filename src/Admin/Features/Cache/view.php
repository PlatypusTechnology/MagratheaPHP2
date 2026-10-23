<?php

include(__DIR__."/prettyfi.php");

use Magrathea2\Admin\AdminElements;

// $file (name) and $filePath (validated) come from AdminFeatureCache::View()
$elements = AdminElements::Instance();

$content = file_get_contents($filePath);
$pretty = prettyPrint($content);

// cached responses hold user data: always escape (file names too, they can come from route params)
$fileHtml = htmlspecialchars($file);
$fileJs = htmlspecialchars(json_encode($file));

?>

<div class="card">
	<div class="card-header">
		<?=$fileHtml?>
		<div class="card-close" aria-label="Close" onclick="closeCard(this);">&times;</div>
	</div>
	<div class="card-body">
		<div class="row">
			<div class="col-sm-6">
				<?php
				$switchAction = ["onchange" => "switchCacheView(this);"];
				$elements
					->Checkbox(null, "View Pretty", true, true, [], true, $switchAction);
				?>
			</div>
			<div class="col-sm-6 right">
				<?php
				$elements
					->Button("edit", "editCacheFile(".$fileJs.")", ["btn-success", "label-margin", "mt-0", "mb-1"]);
				$elements
					->Button("delete", "deleteCache(".$fileJs.")", ["btn-danger", "label-margin", "mt-0", "mb-1"]);
				?>
			</div>
		</div>
		<div class="row">
			<div class="col-sm-12">
				<pre class="cache-view pretty-cache"><?=htmlspecialchars($pretty)?></pre>
				<pre class="cache-view raw-cache" style="display: none;"><?=htmlspecialchars($content)?></pre>
			</div>
		</div>
	</div>
</div>
