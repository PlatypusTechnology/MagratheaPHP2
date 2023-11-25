<?php

use function Magrathea2\p_r;
use Magrathea2\Config;

$dbManager = Magrathea2\Admin\AdminDatabase::Instance();
$adminElements = \Magrathea2\Admin\AdminElements::Instance();

$adminElements->Header("Database Connection");


$config = Config::Instance();
$env = $config->GetEnvironment();

?>

<div class="container">
	<div class="card">
		<div class="card-header">
			Connect
		</div>
		<div class="card-body">
			<div class="row">
				<div class="col-4 right label"><b>Environment:</b></div>
				<div class="col-8"><?=$env?></div>
			</div>
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
				<div class="col-4 mt-2 actions right">
					<button class="btn btn-primary" onclick="testConnection();">Test Connection</button>
				</div>
				<div class="col-8 mt-4" id="query-response"></div>
			</div>
		</div>
	</div>
</div>

<script type="text/javascript">
function testConnection() {
	callAction("database-test")
		.then(rs => showOn("#query-response", rs));
}
</script>

