<?php

use Magrathea2\Config;
use Magrathea2\ConfigFile;

use Magrathea2\Admin\AdminElements;
use Magrathea2\Admin\AdminForm;
use Magrathea2\Admin\AdminManager;
use Magrathea2\Admin\AdminUrls;

use function Magrathea2\p_r;

$configControl = Config::Instance();

$config = $configControl->GetConfig();
$filePath = $configControl->GetFilePath();
$envs = $configControl->GetAvailableEnvironments();
$defaultEnv = $configControl->GetEnvironment();

if(!empty(@$_GET["env"])) {
	$activeEnv = $_GET["env"];
} else {
	$activeEnv = $defaultEnv;
}

AdminElements::Instance()->Header("Config");

if(@$_POST["magrathea-action"] && $_POST["magrathea-action"] == "config-save") {
	$data = $_POST;
	unset($data["magrathea-action"]);
	unset($data["magrathea-submit"]);
	$mconfig = new ConfigFile();
	$mconfig->setPath($configControl->GetPath());
	$mconfig->setFile("magrathea.conf");

	$configData = $mconfig->getConfig();
	
	if(empty($data["magrathea_use_environment"]))
		$config["general"] = $data;
	else {
		$environment = $data["magrathea_use_environment"];
		unset($data["magrathea_use_environment"]);
		$config[$environment] = $data;
	}

	$mconfig->setConfig($config);
	try{
		$success = $mconfig->Save(true);
		AdminManager::Instance()->Log("config file saved", "magrathea.conf", $config);
	} catch(Exception $ex) {
		\Magrathea2\Debugger::Instance()->Error($ex);
		$saveError = $ex->getMessage();
		$success = false;
	}

	$elements = AdminElements::Instance();
	$elements->Buffer();
	if (@$success) {
		$elements->Alert("Config saved", "success");
	} else {
		$elements->Alert(@$saveError, "danger");
	}
	$alertBox = $elements->Get();

}

?>

<div class="container">
	<?php if(@$alertBox) { echo $alertBox; } ?>

	<div class="card">
		<div class="card-header">
			Since v.2.1.18:
			<div class="card-close" aria-label="Close" onclick="closeCard(this);">&times;</div>
		</div>
		<div class="card-body config-form">
			<div class="row">
				<div class="col-12">
					Now you can use environment variables in your config files, starting with `$=`.
				</div>
			</div>
		</div>
	</div>

	<div class="card">
		<div class="card-header">
			Environments:
		</div>
		<div class="card-body config-form">
			<div class="row">
				<div class="col-6 offset-2">
					<?php
						$envForm = new AdminForm();
						$envForm->Build([
								[
									"key" => "config-env",
									"name" => "Environment",
									"type" => $envs,
									"size" => "col-6",
									"attributes" => [
										"onchange" => "selectChange(this);",
									],
								],
								[
									"key" => "default-env",
									"name" => "Default Env",
									"type" => "disabled",
									"size" => "col-6"
								]
							], ['config-env' => $activeEnv, 'default-env' => $defaultEnv]
						)->Print();
					?>
				</div>
			</div>
		</div>
	</div>

	<div class="card">
		<div class="card-header">
			<b><?=$activeEnv?></b> configuration:
		</div>
		<div class="card-body config-form">
			<div class="row">
				<div class="col-6 offset-2">
				<?php
					$envs = array_keys($config);
					$configFormData = $config[$activeEnv];
					include("config/config-form.php");
				?>
				</div>
			</div>
		</div>
	</div>

</div>

<script type="text/javascript">
	function selectChange(sel) {
		let env = $(sel).find(":selected").val();
		console.info('select: ', env);
		window.location.href = "<?=AdminUrls::Instance()->GetConfigUrl()?>" + env;
	}

	function saveConfig() {

	}
</script>

