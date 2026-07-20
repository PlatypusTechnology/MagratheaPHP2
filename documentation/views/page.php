<?php
use AiDocs\DocMap;
use AiDocs\Reflector;
use AiDocs\MarkdownParser;

$page = DocMap::FindPage($category, $slug);
$pageTitle = $page["title"];
$topicKey = "$category/$slug";
$topicExamples = examples_for_topic($examples, $topicKey);

$mdFile = DocMap::MdPath($page["md"]);
$mdHtml = file_exists($mdFile) ? MarkdownParser::ToHtml(file_get_contents($mdFile)) : "<p><em>No narrative doc yet.</em></p>";
$fresh = DocMap::Freshness($page);
?>

<?php if ($page["src"]): ?>
	<div class="freshness-note">
		<?php if ($fresh["stale"]): ?>
			<span class="badge badge-stale">May be outdated</span>
			Source (<code><?= htmlspecialchars($fresh["newestSrcFile"]) ?></code>) changed on <?= htmlspecialchars(substr($fresh["newestSrcDate"], 0, 10)) ?>,
			after this narrative was last written on <?= htmlspecialchars(substr($fresh["docDate"] ?? "unknown", 0, 10)) ?>.
			The method signatures below are still live and accurate — only the prose above may lag.
		<?php else: ?>
			<span class="badge badge-fresh">Up to date</span>
			Narrative last written <?= $fresh["docDate"] ? htmlspecialchars(substr($fresh["docDate"], 0, 10)) : "—" ?>;
			no source changes since. Method signatures below are reflected live.
		<?php endif; ?>
	</div>
<?php endif; ?>

<?= $mdHtml ?>

<?php foreach ($page["src"] as $srcFile):
	if (!DocMap::HasClass($srcFile)) continue;
	$fqcn = DocMap::FqcnFromSrc($srcFile);
	$data = Reflector::ReflectClass($fqcn);
	if (!$data) continue;
?>
	<h2 id="class-<?= htmlspecialchars($data["name"]) ?>">Class Reference — <?= htmlspecialchars($data["name"]) ?></h2>
	<div class="class-meta">
		<span class="badge badge-static"><?= htmlspecialchars($data["fqcn"]) ?></span>
		<?php if ($data["parent"]): ?><span class="badge badge-static">extends <?= htmlspecialchars($data["parent"]) ?></span><?php endif; ?>
		<?php foreach ($data["interfaces"] as $i): ?><span class="badge badge-static">implements <?= htmlspecialchars($i) ?></span><?php endforeach; ?>
		<?php if ($data["abstract"]): ?><span class="badge badge-static">abstract</span><?php endif; ?>
		<span class="badge badge-static"><?= htmlspecialchars($data["file"]) ?></span>
	</div>
	<?php if ($data["classDoc"]["summary"]): ?><p><?= htmlspecialchars($data["classDoc"]["summary"]) ?></p><?php endif; ?>

	<?php foreach ($data["methods"] as $m):
		$anchor = "m-" . $data["name"] . "-" . $m["name"];
		$matchExample = null;
		foreach ($topicExamples as $ex) { if (stripos($ex["code"], $m["name"] . "(") !== false || $ex["id"] === "control-usage" && $m["name"] === "GetAll") { $matchExample = $ex; break; } }
	?>
		<div class="method" id="<?= $anchor ?>">
			<div class="method-head">
				<span class="method-sig">
					<?php if ($m["static"]): ?><span class="badge badge-static">static</span><?php endif; ?>
					<span class="mname"><?= htmlspecialchars($m["name"]) ?></span>(<?php
						echo implode(", ", array_map(function ($p) {
							$s = $p["type"] ? htmlspecialchars($p["type"]) . " " : "";
							$s .= ($p["variadic"] ? "..." : "") . "$" . htmlspecialchars($p["name"]);
							if ($p["default"] !== null) $s .= " = " . htmlspecialchars($p["default"]);
							return $s;
						}, $m["params"]));
					?>)<?= $m["returns"] ? ": " . htmlspecialchars($m["returns"]) : "" ?>
				</span>
				<?php if ($matchExample): ?>
					<button class="method-example-btn" data-example="<?= htmlspecialchars($matchExample["id"]) ?>">Example</button>
				<?php endif; ?>
			</div>
			<div class="method-body">
				<?php if ($m["doc"]["summary"]): ?><p><?= htmlspecialchars($m["doc"]["summary"]) ?></p><?php endif; ?>
				<?php if ($m["params"]): ?>
					<table class="params-table">
						<thead><tr><th>Param</th><th>Type</th><th>Default</th></tr></thead>
						<tbody>
						<?php foreach ($m["params"] as $p): ?>
							<tr>
								<td><code>$<?= htmlspecialchars($p["name"]) ?></code></td>
								<td><?= $p["type"] ? htmlspecialchars($p["type"]) : "mixed" ?></td>
								<td><?= $p["default"] !== null ? htmlspecialchars($p["default"]) : ($p["optional"] ? "—" : "required") ?></td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</div>
		</div>
	<?php endforeach; ?>
<?php endforeach; ?>

<?php if ($topicExamples): ?>
	<h2>Examples</h2>
	<div class="cards-grid" style="margin:0; padding:0;">
		<?php foreach ($topicExamples as $ex): ?>
			<a class="card" href="#" data-example="<?= htmlspecialchars($ex["id"]) ?>" onclick="return false;">
				<h3><?= htmlspecialchars($ex["title"]) ?></h3>
				<p><?= $ex["source"] === "skills.MD" ? "From skills.MD" : "Original example" ?></p>
			</a>
		<?php endforeach; ?>
	</div>
<?php endif; ?>
