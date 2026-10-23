<?php

namespace Magrathea2\Admin\Features\AppConfig;

use Magrathea2\Admin\AdminElements;
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
	public bool $onlyApp = false;

	public function __construct(bool $hideSystem=false) {
		parent::__construct();
		$this->onlyApp = $hideSystem;
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
		// POST only: admin CSRF tokens are checked on POST, so a GET link can't import
		if(@$_SERVER["REQUEST_METHOD"] !== "POST") {
			http_response_code(405);
			AdminElements::Instance()->Alert("Importing requires a POST request", "danger");
			return;
		}
		$postDataStr = (string)@$_POST["data"];
		$control = new AppConfigControl();
		$control->ImportData($postDataStr, true);
	}
}
