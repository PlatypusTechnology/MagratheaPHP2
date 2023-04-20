<?php

namespace Magrathea2\Admin;

use Exception;
use Magrathea2\Singleton;
use Magrathea2\DB\Database;
use Magrathea2\MagratheaPHP;

#######################################################################################
####
####    MAGRATHEA PHP2
####    v. 2.0
####    Magrathea by Paulo Henrique Martins
####    Platypus technology
####    Admin created: 2022-12 by Paulo Martins
####
#######################################################################################

/**
 * Class for installing Magrathea's Admin
 */
class AdminDatabase extends Singleton {

	/**
	 * Gets tables from Database
	 * @param 	bool 		$allTables 		if true, gets all tables, if false, gets only tables that does not belongs to magrathea db system
	 * @param 	string  $db						db name. If none sent, will use default from config file
	 * @return 	array									array with data from tables
	 */
	public function GetTables($allTables=true, $db=null){
		$magdb = Database::Instance();
		if(!$db) {
			$db = $magdb->getDatabaseName();
		}
		try	{
			if($allTables)
				$sql = "SELECT table_name FROM information_schema.tables WHERE TABLE_SCHEMA = '".$db."' ORDER BY TABLE_NAME";
			else 
				$sql = "SELECT table_name FROM information_schema.tables WHERE TABLE_SCHEMA = '".$db."' AND TABLE_NAME NOT LIKE '_magrathea%' ORDER BY TABLE_NAME";
			$tables = $magdb->queryAll($sql);
			return $tables;
		} catch (Exception $ex){
			$error_msg = "Error: ".$ex->getMessage();
			throw $ex;
		}
	}

	/**
	 * Get sql file location
	 * @return 	string 			String with full path for admin's sql file
	 */
	public function GetSQLFile() {
		return realpath(__DIR__."/database.sql");
	}

	/**
	 * Get sql file content
	 * @return	string			SQL for create admin's database
	 */
	public function GetSQLFileContents() {
		$file = $this->GetSQLFile();
		return file_get_contents($file);
	}


}

?>