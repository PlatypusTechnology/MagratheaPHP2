<?php
	use Magrathea2\Config;

	$config = Config::Instance();
	$env = $config->GetEnvironment();

?>

<h3>[<?=$env?>] database connection</h3>
<div class="row">
  <div class="col-4 right label">Database:</div>
  <div class="col-8"><?=$config->Get("db_host")?></div>
</div>
<div class="row">
  <div class="col-4 right label">Database Name:</div>
  <div class="col-8"><?=$config->Get("db_name")?></div>
</div>
<div class="row">
  <div class="col-4 right label">Username:</div>
  <div class="col-8"><?=$config->Get("db_user")?></div>
</div>
<div class="row">
  <div class="col-4 right label">Password:</div>
  <div class="col-8"><?=$config->Get("db_pass")?></div>
</div>

<div class="row">
  <div class="col-12 actions">
		<button class="btn btn-primary" onclick="testConnection();">Test Connection</button>
		<button class="btn btn-danger" onclick="window.location.href='/?step=2'">Change Database Data</button>
	</div>
</div>

<div class="row">
	<div id="ajax-response" style="display: none;">
	</div>
</div>


<div class="row">
  <div class="col-12 actions">
		<button class="btn btn-primary" onclick="getTables();">View Tables</button>
	</div>
</div>

<div class="row">
	<div id="ajax-response2" class="response" style="display: none;">
	</div>
</div>

