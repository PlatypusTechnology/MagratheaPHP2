<?php

namespace Magrathea2\Admin\Features\AppConfig;

use Exception;
use Magrathea2\MagratheaModelControl;
use Magrathea2\DB\Query;

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
class AppConfigControl extends MagratheaModelControl { 
	protected static $modelName = "Magrathea2\Admin\Features\AppConfig\AppConfig";
	protected static $dbTable = "_magrathea_config";

	/**
	 * returns a value for a key
	 * @param 	string 	$key		key to get
	 */
	public function GetValue(string $key) {
		return $this->GetValueByKey($key);
	}

	public function SetValue(string $key, string $value) {
		$query = Query::Update()
			->SetArray([ "value" => $value ])
			->Where([ "name" => $key ])
			->Obj(new AppConfig());
		return self::Run($query);
	}

	public function Save(string $key, string $value, bool $overwrite=true): AppConfig {
		try {
			$config = $this->GetByKey($key);
			if(!$config) {
				$config = new AppConfig();
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

	public function GetByKey(string $key): AppConfig|null {
		$query = Query::Select()
			->Obj(new AppConfig())
			->Where(["name" => $key]);
		$a = self::RunRow($query);
		return $a;
	}

	public function GetValueByKey(string $key) {
		$c = $this->GetByKey($key);
		if(!$c) return null;
		return $c->GetValue();
	}

	public function ExportData(): string {
		$export = "";
		$data = $this->GetAll();
		foreach($data as $c) {
			$export .= '=='.$c->key.'==|>>'.$c->GetValue().'>>;;\n';
		}
		return $export;
	}

	public function ImportData(string $dataStr): bool {
		return true;
	}

}
