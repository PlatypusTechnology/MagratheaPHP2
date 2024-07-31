<?php

use Magrathea2\Admin\AdminElements;
use Magrathea2\Config;
use Magrathea2\MagratheaHelper;

$logFile = @$_POST["log"];
if(!$logFile) throw new \Exception("invalid log file");
$pieces = explode('/', $logFile);
$file = end($pieces);

$logPath = Config::Instance()->Get("logs_path");
$logFile = MagratheaHelper::EnsureTrailingSlash($logPath).$file;

$elements = AdminElements::Instance();
if(!file_exists($logFile)) {
	$elements->ErrorCard("file [".$logFile."] does not exists");
}
if(unlink($logFile)) {
	$elements->Alert("File deleted!", "success");
} else {
	$elements->Alert("Error deleting file!", "danger");
}
