<?php
use AiDocs\MarkdownParser;

$pageTitle = "Changelog";
$changelogPath = __DIR__ . "/../../src/changelog.md";
?>

<h1>Changelog</h1>

<?php if (file_exists($changelogPath)): ?>
	<?= MarkdownParser::ToHtml(file_get_contents($changelogPath)) ?>
<?php else: ?>
	<p><em>Changelog file not found.</em></p>
<?php endif; ?>
