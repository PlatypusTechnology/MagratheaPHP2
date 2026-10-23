<?php

namespace Magrathea2\Admin\Api;

use Magrathea2\Admin\AdminDatabase;

class DatabaseApi extends \Magrathea2\MagratheaApiControl {

	/**
	 * Creates a backup file in the backups folder (see `AdminDatabase::DoBackup()`)
	 * @param 	array 	$params		["filename"]
	 * @return 	array		["success" => true, "file" => file name, "size" => bytes]
	 */
	public function CreateBackupFile($params) {
		$rs = AdminDatabase::Instance()->DoBackup(@$params["filename"]);
		return [
			"success" => true,
			"file" => $rs["file"],
			"size" => $rs["size"],
		];
	}
}

