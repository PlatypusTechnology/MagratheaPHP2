<?php

namespace Magrathea2\Admin\Models;

use Exception;
use Magrathea2\MagratheaModelControl;
use Magrathea2\DB\Query;

class AdminLogControl extends MagratheaModelControl { 
	protected static $modelName = "Magrathea2\Admin\Models\AdminLog";
	protected static $dbTable = "_magrathea_logs";

	public function Log($user_id, $action, $data=null): AdminLog {
		$log = new AdminLog();
		$log->user_id = $user_id;
		$log->action = $action;
		if ($data) {
			$log->data = $data;
		}
		try {
			$log->Insert();
			return $log;
		} catch(Exception $ex) {
			throw $ex;
		}
	}

}
