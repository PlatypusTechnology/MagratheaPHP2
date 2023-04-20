<?php

$testManager = \Magrathea2\Tests\TestsManager::Instance();
$adminElements = \Magrathea2\Admin\AdminElements::Instance();

$folder = $_POST["folder"];
$resultContainer = $_POST["container"];

$files = $testManager->GetTestsFromFolder($folder);
echo "<ul class='list-unstyled ul-files'>";
foreach($files as $f) {
	$thisFile = $folder."/".$f;
	$fileContainer = "container-for-".$f;
	echo "<li>";
//	$expandFn = "expandFunctions('#methods-for-".$f."')";
//	$adminElements->Button('+', $expandFn, ['btn-action','btn-outline-secondary']);
	$runFile = "callRunFile('".$thisFile."', '#".$resultContainer."');";
	$fileClick = 'viewFile("'.$thisFile.'", "'.$resultContainer.'", "'.$fileContainer.'");';
	$adminElements->Button('>', $runFile, ['btn-action','btn-success']);
	echo "<a href='#' onclick='".$fileClick."'>".$f."</a>";
	echo "<div id='".$fileContainer."'></div>";
	echo "</li>";
}
echo "</ul>";
