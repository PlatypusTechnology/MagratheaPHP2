<?php

namespace Magrathea2\Admin\Features\AppConfig;

use Exception;
use Magrathea2\Admin\AdminManager;
use Magrathea2\MagratheaModelControl;
use Magrathea2\DB\Query;
use Magrathea2\MagratheaCache;

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
	 * hides system config
	 * @return 	array		array of AdminConfig
	 */
	public function GetOnlyApp() {
		$query = Query::Select()
			->Obj(new AppConfig())
			->Where(['is_system' => 0]);
		return self::Run($query);
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
	 * saves a system key (for Magrathea's internal use)
	 * @param 	string 				$key
	 * @param 	string|any 		$value
	 */
	public function SaveSystem(string $key, $value, $overwrite=true): AppConfig {
		return $this->Save($key, $value, $overwrite, true);
	}

	/**
	 * sets the value for a key
	 * @param 	string 				$key
	 * @param 	string|any 		$value
	 * @param 	boolean 			$overwrite 		should overwrite value
	 * @param		boolean				$system				is this a system var?
	 */
	public function Save(string $key, $value, bool $overwrite=true, bool $system=false): AppConfig {
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
			if($system) {
				$config->is_system = 1;
			}
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
	public function GetValueByKey(string $key, ?string $default=null): string|null {
		$c = $this->GetByKey($key);
		if(!$c) return $default;
		return $c->GetValue();
	}

	/**
	 * Exports data
	 */
	public function ExportData(bool $hideSystem = false): string {
		$export = "";
		if($hideSystem) {
			$data = $this->GetOnlyApp();
		} else {
			$data = $this->GetAll();
		}

		foreach($data as $c) {
			if($c->key == "admin_install_date") continue;
			$system = $c->is_system ? "[s]" : "[n]";
			$export .= '=='.$system.$c->key.'==|>>'.$c->GetValue().'>>;;\n';
		}
		AdminManager::Instance()->Log("Exporting Config Data", "config", $export);
		return $export;
	}

	/**
	 * parses line
	 * @param string 	$line		line
	 * @return array 	[key, system, value]
	 */
	public function ParseLine(string $line): array {
		$config = explode("==|>>", $line);
		$key_regex = '/==\[(\w+)\](\w+)/';
		preg_match($key_regex, $config[0], $matches);
		if(empty($matches)) {
			$system = false;
			$key = preg_replace('/^={2}/', '', $config[0]);
		} else {
			$system = $matches[1] == 's';
			$key = $matches[2];
		}
		$value = str_replace(">>;;", "", $config[1]);
		return [
			"key" => $key,
			"system" => $system,
			"value" => $value,
		];
	}

	/**
	 * Imports data
	 */
	public function ImportData(string $dataStr, bool $echoProgress = false): bool {
		$data = explode(">>;;\n", $dataStr);
		foreach($data as $config) {
			if(empty($config)) continue;
			$row = $this->ParseLine($config);
			if($echoProgress) echo "updating: <b>".$row["key"]."</b> = ".$row["value"]."<br/>";
			$this->Save($row["key"], $row["value"], true, $row["system"]);
		}
		AdminManager::Instance()->Log("Importing Config Data", "config", $dataStr);
		return true;
	}

}
