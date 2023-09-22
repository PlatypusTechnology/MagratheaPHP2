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
	public function HasPermission($action=null): bool;
}

/**
 * Class for Admin Feature
 */
class AdminFeature {

	public string $featureName = "Unknown";
	public string $featureId = "some-feature";
	public $featureIcon = null;
	public $featureClassPath = __DIR__;

	public function __construct() {
		$className = get_class($this);
		if(empty($this->featureId)) {
			$this->featureId = basename(str_replace('\\', '/', $className));
		}
	}

	/**
	 * Add a JS file to admin
	 * @param string $file		absolute path of tile
	 * @return AdminFeature		itself
	 */
	public function AddJs(string $file): AdminFeature {
		AdminManager::Instance()->AddJs($file);
		return $this;
	}

	/**
	 * Sets feature class path
	 */
	public function SetClassPath($path) { $this->featureClassPath = $path; }

	/**
	 * Checks if user has permission to see a feature
	 * @return 		bool		has it?
	 */
	public function HasPermission($action=null): bool {
		return true;
	}

	/**
	 * prints index page (default at index.php, located in class path)
	 * loads [$featureClass] var with own class to the index page
	 */
	public function GetPage() {
		$action = @$_GET["magrathea_feature_subpage"];
		if(!$this->HasPermission($action)) {
			AdminManager::Instance()->PermissionDenied();
		}
		$featureClass = $this;
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

