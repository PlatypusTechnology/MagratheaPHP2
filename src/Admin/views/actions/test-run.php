<?php

use Magrathea2\Tests\TestsManager;
use Magrathea2\Exceptions\MagratheaException;

$testManager = TestsManager::Instance();

//p_r($_POST);

//echo MagratheaPHP::Instance()->magRoot;


// TestsManager only runs paths inside the registered test folders
try {
	$file = @$_POST["file"];
	if ($file) {
		$fn = @$_POST["fn"];
		if ($fn) {
			$response = $testManager->RunFunction($file, $fn);
			echo "<span class='test-desc'>Testing function [".htmlspecialchars($testManager::TestFolderPrint($file)."::".$fn)."]</span>";
		} else {
			$response = $testManager->RunFile($file);
			echo "<span class='test-desc'>Testing file [".htmlspecialchars($testManager::TestFolderPrint($file))."]</span>";
		}
		printResponse($response);
	}

	$folder = @$_POST["folder"];
	if ($folder) {
		$response = $testManager->RunFolder($folder);
		echo "<span class='test-desc'>Testing folder [".htmlspecialchars($testManager::TestFolderPrint($folder))."]</span>";
		printResponse($response);
	}
} catch(MagratheaException $ex) {
	echo "<span class='test-desc'>".htmlspecialchars($ex->getMessage())."</span>";
}



function printResponse($response) {
	echo '<pre class="code">';
	foreach ($response as $line) {
		echo htmlspecialchars($line) . PHP_EOL;
	}
	echo '</pre>';
}

