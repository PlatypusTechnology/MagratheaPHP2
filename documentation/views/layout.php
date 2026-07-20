<?php use AiDocs\MarkdownParser; ?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . " — " : "" ?>MagratheaPHP2 Documentation</title>
<link rel="icon" href="<?= $baseUrl ?>/assets/img/logo.svg">
<link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/style.css">
</head>
<body>

<div class="topbar">
	<a class="brand" href="<?= $baseUrl ?>/">
		<img src="<?= $baseUrl ?>/assets/img/logo.svg" alt="">
		MagratheaPHP2
	</a>
	<div class="spacer"></div>
	<div class="searchbox" data-open-search>
		<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
		Search docs & functions…
		<kbd>Ctrl K</kbd>
	</div>
	<div class="topbar-links">
		<a href="<?= $baseUrl ?>/?p=examples">Examples</a>
		<a href="<?= $baseUrl ?>/?p=md-files">MD Files</a>
		<a href="<?= $baseUrl ?>/?p=about">About</a>
		<a href="https://github.com/PlatypusTechnology/MagratheaPHP2" target="_blank" rel="noopener" aria-label="GitHub" title="GitHub" style="display:flex; align-items:center;">
			<svg width="20" height="20" viewBox="0 0 16 16" fill="currentColor"><path d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27.68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.01 8.01 0 0 0 16 8c0-4.42-3.58-8-8-8Z"/></svg>
		</a>
	</div>
</div>

<div class="layout">
	<?php include __DIR__ . "/partials/sidebar.php"; ?>
	<div class="content">
		<?= $content ?>
	</div>
</div>

<!-- Example modal -->
<div class="modal-overlay" id="example-modal">
	<div class="modal-box">
		<div class="modal-head">
			<h3></h3>
			<button class="modal-close">&times;</button>
		</div>
		<div class="modal-body">
			<span class="source-tag"></span>
			<pre class="code-block"><code class="modal-code"></code></pre>
			<div class="modal-note"></div>
		</div>
	</div>
</div>

<!-- Search modal -->
<div class="modal-overlay" id="search-modal">
	<div class="modal-box">
		<div class="modal-head">
			<input id="search-input" type="text" placeholder="Search classes, methods, guides…" autocomplete="off">
			<button class="modal-close">&times;</button>
		</div>
		<div id="search-results"></div>
	</div>
</div>

<!-- Pre-rendered example templates (used by JS modal) -->
<?php foreach ($examples as $ex): ?>
<template id="example-tpl-<?= htmlspecialchars($ex["id"]) ?>"
	data-title="<?= htmlspecialchars($ex["title"]) ?>"
	data-source="<?= htmlspecialchars($ex["source"]) ?>"
	data-note="<?= htmlspecialchars($ex["note"] ?? "") ?>"><?= MarkdownParser::HighlightPhp($ex["code"], $ex["lang"] ?? "php") ?></template>
<?php endforeach; ?>

<script>window.aiDocsBase = <?= json_encode($baseUrl) ?>;</script>
<script src="<?= $baseUrl ?>/assets/js/app.js"></script>
</body>
</html>
