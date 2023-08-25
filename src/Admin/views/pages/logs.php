<?php

use Magrathea2\Admin\AdminElements;
use Magrathea2\Debugger;
use Magrathea2\Logger;

$adminElements = AdminElements::Instance();

$logger = Logger::Instance();
$logPath = $logger->GetLogPath();
$activeFile = $logger->GetFullLogFile();

$pageTitle = "Logs";
$adminElements->Header($pageTitle);

$info = [
	[
		"title" => "Path:",
		"value" => $logPath,
	],
	[
		"title" => "Current log file:",
		"value" => $logger->GetLogFile(),
	],
	[
		"title" => "Debug Level:",
		"value" => Debugger::Instance()->GetTypeDesc(),
	],
];

$dirData = scandir($logPath);
$files = array();
foreach ($dirData as $d) {
	if ($d === '.' or $d === '..') continue;
	array_push($files, $d);
}

?>

<style>
#file-view {
	max-height: 750px;
	overflow-y: scroll;
}
</style>

<div class="container">

	<div class="card">
		<div class="card-header">
			Logs Info
			<div class="card-close" aria-label="Close" onclick="closeCard(this);">&times;</div>
		</div>
		<div class="card-body">
			<?
			$adminElements->Table($info, null, "table-bordered hide-header");
			?>
		</div>
	</div>

	<div class="card">
		<div class="card-header">
			<?=$logPath?>
			<div class="card-close" aria-label="Close" onclick="closeCard(this);">&times;</div>
		</div>
		<div class="card-body">
			<div class="row">
				<div class="col-2">
					<ul class="list-group list-group-flush bg-light border-end">
					<?php
						foreach($files as $f) {
							$click = "viewFile('".$logPath."/".$f."');";
							echo '<li class="list-group-item pointer" onclick="'.$click.'">'.$f.'</li>';
						}
					?>
					</ul>
				</div>
				<div class="col-10">
					<div id="file-view" class="file-window" style="display: none;"></div>
				</div>
			</div>
		</div>
	</div>

	<div class="card">
		<div class="card-header">
			Tail
			<div class="card-close" aria-label="Close" onclick="pause(); closeCard(this);">&times;</div>
		</div>
		<div class="card-body">
			<div class="row">
				<div class="col-6">
					<? $adminElements->Input("disabled", "tail-file", "Current Log File", $activeFile, "w-100"); ?>
				</div>
				<div class="col-2">
					<? $adminElements->Input("text", "tail-lines", "Lines", 250, "w-100"); ?>
				</div>
				<div class="col-2">
					<? $adminElements->Input("number", "tail-rate", "Refresh Rate (s)", 30, "w-100"); ?>
				</div>
				<div class="col-2">
					<?
					$argsTailStart = ["id" => "tail-start", "styles" => ""];
					$adminElements->Button("Tail! &#x23F5", "start()", "btn-success w-100", null, false, $argsTailStart);
					$argsTailStop = ["id" => "tail-stop", "styles" => "display: none;"];
					$adminElements->Button("Pause! &#x23F8;", "pause()", "btn-warning w-100", null, false, $argsTailStop);
					?>
				</div>
				<div class="col-12 mt-2">
					<div id="file-tail" class="file-window" style="display: none;"></div>
				</div>
			</div>
		</div>
	</div>

</div>

<script type="text/javascript">
function viewFile(fileAddress) {
	callAction("file-view", "POST", { file: fileAddress })
		.then( rs => showOn('#file-view', rs) );
}
function tailRun() {
	console.info("tail running");
	let file = $("#tail-file").val();
	let lines = $("#tail-lines").val();
	callAction("file-view", "POST", { file, lines })
		.then( rs => showOn('#file-tail', rs) );
}
var tailInterval = null;
function start() {
	let interval = $("#tail-rate").val() * 1000;
	tailInterval = setInterval(tailRun, interval);
	tailRun();
	$("#tail-start").hide();
	$("#tail-stop").show();
}
function pause() {
	clearInterval(tailInterval);
	$("#tail-stop").hide();
	$("#tail-start").show();
}
</script>
