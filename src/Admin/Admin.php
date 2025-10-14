<?php

namespace Magrathea2\Admin;

use Magrathea2\Admin\Features\CrudObject\AdminCrudObject;
use Magrathea2\Admin\iAdmin;
use Magrathea2\Tests\TestsManager;

#######################################################################################
####
####    MAGRATHEA PHP2
####    v. 2.0
####    Magrathea by Paulo Henrique Martins
####    Platypus technology
####    Admin created: 2022-12 by Paulo Martins
####
#######################################################################################

/**
 * Class for managing Magrathea's Admin
 */
class Admin implements iAdmin {

	/** @var string Title for the admin panel. */
	public $title = "Magrathea Admin";
	/** @var string Primary color for the admin panel in RGB decimal format. */
	public $primaryColor = "203, 128, 8";
	/** @var string Path to the admin logo file. */
	public $adminLogo = __DIR__."/views/logo.svg";
	public $favicon = __DIR__."/views/magrathea_logo.svg";

	/** @var array Extra items to be added to the menu. */
	public $extraMenu = [];

	/** @var array Holds all registered admin features. */
	protected $adminFeatures = [];
	/** @var array Holds keys of all registered CRUD features. */
	protected $crudFeatures = [];

	/**
	 * Constructor. Adds the base javascript file for the admin panel.
	 */
	public function __construct() {
		$this->AddJs(__DIR__."/views/javascript/scripts.js");
	}

	/**
	 * Initializes the admin, adding Magrathea tests.
	 */
	public function Initialize() {
		$this->AddTests();
	}

	/**
	 * Sets title
	 * @param 	string 		$title		title
	 * @return 	Admin			itself
	 */
	public function SetTitle($t): Admin {
		$this->title = $t;
		return $this;
	}
	/**
	 * Sets the primary color from a hexadecimal value.
	 * @param string $color		Color as hexaRGB
	 * @return 	Admin			itself
	 */
	public function SetPrimaryColor($color): Admin {
		$helper = new \Magrathea2\MagratheaHelper();
		$dec = $helper->HexToRgb($color);
		return $this->SetPrimaryColorDecimal(implode(',', $dec));
	}
	/**
	 * Sets the primary color from a decimal RGB string.
	 * @param string $color		Color as Decimal RGB
	 * @return 	Admin			itself
	 */
	public function SetPrimaryColorDecimal($color): Admin {
		$this->primaryColor = $color;
		return $this;
	}
	/**
	 * Defines admin logo (alias)
	 * @param string $logo		logo address
	 * @return 	Admin			itself
	 */
	public function SetLogo(string $logo): Admin {
		return $this->SetAdminLogo($logo);
	}
	/**
	 * Defines admin logo
	 * @param string $logo		logo address
	 * @return 	Admin			itself
	 */
	public function SetAdminLogo($logo): Admin {
		$this->adminLogo = $logo;
		$ext = pathinfo($logo, PATHINFO_EXTENSION);
		if($ext == "svg") $this->favicon = $logo;
		return $this;
	}

	/**
	 * Adds Magrathea's built-in tests to the test manager.
	 * @return Admin	itself
	 */
	public function AddTests(): Admin {
		TestsManager::Instance()->AddMagrathaTests();
		return $this;
	}

	/**
	 * Adds an admin feature.
	 * @param AdminFeature 	$feature		feature class to be added
	 * @param string|null	$key			(optional) key for the feature. If not provided, featureId will be used.
	 * @return Admin		itself
	 */
	protected function AddFeature(AdminFeature $feature, ?string $key=null): Admin {
		if(!$key) $key = $feature->featureId;
		else $feature->featureId = $key;
		$this->adminFeatures[$key] = $feature;
		return $this;
	}
	/**
	 * Adds a CRUD admin feature.
	 * @param AdminCrudObject 	$admin		feature class to be added
	 * @return Admin			itself
	 */
	protected function AddCrudFeature(AdminCrudObject $admin) {
		$key = $admin->featureId;
		$this->adminFeatures[$key] = $admin;
		array_push($this->crudFeatures, $key);
		return $this;
	}

	/**
	 * Sets the default features for the admin panel.
	 */
	public function SetFeatures() {
		$this->LoadAppConfig();
		$this->LoadCache();
		$this->LoadUser();
		$this->LoadFileEditor();
	}	

	/**
	 * Loads the AppConfig feature.
	 */
	protected function LoadAppConfig() {
		$this->AddFeature(new \Magrathea2\Admin\Features\AppConfig\AdminFeatureAppConfig());
	}
	/**
	 * Loads the Cache feature.
	 */
	protected function LoadCache() {
		$this->AddFeature(new \Magrathea2\Admin\Features\Cache\AdminFeatureCache());
	}
	/**
	 * Loads User and UserLog features.
	 */
	protected function LoadUser() {
		$this->AddFeature(new \Magrathea2\Admin\Features\User\AdminFeatureUser());
		$this->AddFeature(new \Magrathea2\Admin\Features\UserLogs\AdminFeatureUserLog());
	}
	/**
	 * Loads the File Editor feature.
	 */
	protected function LoadFileEditor() {
		$this->AddFeature(new \Magrathea2\Admin\Features\FileEditor\AdminFeatureFileEditor());
	}

	/**
	 * Gets all registered admin features.
	 * @return array
	 */
	public function GetFeatures() {
		return $this->adminFeatures;
	}
	/**
	 * Inserts an array of features.
	 * @param array $arrFeatures		array of features
	 * @return Admin		itself
	 */
	public function AddFeaturesArray(array $arrFeatures): Admin {
		foreach($arrFeatures as $f) {
			$this->AddFeature($f);
		}
		return $this;
	}

	/**
	 * Add a JS file to the admin panel.
	 * @param string 	$filePath		path of js file
	 * @return Admin	itself
	 */
	public function AddJs(string $filePath): Admin {
		AdminManager::Instance()->AddJs($filePath);
		return $this;
	}

	/**
	 * Adds one or more menu items to the extra menu.
	 * @param array $item			menu item ["title", "link"]
	 * @return itself
	 */
	public function AddMenuItem(array ...$item): Admin {
		array_push($this->extraMenu, ...$item);
		return $this;
	}
	/**
	 * Gets the menu item for a specific feature.
	 * @param		string 	$key		key of the feature
	 * @return 	array		menu item
	 */
	public function GetMenuItem(string $key): array {
		if(!array_key_exists($key, $this->adminFeatures)) {
			return [
				"title" => "!! invalid key [".$key."]",
				"active" => false,
				"icon" => "fa fa-alert"
			];
		}
		return $this->adminFeatures[$key]->GetMenuItem();
	}

	/**
	 * Builds the admin menu.
	 * @return AdminMenu
	 */
	public function BuildMenu(): AdminMenu{
		$adminMenu = new AdminMenu();
		$this->AddMagratheaMenu($adminMenu);

		if(count($this->extraMenu) > 0) {
			foreach($this->extraMenu as $i) {
				$adminMenu->Add($i);
			}
		}

		$adminMenu
			->Add($adminMenu->GetLogoutMenuItem());
		return $adminMenu;
	}

	/**
	 * Adds the default Magrathea menu sections to the menu.
	 * @param AdminMenu $adminMenu
	 * @return AdminMenu
	 */
	public function AddMagratheaMenu(AdminMenu &$adminMenu): AdminMenu {
		$adminMenu
			->Add("Setup")
			->Add($adminMenu->GetItem("conf-file"));

		if(@$this->adminFeatures["AdminFeatureAppConfig"])
			$adminMenu->Add($this->adminFeatures["AdminFeatureAppConfig"]->GetMenuItem());
		$adminMenu
			->Add($adminMenu->GetItem("structure"))
			->Add($adminMenu->GetItem("htaccess"))

			->Add($adminMenu->GetDatabaseSection())
			->Add($adminMenu->GetObjectSection());

		if(@$this->adminFeatures["AdminFeatureCache"])
			$adminMenu
				->Add("Cache")
				->Add($this->adminFeatures["AdminFeatureCache"]->GetMenuItem());

		$adminMenu
			->Add($adminMenu->GetDebugSection())
			->Add($adminMenu->GetItem("version"));

		if(@$this->adminFeatures["AdminFeatureUser"] && @$this->adminFeatures["AdminFeatureUserLog"])
			$adminMenu
				->Add($adminMenu->GetMenuFeatures([
					$this->adminFeatures["AdminFeatureUser"],
					$this->adminFeatures["AdminFeatureUserLog"],
				], "Users"));

		if(@$this->adminFeatures["AdminFeatureFileEditor"])
			$adminMenu
				->Add("Tools")
				->Add($this->adminFeatures["AdminFeatureFileEditor"]->GetMenuItem());
		
		$adminMenu->Add($adminMenu->GetHelpSection());
		return $adminMenu;
	}

	/**
	 * Adds menu items for all registered CRUD features.
	 * @param AdminMenu $adminMenu
	 * @return AdminMenu
	 */
	public function AddFeaturesMenu(AdminMenu &$adminMenu): AdminMenu {
		$adminMenu->Add($adminMenu->CreateTitle("Features"));
		foreach($this->crudFeatures as $fkey) {
			$adminMenu->Add($this->adminFeatures[$fkey]->GetMenuItem());
		}
		return $adminMenu;
	}

	/**
	 * Checks if a user has authorization to access the admin.
	 * @param \Magrathea2\Admin\Features\User\AdminUser $user
	 * @return bool
	 */
	public function Auth($user): bool {
		return $user->IsAdmin();
	}
}
