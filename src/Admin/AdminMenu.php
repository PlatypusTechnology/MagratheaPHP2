<?php

namespace Magrathea2\Admin;

use Magrathea2\MagratheaPHP;
use Magrathea2\Singleton;


#######################################################################################
####
####    MAGRATHEA PHP2
####    v. 2.0
####    Magrathea by Paulo Henrique Martins
####    Platypus technology
####    AdminMenu created: 2023-07 by Paulo Martins
####
#######################################################################################


// MENU ITEM ARRAY STRUCTURE:
/*
[
	"title" => string		// caption
	"type" => string 		// "sub"|"link"|"empty"|null
	"icon" => string 		// icon (not in use)
	"link" => string		// destination
	"class" => string		// optional class
]
*/

/**
 * Class for managing Magrathea's Admin Menu
 */
class AdminMenu {

	public $menu = [];
	private $adminUrls, $menuItems = [];

	public function __construct() {
		$this->Initialize();
	}

	/**
	 * Gets the working menu
	 * @return array	menu;
	 */
	public function GetMenu(): array {
		return $this->menu;
	}

	/**
	 * add an item or array of items to the menu
	 * @param array|string	$items	items to be added
	 * @return AdminMenu		itself
	 */
	public function Add($items): AdminMenu {
		if(is_string($items)) return $this->Add($this->CreateTitle($items));
		if(is_array(@$items[0])) {
			$this->menu = array_merge($this->menu, $items);
		} else {
			array_push($this->menu, $items);
		}
		return $this;
	}

	/**
	 * Create a title menu item
	 * @param string $t		item title [ title, type ] (type = main|sub|empty|link)
	 * @return array			item object with title
	 */
	public function CreateTitle($t): array {
		return [ "title" => $t, "type" => "sub" ];
	}

	/**
	 * Create a Link Menu item
	 * @param string $caption		item title
	 * @param string $link			item link
	 * @return 	array						item object with link
	 */
	public function CreateLink($caption, $link): array {
		return [ "title" => $caption, "link" => $link, "type" => "link" ];
	}

	/**
	 * Create an empty space
	 * @return array		empty space data
	 */
	public function CreateSpace($class=null): array {
		return [ "type" => "empty", "class" => $class ];
	}

	/**
	 * Get database items array
	 * @return array		items
	 */
	public function GetDatabaseSection(): array {
		$rs = [];
		array_push($rs, 
			$this->CreateTitle("Database"),
			$this->GetItem("connect"),
			$this->GetItem("query"),
			$this->GetItem("backups"),
			$this->GetItem("objects-create"),
			$this->GetItem("mag-query")
		);
		return $rs;
	}
	/**
	 * Get object items array
	 * @return array		items
	 */
	public function GetObjectSection(): array {
		$rs = [];
		array_push($rs,
			$this->CreateTitle("Magrathea Objects"),
			$this->GetItem("objects-conf"),
			$this->GetItem("objects-create"),
			$this->GetItem("objects"),
			$this->GetItem("objects-edit"),
			$this->GetItem("code-gen")
		);
		return $rs;
	}
	/**
	 * Get debug items array
	 * @return array		items
	 */
	public function GetDebugSection(): array {
		$rs = [];
		array_push($rs,
			$this->CreateTitle("Debugging"),
			$this->GetItem("logs"),
			$this->GetItem("tests"),
			$this->GetItem("server"),
		);
		return $rs;
	}
	/**
	 * Get object items array
	 * @return array		items
	 */
	public function GetHelpSection(): array {
		$rs = $this->GetMagratheaLinks();
		array_push($rs,
			$this->GetItem("help-admin-demos"),
			$this->GetItem("help-admin-scripts")
		);
		return $rs;
	}

	/**
	 * Gets an array of features and add the menu items
	 * @param array 	$features		features
	 * @param string 	$title			title for section (default: "Features")
	 * @return array							features menu items
	 */
	public function GetMenuFeatures($features, $title="Features") { 
		$rs = [];
		array_push($rs, $this->CreateTitle($title));
		foreach($features as $f) {
			if(@$f) array_push($rs, $f->GetMenuItem());
		}
		return $rs;
	}

	/**
	 * Gets an array with valuable links for development
	 * @return		array
	 */
	public function GetMagratheaLinks() {
		$documentationLink = [
			'title' => 'Reference',
			'class' => 'menu-link',
			'link' => MagratheaPHP::GetDocumentationLink(),
		];
		$repoLink = [
			'title' => "Github repo",
			'class' => 'menu-link',
			'link' => "https://github.com/PlatypusTechnology/MagratheaPHP2",
		];
		$platypusLink = [
			'title' => 'Platypus Web (author)',
			'class' => 'menu-link',
			'link' => "https://www.platypusweb.com.br/",
		];
		return [
			$this->CreateTitle("Documentation"),
			$documentationLink, $repoLink, $platypusLink
		];
	}

	/**
	 * Get item for user logout
	 * @return array
	 */
	public function GetLogoutMenuItem() {
		return [
			'title' => "Logout",
			'class' => 'menu-logout',
			'link' => AdminUrls::Instance()->GetLogoutUrl(),
		];
	}

	/**
	 * get an item from the pre-existing menu items
	 * @param string $item		item key
	 * @return 	array					item for the menu
	 */
	public function GetItem($item): array|null {
		return @$this->menuItems[$item];
	}

	/**
	 * check if item menu is active
	 * @param string $item		item name
	 * @return boolean				is it?
	 */
	public function IsMenuActive($item): bool {
		$page = @$_GET["magrathea_page"];
		return ($page == $item);
	}

	/**
	 * creates a simple menu item
	 * @param string $title		Title for the item
	 * @param string $page		page that will be called
	 * @return array					menu item
	 */
	public function SimpleItem($title, $page): array {
		return [
			'title' => $title,
			'link' => $this->adminUrls->GetPageUrl($page),
			'active' => $this->IsMenuActive($page),
		];
	}

	public function Initialize() {
		$this->adminUrls = AdminUrls::Instance();
		$this->menuItems = [
			"app-conf" => $this->SimpleItem("App Configuration","config-data"),
			"conf-file" => $this->SimpleItem("Configuration File", "config"),
			"htaccess" => $this->SimpleItem(".htaccess", "htaccess"),
			"tests" => $this->SimpleItem("Tests", "tests"),
			"query" => $this->SimpleItem("Queries", "db-query"),
			"connect" => $this->SimpleItem("Connect", "db-connect"),
			"backups" => $this->SimpleItem("Backups", "backups"),
			"mag-query" => $this->SimpleItem("Magrathea Query", "query-creator"),
			"objects-create" => $this->SimpleItem("Create Objects", "objects-create"),
			"objects-conf" => $this->SimpleItem("Objects Config", "objects-config"),
			"objects" => $this->SimpleItem("View Objects", "objects-view"),
			"objects-edit" => $this->SimpleItem("Edit Objects", "objects-edit"),
			"code-gen" => $this->SimpleItem("Generate Code", "generate-code"),
			"logs" => $this->SimpleItem("Logs", "logs"),
			"structure" => $this->SimpleItem("Structure", "structure"),
			"server" => $this->SimpleItem("Server", "server"),
			"tools-file-editor" => $this->SimpleItem("File Editor", "file-editor"),
			"help-admin-demos" => $this->SimpleItem("Admin Elements", "demo-form"),
			"help-admin-scripts" => $this->SimpleItem("Admin Scripts", "demo-scripts"),
		];	
	}

}
