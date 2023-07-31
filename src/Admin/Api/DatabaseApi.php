<?php

namespace Magrathea2\Admin\Api;

use Magrathea2\Admin\AdminDatabase;
use Magrathea2\Admin\Features\User\AdminUser;
use Magrathea2\Admin\Features\User\AdminUserControl;
use Magrathea2\Exceptions\MagratheaApiException;

class DatabaseApi extends \Magrathea2\MagratheaApiControl {

	public function CreateBackupFile($params) {
		$destination = $params["filename"];
		$control = AdminDatabase::Instance();
		$command = $control->GetCommand($destination);
		$rs = $control->DoBackup($destination);
		if ($rs === false) {
			throw new MagratheaApiException("Could not create backup file");
		}
		return [
			"success" => true,
			"rs" => $rs,
			"destination" => $destination,
			"command" => $command,
		];
	}
}

