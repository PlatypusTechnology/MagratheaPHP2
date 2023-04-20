<?php

$testManager = \Magrathea2\Tests\TestsManager::Instance();
$adminElements = \Magrathea2\Admin\AdminElements::Instance();

$file = $_POST["file"];
$resultContainer = $_POST["container"];

$methods = $testManager->GetMethodsFromTest($file);
echo "<ul class='test-methods'>";
foreach($methods as $fn) {
	echo "<li>".$fn." ";
	$runFn = "callRunFunction('".$file."', '".$fn. "', '#".$resultContainer."');";
	$adminElements->Button('>', $runFn, ['btn-action', 'btn-outline-success']);
	echo "</li>";
}
echo "</ul>";
