<?php

namespace Magrathea2\Admin;

use Magrathea2\Admin\Models\AdminConfig;
use Magrathea2\MagratheaPHP;
use Magrathea2\Singleton;

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
class AdminUrls extends Singleton {

	/**
	 * Gets the url for admin page
	 * @param string 	$page					page
	 * @param string 	$action				action
	 * @param array 	$extraParams	extra params
	 */
	public function GetPageUrl($page, $action=null, $extraParams=[]) {
		$params = [];
		if ($page) $params["magrathea_page"] = $page;
		if ($action) $params["magrathea_subpage"] = $action;
		if (count($extraParams) > 0) $params = array_merge($params, $extraParams);
		return "?".http_build_query($params);
	}

	/** 
	 * Gets an url for a feature
	 * @param		string		$feature			feature name
	 * @param 	string 		$action				action
	 * @param 	array 		$extraParams	other params
	 * @return 	string		url
	 */
	public function GetFeatureUrl($feature, $action=null, $extraParams=[]) {
		$params = [];
		if ($feature) $params["magrathea_feature"] = $feature;
		if ($action) $params["magrathea_feature_subpage"] = $action;
		if (count($extraParams) > 0) $params = array_merge($params, $extraParams);
		return "?".http_build_query($params);
	}

	public function GetConfigUrl($env="") {
		return $this->GetPageUrl("config", null, ["env" => $env]);
	}

	/**
	 * Gets a file view url
	 * @param 	string 		$file		file name
	 * @return 	string		file view url
	 */
	public function FileViewUrl($file=null) {
		$base = MagratheaPHP::Instance()->appRoot;
		if (str_starts_with($file, $base)) {
			$file = substr($file, strlen($base)+1);
		}
		return $this->GetPageUrl("file-editor", null, ["file" => $file]);
	}

}

?>