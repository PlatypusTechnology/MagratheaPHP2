<?php

namespace Magrathea2\Admin;

use Exception;
use Magrathea2\Config;
use Magrathea2\Exceptions\MagratheaConfigException;
use Magrathea2\Singleton;
use Magrathea2\DB\Database;
use Magrathea2\MagratheaPHP;

use function Magrathea2\now;

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

	private $backupPath;

	public function Initialize() {
		$this->LoadBackupFolder();
	}

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

	/**
	 * Loads the backup folder
	 */
	public function LoadBackupFolder() {
		try {
			$backupPath = Config::Instance()->Get("backups_path");
		} catch(MagratheaConfigException $ex) {
			$magRoot = MagratheaPHP::Instance()->magRoot;
			if(!$magRoot) throw new MagratheaConfigException("backups_path is invalid and mag root is null");
			$backupPath = $magRoot."/backups";
		} catch(\Exception $ex) {
			throw $ex;
		}
		$this->backupPath = $backupPath;
		if(!$this->backupPath) {
			$this->backupPath = MagratheaPHP::Instance()->magRoot."/backups";
		}
		return $this;
	}


	/**
	 * Gets the backup path
	 * @return string		path
	 */
	public function GetBackupFolder(): string {
		return $this->backupPath;
	}

	/**
	 * Gets the default file name ("backup_[date].sql")
	 */
	public function GetDefaultFileName(): string {
		return "backup_".date("Ymd").".sql";
	}

	/**
	 * Normalize file's name (for ending with .sql)
	 * @param string $filename		filename
	 * @return string							filename fixed
	 */
	private function NormalizeFilename($filename): string {
		if (!str_ends_with($filename, '.sql')) {
			$filename .= '.sql';
		}
		return $filename;
	}

	/**
	 * Gets mysqldump command
	 * @param string 	$fileName
	 * @return string	command
	 */
	public function GetCommand($fileName): string {
		$config = Config::Instance();
		$fileName = $this->NormalizeFilename($fileName);
		return "mysqldump --opt --user=".$config->Get("db_user")." --password=".$config->Get("db_pass")." --host=".$config->Get("db_host")." ".$config->Get("db_name")." > ".$this->GetBackupFolder()."/".$fileName;
	}

	/**
	 * Backup database
	 * @param string $fileName
	 * 
	 */
	public function DoBackup($fileName) {
		$command = $this->GetCommand($fileName);
		$code = null;
		$rs = system($command, $code);
		return [ "rs" => $rs, "code" => $code ];
	}


}

?>