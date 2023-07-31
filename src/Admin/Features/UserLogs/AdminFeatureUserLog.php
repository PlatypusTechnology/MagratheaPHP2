<?php

namespace Magrathea2\Admin\Features\UserLogs;

use Magrathea2\Admin\AdminElements;
use Magrathea2\Admin\AdminFeature;
use Magrathea2\Admin\AdminManager;
use Magrathea2\Admin\Features\User\AdminUser;
use Magrathea2\Admin\iAdminFeature;

use function Magrathea2\p_r;

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
class AdminFeatureUserLog extends AdminFeature implements iAdminFeature { 

	public $featureName = "UserLog";
	public $featureId = "AdminFeatureUserLog";

	public function __construct() {
		parent::__construct();
		$this->SetClassPath(__DIR__);
	}

	public function HasPermission(): bool {
		return true;
	}

	public function Detail() {
		$id = @$_POST["id"];
		$log = new AdminLog($id);
		if(!$log->id) {
			AdminElements::Instance()->Alert("Log [".$id."] not found!", "danger");
		}
		$user = new AdminUser($log->user_id);
		include(__DIR__."/detail.php");
	}


}
