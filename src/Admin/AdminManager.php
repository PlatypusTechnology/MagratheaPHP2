<?php

namespace Magrathea2\Admin;

use Exception;
use Magrathea2\Admin\Features\User\AdminUser;
use Magrathea2\Admin\Features\UserLogs\AdminFeatureUserLog;
use Magrathea2\Admin\Features\UserLogs\AdminLogControl;
use Magrathea2\Singleton;
use Magrathea2\MagratheaPHP;

use function Magrathea2\isMagratheaModel;
use function Magrathea2\p_r;

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
class AdminManager extends Singleton {

	public $title = "Magrathea Admin";
	public $primaryColor = "203, 128, 8";
	private $adminLogo = __DIR__."/views/logo.svg";
	private $features = [];
	private $menu = null;


	public function Initialize() {
		$this
			->AddFeature(new \Magrathea2\Admin\Features\User\AdminFeatureUser())
			->AddFeature(new \Magrathea2\Admin\Features\UserLogs\AdminFeatureUserLog())
			->AddFeature(new \Magrathea2\Admin\Features\FileEditor\AdminFeatureFileEditor());
	}

	/**
	 * Sets title
	 * @param 	string 		$title		title
	 * @return 	Start
	 */
	public function SetTitle($t): AdminManager {
		$this->title = $t;
		return $this;
	}

	/**
	 * Sets color as decimal value
	 * @param string $color		Color as hexaRGB
	 */
	public function SetPrimaryColor($color): AdminManager {
		$helper = new \Magrathea2\Helper();
		$dec = $helper->HexToRgb($color);
		return $this->SetPrimaryColorDecimal(implode(',', $dec));
	}
	/**
	 * Sets color as decimal value
	 * @param string $color		Color as Decimal RGB
	 */
	public function SetPrimaryColorDecimal($color): AdminManager {
		$this->primaryColor = $color;
		return $this;
	}

	/**
	 * Defines admin logo
	 * @param string $logo		logo address
	 * @return AdminManager		itself
	 */
	public function SetAdminLogo($logo): AdminManager {
		$this->adminLogo = $logo;
		return $this;
	}

	/**
	 * Prints the logo
	 * @param int $logoSize		size of the logo
	 */
	public function PrintLogo($logoSize): void {
		include($this->adminLogo);
	}

	/**
	 * sets admin feature
	 * @param AdminFeature $feature		feature class to be added
	 * @return AdminManager						itself
	 */
	public function AddFeature($feature): AdminManager {
		$this->features[$feature->featureId] = $feature;
		return $this;
	}
	/**
	 * gets admin feature id and returns its object
	 * @param string $featureId				admin feature id
	 * @return 	AdminFeature | null		Admin Feature class (null if it does not exists)
	 */
	public function GetFeature($featureId): AdminFeature | null {
		if(array_key_exists($featureId, $this->features)) {
			return $this->features[$featureId];
		}
		return null;
	}
	/**
	 * returns active feature (the one from "magrathea_feature" data)
	 */
	public function GetActiveFeature(): AdminFeature | null {
		$featureId = @$_GET["magrathea_feature"];
		if(!$featureId) return null;
		return $this->GetFeature($featureId);
	}

	/**
	 * Saves menu inside menu var
	 * @return 	void
	 */
	public function BuildMenu(): void {
		$adminMenu = new AdminMenu();
		$adminMenu
			->Add($adminMenu->GetItem("conf-file"))
			->Add($adminMenu->GetItem("app-conf"))
			->Add($adminMenu->GetItem("tests"))

			->Add($adminMenu->GetDatabaseSection())
			->Add($adminMenu->GetObjectSection())
			->Add($adminMenu->GetDebugSection())

			->Add($adminMenu->GetMenuFeatures([
				$this->features["AdminFeatureUser"],
				$this->features["AdminFeatureUserLog"],
			], "Users"))

			->Add("Tools")
			->Add($this->features["AdminFeatureFileEditor"]->GetMenuItem())

			->Add($adminMenu->GetHelpSection())
			->Add($adminMenu->GetLogoutMenuItem());
		$this->SetMenu($adminMenu);
	}

	/**
	 * Sets the menu
	 * @param AdminMenu $m		menu
	 * @return AdminManager		itself
	 */
	public function SetMenu($m): AdminManager {
		$this->menu = $m;
		return $this;
	}

	/**
	 * Gets the AdminMenu
	 * @return array		menu items
	 */
	public function GetMenu(): AdminMenu {
		if(!$this->menu) $this->BuildMenu();
		return $this->menu;
	}

	/**
	 * Log an action
	 * @param string 	$action		action executed
	 * @param array|object 	$data			data for log
	 * @param int			$user_id  action user id
	 * @return void
	 */
	public function Log($action, $data=null, $user_id=false): void {
		if(!$user_id) {
			$user = $this->GetLoggedUser();
			$user_id = $user->id;
		}
		if (isMagratheaModel($data)) {
			$data = json_encode($data);
		}
		$control = new AdminLogControl();
		$control->Log($user_id, $action, $data);
	}

	/**
	 * Starts Admin
	 */
	public function Start() {
		Start::Instance()->StartDb()->Load();
	}

	/**
	 * Return logged user
	 */
	public function GetLoggedUser(): AdminUser {
		return \Magrathea2\Admin\AdminUsers::Instance()->GetLoggedUser();
	}

	/**
	 * Show permission denied page
	 */
	public function PermissionDenied() {
		die("Permission denied!");
	}

}

?>