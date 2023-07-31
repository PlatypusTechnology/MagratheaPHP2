<?php

namespace Magrathea2\Admin\Features\FileEditor;

use Admin;
use Magrathea2\Admin\AdminElements;
use Magrathea2\Admin\AdminFeature;
use Magrathea2\Admin\AdminManager;
use Magrathea2\Admin\iAdminFeature;
use Magrathea2\iMagratheaModel;
use Magrathea2\MagratheaModel;

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
class AdminFeatureFileEditor extends AdminFeature implements iAdminFeature { 

	public $featureName = "File Editor";
	public $featureId = "AdminFeatureFileEditor";

	public function __construct() {
		parent::__construct();
		$this->SetClassPath(__DIR__);
	}

	public function HasEditPermission($user): bool {
		$loggedUser = AdminManager::Instance()->GetLoggedUser();
		return !empty($loggedUser->id);
	}

	public function View() {
		include(__DIR__."/editor.php");
	}

}
