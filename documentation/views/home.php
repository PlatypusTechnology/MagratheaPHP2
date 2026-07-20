<?php
use AiDocs\MarkdownParser;
$pageTitle = null;
?>
<div class="hero" style="margin: -2rem -2.5rem 0; border-radius: 0;">
	<img src="<?= $baseUrl ?>/assets/img/logo.svg" alt="MagratheaPHP2">
	<h1>Magrathea<span>PHP2</span> Documentation</h1>
	<p>A full PHP framework for building APIs, web applications, and admin panels — ORM, fluent query builder,
	JWT-secured REST framework, and a pluggable admin panel. This reference is generated live from the source
	via reflection, so method signatures never drift out of date.</p>
	<div class="searchbox" data-open-search>
		<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
		Search classes, methods, guides…
		<kbd>Ctrl K</kbd>
	</div>
</div>

<div class="cards-grid" style="margin-left:0; margin-right:0; padding:0;">
	<?php foreach ($tree as $catKey => $cat): $first = array_key_first($cat["pages"]); ?>
		<a class="card" href="<?= $baseUrl ?>/?p=<?= $catKey ?>/<?= $first ?>">
			<h3><?= htmlspecialchars($cat["title"]) ?></h3>
			<p><?= count($cat["pages"]) ?> topic<?= count($cat["pages"]) === 1 ? "" : "s" ?></p>
		</a>
	<?php endforeach; ?>
	<a class="card" href="<?= $baseUrl ?>/?p=all-classes">
		<h3>All Classes</h3>
		<p>Full reflected index of every class under <code>src/</code></p>
	</a>
	<a class="card" href="<?= $baseUrl ?>/?p=examples">
		<h3>Examples</h3>
		<p>Copy-pasteable code samples for the most-used features</p>
	</a>
	<a class="card" href="<?= $baseUrl ?>/?p=md-files">
		<h3>MD Files</h3>
		<p>View or download skill.md and instructions.md</p>
	</a>
</div>

<h2 style="margin-top:3rem;">Quick start</h2>
<p>Drop this in your project's public entry point:</p>
<?php
$setup = current(array_filter($examples, fn($e) => $e["id"] === "setup-php"));
?>
<pre class="code-block"><code><?= MarkdownParser::HighlightPhp($setup["code"], "php") ?></code></pre>
<p>See the <a href="<?= $baseUrl ?>/?p=getting-started/getting-started">Getting Started guide</a> for the full walkthrough,
or grab <a href="<?= $baseUrl ?>/?p=md-files">skill.md</a> to hand this framework's conventions to an AI assistant.</p>
