<?php

namespace Magrathea2\Admin;

use Exception;

use function Magrathea2\p_r;

#######################################################################################
####
####    MAGRATHEA PHP2 Admin features
####    v. 2.0
####    Magrathea by Paulo Henrique Martins
####    Platypus technology
####    Admin created: 2022-12 by Paulo Martins
####
#######################################################################################

interface iAdminFeature {
	public function GetPage();
}

/**
 * Class for Admin Feature
 */
class AdminFeature {

	public $featureName = "Unknown";
	public $featureId = "some-feature";
	public $featureIcon = null;
	public $featureClassPath = __DIR__;

	public function __construct() {
		$className = get_class($this);
		$this->featureId = basename(str_replace('\\', '/', $className));
	}

	/**
	 * Sets feature class path
	 */
	public function SetClassPath($path) { $this->featureClassPath = $path; }

	/**
	 * Checks if user has permission to see a feature
	 * @return 		bool		has it?
	 */
	public function HasPermission() {
		return true;
	}

	/**
	 * prints index page (default at index.php, located in class path)
	 * loads [$featureClass] var with own class to the index page
	 */
	public function GetPage() {
		if(!$this->HasPermission()) {
			AdminManager::Instance()->PermissionDenied();
		}
		$featureClass = $this;
		$action = @$_GET["magrathea_feature_subpage"];
		if($action) {
			$this->$action();
		} else {
			include($this->featureClassPath."/index.php");
		}
	}

	public function GetSubpageUrl($subpage, $params=[]) {
		return AdminUrls::Instance()->GetFeatureUrl($this->featureId, $subpage, $params);
	}

	/**
	 * checks if this feature is currently being displayed (for menu highlighting)
	 * @return bool
	 */
	public function IsFeatureActive(): bool {
		$feature = @$_GET["magrathea_feature"];
		return ($feature == $this->featureId);
	}

	/**
	 * returns the menu item
	 * @return 	array		["title", "name", "icon", "link", "type"]
	 */
	public function GetMenuItem(): array {
		return [
				"title" => $this->featureName,
				"icon" => $this->featureIcon,
				"feature" => $this->featureId,
				"active" => $this->IsFeatureActive(),
			];
	}

}

