<?php

use Magrathea2\Tests\TestsManager;

$testManager = TestsManager::Instance();

//p_r($_POST);

//echo MagratheaPHP::Instance()->magRoot;


$file = @$_POST["file"];
if ($file) {
	$fn = @$_POST["fn"];
	if ($fn) {
		echo "<span class='test-desc'>Testing function [".$testManager::TestFolderPrint($file)."::".$fn."]</span>";
		$response = $testManager->RunFunction($file, $fn);
	} else {
		echo "<span class='test-desc'>Testing file [".$testManager::TestFolderPrint($file)."]</span>";
		$response = $testManager->RunFile($file);
	}
	printResponse($response);
}

$folder = @$_POST["folder"];
if ($folder) {
	echo "<span class='test-desc'>Testing folder [".$testManager::TestFolderPrint($folder)."]</span>";
	$response = $testManager->RunFolder($folder);
	printResponse($response);
}



function printResponse($response) {
	echo '<pre class="code">';
	foreach ($response as $line) {
		echo $line . PHP_EOL;
	}
	echo '</pre>';
}

