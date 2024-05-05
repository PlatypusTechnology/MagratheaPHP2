<?php

namespace Magrathea2\Admin\Api;

use Magrathea2\Admin\Features\UserLogs\AdminLog;
use Magrathea2\Admin\Features\UserLogs\AdminLogControl;
use Magrathea2\Exceptions\MagratheaApiException;

class UserLogsApi extends \Magrathea2\MagratheaApiControl {
	public function __construct() {
		$this->model = get_class(new AdminLog());
		$this->service = new AdminLogControl();
	}

	public function GetLast($params) {
		$page = @$_GET["page"] ?? 0;
		$count = 20;
		$data = $this->service->GetLatest($count, $page);
		array_map(function($i) { return $i->Simple(); }, $data);
		return [
			"page" => $page,
			"data" => $data,
			"has_more" => count($data) == $count,
		];
	}

	public function GetByUser($params) {
		$user = @$params["user"];
		$page = @$_GET["page"] ?? 0;
		$count = 20;
		if(!$user) throw new MagratheaApiException("User is invalid");
		$data = $this->service->GetByUser($user, $count, $page);
		return [
			"user" => $user,
			"page" => $page,
			"data" => $data,
			"has_more" => count($data) == $count,
		];
	}

	public function GetByVictim($params) {
		$victim = @$params["victim"];
		$page = @$_GET["page"] ?? 0;
		$count = 20;
		if(!$victim) throw new MagratheaApiException("Victim is invalid");
		$data = $this->service->GetByVictim($victim, $count, $page);
		return [
			"victim" => $victim,
			"page" => $page,
			"data" => $data,
			"has_more" => count($data) == $count,
		];
	}

}
