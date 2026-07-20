<?php

require __DIR__ . "/lib/DocMap.php";
require __DIR__ . "/lib/Reflector.php";
require __DIR__ . "/lib/SearchIndex.php";

require __DIR__ . "/../vendor/autoload.php";

use AiDocs\SearchIndex;

$cacheFile = __DIR__ . "/cache/search-index.json";
$maxAge = 300;

if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $maxAge) {
	header("Content-Type: application/json");
	readfile($cacheFile);
	exit;
}

$data = SearchIndex::Build();
$json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
@file_put_contents($cacheFile, $json);

header("Content-Type: application/json");
echo $json;
