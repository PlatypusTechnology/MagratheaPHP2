<?php
namespace Magrathea2;

use Magrathea2\DB\Database;

#######################################################################################
####
####    MAGRATHEA PHP2
####    v. 2.0
####    Magrathea by Paulo Henrique Martins
####    Platypus technology
####	Magrathea1 created: 2012-12 by Paulo Martins
####    Magrathea2 created: 2022-11 by Paulo Martins
####
#######################################################################################

/**
 * Date of now - mySQL format
 * @return 	string 		Y-m-d H:i:s format of date
 */
function now(): string {
	return date("Y-m-d H:i:s");
}

//.$trace[0]["file"].":".$trace[0]["line"]."\n"
/**
 * Prints easily and beautifully
 * @param 	object|array|string|int		 		$debugme	Object to be printed
 * @param  	boolean  											$beautyme	How beautifull do you want your object printed?
 */
function p_r($debugme){
	print_r($debugme);
}

/**
 * Gets an object and returns if it is a Magrathea Model
 */
function isMagratheaModel($object): bool {
	return is_a($object, "\Magrathea2\MagratheaModel");
}



/**
 * Loads database configuration for the selected environment.
 * 	If no environment is sent, it will use the information from the default environment
 * @param 	string 		$env	Environment to load
 * @return 	MagratheaDatabase Instance
 */
function loadMagratheaEnv($env = null): Database|bool{
	global $magdb;
	if( empty($env) ){
		try {
			$env = Config::Instance()->GetEnvironment();
		} catch(\Exception $ex) { return false; }
		if(empty($env)) return false;
	} else {
		Config::Instance()->SetEnvironment($env);
	}
	try {
		$configSection = Config::Instance()->GetConfigSection($env);
		date_default_timezone_set( Config::Instance()->GetConfig("general/time_zone") );

		$magdb = Database::Instance();
		$conn = $magdb->SetConnection($configSection["db_host"], $configSection["db_name"], $configSection["db_user"], $configSection["db_pass"]);
	} catch(\Exception $ex) {
		throw $ex;
	}
	return $conn;
}

/**
 * dumps vars
 * @param 	object 		$debugme 	Object to be printed
 * @return  string  	nicely printed var
 */	
/*
 function dump($debugme): string {
	ob_start();
	var_dump($debugme);
	return ob_get_clean();
}
*/

/**
 * gets an array and prints a select
 * @param   array 		array to be printed
 * @param 	string 		type to be selected
 * @return 	boolean		is field selected?
 */
function magrathea_printFields($fields_arr, $selected = null) : bool {
	$options = "";
	$selected = false;
	foreach($fields_arr as $field){
		if( $field == $selected ){
			$selected = true;
			$options .= "<option value='".$field."' selected>".$field."</option>";
		} else {
			$options .= "<option value='".$field."'>".$field."</option>";
		}
	}
	echo $options;
	return $selected;
}

/**
 * Array of types available at Magrathea
 * @return  	array
 */
function magrathea_getTypesArr() : array{
	$types = array("int", "boolean", "string", "text", "float", "datetime");
	return $types;
}

/**
 * Function that will be executed after script is complete!
 * in Magrathea, will print debug, if available...
 * 	@todo  print debug in a beautifull way in the end of the page...
 */
register_shutdown_function(function(){
	if(Debugger::Instance()->GetType() == Debugger::DEBUG){
		Debugger::Instance()->Show();
	}
});

?>