<?php

use Magrathea2\Admin\AdminElements;
use Magrathea2\Admin\AdminUrls;
use Magrathea2\Config;
use Magrathea2\Errors\ErrorManager;
use Magrathea2\MagratheaCache;

$elements = AdminElements::Instance();

$configUrl = AdminUrls::Instance()->GetConfigUrl();
$cacheActive = Config::Instance()->Get("no_cache");

try {
	$cachePath = MagratheaCache::Instance()->GetCachePath();
} catch(\Exception $ex) {
	ErrorManager::Instance()->DisplayException($ex);
}

$cacheDetails = array(
	[
		"Path",
		$cachePath,
		'from <a href="'.$configUrl.'">config file</a>'
	],
	[
		"no_cache",
		$cacheActive,
		'[no_cache] from <a href="'.$configUrl.'">config</a> (default: false)'
	]
);

?>

<div class="card">
	<div class="card-header">
		Cache Details
		<div class="card-close" aria-label="Close" onclick="closeCard(this);">&times;</div>
	</div>
	<div class="card-body ">
		<div class="row">
			<div class="col-12">
				set [<b>no_cache</b>] as "true" to turn it off <br/>
				<?
				$elements->Table($cacheDetails, null, ["hide-header"]);
				?>
			</div>
		</div>
	</div>
</div>

