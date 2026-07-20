<?php

$map = [
	"skills" => [__DIR__ . "/../skills.MD", "skill.md"],
	"instructions" => [__DIR__ . "/../instructions.MD", "instructions.md"],
];

$key = $_GET["file"] ?? "";
if (!isset($map[$key])) {
	http_response_code(404);
	exit("Unknown file.");
}

[$path, $downloadName] = $map[$key];
if (!file_exists($path)) {
	http_response_code(404);
	exit("File not found.");
}

header("Content-Type: text/markdown; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$downloadName\"");
header("Content-Length: " . filesize($path));
readfile($path);
