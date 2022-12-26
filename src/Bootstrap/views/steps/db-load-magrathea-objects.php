<?php

	$bootstrap = Magrathea2\Bootstrap\CodeManager::Instance()->Load();
	$confFile = $bootstrap->getMagratheaObjectsFile();

	?>
	<h5>Loading Magrathea Objects</h5>
	<span><?=$confFile?></span>
	<br/>
	<?

	if(!$confFile) {
		?>
		<span class="error">no magrathea_objects.conf file</span>
		<?
		return;
	}

	?>
	<span>Magrathea Objects:</span>
	<br/><br/>
	<?
	$config = $bootstrap->getMagratheaObjectsData();
	foreach($config as $object => $data) {
		$columns = "";
		if($object === "relations") continue;
		$table = $data["table_name"];
		?>
		<div class="row">
			<div class="col-12">
				<b><?=$object?></b> (table: <i><?=$table?></i>)
			</div>
			<?
			$fields = $bootstrap->getFieldsFromObject($data);
			foreach($fields as $k => $i) {
				?>
				<div class="col-6">
					<?=$k?>:
				</div>
				<div class="col-6">
					<?=$i?>
				</div>
				<?
			}
			?>
			<div class="col-12">
				<?
					$query = $bootstrap->generateQueryForObject($data);
				?>
				<br/>
				<pre class="code" id="create-<?=$table?>"><?=$query?></pre>
			</div>
			<div class="col-12 right">
				<button class="btn btn-primary" onclick="sendCreateToExecute('<?=$table?>');">Run Query</button>
			</div>
		</div>
		<hr/>
		<?
	}
