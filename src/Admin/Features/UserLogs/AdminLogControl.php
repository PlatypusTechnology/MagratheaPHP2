<?php

namespace Magrathea2\Admin\Features\UserLogs;

use Exception;
use Magrathea2\MagratheaModelControl;
use Magrathea2\DB\Query;

use function Magrathea2\p_r;

class AdminLogControl extends MagratheaModelControl { 
	protected static $modelNamespace = "Magrathea2\Admin\Features\UserLogs";
	protected static $modelName = "AdminLog";
	protected static $dbTable = "_magrathea_logs";

	public function Log($user_id, $action, $data=null): AdminLog {
		$log = new AdminLog();
		$log->user_id = $user_id;
		$log->action = $action;
		if ($data) {
			$log->info = $data;
		}
		try {
			$log->Insert();
//			p_r($log);
			return $log;
		} catch(Exception $ex) {
			throw $ex;
		}
	}

	/**
	 * Get Latest Logs
	 * @param int $count		how much (default: 20)
	 * @return array				array of AdminLog
	 */
	public function GetLatest($count=20): array {
		$q = Query::Select();
		$q->Obj(new AdminLog())->Limit($count)->Order("created_at DESC");
		return self::Run($q);
	}

}