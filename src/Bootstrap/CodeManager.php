<?php

namespace Magrathea2\Bootstrap;
use Magrathea2\MagratheaPHP;
use Magrathea2\ConfigFile;
use Exception;

use function Magrathea2\p_r;

#######################################################################################
####
####    MAGRATHEA PHP2
####    v. 2.0
####    Magrathea by Paulo Henrique Martins
####    Platypus technology
####    Bootstrap Start created: 2022-12 by Paulo Martins
####
#######################################################################################

/**
 * Class for handling and code generations
 */
class CodeManager extends \Magrathea2\Singleton { 

	private $appPath;
	private $configPath;
	private $objectsConfFile = "magrathea_objects.conf";

	/**
	 * Loads the class and returns the class itself
	 * @return CodeManager		itself
	 */
	public function Load() {
		$this->appPath = MagratheaPHP::Instance()->magRoot;
		$this->configPath = MagratheaPHP::Instance()->getConfigRoot();
		return $this;
	}

	/**
	 * Return the path for the magrathea_objects.conf file
	 * @return string 	string for the path or null if the file does not exist
	 */
	public function getMagratheaObjectsFile() {
		$confFilePath = $this->configPath."/".$this->objectsConfFile;
		$confFile = realpath($confFilePath);
		return $confFile;
	}

	/**
	 * Return the path for the magrathea_objects.conf file
	 * @return array 	array of objects config
	 */
	public function getMagratheaObjectsData() {
		$mconfig = new ConfigFile();
		$mconfig->SetPath($this->configPath);
		$mconfig->SetFile($this->objectsConfFile);
		$config = $mconfig->GetConfig();
		return $config;
	}

	/** 
	 * Return array of fields for a database from an object of magrathea_objects.conf
	 * @param			array			magrathea_objects.conf element
	 * @return 		array			array with [field_name] => type
	 */
	public function getFieldsFromObject($arrObject) {
		$fields = [];
		foreach($arrObject as $name => $type){
			if( substr($name, -5) == "_type" ){
				$field_name = substr($name, 0, -5);
				$fields[$field_name] = $type;
			}
		}
		return $fields;
	}

	/** 
	 * Return array of fields for a database from an object of magrathea_objects.conf
	 * @param			array			magrathea_objects.conf element
	 * @return 		string		mySQL table query
	 */
	public function generateQueryForObject($arrObject) {
		$columnsArr = $this->getFieldsFromObject($arrObject);
		$table = $arrObject["table_name"];
		$pk = $arrObject["db_pk"];
		$columns = "";
		foreach($columnsArr as $name => $type) {
			switch($type) {
				case "string":
					$t = "VARCHAR (255)";
					break;
				default:
					$t = strtoupper($type);
			}
			$columns .= "\t${name}\t${t}";
			if ($name == $pk) {
				$columns .= " NOT NULL AUTO_INCREMENT";
			}
			$columns .= ",\n";
		}
		$query = "CREATE TABLE IF NOT EXISTS ${table} \n(\n${columns} \t PRIMARY KEY (${pk})\n);";
		return $query;
	}

	/** 
	 * check if the folder exists and is writable
	 * @param 		string		folder path
	 * @param 		string		folder name (for error messages)
	 * @return 		boolean		true if it's all good
	 */
	private function canUseFolder($folder, $name="directory") {
		if(!$folder) {
			throw new Exception($name." does not exists [".$folder."]");
		}
		if(!is_writable($folder)) {
			throw new Exception($name." is not writable [".$folder."]");
		}
		return true;
	}

	/**
	 * Writes a file with some content (code, please)
	 * Deletes the file before writing, so beware!
	 * @param		string 		file that will be written
	 * @param		string		content to be written
	 * @return 	boolean		success?
	 */
	function writeFile($file, $content){
		if(file_exists($file)){
			unlink($file);
		}
		if (!$handle = @fopen($file, 'w')) { 
			return false; 
		} 
		if (!fwrite($handle, $content)) { 
			return false; 
		} 
		fclose($handle); 
		return true; 
	}

	
	private function getRelations(){
		$relations = null;
		try	{
			$mconfig = $this->getMagratheaObjectsData();
			@$relations = $mconfig["relations"];
		} catch (Exception $ex){
			$error_msg = "Error: ".$ex->getMessage();
		}
		return $relations;
	}
	
	/**
	 * returns a relation from relation array
	 * this is a function from Magrathea 1.0, I didn't care to explore it deeply
	 * @param 		array			array Relation, maybe?
	 * @param 		integer		index for something
	 * @return 		array			something
	 */
	private function extractRelFromRelArray($rel_arr, $index){
		$relation = array();
		$relation["rel_name"] = $rel_arr["rel_name"][$index];
		$relation["rel_obj_base"] = $rel_arr["rel_obj_base"][$index];
		$relation["rel_type"] = $rel_arr["rel_type"][$index];
		$relation["rel_type_text"] = $rel_arr["rel_type_text"][$index];
		$relation["rel_object"] = $rel_arr["rel_object"][$index];
		$relation["rel_field"] = $rel_arr["rel_field"][$index];
		$relation["rel_property"] = $rel_arr["rel_property"][$index];
		$relation["rel_method"] = $rel_arr["rel_method"][$index];
		$relation["rel_lazyload"] = @$rel_arr["rel_lazyload"][$index];
		$relation["rel_autoload"] = @$rel_arr["rel_autoload"][$index];
		return $relation;
	}

	/**
	 * Returns the relation for some object
	 * @param			array			magrathea_objects.conf element
	 * @return		array			relations array
	 */
	private function getRelationsByObject($obj){
		$rels = $this->getRelations();
		$relations = array();
		if( !$rels || count($rels) == 0 ) return $relations;
		$index = 0;
		foreach( $rels["rel_obj_base"] as $objbase ){
			if($objbase == $obj){
				array_push($relations, $this->extractRelFromRelArray($rels, $index));
			}
			$index++;
		}
		return $relations;
	}

	/**
	 * Generate codes for a given object
	 * @param			string		object name
	 * @param			array			magrathea_objects.conf element
	 * @return 		boolean		success or not to generate object
	 */
	public function generateCode($object, $data, $debug=false) {
		$success = $this->writeBaseCode($object, $data, $debug);
		if(!$success) {
			if($debug) {
				echo "\tERROR: could not generate base code\n";
			}
			return $success;
		}
		$success = $this->writeModelsCode($object, $data, $debug);
		return $success;
	}

	/**
	 * Write base code for a given object
	 * @param			string		object name
	 * @param			array			magrathea_objects.conf element
	 * @return 		boolean		success or not to generate object
	 */
	public function writeBaseCode($object, $data, $debug=false) {
		$modelsDir = $this->appPath."/Models";
		$baseDir = $modelsDir."/Base";
		try {
			$this->canUseFolder($baseDir, "Base dir");
		} catch(Exception $ex) {
			throw $ex;
		}

		$code = $this->generateBaseCode($object, $data);
		$baseFile = $baseDir."/".$object."Base.php";
		if ($debug) {
			echo "\twriting ".$object." base file at [".$baseFile."]\n"; 
		}
		return $this->writeFile($baseFile, $code);
	}

	/**
	 * Generate only base code for a given object
	 * @param			string		object name
	 * @param			array			magrathea_objects.conf element
	 */
	public function generateBaseCode($object, $data) {
		$code = "";

		$obj_fields = array();
		foreach($data as $key => $item){
			if( substr($key, -6) == "_alias" ){
				$field_name = substr($key, 0, -6);
				if( $field_name == "created_at" || $field_name == "updated_at" ) continue;
				array_push($obj_fields, $field_name);
			}
		}

		$relations = array();
		$relations = $this->getRelationsByObject($data);
		$relations_properties = "";
		$relations_functions = "";
		$relations_autoload = array();
		$autoload_objs = array();
		foreach($relations as $rel){
			$relations_properties .= "\t\t\$this->relations[\"properties\"][\"".$rel["rel_property"]."\"] = null;\n";
			$relations_properties .= "\t\t\$this->relations[\"methods\"][\"".$rel["rel_property"]."\"] = \"".$rel["rel_method"]."\";\n";
			$relations_properties .= "\t\t\$this->relations[\"lazyload\"][\"".$rel["rel_property"]."\"] = \"".($rel["rel_lazyload"] == 1 ? "true" : "false")."\";\n";
			
			$relations_functions .= "\tpublic function ".$rel["rel_method"]."(){\n";
			$relations_functions .= "\t\tif(\$this->relations[\"properties\"][\"".$rel["rel_property"]."\"] != null) return \$this->relations[\"properties\"][\"".$rel["rel_property"]."\"];\n";
			if( $rel["rel_type"] == "belongs_to" ) {
				$relations_functions .= "\t\t\$this->relations[\"properties\"][\"".$rel["rel_property"]."\"] = new ".$rel["rel_object"]."(\$this->".$rel["rel_field"].");\n";
			} else if ( $rel["rel_type"] == "has_many" ) {
				$relations_functions .= "\t\t\$pk = \$this->dbPk;\n";
				$relations_functions .= "\t\t\$this->relations[\"properties\"][\"".$rel["rel_property"]."\"] = ".$rel["rel_object"]."ControlBase::GetWhere(array(\"".$rel["rel_field"]."\" => \$this->\$pk));\n";
			}
			$relations_functions .= "\t\treturn \$this->relations[\"properties\"][\"".$rel["rel_property"]."\"];\n";
			$relations_functions .= "\t}\n";

			if( $rel["rel_type"] == "belongs_to" ) {
				$obj_var = "\$".strtolower($rel["rel_property"]);
				$relations_functions .= "\tpublic function Set".$rel["rel_property"]."(".$obj_var."){\n";
				$relations_functions .= "\t\t\$this->relations[\"properties\"][\"".$rel["rel_property"]."\"] = ".$obj_var.";\n";
				$relations_functions .= "\t\t\$this->".$rel["rel_field"]." = ".$obj_var."->GetID();\n";
				$relations_functions .= "\t\treturn \$this;\n";
				$relations_functions .= "\t}\n";
			}

			if($rel["rel_autoload"] == 1){
				array_push($relations_autoload, "\"".$rel["rel_property"]."\" => \"".$rel["rel_field"]."\"");
				array_push($autoload_objs, $rel["rel_property"]);
			}

		} // close foreach relations
	
		$code = "<?php\n\n";
		$code .= "## FILE GENERATED BY MAGRATHEA.\n## SHOULD NOT BE CHANGED MANUALLY\n\n";

		$code .= "class ".$object."Base extends MagratheaModel implements iMagratheaModel {\n\n";
		
		$code .= "\tpublic \$".implode(", $", $obj_fields).";\n";
		$code .= "\tpublic \$created_at, \$updated_at;\n";
		$code .= "\tprotected \$autoload = ".(count($relations_autoload) == 0 ? "null" : "array(".implode(", ", $relations_autoload).")").";\n";
		if(count($autoload_objs) > 0) {
			$code .= "\tpublic \$".implode(", $", $autoload_objs).";\n";
		}
		$code .= "\n";

		$code .= "\tpublic function __construct( ".( ($data["db_pk"]) ? " \$".$data["db_pk"]."=0 " : "\$id=0" )." ){ \n";
		$code .= "\t\t\$this->MagratheaStart();\n";
		if($data["db_pk"]){
			$code .= "\t\tif( !empty(\$".$data["db_pk"].") ){\n";
			$code .= "\t\t\t\$pk = \$this->dbPk;\n";
			$code .= "\t\t\t\$this->\$pk = \$".$data["db_pk"].";\n";
			$code .= "\t\t\t\$this->GetById(\$".$data["db_pk"].");\n";
			$code .= "\t\t}\n";
		}
		$code .= "\t}\n";
		$code .= "\tpublic function MagratheaStart(){\n";
		$code .= "\t\t\$this->dbTable = \"".$data["table_name"]."\";\n";
		$code .= "\t\t\$this->dbPk = \"".$data["db_pk"]."\";\n";
		foreach($obj_fields as $f){
			$code .= "\t\t\$this->dbValues[\"".$f."\"] = \"".$data[$f."_type"]."\";\n";
			if( !empty($data[$f."_alias"]) )
				$code .= "\t\t\$this->dbAlias[\"".$data[$f."_alias"]."\"] = \"".$f."\";\n";
		}

		$code .= "\n".$relations_properties;
		$code .= "\t\t\$this->dbValues[\"created_at\"] =  \"datetime\";\n";
		$code .= "\t\t\$this->dbValues[\"updated_at\"] =  \"datetime\";\n";			
		$code .= "\n";
		$code .= "\t}\n\n";

		$code .= "\t// >>> relations:\n".$relations_functions."\n";

		$code .= "}\n\n";
		
		$code .= "class ".$object."ControlBase extends MagratheaModelControl {\n";
			$code .= "\tprotected static \$modelName = \"".$object."\";\n";
			$code .= "\tprotected static \$dbTable = \"".$data["table_name"]."\";\n";
		$code .= "}\n";		

		$code .= "?>";
		return $code;
	}

	/**
	 * Write model code for a given object
	 * @param			string		object name
	 * @param			array			magrathea_objects.conf element
	 * @return 		boolean		success or not to generate object
	 */
	public function writeModelsCode($object, $data, $debug=false) {
		$modelsDir = $this->appPath."/Models";
		try {
			$this->canUseFolder($modelsDir, "Models dir");
		} catch(Exception $ex) {
			throw $ex;
		}

		$baseFile = $modelsDir."/".$object.".php";
		if(file_exists($baseFile)){
			echo "\tclass for ".$object." already exists at [".$baseFile."]\n";
			return false;
		}

		$code = $this->generateModelsCode($object, $data);
		if ($debug) {
			echo "\twriting ".$object." model file at [".$baseFile."]\n"; 
		}
		return $this->writeFile($baseFile, $code);
	}

	/**
	 * Generate only base code for a given object
	 * @param			string		object name
	 * @param			array			magrathea_objects.conf element
	 */
	public function generateModelsCode($object, $data) {
		$code = "<?php\n\n";
		$code .= "include(__DIR__.\"/Base/".$object."Base.php\");\n\n";

		$code .= "class ".$object." extends ".$object."Base {\n";
		$code .= "\t// your code goes here!\n";
		$code .= "}\n\n";
		
		$code .= "class ".$object."Control extends ".$object."ControlBase {\n";
		$code .= "\t// and here!\n";
		$code .= "}\n\n";
		
		$code .= "?>";
		return $code;
	}



}
