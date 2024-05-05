<?php

namespace Magrathea2\Admin\Features\UserLogs;

use Exception;
use Magrathea2\MagratheaModelControl;
use Magrathea2\DB\Query;
use Magrathea2\MagratheaModel;

use function Magrathea2\p_r;

class AdminLogControl extends MagratheaModelControl { 
	protected static $modelNamespace = "Magrathea2\Admin\Features\UserLogs";
	protected static $modelName = "AdminLog";
	protected static $dbTable = "_magrathea_logs";

	public function GetVictim(MagratheaModel|string $victim) {
		if(is_string($victim)) return '"'.$victim.'"';
		return $victim->ModelName()." (".$victim->GetID().")";
	}

	public function Log($user_id, $action, null|MagratheaModel|string $victim=null, $data=null): AdminLog {
		$log = new AdminLog();
		$log->user_id = $user_id;
		$log->action = $action;
		if($victim) $log->victim = $this->GetVictim($victim);
		else $log->victim = null;
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
	public function GetLatest($count=10, $page=0): array {
		$q = Query::Select()
			->Obj(new AdminLog())
			->Limit($count)
			->Page($page)
			->Order("created_at DESC");
		return self::Run($q);
	}

	public function GetByUser($user_id, $count=10, $page=0): array {
		return $this->GetBy(["user_id" => $user_id], $count, $page);
	}
	public function GetByVictim($victim, $count=10, $page=0): array {
		return $this->GetBy(["victim" => $victim], $count, $page);
	}

	public function GetBy($condition, $count, $page): array {
		$q = Query::Select()
			->Obj(new AdminLog())
			->Where($condition)
			->Limit($count)
			->Page($page)
			->Order("created_at DESC");
		return self::Run($q);
	}

}
