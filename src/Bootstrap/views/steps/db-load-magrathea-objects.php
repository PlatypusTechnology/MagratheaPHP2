<?php

use function Magrathea2\p_r;

	$manager = Magrathea2\Admin\ObjectManager::Instance();
	$confFile = $manager->GetObjectFilePath();

	?>
	<h5>Loading Magrathea Objects</h5>
	<span><?=$confFile?></span>
	<br/>
	<?

	if(!$confFile || !$manager->DoesObjectFileExists()) {
		?>
		<span class="error">no magrathea_objects.conf file</span>
		<?
		return;
	}

	try {
		$config = @$manager->GetFullObjectData();
	} catch(Exception $ex) {
		echo '<span class="error">'.$ex->getMessage().'</span>';
		return;
	}

	?>
	<span>Magrathea Objects:</span>
	<br/><br/>
	<?
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
			$fields = $manager->GetPublicProperties($data);
			foreach($fields as $field) {
				?>
				<div class="col-6">
					<?=$field["name"]?>:
				</div>
				<div class="col-6">
					<?=$field["type"]?>
				</div>
				<?
			}
			?>
			<div class="col-12">
				<?
					$query = $manager->GenerateQueryForObject($object);
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
