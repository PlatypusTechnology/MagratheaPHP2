<?php

use Magrathea2\Exceptions\MagratheaException;

$testManager = \Magrathea2\Tests\TestsManager::Instance();
$adminElements = \Magrathea2\Admin\AdminElements::Instance();

$folder = @$_POST["folder"];
$resultContainer = (string)@$_POST["container"];

try {
	$files = $testManager->GetTestsFromFolder($folder);
} catch(MagratheaException $ex) {
	$adminElements->Alert(htmlspecialchars($ex->getMessage()), "danger");
	return;
}
// onclick arguments: JSON for JS, then escaped for the HTML attribute
$js = fn($value) => htmlspecialchars(json_encode($value));
echo "<ul class='list-unstyled ul-files'>";
foreach($files as $f) {
	$thisFile = $folder."/".$f;
	$fileContainer = "container-for-".$f;
	echo "<li>";
//	$expandFn = "expandFunctions('#methods-for-".$f."')";
//	$adminElements->Button('+', $expandFn, ['btn-action','btn-outline-secondary']);
	$runFile = "callRunFile(".$js($thisFile).", ".$js("#".$resultContainer).");";
	$fileClick = "viewFile(".$js($thisFile).", ".$js($resultContainer).", ".$js($fileContainer).");";
	$adminElements->Button('>', $runFile, ['btn-action','btn-success']);
	echo "<a href='#' onclick='".$fileClick."'>".htmlspecialchars($f)."</a>";
	echo "<div id='".htmlspecialchars($fileContainer)."'></div>";
	echo "</li>";
}
echo "</ul>";
