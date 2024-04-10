<?php
	use Magrathea2\Config;

	$config = Config::Instance();
	$env = $config->GetEnvironment();

	function printField($id, $name, $default=null) {
		$value = Config::Instance()->Get($id);
		if(empty($value)) $value = $default;
		?>
		<div class="col-12">
			<label for="<?=$id?>" class="form-label"><?=$name?></label>
			<input type="text" name="<?=$id?>" class="form-control" id="<?=$id?>" value="<?=$value?>">
		</div>
		<?
	}

	$appPath = \Magrathea2\MagratheaPHP::Instance()->appRoot;

?>

<h3>[<?=$env?>] environment</h3>
<div class="form">
	<form id="dbInfoForm">
	<?
	printField("db_host", "Database URL");
	printField("db_name", "Database Name");
	printField("db_user", "Database User");
	printField("db_pass", "Database Password");
	printField("logs_path", "Logs path", $appPath."/logs");
	printField("app_url", "Application URL");
	printField("jwt_key", "Random Hash", Magrathea2\MagratheaHelper::RandomString(20));
	
	?>
	</form>
	<div class="actions">
		<button class="btn btn-primary" onclick="saveDatabaseInfo();">Save</button>
	</div>
	<div id="ajax-response" style="display: none;">
	</div>
</div>

