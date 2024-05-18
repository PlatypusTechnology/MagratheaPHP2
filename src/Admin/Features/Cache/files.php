<?php

use Magrathea2\Admin\AdminElements;
use Magrathea2\MagratheaCache;
use Magrathea2\MagratheaHelper;

$elements = AdminElements::Instance();

$path = MagratheaCache::Instance()->GetCachePath();
if(!$path) {
	$elements->Alert("cache_path is empty", "danger");
	die;
}
$path = MagratheaHelper::EnsureTrailingSlash($path);
$dirData = scandir($path);
$files = array();
foreach ($dirData as $d) {
	if ($d === '.' or $d === '..') continue;
	if ($d == ".gitkeep") continue;
	array_push($files, $d);
}

$tableData = [];
foreach ($files as $file) {
	$date = date("Y-m-d h:i:s",filemtime($path.$file));
	$size = MagratheaHelper::FormatSize(filesize($path.$file));
	array_push($tableData,
	[
		"_file" => $file,
		"_size" => $size,
		"_date" => $date,
		"_actions" => function($row) {
			$f = $row["_file"];
			$actions = '<a class="action" onclick="viewCacheFile(\''.$f.'\')">view</a>';
			$actions .= '<a class="action" onclick="deleteCache(\''.$f.'\')">delete</a>';
			return $actions;
		}
	]);
}
$titles = [
	[ "key" => "_file", "title" => "file" ],
	[ "key" => "_size", "title" => "size" ],
	[ "key" => "_date", "title" => "date" ],
	[ "key" => "_actions", "title" => "" ],
];

?>

<div class="card">
	<div class="card-header">
		Cached requests:
	</div>
	<div class="card-body">
		<div class="row">
			<div class="col-8">
				<?=$path?>
			</div>
			<div class="col-4 right">
				<?
					$elements->Button("clear cache", "clearCache()", ["btn-danger", "label-margin", "mt-0", "mb-1"]);
				?>
			</div>
		</div>
		<div class="row">
			<div class="col-12">
				<?
					$elements->Table(
						$tableData,
						$titles,
					);
				?>
			</div>
		</div>
	</div>
</div>


