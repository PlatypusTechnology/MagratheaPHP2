<?php

use Magrathea2\Admin\AdminElements;
use Magrathea2\Admin\AdminUrls;
use Magrathea2\Config;
use Magrathea2\Logger;
use Magrathea2\MagratheaPHP;

$pageTitle = "Structure";
include(__DIR__."/../sections/header.php");

$table = [
	[
		"name" => "Magrathea Root",
		"value" => MagratheaPHP::Instance()->magRoot,
	],
	[
		"name" => "Config Path",
		"value" => Config::Instance()->GetFilePath(),
		"action" => "<a href='".AdminUrls::Instance()->GetPageUrl("config")."'>View</a>",
	],
	[
		"name" => "Log Path",
		"value" => Logger::Instance()->GetLogPath(),
		"action" => "<a href='".AdminUrls::Instance()->GetPageUrl("logs")."'>View</a>",
	],
	[
		"name" => "App Root",
		"value" => MagratheaPHP::Instance()->appRoot,
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
			AdminElements::Instance()->Table($table, [ "name" => "Name", "value" => "Path", "action" => "" ])
			?>
		</div>
	</div>
</div>

