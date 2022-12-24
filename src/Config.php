<?php

namespace Magrathea2;
use Magrathea2\Exceptions\MagratheaConfigException;

#######################################################################################
####
####    MAGRATHEA PHP2
####    v. 2.0
####    Magrathea by Paulo Henrique Martins
####    Platypus technology
####    Magrathea2 created: 2022-11 by Paulo Martins
####
#######################################################################################

/**
* This class will provide you the quickest access possible to the magrathea.conf config file.
*/
class Config extends Singleton {
	private $path = "configs";
	private $configFile = "magrathea.conf";
	private $configs = null;
	private $environment = null;

	/**
	* Function for openning the file specified in `$this->config_file_name`
	* actually, it works checking if file exists and throwing an error if don't.
	* @throws MagratheaConfigException for file not found
	* @return Config
	*/
	private function LoadFile(){
		if(!file_exists($this->path."/".$this->configFile)){
			if(!$this->path) {
				throw new MagratheaConfigException("Invalid path", $this->path."/".$this->configFile);
			}
			throw new MagratheaConfigException("File could not be found", $this->path."/".$this->configFile);
		}
		return $this;
	}

	/**
	* set path for config file
	* @param string $p Path to the file
	* @return Config
	*/
	public function SetPath($p){
		$this->path = rtrim($p, '/').'/';
		return $this;
	}

	/**
	* This function will set the environment for future operations
	* @param string Environment name
	* @return Config
	*/
	public function SetEnvironment($e){
		$this->environment = $e;
		return $this;
	}

	/**
	* This function will return the environment being used in the project.
	* The environment is defined in `general/use_environment` property and can be defined in the `magrathea.conf` file. 
	* @return string Environment name
	*/
	public function GetEnvironment(){
		if(!isset($this->environment)){
			$this->environment = $this->GetConfig("general/use_environment");
		}
		return $this->environment;
	}

	/**
	* `$config_name` can be called to get a parameter from inside a section of the config file. To achieve this, you should use a slash (/) to separate the section from the property.
	* If the slash is not used, the function will return the property only if it's on the root.
	* If `$config_name` is a section name, the function will return the full section as an Array.
	* If `$config_name` is empty, the function will return the full config as an Array (**not recommended!**).
	* @param 	string 		$config_name 	Item to be returned from the `magrathea.conf`.
	* @return 	array|string
	* @todo 	exception 704 on key does not exists
	*/
	public function GetConfig($config_name="") : array|string{
		if( $this->configs == null ){
			$this->LoadFile();
			$this->configs = @parse_ini_file($this->path."/".$this->configFile, true, INI_SCANNER_TYPED);
			if( !$this->configs ){
				throw new MagratheaConfigException("There was an error trying to load the config file. [".$this->path."/".$this->configFile."]<br/>");
			}
		}
		if( empty($config_name) ){
			return $this->configs;
		} else {
			$c_split = explode("/", $config_name);
			return ( count($c_split) == 1 ? $this->configs[$config_name] : $this->configs[$c_split[0]][$c_split[1]] );
		}
	}

	/**
	 * Alias for GetConfigFromDefault
   	 * @param 	string 	$config_name Item to be returned from the `magrathea.conf`. 
 	 * @return 	string
	 */
	public function Get($config_name): string{
		return $this->GetConfigFromDefault($config_name);
	}
	/**
	* This function will get the $config_name property from `magrathea.conf`.
	* It will get from the section defined on `general/use_environment`.
	* @param 	string 		$config_name 	Item to be returned from the `magrathea.conf`.
	* @param 	boolean 	$throwable 		should this function throw an exception if array key don't exist?
	* @return 	string
	*/
	public function GetConfigFromDefault($config_name, $throwable=false){
		if( $this->configs == null ){
			$this->LoadFile();
			$this->configs = @parse_ini_file($this->path."/".$this->configFile, true);
			if( !$this->configs ){
				throw new MagratheaConfigException("There was an error trying to load the config file.<br/>");
			}
		}
		if(!$this->environment)
			$this->environment = $this->configs["general"]["use_environment"];
		if(array_key_exists($config_name, $this->configs[$this->environment])){
			return $this->configs[$this->environment][$config_name];
		} else {
			if ($throwable) {
				throw new MagratheaConfigException("Key ".$config_name." does not exist in magratheaconf!", 704);
			} else {
				return null;
			}
		}
	}

	/**
	* `$section_name` is the name of the section that will be returned as an array.
	* @param string $section_name Name of the section to be returned from the `magrathea.conf`.
	* @return array
	* @todo 	exception 704 on key does not exists
	*/
	public function GetConfigSection($section_name){
		$this->LoadFile();
		$configSection = @parse_ini_file($this->path."/".$this->configFile, true);
		if( !$configSection ){
			throw new MagratheaConfigException("There was an error trying to load the config file.<br/>");
		}
		if(empty($configSection[$section_name])) {
			throw new MagratheaConfigException("Conig [".$section_name."] not available in magrathea.conf", 1);
		}
		return $configSection[$section_name];
	}
}



?>