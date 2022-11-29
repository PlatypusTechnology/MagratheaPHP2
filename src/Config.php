<?php

namespace Magrathea2;

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
* 
*/
class MagratheaConfig {
	private $path = false;
	private $config_file_name = "configs/magrathea.conf";
	private $configs = null;
	private $environment = null;
	protected static $inst = null;

	/**
	* This is a singleton!
	* Instance loader
	* @return 	MagratheaConfig 	Instance of the object
	*/
	public static function Instance(){
		if(!isset(self::$inst)){
			self::$inst = new MagratheaConfig();
		}
		return self::$inst;
	}

	/**
	* Function for openning the file specified in `$this->config_file_name`
	* actually, it works checking if file exists and throwing an error if don't.
	* @throws Magrathea Exception for file not found
	*/
	private function loadFile(){
		if(!file_exists($this->path."/".$this->config_file_name)){
			if(!$this->path) {
				echo "WARNING: \$site_path is empty!... ";
			}
			die ("MagratheaConfig: Config file could not be found - ".$this->path."/".$this->config_file_name);
		}
	}

	/**
	* set path for config file
	* @param string $p Path to the file
	* @return itself
	*/
	public function setPath($p){
		$this->path = rtrim($p, '/').'/';
		return $this;
	}

	/**
	* This function will change the default environment to anything that the users wants to.
	* @param string Environment name
	* @return itself
	*/
	public function ChangeEnvironment($e) {
		loadMagratheaEnv($e);
		return $this;
	}

	/**
	* This function will set the environment for future operations
	* @param string Environment name
	* @return itself
	*/
	public function SetDefaultEnvironment($e){
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
	* This function will return the magrathea path defined in config.php
	* @return 	string 		Magrathea Path
	*/
	public function GetMagratheaPath() {
		global $magrathea_path;
		return $magrathea_path;
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
			$this->loadFile();
			$this->configs = @parse_ini_file($this->path."/".$this->config_file_name, true, INI_SCANNER_TYPED);
			if( !$this->configs ){
				throw new MagratheaConfigException("There was an error trying to load the config file. [".$this->path."/".$this->config_file_name."]<br/>");
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
	public function Get($config_name){
		return $this->GetConfigFromDefault($config_name);
	}
	/**
	 * Alias for GetConfigFromDefault
   * @param 	string 	$config_name Item to be returned from the `magrathea.conf`. 
 	 * @return 	string
	 */
	public function GetFromDefault($config_name){
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
			$this->loadFile();
			$this->configs = @parse_ini_file($this->path."/".$this->config_file_name, true);
			if( !$this->configs ){
				throw new MagratheaException("There was an error trying to load the config file.<br/>");
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
		$this->loadFile();
		$configSection = @parse_ini_file($this->path."/".$this->config_file_name, true);
		if( !$configSection ){
			throw new MagratheaException("There was an error trying to load the config file.<br/>");
		}
		if(empty($configSection[$section_name])) {
			throw new \Exception("Conig [".$section_name."] not available in magrathea.conf", 1);
		}
		return $configSection[$section_name];
	}
}


/**
* Magrathea Config loads and saves information in config files.
* 
*/
class MagratheaConfigFile { 
	private $path = __DIR__;
	private $config_file_name = "configs/magrathea_plus.conf";
	private $configs = null;

	public function __construct(){
		$this->configs = null;
	}

	/**
	*	Set the path to the confi file
	*	@param 	string 	$p 		Path to be set
	*	@return 	itself
	*/
	public function setPath($p){
		$this->path = rtrim($p, '/').'/';
		return $this;
	}
	/**
	*	Sets the config information
	*	@param 	array 	$c 		Config to be set
	*	@return 	itself
	*/
	public function setConfig($c){
		$this->configs = $c;
		return $this;
	}
	/**
	*	Sets the name of the config file
	*	@param 	string 	$filePath 	Name of the config file
	*	@return 	itself
	*/
	public function setFile($filePath){
		$this->config_file_name = $filePath;
		return $this;
	}
	/**
	*	Loads the configuration file
	*/
	private function loadFile(){
		if(!file_exists($this->path."/".$this->config_file_name)){
			throw new MagratheaException("Config file could not be found - ".$this->path."/".$this->config_file_name);
		}
	}
	/**
	*	Creates the configuration file if it doesn't exists. And saves it.
	*	@return 	boolean	 	True if the file exists; Return of `Save()` function if it doesn't
	*/
	public function createFileIfNotExists(){
		if(!file_exists($this->path."/".$this->config_file_name)){
			return $this->Save();
		} else return true;
	}
	/**
	*	Gets configuration
	*	@param 	string 	$config_name 	Configuration to be got. If empty, returns all the configuration into the file
	*									If an acceptable config name, returns its value
	*	@return 	string/int/array	If `$config_name` is empty, returns all the configuration. Otherwise, 
	*   @todo 	exception 704 on key does not exists
	*/
	public function getConfig($config_name=""){
		if( is_null($this->configs) ){
			$this->loadFile();
			$this->configs = @parse_ini_file($this->path."/".$this->config_file_name, true);
		}
		if( empty($config_name) ){
			return $this->configs;
		} else {
			$c_split = explode("/", $config_name);
			return ( count($c_split) == 1 ? $this->configs[$config_name] : $this->configs[$c_split[0]][$c_split[1]] );
		}
	}
	/**
	*	Gets a full config section
	*
	*	@param 	string 	$section_name 	Name of the section to be shown
	*
	*	@return 	array	 	All the values of the given section
	*   @todo 	exception 704 on key does not exists
	*/
	public function getConfigSection($section_name){
		$this->loadFile();
		$configSection = @parse_ini_file($this->path.$this->config_file_name, true);
		if( empty($configSection ) ) return null;
		if( !$configSection ){
			throw new MagratheaException("There was an error trying to load the config file.<br/>");
		}
		return $configSection[$section_name];
	}

	/**
	*	Sets the correct format for the value
	*	@param 		any 		$value to be saved
	*	@return 	string 		formatted value to be saved on config file
	*/
	private function SaveValueOnConfig($value) {
		if (in_array($value, array("true", "1", "yes"), true)) return "true";
		if (in_array($value, array("false", "0", "no"), true)) return "false";
		return "\"".$value."\"";
	}

	/**
	*	Saves the config file
	*	@param 		boolean 	$save_sections 		A flag indicating if the sections should be saved also.
	*												Default: `true`
	*	@return 	boolean	 	True if the saved succesfully. False if got any error in the process
	*/
	public function Save($save_sections=true) { 
		$content = "// generated by Magrathea at ".@date("Y-m-d h:i:s")."\n";
		$data = $this->configs;
		if( $data == null ) $data = array();
		if ($save_sections) { 
			foreach ($data as $key=>$elem) { 
				$content .= "\n[".$key."]\n"; 
				if(!is_array($elem)){
					throw new MagratheaConfigException("Hey, you! If you are gonna save a config file with sections, all the configs must be inside one section...", 1);
					
				}
				foreach ($elem as $key2=>$elem2) { 
					if(is_array($elem2)) { 
						for($i=0;$i<count($elem2);$i++) { 
							$content .= "\t".$key2."[] = ".$this->SaveValueOnConfig($elem2[$i])."\n";
						} 
					} else if($elem2=="") $content .= "\t".$key2." = \n";
					else $content .= "\t".$key2." = ".$this->SaveValueOnConfig($elem2)."\n";
				}
			}
		} else {
			foreach ($data as $key=>$elem) { 
				if(is_array($elem)) { 
					for($i=0;$i<count($elem);$i++) { 
						$content .= $key."[] = ".$this->SaveValueOnConfig($elem[$i])."\n";
					}
				} else if($elem=="") $content .= $key." = \n";
				else $content .= $key." = ".$this->SaveValueOnConfig($elem)."\n";
			} 
		} 
		if(!is_writable($this->path)){
			throw new MagratheaConfigException("Permission denied on path: ".$this->path);
			return false; 
		}
		$file = $this->path."/".$this->config_file_name;
		if(file_exists($file)){
			@unlink($file);
		}
		if (!$handle = fopen($file, 'w')) { 
			throw new MagratheaConfigException("Oh noes! Could not open File: ".$file);
			return false; 
		} 
		if (!fwrite($handle, $content)) { 
			throw new MagratheaConfigException("Oh noes! Could not save File: ".$file);
			return false; 
		} 
		fclose($handle); 
		return true; 
	}	
}
?>