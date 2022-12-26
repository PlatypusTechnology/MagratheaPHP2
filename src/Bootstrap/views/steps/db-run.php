<?php
	use Magrathea2\Config;

	$config = Config::Instance();
	$env = $config->GetEnvironment();
?>

<h3>Database creation</h3>
<div class="row">
	<div class="col-12">
		Database at <?=$env?> (<?=$config->Get("db_host")?>)
		<hr/>
		<br/><br/>
	</div>
	<div class="col-12">
		<? include(__DIR__."/db-query.php"); ?>
	</div>
	<div class="col-12"><hr/></div>
	<div class="col-12">
	<? include(__DIR__."/db-load-magrathea-objects.php"); ?>
	</div>
</div>
