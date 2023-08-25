<?php

namespace Magrathea2\Admin\Models;

use Exception;
use Magrathea2\MagratheaModelControl;
use Magrathea2\DB\Query;

use function Magrathea2\p_r;

#######################################################################################
####
####    MAGRATHEA Admin Config PHP2
####    v. 2.0
####    Magrathea by Paulo Henrique Martins
####    Platypus technology
####    Admin created: 2023-02 by Paulo Martins
####`
#######################################################################################

/**
 * Class for installing Magrathea's Admin
 */
class AdminConfigControl extends MagratheaModelControl { 
	protected static $modelName = "Magrathea2\Admin\Models\AdminConfig";
	protected static $dbTable = "_magrathea_config";

	/**
	 * returns a value for a key
	 * @param 	string 	$key		key to get
	 */
	public function GetValue($key) {
		return $this->GetValueByKey($key);
	}

	public function SetValue($key, $value) {
		$query = Query::Update()
			->SetArray([ "value" => $value ])
			->Where([ "name" => $key ])
			->Obj(new AdminConfig());
		return self::Run($query);
	}

	public function Save($key, $value, $overwrite=true): AdminConfig {
		try {
			$config = $this->GetByKey($key);
			if(!$config) {
				$config = new AdminConfig();
				$config->name = $key;
			} else {
				if(!$overwrite) {
					return $config;
				}
			}
			$config->value = $value;
			$config->Save();
			return $config;
		} catch(Exception $ex) {
			throw $ex;
		}
	}

	public function GetByKey($key): AdminConfig|null {
		$query = Query::Select()
			->Obj(new AdminConfig())
			->Where(["name" => $key]);
		$a = self::RunRow($query);
		return $a;
	}

	public function GetValueByKey($key) {
		$c = $this->GetByKey($key);
		if(!$c) return null;
		return $c->GetValue();
	}

}
