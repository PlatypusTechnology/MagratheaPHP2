<?php

namespace Magrathea2\Admin\Features\ApiExplorer;
use Magrathea2\Admin\AdminFeature;
use Magrathea2\Admin\Features\User\AdminUserControl;
use Magrathea2\Admin\iAdminFeature;
use Magrathea2\MagratheaApi;

class ApiExplorer extends AdminFeature implements iAdminFeature {
	public string $featureName = "Explore API";
	public string $featureId = "ApiExplorer";
	public null|MagratheaApi $api = null;
	public $apiName = null;
	public $apiUrl = null;

	public function __construct() {
		parent::__construct();
		$this->SetClassPath(__DIR__);
		$this->AddJs(__DIR__."/scripts.js");
		$this->AddCSS(__DIR__."/styles.css");
	}

	/**
	 * Sets the API for the feature
	 * @param MagratheaApi $api		Api
	 * @return ApiExplorer				itself
	 */
	public function SetApi($api): ApiExplorer {
		$this->api = $api;
		$this->apiName = \Magrathea2\getClassNameOfClass(get_class($api));
		$this->featureName = $this->apiName;
		$this->apiUrl = $this->api->GetAddress();
		return $this;
	}

	public function GetPage() {
		include("pages/index.php");
	}

	public function GetAuthentication() {
		$userControl = new AdminUserControl();
		$users = $userControl->GetSelectWithRoles();
		include("pages/authentication.php");
	}

	public function GetEndpoints() {
		$endpoints = $this->api->GetEndpointsDetail();
		include("pages/endpoints.php");
	}

}
