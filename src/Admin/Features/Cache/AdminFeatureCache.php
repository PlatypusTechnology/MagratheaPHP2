<?php

namespace Magrathea2\Admin\Features\Cache;

use Magrathea2\Admin\AdminElements;
use Magrathea2\Admin\AdminFeature;
use Magrathea2\Admin\Features\UserLogs\AdminLog;
use Magrathea2\Admin\Features\UserLogs\AdminLogControl;
use Magrathea2\Admin\iAdminFeature;
use Magrathea2\Exceptions\MagratheaException;
use Magrathea2\Logger;
use Magrathea2\MagratheaCache;
use Magrathea2\MagratheaHelper;

#######################################################################################
####
####    MAGRATHEA Admin Config PHP2
####    v. 2.0
####    Magrathea by Paulo Henrique Martins
####    Platypus technology
####    Admin created: 2023-02 by Paulo Martins
####
#######################################################################################

/**
 * Class for installing Magrathea's Admin
 */
class AdminFeatureCache extends AdminFeature implements iAdminFeature { 

	public string $featureName = "Cache";
	public string $featureId = "AdminFeatureCache";
	public bool $onlyApp = false;

	public function __construct() {
		parent::__construct();
		$this->AddJs(__DIR__."/scripts.js");
		$this->AddCSS(__DIR__."/styles.css");
		$this->SetClassPath(__DIR__);
	}

	public function Index() {
		include(__DIR__."/index.php");
	}

	/**
	 * Gets the cache file posted as `file`: a plain file name of an existing file directly inside the cache path.
	 * Anything else (`../`, sub-folders, a missing file) throws, so the admin can't read, write or delete
	 * files outside the cache folder.
	 * @return array		["name" => file name, "path" => full path]
	 * @throws MagratheaException	invalid file
	 */
	private function GetPostedCacheFile(): array {
		$file = @$_POST["file"];
		if(!is_string($file) || $file === "" || $file === "." || $file === ".."
			|| basename($file) !== $file || str_contains($file, "\\") || str_contains($file, "\0")) {
			throw new MagratheaException("invalid file");
		}
		$cachePath = MagratheaCache::Instance()->GetCachePath();
		$filePath = realpath(MagratheaHelper::EnsureTrailingSlash($cachePath).$file);
		if(!$filePath || !is_file($filePath) || dirname($filePath) !== realpath($cachePath)) {
			throw new MagratheaException("invalid file");
		}
		return ["name" => $file, "path" => $filePath];
	}

	public function View() {
		$cacheFile = $this->GetPostedCacheFile();
		$file = $cacheFile["name"];
		$filePath = $cacheFile["path"];
		include(__DIR__."/view.php");
	}

	public function Edit() {
		$cacheFile = $this->GetPostedCacheFile();
		$file = $cacheFile["name"];
		$filePath = $cacheFile["path"];
		include(__DIR__."/edit.php");
	}

	public function List() {
		include(__DIR__."/files.php");
	}

	public function Save() {
		$filePath = $this->GetPostedCacheFile()["path"];
		$data = $_POST["content"];
		$f=fopen($filePath,'w');
		$success = fwrite($f,$data);
		fclose($f);
		$elements = AdminElements::Instance();
		if($success > 0) {
			$elements->Alert("File successfully saved [".$success." bytes saved]", "success");
		} else {
			$elements->Alert("Error saving file", "danger");
		}
	}

	public function Remove() {
		$file = $this->GetPostedCacheFile()["name"];
		$success = MagratheaCache::Instance()->DeleteFile($file, false);
		$elements = AdminElements::Instance();
		if($success) {
			$elements->Alert("File deleted [".htmlspecialchars($file)."]", "success");
		} else {
			Logger::Instance()->LogLastError();
			$elements->Alert("Error removing file", "danger");
		}
	}

	public function ClearCache() {
		// POST only: admin CSRF tokens are checked on POST, so a GET link can't purge the cache
		if(@$_SERVER["REQUEST_METHOD"] !== "POST") {
			http_response_code(405);
			AdminElements::Instance()->Alert("Clearing the cache requires a POST request", "danger");
			return;
		}
		$deletedFiles = MagratheaCache::Instance()->RemoveAllCache();
		AdminElements::Instance()->Alert("Cache purged!", "success");
	}

}
