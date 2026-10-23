<?php

namespace Magrathea2\Admin;

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
	public function Index();
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
		$this->Initialize();
		// after Initialize(), so a feature can set its own id there (AdminCrudObject::SetObject() does)
		if(empty($this->featureId)) {
			$this->featureId = basename(str_replace('\\', '/', get_class($this)));
		}
	}

	/**
	 * Initialize function for AdminFeature
	 */
	public function Initialize() { }

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
	 * Add a CSS file to admin
	 * @param string $file		absolute path of tile
	 * @return AdminFeature		itself
	 */
	public function AddCSS(string $file): AdminFeature {
		AdminManager::Instance()->AddCss($file);
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
		// "GetPage" itself (method names are case-insensitive) would call this method again, forever
		if(is_string($action) && strtolower($action) === "getpage") $action = null;
		if($action && !$this->IsDispatchable($action)) {
			http_response_code(404);
			AdminElements::Instance()->Alert("Page not found", "danger");
			return;
		}
		if(!$this->HasPermission($action)) {
			AdminManager::Instance()->PermissionDenied();
		}
		if($action) {
			$this->$action();
		} else {
			$this->Index();
		}
	}

	/**
	 * Can this method be called from the URL (`magrathea_feature_subpage`, `magrathea_feature_action`)?
	 * Only a public, non-static method of the feature with no required parameters, named `^[A-Za-z]\w*$`,
	 * and not one of AdminFeature's own methods (overridden or not), except `GetPage` and `Index`.
	 * GET requests have no CSRF check: an action that changes data must also refuse anything but POST.
	 * @param 	mixed 	$method		method name, from the query string
	 * @return 	bool
	 */
	public function IsDispatchable($method): bool {
		if(!is_string($method) || !preg_match('/^[A-Za-z]\w*$/', $method)) return false;
		if(!method_exists($this, $method)) return false;
		$reflection = new \ReflectionMethod($this, $method);
		if(!$reflection->isPublic() || $reflection->isStatic() || $reflection->getNumberOfRequiredParameters() > 0) {
			return false;
		}
		if(in_array(strtolower($method), ["getpage", "index"], true)) return true;
		return !method_exists(AdminFeature::class, $method);
	}

	public function Index() {
		include($this->featureClassPath."/index.php");
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

