<?php
use AiDocs\DocMap;
use AiDocs\Reflector;

$pageTitle = "All Classes";
$all = Reflector::AllClasses();

// build reverse map fqcn -> [category, slug] for linking to the narrated page when one exists
$linked = [];
foreach ($tree as $catKey => $cat) {
	foreach ($cat["pages"] as $pageSlug => $pg) {
		foreach ($pg["src"] as $srcFile) {
			$linked[DocMap::FqcnFromSrc($srcFile)] = "$catKey/$pageSlug";
		}
	}
}
?>
<h1>All Classes</h1>
<p>Every class, interface, and trait reflected live from <code>src/</code> — <?= count($all) ?> total.
Entries with a narrated guide link there; the rest are reflection-only.</p>

<ul class="class-index-list">
<?php foreach ($all as $fqcn => $rel): ?>
	<li>
		<?php if (isset($linked[$fqcn])): ?>
			<a href="<?= $baseUrl ?>/?p=<?= $linked[$fqcn] ?>#class-<?= htmlspecialchars(basename(str_replace('\\', '/', $fqcn))) ?>"><?= htmlspecialchars($fqcn) ?></a>
		<?php else: ?>
			<span title="<?= htmlspecialchars($rel) ?>"><?= htmlspecialchars($fqcn) ?></span>
		<?php endif; ?>
	</li>
<?php endforeach; ?>
</ul>
