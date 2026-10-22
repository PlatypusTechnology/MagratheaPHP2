<?php

use Magrathea2\Exceptions\MagratheaException;

$testManager = \Magrathea2\Tests\TestsManager::Instance();
$adminElements = \Magrathea2\Admin\AdminElements::Instance();

$file = @$_POST["file"];
$resultContainer = (string)@$_POST["container"];

try {
	$methods = $testManager->GetMethodsFromTest($file);
} catch(MagratheaException $ex) {
	$adminElements->Alert(htmlspecialchars($ex->getMessage()), "danger");
	return;
}
// onclick arguments: JSON for JS, then escaped for the HTML attribute
$js = fn($value) => htmlspecialchars(json_encode($value));
echo "<ul class='test-methods'>";
foreach($methods as $fn) {
	echo "<li>".$fn." ";
	$runFn = "callRunFunction(".$js($file).", ".$js($fn).", ".$js("#".$resultContainer).");";
	$adminElements->Button('>', $runFn, ['btn-action', 'btn-outline-success']);
	echo "</li>";
}
echo "</ul>";
