<?php

namespace Magrathea2\Admin\Features\AppConfig;

use Magrathea2\Admin\AdminFeature;
use Magrathea2\Admin\AdminManager;
use Magrathea2\Admin\iAdminFeature;

#######################################################################################
####
####    MAGRATHEA Admin Config PHP2
####    v. 2.0
####    Magrathea by Paulo Henrique Martins
####    Platypus technology
####    Admin created: 2023-02 by Paulo Martins
####
#######################################################################################

/**
 * Class for installing Magrathea's Admin
 */
class AdminFeatureAppConfig extends AdminFeature implements iAdminFeature { 

	public string $featureName = "App Configuration";
	public string $featureId = "AdminFeatureAppConfig";

	public function __construct() {
		parent::__construct();
		$this->AddJs(__DIR__."/scripts.js");
		$this->SetClassPath(__DIR__);
	}

	public function HasEditPermission(): bool {
		$loggedUser = AdminManager::Instance()->GetLoggedUser();
		return !empty($loggedUser->id);
	}

	public function View() {
		include(__DIR__."/form.php");
	}

	public function List() {
		include(__DIR__."/list.php");
	}

	public function Migration() {
		include(__DIR__."/migration.php");
	}

	public function Import() {
		$postDataStr = @$_POST["data"];
		$data = explode(">>;;\n", $postDataStr);
		$control = new AppConfigControl();
		foreach($data as $config) {
			if(empty($config)) continue;
			$config = explode("==|>>", $config);
			$key = preg_replace('/^={2}/', '', $config[0]);
			$value = str_replace(">>;;", "", $config[1]);
			echo "updating: <b>".$key."</b> = ".$value."<br/>";
			$control->Save($key, $value);
		}
	}
}
