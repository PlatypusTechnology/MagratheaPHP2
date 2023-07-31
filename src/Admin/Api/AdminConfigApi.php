<?php

namespace Magrathea2\Admin\Api;
use Magrathea2\Admin\Models\AdminConfigControl;
use Magrathea2\Bootstrap\CodeManager;
use Magrathea2\Exceptions\MagratheaApiException;

use function Magrathea2\p_r;

#######################################################################################
####
####    MAGRATHEA PHP2
####    v. 2.0
####    Magrathea by Paulo Henrique Martins
####    Platypus technology
####    Admin Api created: 2023-04 by Paulo Martins
####
#######################################################################################
class AdminConfigApi extends \Magrathea2\MagratheaApiControl {

	public function SaveConfig($params) {
		unset($params["magrathea_api"]);
		unset($params["magrathea_api_method"]);
		$configs = [];
		$control = new AdminConfigControl();
		foreach($params as $key => $value) {
			$k = str_replace('-', '_', $key);
			$configs[$k] = $value;
			$control->Save($k, $value);
		}
		return CodeManager::Instance()->GetCodeCreationData();
	}


}
