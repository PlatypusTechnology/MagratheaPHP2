<?php

$view = $_GET["view"];

if($view == "code") {
	$adminInstall = new Magrathea2\Admin\Install();
	$adminCode = htmlspecialchars($adminInstall->GetAdminCode());
	die($adminCode);
}

if($view == "db") {
	$adminDb = Magrathea2\Admin\AdminDatabase::Instance();
	$adminSQL = $adminDb->getSQLFileContents();
	die($adminSQL);
}
