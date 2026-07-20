<?php
use AiDocs\MarkdownParser;

$pageTitle = "MD Files";

$files = [
	"skills" => [
		"label" => "skill.md",
		"path" => __DIR__ . "/../../skills.MD",
		"desc" => "The AI-facing cookbook - how to correctly generate code using MagratheaPHP2.",
	],
	"instructions" => [
		"label" => "instructions.md",
		"path" => __DIR__ . "/../../instructions.MD",
		"desc" => "Project structure, conventions, and how an AI assistant should approach this codebase.",
	],
];

$active = $_GET["file"] ?? "skills";
if (!isset($files[$active])) $active = "skills";
?>

<h1>MD Files</h1>
<p>These two files are written for AI assistants (Claude, GPT, etc.) working on projects built with
MagratheaPHP2. View them here, or download a copy to hand to your own assistant.</p>

<div style="display:flex; gap:0.6rem; margin: 1.5rem 0; border-bottom: 1px solid var(--line);">
	<?php foreach ($files as $key => $f): ?>
		<a href="<?= $baseUrl ?>/?p=md-files&file=<?= $key ?>"
		   style="padding:0.6rem 1rem; font-weight:600; border-bottom: 2px solid <?= $active === $key ? "var(--bronze)" : "transparent" ?>; color: <?= $active === $key ? "var(--text)" : "var(--text-dim)" ?>;">
			<?= htmlspecialchars($f["label"]) ?>
		</a>
	<?php endforeach; ?>
</div>

<?php foreach ($files as $key => $f):
	if ($key !== $active) continue;
	$exists = file_exists($f["path"]);
?>
	<div class="freshness-note" style="justify-content: space-between;">
		<span><?= htmlspecialchars($f["desc"]) ?></span>
		<span style="display:flex; gap:0.5rem;">
			<?php if ($exists): ?>
				<button class="btn-download" style="border:none; cursor:pointer;" data-view-md="<?= $key ?>">View md</button>
			<?php endif; ?>
			<a class="btn-download" href="<?= $baseUrl ?>/download.php?file=<?= $key ?>">Download <?= htmlspecialchars($f["label"]) ?></a>
		</span>
	</div>

	<?php if ($exists): ?>
		<?php
		$raw = file_get_contents($f["path"]);
		// strip a leading YAML frontmatter block (---\n...\n---) - it's metadata for
		// skill-loading tools, not something a human reader needs to see rendered.
		$rendered = preg_replace('/^---\s*\n.*?\n---\s*\n/s', '', $raw, 1);
		?>
		<?= MarkdownParser::ToHtml($rendered) ?>
	<?php else: ?>
		<p><em>File not found.</em></p>
	<?php endif; ?>
<?php endforeach; ?>

<!-- Raw source templates for the "View md" modal - full, unmodified file content, meant to be copied as-is -->
<?php foreach ($files as $key => $f): if (!file_exists($f["path"])) continue; ?>
	<template id="raw-md-tpl-<?= $key ?>" data-title="<?= htmlspecialchars($f["label"]) ?> (raw source)"><?= htmlspecialchars(file_get_contents($f["path"])) ?></template>
<?php endforeach; ?>

<!-- View md modal -->
<div class="modal-overlay" id="raw-md-modal">
	<div class="modal-box" style="max-width: 860px;">
		<div class="modal-head">
			<h3></h3>
			<button class="modal-close">&times;</button>
		</div>
		<div class="modal-body">
			<pre class="code-block"><code class="raw-md-code" style="white-space: pre-wrap;"></code></pre>
		</div>
	</div>
</div>

<script>
(function () {
	document.querySelectorAll("[data-view-md]").forEach(function (btn) {
		btn.addEventListener("click", function () {
			var tpl = document.getElementById("raw-md-tpl-" + btn.getAttribute("data-view-md"));
			if (!tpl) return;
			var overlay = document.getElementById("raw-md-modal");
			overlay.querySelector("h3").textContent = tpl.dataset.title;
			overlay.querySelector(".raw-md-code").textContent = tpl.content.textContent;
			overlay.classList.add("open");
		});
	});
})();
</script>
