<?php

namespace Magrathea2;

use Magrathea2\Admin\Features\AppConfig\AppConfig;
use Magrathea2\Admin\Features\AppConfig\AppConfigControl;

#######################################################################################
####
####    MAGRATHEA PHP2
####		Class for controlling the App configuration (the one that are stored in a database)
####    v. 1.0
####    Magrathea by Paulo Henrique Martins
####    Platypus technology
####    Magrathea2 created: 2023-11 by Paulo Martins
####
#######################################################################################

/**
* This class will provide you the quickest access possible to the magrathea.conf config file.
*/
class ConfigApp extends Singleton {

	private ?AppConfigControl $control = null;

	private function GetControl(): AppConfigControl {
		if($this->control == null) $this->control = new AppConfigControl();
		return $this->control;
	}

	/**
	 * saves a config
	 * @param string 	$key				config key
	 * @param string 	$value			config value
	 * @param bool 		$overwrite	should overwrite existing values?
	 * @return AppConfig			saved config
	 */
	public function Save($key, $value, $overwrite=true): AppConfig {
		return $this->GetControl()->Save($key, $value, $overwrite);
	}

	/**
	 * Gets the value by key
	 * @param string 			$key			config key
	 * @param string|null $default	default (optional), if config does not exists
	 * @return string			key value
	 */
	public function Get(string $key, $default=null): ?string {
		$value = $this->GetControl()->GetValueByKey($key);
		if($value == null) return $default;
		return $value;
	}

	/**
	 * Gets a boolean by key
	 * @param string 		$key			config key
	 * @param bool 			$default	default (optional), if config does not exists
	 * @return string		key value
	 */
	public function GetBool(string $key, bool $default=false): bool {
		$val = $this->Get($key, $default);
		$boolval = filter_var($val, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
		if($boolval === null) return false;
		return $boolval;
	}

	/**
	 * Gets an integer by key
	 * @param string 		$key			config key
	 * @param int 			$default	default (optional), if config does not exists
	 * @return string		key value
	 */
	public function GetInt(string $key, int $default=0): int {
		$val = $this->Get($key, $default);
		return intval($val);
	}

	/**
	 * Gets an integer by key
	 * @param string 		$key			config key
	 * @param float 		$default	default (optional), if config does not exists
	 * @return string		key value
	 */
	public function GetFloat(string $key, float $default=0): float {
		$val = $this->Get($key, $default);
		return floatval($val);
	}

}