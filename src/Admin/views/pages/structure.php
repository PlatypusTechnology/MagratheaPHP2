<?php

use Magrathea2\Admin\AdminDatabase;
use Magrathea2\Admin\AdminElements;
use Magrathea2\Admin\AdminUrls;
use Magrathea2\Config;
use Magrathea2\Logger;
use Magrathea2\MagratheaCache;
use Magrathea2\MagratheaPHP;

$pageTitle = "Structure";
AdminElements::Instance()->Header($pageTitle);

function checkPathIsOk($p) {
	$nok = "<span class='error'>&#9746;</span> ";
	$path = realpath($p);
	if(!$path) {
		return $nok."Path invalid";
	}
	$ok = "<span class='success'>&#9745;</span> ";
	$rs = $ok."Folder exists<br/>";
	if(is_writeable($path)) {
		return $rs.$ok." Folder writable";
	} else {
		return $rs.$nok." Folder not writable";
	}
}

$table = [
	[
		"name" => "Magrathea Root",
		"value" => MagratheaPHP::Instance()->magRoot,
		"check" => function($r) { return checkPathIsOk($r["value"]); }
	],
	[
		"name" => "Config Path",
		"value" => Config::Instance()->GetFilePath(),
		"action" => "<a href='".AdminUrls::Instance()->GetPageUrl("config")."'>View</a>",
		"check" => function($r) { return checkPathIsOk($r["value"]); }
	],
	[
		"name" => "Log Path",
		"value" => Logger::Instance()->GetLogPath(),
		"action" => "<a href='".AdminUrls::Instance()->GetPageUrl("logs")."'>View</a>",
		"check" => function($r) { return checkPathIsOk($r["value"]); }
	],
	[
		"name" => "Database backups",
		"value" => AdminDatabase::Instance()->GetBackupFolder(),
		"action" => "<a href='".AdminUrls::Instance()->GetPageUrl("backups")."'>Backups</a>",
		"check" => function($r) { return checkPathIsOk($r["value"]); }
	],
	[
		"name" => "Cache",
		"value" => MagratheaCache::Instance()->GetCachePath(),
		"action" => "<a href='".AdminUrls::Instance()->GetPageUrl("cache")."'>Cache</a>",
		"check" => function($r) { return checkPathIsOk($r["value"]); }
	],
	[
		"name" => "App Root",
		"value" => MagratheaPHP::Instance()->appRoot,
		"check" => function($r) { return checkPathIsOk($r["value"]); }
	],
];

?>


<div class="container">
	<div class="card">
		<div class="card-header">
			Structure
		</div>
		<div class="card-body">
			<?
			AdminElements::Instance()->Table($table,
				[ 
					"name" => "Name",
					"value" => "Path",
					"action" => "",
					"check" => "",
				]
			)
			?>
		</div>
	</div>
</div>

