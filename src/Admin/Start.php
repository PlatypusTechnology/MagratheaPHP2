<?php

namespace Magrathea2\Admin;

use Exception;
use Magrathea2\Admin\Api\AdminApi;
use Magrathea2\Admin\Features\AppConfig\AppConfigControl;
use Magrathea2\Debugger;
use Magrathea2\Logger;
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
 * Class for installing Magrathea's Admin
 */
class Start extends Singleton { 

	protected $appPath;
	private ?Exception $dbError = null;

	const STATE_SETUP = "setup";
	const STATE_READY = "ready";
	const STATE_DB_ERROR = "db-error";

	protected function Initialize() {
		MagratheaPHP::Instance()->StartSession();
		AdminCsrf::Instance()->GetToken();
		$this->appPath = MagratheaPHP::Instance()->appRoot;
		return $this;
	}

	/**
	 * Starts the database
	 * @return 	Start
	 */
	public function StartDb(): Start {
		MagratheaPHP::Instance()->Connect();
		return $this;
	}

	/**
	 * Check Admin API calls
	 */
	public function CheckApi(): void {
		if(@$_GET["magrathea_api"]) {
			$api = new AdminApi();
			$api->Call($_GET["magrathea_api"], @$_GET["magrathea_api_method"]);
			die;
		}
	}

	/**
	 * Checks if a feature is being loaded
	 */
	public function CheckFeature(): void {
		$featureName = @$_GET["magrathea_feature"];
		if(!$featureName) return;
		$action = @$_GET["magrathea_feature_action"];
		if(!$action) return;
		try { 
			$feature = AdminManager::Instance()->GetFeature($featureName);
			if (!$feature) {
				AdminElements::Instance()->ErrorCard("Feature [".$featureName."] not available!");
			} else if (!$feature->HasPermission($action)) {
				AdminManager::Instance()->PermissionDenied();
			} else {
				$feature->$action();
			}
			die;
		} catch(Exception $ex) {
			AdminElements::Instance()->ErrorCard($ex->getMessage());
		}
	}

	/**
	 * Checks the CSRF token on every admin POST request.
	 * Reads the token from the `magrathea_csrf_token` POST field, falling back to the
	 * `X-Magrathea-CSRF-Token` header (used by AJAX calls). GET is exempt: a token embedded
	 * in a URL would leak via browser history, server/proxy logs, and Referer headers.
	 */
	private function CheckCsrf(): void {
		if ($_SERVER["REQUEST_METHOD"] !== "POST") return;
		$token = @$_POST["magrathea_csrf_token"];
		if (!$token) $token = @$_SERVER["HTTP_X_MAGRATHEA_CSRF_TOKEN"];
		if (!AdminCsrf::Instance()->Validate($token)) {
			Logger::Instance()->SetLogFile("csrf")->Log("CSRF check failed for ".(@$_SERVER["REQUEST_URI"] ?? "unknown"));
			AdminManager::Instance()->PermissionDenied();
		}
	}

	/**
	 * Gets the file for an admin page (`?magrathea_page=`, in `views/pages/`) or action
	 * (`?magrathea_subpage=`, in `views/actions/`): a plain name of an existing file in that folder.
	 * Anything else (`../`, sub-folders, a missing file) gets `null`, so the query string can't include
	 * any other PHP file.
	 * @param 	string 	$folder		"pages" or "actions"
	 * @param 	mixed 	$name			name from the query string
	 * @return 	string|null			file path, or null
	 */
	public function GetViewFile(string $folder, $name): ?string {
		if (!in_array($folder, ["pages", "actions"], true)) return null;
		if (!is_string($name) || $name === "" || $name === "." || $name === ".."
			|| basename($name) !== $name || str_contains($name, "\\") || str_contains($name, "\0")) {
			return null;
		}
		$file = __DIR__."/views/".$folder."/".$name.".php";
		return is_file($file) ? $file : null;
	}

	/**
	 * Loads Magrathea Admin
	 */
	public function Load(): void {
		$state = $this->GetInstallState();
		if ($state === self::STATE_DB_ERROR) {
			$this->DatabaseErrorPage();
		}
		if ($state === self::STATE_SETUP) {
			include ("views/setup.php");
			return;
		}
		$this->ShouldLogout();
		if($this->IsLoggedIn()) {
			if(!AdminManager::Instance()->Auth()) {
				$message = "Access Denied: User is not an admin!";
				AdminManager::Instance()->ErrorPage($message);
			}
			$this->CheckCsrf();
			$this->CheckApi();
			$this->CheckFeature();
			include ("views/index.php");
		} else {
			include ("views/login.php");
		}
	}

	/**
	 * Checks what Admin should load:
	 * - `STATE_SETUP` (first-run setup) only when there are no admin users at all;
	 * - `STATE_DB_ERROR` if the database fails (never the setup form);
	 * - `STATE_READY` otherwise. A missing `admin_install_date` is written on the way.
	 * @return string		one of the STATE_* constants
	 */
	public function GetInstallState(): string {
		try {
			if (!$this->HasUsers()) return self::STATE_SETUP;
			$this->EnsureInstallDate();
			return self::STATE_READY;
		} catch(Exception $ex) {
			$this->dbError = $ex;
			return self::STATE_DB_ERROR;
		}
	}

	/**
	 * Checks if the first super admin can be created (there are no users yet).
	 * A database error means it can't.
	 * @return bool
	 */
	public function CanCreateFirstUser(): bool {
		try {
			return $this->HasUsers() === false;
		} catch(Exception $ex) {
			return false;
		}
	}

	/**
	 * Writes the basic configs (`admin_install_date` and others) if `admin_install_date` is missing.
	 * Existing values are never overwritten.
	 */
	protected function EnsureInstallDate(): void {
		$configControl = new AppConfigControl();
		if ($configControl->GetByKey("admin_install_date") !== null) return;
		$install = new Install();
		$install->SetBasicConfigs();
	}

	/**
	 * Shows the database error page (exception details only in DEV) and dies
	 */
	private function DatabaseErrorPage(): void {
		try {
			Logger::Instance()->LogError($this->dbError);
		} catch(\Throwable $ex) { }
		http_response_code(500);
		$message = "Database error: the admin can't be loaded right now.";
		if (MagratheaPHP::Instance()->IsDev() && $this->dbError) {
			$message .= "<br/>".htmlspecialchars($this->dbError->getMessage());
		}
		AdminManager::Instance()->ErrorPage($message);
	}

	public function ShouldLogout() {
		$logout = @$_GET["magrathea_logout"];
		if($logout == "true") {
			\Magrathea2\Admin\AdminUsers::Instance()->Logout();
			$url = strtok($_SERVER['REQUEST_URI'], '?');
			header('Location: ' . $url);
			exit;
		}
	}

	/**
	 * checks if any admin user is logged in
	 * @return 	bool 		$isLogged 			is it?
	 */
	public function IsLoggedIn(): bool {
		$user = \Magrathea2\Admin\AdminUsers::Instance()->GetLoggedUser();
		return ($user !== null);
	}

	/**
	 * Checks if MagratheaAdmin is already started
	 */
	public function IsStarted(): bool {
		$configContol = new AppConfigControl();
		try {
			$config = $configContol->GetByKey("admin_install_date");
			$hasConfig = ($config !== null);
			$hasUsers = $this->HasUsers();
			return ($hasConfig && $hasUsers);
		} catch(\Magrathea2\Exceptions\MagratheaDBException $ex) {
			$title = "Error on Database. Is it installed?";
			AdminElements::Instance()->ErrorCard($ex->getMessage(), $title);
			return false;
		} catch(Exception $ex) {
			throw $ex;
		}
	}

	public function HasUsers(): bool {
		$control = new \Magrathea2\Admin\Features\User\AdminUserControl();
		return ($control->CountUsers() > 0);
	}

}

?>