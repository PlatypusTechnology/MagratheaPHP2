<?php $pageTitle = "Examples"; ?>
<h1>Examples</h1>
<p>Copy-pasteable snippets for the most common tasks. Click any card to open it, or jump straight to a topic's page for it in context.</p>

<div class="cards-grid" style="margin:0; padding:0;">
	<?php foreach ($examples as $ex): ?>
		<a class="card" href="#" data-example="<?= htmlspecialchars($ex["id"]) ?>" onclick="return false;">
			<h3><?= htmlspecialchars($ex["title"]) ?></h3>
			<p><?= $ex["source"] === "skills.MD" ? "From skills.MD" : "Original example" ?></p>
		</a>
	<?php endforeach; ?>
</div>
