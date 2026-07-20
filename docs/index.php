<?php

require __DIR__ . "/lib/DocMap.php";
require __DIR__ . "/lib/Reflector.php";
require __DIR__ . "/lib/MarkdownParser.php";
require __DIR__ . "/../vendor/autoload.php";

use AiDocs\DocMap;
use AiDocs\Reflector;
use AiDocs\MarkdownParser;

$examples = require __DIR__ . "/data/examples.php";
$tree = DocMap::Tree();
$baseUrl = rtrim(dirname($_SERVER["SCRIPT_NAME"]), "/");

$p = $_GET["p"] ?? "";
$route = "home";
$category = null;
$slug = null;

if ($p === "all-classes") {
	$route = "all-classes";
} elseif ($p === "examples") {
	$route = "examples";
} elseif ($p === "md-files") {
	$route = "md-files";
} elseif ($p === "about") {
	$route = "about";
} elseif ($p === "changelog") {
	$route = "changelog";
} elseif ($p !== "" && strpos($p, "/") !== false) {
	[$category, $slug] = explode("/", $p, 2);
	$page = DocMap::FindPage($category, $slug);
	if ($page) {
		$route = "page";
	}
}

function examples_for_topic(array $examples, string $topic): array {
	return array_values(array_filter($examples, fn($e) => in_array($topic, $e["topics"])));
}

ob_start();

if ($route === "home") {
	include __DIR__ . "/views/home.php";
} elseif ($route === "all-classes") {
	include __DIR__ . "/views/all-classes.php";
} elseif ($route === "examples") {
	include __DIR__ . "/views/examples.php";
} elseif ($route === "md-files") {
	include __DIR__ . "/views/md-files.php";
} elseif ($route === "about") {
	include __DIR__ . "/views/about.php";
} elseif ($route === "changelog") {
	include __DIR__ . "/views/changelog.php";
} elseif ($route === "page") {
	include __DIR__ . "/views/page.php";
} else {
	http_response_code(404);
	echo "<h1>Not found</h1>";
}

$content = ob_get_clean();

include __DIR__ . "/views/layout.php";
