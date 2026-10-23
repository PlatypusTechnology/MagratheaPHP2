<?php

namespace Magrathea2\Admin;

use Exception;
use Magrathea2\Config;
use Magrathea2\Exceptions\MagratheaConfigException;
use Magrathea2\Exceptions\MagratheaException;
use Magrathea2\Singleton;
use Magrathea2\DB\Database;
use Magrathea2\MagratheaHelper;
use Magrathea2\MagratheaPHP;

use function Magrathea2\now;
use function Magrathea2\p_r;

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
			if(!$magRoot) throw new MagratheaConfigException("backups_path is invalid and magrathea root is null");
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
	 * Get Table Columns for object
	 * @param string 	$table		table name
	 * @return array	return with ["name", "type", "null", "default" "pk"]
	 */
	public function GetTableColumns($table): array {
		$rs = [];
		$magdb = Database::Instance();
		$query = "SHOW columns FROM `".str_replace("`", "``", $table)."`";
		$columns = $magdb->QueryAll($query);
		foreach($columns as $c) {
			$field = [
				"name" => $c["field"],
				"type" => $c["type"],
				"default" => $c["default"],
				"nullable" => ($c["null"] != "NO"),
				"pk" => ($c["key"] == "PRI")
			];
			array_push($rs, $field);
		}
		return $rs;
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
	 * Validates a backup file name: `.sql` is appended when it's missing, then it must be a plain name
	 * (`^[A-Za-z0-9][A-Za-z0-9._-]*\.sql$`, no `/`, `\` or `..`), so it can't leave the backups folder.
	 * @param 	mixed 	$name		file name
	 * @return 	string					file name, ending with `.sql`
	 * @throws 	MagratheaException	invalid file name
	 */
	public function ValidateBackupFileName($name): string {
		if(!is_string($name)) throw new MagratheaException("invalid file name");
		if(!str_ends_with($name, ".sql")) $name .= ".sql";
		if(!preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*\.sql$/', $name) || str_contains($name, "..")) {
			throw new MagratheaException("invalid file name");
		}
		return $name;
	}

	/**
	 * Backup database: runs `mysqldump` into `<backups folder>/<file name>`, without a shell.
	 * The credentials go in a temporary `[client]` defaults file (0600, deleted afterwards), never on the command line.
	 * @param 	mixed 	$fileName		file name (see `ValidateBackupFileName()`)
	 * @return 	array		["file" => file name, "size" => bytes]
	 * @throws 	MagratheaException	invalid name, existing file, no `proc_open()`, or mysqldump failed
	 */
	public function DoBackup($fileName): array {
		$fileName = $this->ValidateBackupFileName($fileName);
		$destination = MagratheaHelper::EnsureTrailingSlash($this->GetBackupFolder()).$fileName;
		if(file_exists($destination)) throw new MagratheaException("file already exists");
		if(!function_exists("proc_open")) {
			throw new MagratheaException("proc_open() is not available (disable_functions): can't run mysqldump");
		}
		$config = Config::Instance();
		$defaultsFile = tempnam(sys_get_temp_dir(), "magrathea-dump-");
		if($defaultsFile === false) throw new MagratheaException("could not create the mysqldump credentials file");
		try {
			chmod($defaultsFile, 0600);
			file_put_contents($defaultsFile, $this->GetDefaultsFileContent([
				"user" => $config->Get("db_user"),
				"password" => $config->Get("db_pass"),
				"host" => $config->Get("db_host"),
				"port" => $config->Get("db_port"),
			]));
			// --defaults-extra-file must be the first option
			$rs = $this->RunProcess([
				"mysqldump",
				"--defaults-extra-file=".$defaultsFile,
				"--opt",
				"--result-file=".$destination,
				(string)$config->Get("db_name"),
			]);
			if($rs["code"] !== 0) {
				if(file_exists($destination)) unlink($destination);
				throw new MagratheaException("mysqldump failed (exit code ".$rs["code"]."): ".trim($rs["stderr"]));
			}
		} finally {
			if(file_exists($defaultsFile)) unlink($defaultsFile);
		}
		clearstatcache(true, $destination);
		return [ "file" => $fileName, "size" => filesize($destination) ];
	}

	/**
	 * Builds a MySQL option file's `[client]` section. Empty values are left out.
	 * Backslashes are doubled and values are quoted with `"`, or with `'` when they contain a `"`.
	 * @param 	array 	$values		[ option => value ]
	 * @return 	string		file content
	 * @throws 	MagratheaException	a value with both quote characters or a line break
	 */
	private function GetDefaultsFileContent(array $values): string {
		$content = "[client]\n";
		foreach($values as $option => $value) {
			if($value === null || $value === "") continue;
			$value = (string)$value;
			if(str_contains($value, "\n") || str_contains($value, "\r")) {
				throw new MagratheaException("the database ".$option." can't contain a line break for mysqldump");
			}
			$quote = str_contains($value, '"') ? "'" : '"';
			if($quote === "'" && str_contains($value, "'")) {
				throw new MagratheaException("the database ".$option." can't contain both quote characters for mysqldump");
			}
			$content .= $option."=".$quote.str_replace("\\", "\\\\", $value).$quote."\n";
		}
		return $content;
	}

	/**
	 * Runs a command without a shell (`proc_open()` with an argument array).
	 * stderr is read before stdout: for commands that write their output to a file (mysqldump's `--result-file`).
	 * @param 	array 	$command		program and arguments
	 * @return 	array		["code" => exit code, "stdout" => string, "stderr" => string]
	 * @throws 	MagratheaException	the process couldn't start
	 */
	protected function RunProcess(array $command): array {
		$pipes = [];
		$process = proc_open($command, [1 => ["pipe", "w"], 2 => ["pipe", "w"]], $pipes);
		if(!is_resource($process)) throw new MagratheaException("could not run ".$command[0]);
		$stderr = stream_get_contents($pipes[2]);
		$stdout = stream_get_contents($pipes[1]);
		fclose($pipes[1]);
		fclose($pipes[2]);
		return [ "code" => proc_close($process), "stdout" => $stdout, "stderr" => $stderr ];
	}

}
