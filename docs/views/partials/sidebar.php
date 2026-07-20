<nav class="sidebar">
	<?php foreach ($tree as $catKey => $cat): ?>
		<h4><?= htmlspecialchars($cat["title"]) ?></h4>
		<ul>
			<?php foreach ($cat["pages"] as $pageSlug => $page): ?>
				<li>
					<a href="<?= $baseUrl ?>/?p=<?= $catKey ?>/<?= $pageSlug ?>"
					   class="<?= ($category === $catKey && $slug === $pageSlug) ? "active" : "" ?>">
						<?= htmlspecialchars($page["title"]) ?>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endforeach; ?>
	<h4>Reference</h4>
	<ul>
		<li><a href="<?= $baseUrl ?>/?p=all-classes" class="<?= $route === "all-classes" ? "active" : "" ?>">All Classes</a></li>
		<li><a href="<?= $baseUrl ?>/?p=examples" class="<?= $route === "examples" ? "active" : "" ?>">Examples</a></li>
		<li><a href="<?= $baseUrl ?>/?p=md-files" class="<?= $route === "md-files" ? "active" : "" ?>">MD Files</a></li>
		<li><a href="<?= $baseUrl ?>/?p=about" class="<?= $route === "about" ? "active" : "" ?>">About</a></li>
		<li><a href="<?= $baseUrl ?>/?p=changelog" class="<?= $route === "changelog" ? "active" : "" ?>">Changelog</a></li>
	</ul>
</nav>
