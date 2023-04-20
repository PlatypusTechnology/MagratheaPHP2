<?php

namespace Magrathea2\Admin;

use Exception;
use Magrathea2\Singleton;
use Magrathea2\MagratheaPHP;

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

	public $primaryColor = "203, 128, 8";

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
	 * Prints the logo
	 * @param int $logoSize		size of the logo
	 */
	public function PrintLogo($logoSize): void {
		include(__DIR__."/views/logo.svg");
	}

	public function IsMenuActive($item): bool {
		$page = @$_GET["magrathea_page"];
		return ($page == $item);
	}


	public function GetMenuItems() {
		$adminUrls = AdminUrls::Instance();
		return [
			[
				'title' => "Users",
				'link' => $adminUrls->GetPageUrl("users"),
				'active' => $this->IsMenuActive("users"),
			],
			[
				'title' => "App Configuration",
				'link' => $adminUrls->GetPageUrl("config-data"),
				'active' => $this->IsMenuActive("config-data"),
			],
			[
				'title' => "Configuration File",
				'link' => $adminUrls->GetConfigUrl(),
				'active' => $this->IsMenuActive("config"),
			],
			[
				'title' => "Tests",
				'link' => $adminUrls->GetPageUrl("tests"),
				'active' => $this->IsMenuActive("tests"),
			],

			[
				'title' => "Database",
				'type' => "Sub"
			],
			[
				'title' => "View Tables",
				'link' => $adminUrls->GetPageUrl("db-tables"),
				'active' => $this->IsMenuActive("db-tables"),
			],

			[
				'title' => "Objects",
				'type' => "Sub"
			],
			[
				'title' => "View Objects",
				'link' => $adminUrls->GetPageUrl("objects-view"),
				'active' => $this->IsMenuActive("objects-view"),
			],
			[
				'title' => "Edit Objects",
				'link' => $adminUrls->GetPageUrl("objects-edit"),
				'active' => $this->IsMenuActive("objects-edit"),
			],
			[
				'title' => "Objects Config",
				'link' => $adminUrls->GetPageUrl("objects-config"),
				'active' => $this->IsMenuActive("objects-config"),
			],
			[
				'title' => "Generate Code",
				'link' => $adminUrls->GetPageUrl("generate-code"),
				'active' => $this->IsMenuActive("generate-code"),
			],

			[
				'title' => "Debugging",
				'type' => "Sub"
			],
			[
				'title' => "Logs",
				'link' => $adminUrls->GetPageUrl("logs"),
				'active' => $this->IsMenuActive("logs"),
			],
			[
				'title' => "Structure",
				'link' => $adminUrls->GetPageUrl("structure"),
				'active' => $this->IsMenuActive("structure"),
			],
			[
				'title' => "Server",
				'link' => $adminUrls->GetPageUrl("server"),
				'active' => $this->IsMenuActive("server"),
			],

			[
				'title' => "Help",
				'type' => "Sub"
			],
			[
				'title' => "Admin Demos",
				'link' => $adminUrls->GetPageUrl("form-demo"),
				'active' => $this->IsMenuActive("form-demo"),
			],

			[
				'title' => "Logout",
				'class' => 'menu-logout',
				'link' => $adminUrls->GetPageUrl(null, "logout"),
			],
		];
	}

}

?>