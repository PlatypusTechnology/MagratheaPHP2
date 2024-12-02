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

	public function View() {
		$file = @$_POST["file"];
		if(!$file) throw new MagratheaException("invalid file");
		include(__DIR__."/view.php");
	}

	public function Edit() {
		$file = @$_POST["file"];
		if(!$file) throw new MagratheaException("invalid file");
		include(__DIR__."/edit.php");
	}

	public function List() {
		include(__DIR__."/files.php");
	}

	public function Save() {
		$file = $_POST["file"];
		$data = $_POST["content"];
		$path = MagratheaCache::Instance()->GetCachePath();
		$filePath = MagratheaHelper::EnsureTrailingSlash($path).$file;
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
		$file = $_POST["file"];
		$success = MagratheaCache::Instance()->DeleteFile($file);
		$elements = AdminElements::Instance();
		if($success) {
			$elements->Alert("File deleted [".$file."]", "success");
		} else {
			Logger::Instance()->LogLastError();
			$elements->Alert("Error removing file", "danger");
		}
	}

	public function ClearCache() {
		$deletedFiles = MagratheaCache::Instance()->RemoveAllCache();
		AdminElements::Instance()->Alert("Cache purged!", "success");
	}

}
