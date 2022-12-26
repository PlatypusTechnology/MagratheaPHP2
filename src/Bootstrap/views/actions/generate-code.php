<?php

use function Magrathea2\p_r;

$bootstrap = Magrathea2\Bootstrap\CodeManager::Instance()->Load();
$confFile = $bootstrap->getMagratheaObjectsFile();

if(!$confFile) {
	die("no magrathea_objects.conf file");
}

echo "generating code...\n\n";

$objects = $bootstrap->getMagratheaObjectsData();

foreach ($objects as $name => $obj) {
	echo "\n\n==========[".$name."]==========> \n\n";
	$bootstrap->generateCode($name, $obj, true);
}

//p_r($objects);

