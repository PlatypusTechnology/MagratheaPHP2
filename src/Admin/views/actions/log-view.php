<?php

use Magrathea2\Admin\AdminElements;
use Magrathea2\Admin\AdminFiles;
use Magrathea2\Logger;

// a log file: a plain name of a file inside the logs folder
$viewFile = AdminFiles::ResolveInFolder(Logger::Instance()->GetLogPath(), @$_POST["file"]);
if(!$viewFile) {
	AdminElements::Instance()->ErrorCard("invalid log file");
	return;
}

// tail: the last 1–10000 lines
$viewLines = null;
if(!empty($_POST["lines"])) {
	$viewLines = min(max((int)$_POST["lines"], 1), 10000);
}

include(__DIR__."/file-view.php");
