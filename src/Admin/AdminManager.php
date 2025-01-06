<?php

namespace Magrathea2\Admin;

use Exception;
use Magrathea2\Admin\Features\User\AdminUser;
use Magrathea2\Admin\Features\UserLogs\AdminLogControl;
use Magrathea2\Compressors\CssCompressor;
use Magrathea2\Compressors\JavascriptCompressor;
use Magrathea2\Singleton;

use function Magrathea2\isMagratheaModel;

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

	private null|AdminMenu $menu = null;
	private null|JavascriptCompressor $javascript = null;
	private null|CssCompressor $css = null;
	private null|Admin $admin = null;

	public function Initialize() {
	}

	/**
	 * Gets title
	 * @return string	title
	 */
	public function GetTitle(): string {
		return $this->admin->title;
	}
	/**
	 * Gets color
	 * @return string	color in decimal format (255, 255, 255)
	 */
	public function GetColor(): string {
		return $this->admin->primaryColor;
	}

	/**
	 * Prints the logo
	 * @param int $logoSize		size of the logo
	 */
	public function PrintLogo($logoSize): void {
		$logoFile = $this->admin->adminLogo;
		$ext = pathinfo($logoFile, PATHINFO_EXTENSION);
		switch($ext) {
			case "svg": include($logoFile); break;
			case "jpeg": case "jpg":
			case "png":
				include(__DIR__."/views/sections/logo.php"); break;
			default: echo "[logo]";
		}
	}

	/**
	 * gets admin feature id and returns its object
	 * @param string $featureId				admin feature id
	 * @return 	AdminFeature | null		Admin Feature class (null if it does not exists)
	 */
	public function GetFeature($featureId): AdminFeature | null {
		$features = $this->admin->GetFeatures();
		if(array_key_exists($featureId, $features)) {
			return $features[$featureId];
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
	 * Sets the menu
	 * @param AdminMenu $m		menu
	 * @return AdminManager		itself
	 */
	public function SetMenu(AdminMenu $m): AdminManager {
		$this->menu = $m;
		return $this;
	}
	/**
	 * Gets the AdminMenu
	 * @return array		menu items
	 */
	public function GetMenu(): AdminMenu {
		if(!$this->menu) $this->SetMenu($this->admin->BuildMenu());
		return $this->menu;
	}

	/**
	 * Log an action
	 * @param string 	$action		action executed
	 * @param array|object|string 	$victim		victim of action
	 * @param array|object 					$data			data for log
	 * @param int			$user_id  action user id
	 * @return void
	 */
	public function Log($action, $victim=null, $data=null, $user_id=false): void {
		if(!$user_id) {
			$user = $this->GetLoggedUser();
			if(!$user) $user_id = 0;
			else $user_id = $user->id;
		}
		if (!isMagratheaModel($victim) && !is_string($victim)) {
			$data = $victim;
			$victim = null;
		}
		if(empty($data)) {
			$data = $victim;
		}
		$data = json_encode($data);
		$control = new AdminLogControl();
		$control->Log($user_id, $action, $victim, $data);
	}

	/**
	 * Adds a javascript file to admin
	 * @param string $file	
	 * @return AdminManger itself
	 */
	public function AddJs($file): AdminManager {
		$this->GetJSManager()->AddFile($file);
		return $this;
	}
	/**
	 * Gets Js compressor
	 * @return JavascriptCompressor
	 */
	public function GetJSManager(): JavascriptCompressor {
		if($this->javascript == null) {
			$this->javascript = new JavascriptCompressor();
		}
		return $this->javascript;
	}
	/**
	 * Gets Js code
	 * @return string code
	 */
	public function GetJs(): string {
		return $this->GetJSManager()->GetCode();
	}


	/**
	 * Gets CSS compressor
	 * @return CssCompressor
	 */
	public function GetCSSManager(): CssCompressor {
		if($this->css == null) {
			$this->css = new CssCompressor();
		}
		return $this->css;
	}
	/**
	 * Adds a css file to admin
	 * @param string $file	
	 * @return AdminManger itself
	 */
	public function AddCss($file): AdminManager {
		$this->GetCSSManager()->AddFile($file);
		return $this;
	}
	/**
	 * Gets Js code
	 * @return string code
	 */
	public function GetCss(): string {
		return $this->GetCSSManager()->GetCode();
	}


	/**
	 * Starts Admin
	 * @param Admin $admin	admin class
	 * @return AdminManager	itself
	 */
	public function Start(Admin $admin): AdminManager {
		$admin->Initialize(); 
		$admin->SetFeatures();
		$this->admin = $admin;
		Start::Instance()->StartDb()->Load();
		return $this;
	}
	/**
	 * Starts a default admin
	 * @param		null|string 	$title 		(optional) default title
	 * @param		null|string 	$color 		(optional) default color
	 * @return AdminManager	itself
	 */
	public function StartDefault(null|string $title=null, null|string $color=null): AdminManager {
		$default = new Admin;
		if($title) $default->SetTitle($title);
		if($color) $default->SetPrimaryColor($color);
		$this->Start($default);
		return $this;
	}
	/**
	 * returns the current admin.
	 * @return null|Admin		admin
	 */
	public function GetAdmin(): null|Admin {
		return $this->admin;
	}

	/**
	 * Return logged user
	 */
	public function GetLoggedUser(): AdminUser | null {
		return \Magrathea2\Admin\AdminUsers::Instance()->GetLoggedUser();
	}

	/**
	 * Permission verification
	 */
	public function Auth(): bool {
		$user = $this->GetLoggedUser();
		return $this->admin->Auth($user);
	}

	/**
	 * Show permission denied page
	 */
	public function PermissionDenied() {
		die("Permission denied!");
	}
	/**
	 * display error page
	 */
	public function ErrorPage($message) {
		include("views/message.php");
		die;
	}

}

?>