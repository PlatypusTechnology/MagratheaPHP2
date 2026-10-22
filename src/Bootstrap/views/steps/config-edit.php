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
		<?php
	}
	$appPath = \Magrathea2\MagratheaPHP::Instance()->appRoot;
?>
<h3>[<?=$env?>] environment</h3>
<div class="form">
	<form id="dbInfoForm">
	<?php
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
<hr/>
<h4>Timezone check</h4>
<div class="timezone-check">
	<?php
	$levelClass = [
		\Magrathea2\TimezoneCheck::OK => "success",
		\Magrathea2\TimezoneCheck::INFO => "secondary",
		\Magrathea2\TimezoneCheck::WARNING => "warning",
		\Magrathea2\TimezoneCheck::ERROR => "danger",
	];
	try {
		// the database may not be configured yet: TimezoneCheck::Run() reports that as a finding
		\Magrathea2\MagratheaPHP::Instance()->Connect();
		$findings = \Magrathea2\TimezoneCheck::Run();
	} catch(\Throwable $ex) {
		$findings = [["level" => \Magrathea2\TimezoneCheck::WARNING, "message" => "Could not run the timezone check: ".$ex->getMessage()]];
	}
	foreach($findings as $finding) {
		$message = preg_replace('/`([^`]+)`/', '<code>$1</code>', htmlspecialchars($finding["message"]));
		echo '<div class="alert alert-'.$levelClass[$finding["level"]].' py-1 mb-1">'.$message.'</div>';
	}
	?>
</div>
