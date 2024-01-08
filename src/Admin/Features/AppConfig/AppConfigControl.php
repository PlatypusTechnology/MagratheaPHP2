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

	/**
	 * sets the value for a key
	 * @param string $key
	 * @param string $value
	 */
	public function SetValue(string $key, string $value) {
		$query = Query::Update()
			->SetArray([ "value" => $value ])
			->Where([ "name" => $key ])
			->Obj(new AppConfig());
		return self::Run($query);
	}

	/**
	 * sets the value for a key
	 * @param 	string 				$key
	 * @param 	string|any 		$value
	 * @param 	boolean 			$overwrite 		should overwrite value
	 */
	public function Save(string $key, $value, bool $overwrite=true): AppConfig {
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
			$config->value = strval($value);
			$config->Save();
			return $config;
		} catch(Exception $ex) {
			throw $ex;
		}
	}

	/**
	 * Gets an AppConfig by key
	 * @param string $key		key
	 * @return 	AppConfig
	 */
	public function GetByKey(string $key): AppConfig|null {
		$query = Query::Select()
			->Obj(new AppConfig())
			->Where(["name" => $key]);
		$a = self::RunRow($query);
		return $a;
	}

	/**
	 * Gets the value of an AppConfig
	 * @param string 	$key
	 * @param string 	$default		if key is not found, this will be the default value
	 * @return string|null				returns the value, the default or null if no key is found and no default is set
	 */
	public function GetValueByKey(string $key, string $default=null): string|null {
		$c = $this->GetByKey($key);
		if(!$c) return $default;
		return $c->GetValue();
	}

	/**
	 * Exports data
	 */
	public function ExportData(): string {
		$export = "";
		$data = $this->GetAll();
		foreach($data as $c) {
			$export .= '=='.$c->key.'==|>>'.$c->GetValue().'>>;;\n';
		}
		return $export;
	}

	/**
	 * Imports data
	 */
	public function ImportData(string $dataStr, bool $echoProgress = false): bool {
		$data = explode(">>;;\n", $dataStr);
		foreach($data as $config) {
			if(empty($config)) continue;
			$config = explode("==|>>", $config);
			$key = preg_replace('/^={2}/', '', $config[0]);
			$value = str_replace(">>;;", "", $config[1]);
			if($echoProgress) echo "updating: <b>".$key."</b> = ".$value."<br/>";
			$this->Save($key, $value);
		}
		return true;
	}

}
