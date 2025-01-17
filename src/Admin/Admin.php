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

	public $title = "Magrathea Admin";
	public $primaryColor = "203, 128, 8";
	public $adminLogo = __DIR__."/views/logo.svg";
	public $extraMenu = [];

	public function __construct() {
		$this->AddJs(__DIR__."/views/javascript/scripts.js");
	}

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
	 * Sets color as decimal value
	 * @param string $color		Color as hexaRGB
	 * @return 	Admin			itself
	 */
	public function SetPrimaryColor($color): Admin {
		$helper = new \Magrathea2\MagratheaHelper();
		$dec = $helper->HexToRgb($color);
		return $this->SetPrimaryColorDecimal(implode(',', $dec));
	}
	/**
	 * Sets color as decimal value
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
	public function SetLogo(string $logo): Admin { return $this->SetAdminLogo($logo); }
	/**
	 * Defines admin logo
	 * @param string $logo		logo address
	 * @return 	Admin			itself
	 */
	public function SetAdminLogo($logo): Admin {
		$this->adminLogo = $logo;
		return $this;
	}


	public function AddTests(): Admin {
		TestsManager::Instance()->AddMagrathaTests();
		return $this;
	}


	protected $adminFeatures = [];
	protected $crudFeatures = [];
	/**
	 * sets admin feature
	 * @param AdminFeature 	$feature		feature class to be added
	 * @param string				$key				key for the feature
	 * @return Admin						itself
	 */
	protected function AddFeature(AdminFeature $feature, string $key=null): Admin {
		if(!$key) $key = $feature->featureId;
		else $feature->featureId = $key;
		$this->adminFeatures[$key] = $feature;
		return $this;
	}
	/**
	 * adds a crud admin feature
	 * @param AdminCrudOobject 	$feature		feature class to be added
	 * @return Admin						itself
	 */
	protected function AddCrudFeature(AdminCrudObject $admin) {
		$key = $admin->featureId;
		$this->adminFeatures[$key] = $admin;
		array_push($this->crudFeatures, $key);
		return $this;
	}
	public function SetFeatures() {
		$this
			->AddFeature(new \Magrathea2\Admin\Features\AppConfig\AdminFeatureAppConfig())
			->AddFeature(new \Magrathea2\Admin\Features\Cache\AdminFeatureCache())
			->AddFeature(new \Magrathea2\Admin\Features\User\AdminFeatureUser())
			->AddFeature(new \Magrathea2\Admin\Features\UserLogs\AdminFeatureUserLog())
			->AddFeature(new \Magrathea2\Admin\Features\FileEditor\AdminFeatureFileEditor());
	}
	public function GetFeatures() {
		return $this->adminFeatures;
	}
	/**
	 * inserts an array of features
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
	 * Add a JS file
	 * @param string 	$filePath		path of js file
	 * @return Admin	itself
	 */
	public function AddJs(string $filePath): Admin {
		AdminManager::Instance()->AddJs($filePath);
		return $this;
	}

	/**
	 * Adds a menu item
	 * @param array $item			menu item ["title", "link"]
	 * @return itself
	 */
	public function AddMenuItem(array ...$item): Admin {
		array_push($this->extraMenu, ...$item);
		return $this;
	}
	/**
	 * Gets the menu item
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

	public function AddMagratheaMenu(AdminMenu &$adminMenu): AdminMenu {
		$adminMenu
			->Add("Setup")
			->Add($adminMenu->GetItem("conf-file"))
			->Add($this->adminFeatures["AdminFeatureAppConfig"]->GetMenuItem())
			->Add($adminMenu->GetItem("structure"))
			->Add($adminMenu->GetItem("htaccess"))

			->Add($adminMenu->GetDatabaseSection())
			->Add($adminMenu->GetObjectSection())

			->Add("Cache")
			->Add($this->adminFeatures["AdminFeatureCache"]->GetMenuItem())

			->Add($adminMenu->GetDebugSection())
			->Add($adminMenu->GetItem("version"))

			->Add($adminMenu->GetMenuFeatures([
				$this->adminFeatures["AdminFeatureUser"],
				$this->adminFeatures["AdminFeatureUserLog"],
			], "Users"))

			->Add("Tools")
			->Add($this->adminFeatures["AdminFeatureFileEditor"]->GetMenuItem())
			->Add($adminMenu->GetHelpSection());
		return $adminMenu;
	}
	public function AddFeaturesMenu(AdminMenu &$adminMenu): AdminMenu {
		$adminMenu->Add($adminMenu->CreateTitle("Features"));
		foreach($this->crudFeatures as $fkey) {
			$adminMenu->Add($this->adminFeatures[$fkey]->GetMenuItem());
		}
		return $adminMenu;
	}

	public function Auth($user): bool {
		return $user->IsAdmin();
	}
}

