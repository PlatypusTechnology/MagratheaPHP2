<?php

namespace Magrathea2\DB;

use Exception;
use Magrathea2\Debugger;
use Magrathea2\Exceptions\MagratheaDBException;
use Magrathea2\Exceptions\MagratheaDBConnectionLostException;
use Magrathea2\Singleton;

#######################################################################################
####
####    MAGRATHEA PHP2
####    v. 2.0
####    Magrathea by Paulo Henrique Martins
####    Platypus technology
####
####	Database Class
####	Database connection class
####	Magrathea1 created: 2012-12 by Paulo Martins
####    Magrathea2 created: 2022-11 by Paulo Martins
####
#######################################################################################


/**
* This class will provide a layer for connecting with mysql
* Each instance opens one connection on first use and keeps it until the end of the request
* (or until Disconnect() is called).
*/
class Database extends Singleton {

	const FETCH_ASSOC = 1;
	const FETCH_OBJECT = 2;
	const FETCH_NUM = 3;
	const FETCH_ARRAY = 4;

	// 2006 server has gone away, 2013 lost connection during query,
	// 4031 disconnected by the server for inactivity (MySQL 8.0.24+, past wait_timeout)
	const CONNECTION_LOST_ERRORS = [2006, 2013, 4031];

	private $mysqli = null;
	private $connDetails;
	private $fetchmode;

	private $count = 0;
	private $affectedRows = 0;

	/** nesting level of Transaction() calls (0: none open) */
	private $transactionDepth = 0;
	/** an inner Transaction() call threw: the outermost one must roll back */
	private $transactionFailed = false;

	/** set the session's time_zone to PHP's on every new connection (MagratheaPHP::Connect() sets it from `db_sync_timezone`) */
	private $timezoneSync = false;
	/** what the current connection's time_zone was set to: ["zone" => string, "fallback" => bool], or null */
	private $syncedTimezone = null;

	/** instances with an open connection, closed by DisconnectAll() at the end of the request */
	private static array $openConnections = [];
	private static bool $shutdownRegistered = false;

	public function Mock(): void {
		self::MockClass(\Magrathea2\DB\DatabaseSimulate::Instance());
	}
	
	/**
	* Sets the connection array object
	* @param 	array 	$dsn_arr	array with connection data, as the sample:
	*								array(
	*						            'hostspec' => $host,
	*						            'database' => $database,
	*						            'username' => $username,
	*						            'password' => $password,
	*								);
	* @return  	Database
	*/
	public function SetConnectionArray($dsn_arr) : Database{
		$this->connDetails = $dsn_arr;
		return $this;
	}
	/**
	* Setups connection
	* @param 	string 	$host 			host address for connection
	* @param 	string 	$database		database name
	* @param 	string 	$username 	username for connection
	* @param 	string 	$password		password for connection
	* @param 	int|string|null 	$port 	port for connection (empty: mysqli's default)
	* @return  	Database
	*/
	public function SetConnection($host, $database, $username, $password, $port=null): Database{
		$this->connDetails = array(
			'hostspec' => $host,
			'database' => $database,
			'username' => $username,
			'password' => $password,
		);
		if($port){
			$this->connDetails["port"] = $port;
		}
		return $this;
	}

	/**
	 * Gets database name
	 * @return 	string		Database name
	 */
	public function getDatabaseName(): string | null {
		return @$this->connDetails["database"];
	}

	/**
	* Sets fetchmode, according with MDB2 values. Default mode: assoc.
	* @param 	string 	$fetch 			fetchmode for SQL returns
	*									options available:
	*									assoc:
	*										array with keys as the column names
	*									object:
	*										object with columns as properties
	*									if anything different from those values is sent, "assoc" is used
	* @return  	Database
	*/	
	public function SetFetchMode($fetch) : Database {
		switch($fetch){
			case "object":
			$this->fetchmode = self::FETCH_OBJECT;
			break;
			case "assoc":
			default:
			$this->fetchmode = self::FETCH_ASSOC;
			break;
		}
		return $this;
	}

	/**
	* Creates a new connection to the database.
	* Override it to provide a different connection object (the tests use a fake one).
	* @return 	\mysqli		connected mysqli object
	* @throws	MagratheaDbException
	*/
	protected function CreateConnection() {
		if(!empty($this->connDetails["port"]))
			$mysqli = @new \mysqli(
				$this->connDetails["hostspec"],
				$this->connDetails["username"],
				$this->connDetails["password"],
				$this->connDetails["database"],
				(int)$this->connDetails["port"]
			);
		else
			$mysqli = @new \mysqli(
				$this->connDetails["hostspec"],
				$this->connDetails["username"],
				$this->connDetails["password"],
				$this->connDetails["database"]
			);
		if($mysqli->connect_errno){
			throw new MagratheaDBException("Failed to connect to MySQL: (".$mysqli->connect_errno.") ".$mysqli->connect_error);
		}
		return $mysqli;
	}

	/**
	* Open connection, please. Please! 0=)
	* Opens it only once: if this instance already has a connection, it's reused.
	* @return 	boolean		true or false, if connection succedded
	* @throws	MagratheaDbException
	*/
	public function OpenConnectionPlease() : bool {
		if(!$this->connDetails) {
			throw new MagratheaDBException("DB not initialized");
		}
		if($this->mysqli) return true;
		try{
			$connection = $this->CreateConnection();
			$connection->set_charset("utf8mb4");
		} catch (\Exception $ex) {
			Debugger::Instance()->AddError($ex);
			throw new MagratheaDBException($ex->getMessage());
		}
		$this->mysqli = $connection;
		self::$openConnections[spl_object_id($this)] = $this;
		if(!self::$shutdownRegistered) {
			register_shutdown_function(static function() { self::DisconnectAll(); });
			self::$shutdownRegistered = true;
		}
		if($this->timezoneSync) $this->SyncTimezone();
		return true;
	}

	/**
	* Sets whether new connections set MySQL's session time_zone to PHP's timezone.
	* `MagratheaPHP::Connect()` calls it with the config's `db_sync_timezone` (see `TimezoneCheck::SyncState()`).
	* @param 	bool 	$sync 		sync it?
	* @return 	Database
	*/
	public function SetTimezoneSync(bool $sync): Database {
		$this->timezoneSync = $sync;
		return $this;
	}
	/**
	* What the current connection's time_zone was set to
	* @return 	array|null 		["zone" => name or offset, "fallback" => true if the offset was used], null if not synced
	*/
	public function GetSyncedTimezone(): ?array {
		return $this->syncedTimezone;
	}

	/**
	* Sets the session's time_zone to PHP's (`date_default_timezone_get()`, always a valid identifier).
	* If MySQL doesn't know the name (1298: timezone tables not loaded), sets the current offset instead.
	* @throws	MagratheaDBException 	on any other error; the connection is dropped
	*/
	private function SyncTimezone(): void {
		$zone = date_default_timezone_get();
		$fallback = false;
		$error = $this->TrySetTimezone($zone);
		if($error && $error["errno"] == 1298) {
			$zone = date("P");
			$fallback = true;
			$error = $this->TrySetTimezone($zone);
		}
		if($error) {
			$sql = "SET time_zone = '".$zone."'";
			$this->CheckConnectionLost($error["errno"], $error["error"], $sql, null, $error["previous"]);
			$this->Disconnect();
			throw $this->ErrorHandle($error["error"], $sql, null, $error["errno"], $error["previous"]);
		}
		$this->syncedTimezone = ["zone" => $zone, "fallback" => $fallback];
		if($fallback && in_array(Debugger::Instance()->GetType(), [Debugger::DEV, Debugger::DEBUG])) {
			Debugger::Instance()->Add("MySQL doesn't know the timezone [".date_default_timezone_get()."] (its timezone tables aren't loaded): session time_zone set to the offset [".$zone."]");
		}
	}
	/**
	* Runs `SET time_zone`, without going through the error handling (a 1298 is expected)
	* @param 	string 	$zone 		timezone name or offset
	* @return 	array|null 		null on success, or ["errno", "error", "previous"]
	*/
	private function TrySetTimezone(string $zone): ?array {
		$sql = "SET time_zone = '".$zone."'";
		$this->LogControl($sql);
		try {
			if($this->mysqli->query($sql) !== false) return null;
			return ["errno" => (int)$this->mysqli->errno, "error" => $this->mysqli->error, "previous" => null];
		} catch(\mysqli_sql_exception $ex) {
			return ["errno" => (int)$ex->getCode(), "error" => $ex->getMessage(), "previous" => $ex];
		}
	}
	/**
	* Already used you.. but I'll need you again!
	* Does nothing since 2.4.0: the connection stays open until the end of the request.
	* Use Disconnect() to close it.
	*/
	public function CloseConnectionThanks(){
	}

	/**
	* Closes this instance's connection now. The next query opens a new one.
	* Long-running workers should call it at the start of each job.
	*/
	public function Disconnect(): void {
		if($this->mysqli) {
			try {
				@$this->mysqli->close();
			} catch(\Throwable $ex) { }
		}
		$this->mysqli = null;
		$this->syncedTimezone = null;
		unset(self::$openConnections[spl_object_id($this)]);
	}
	/**
	* Closes the connections of every Database instance.
	* Runs automatically at the end of the request.
	*/
	public static function DisconnectAll(): void {
		foreach(self::$openConnections as $db) {
			$db->Disconnect();
		}
	}

	/**
	* Number of rows affected by the last query
	* (for a SELECT, the number of rows returned)
	* @return 	int
	*/
	public function LastAffectedRows(): int {
		return $this->affectedRows;
	}

	/**
	* Escapes a value for safe interpolation into a SQL string.
	* @param 	mixed 		$value 		value to be escaped
	* @return  	string 		escaped value
	*/
	public function Escape($value): string {
		$this->OpenConnectionPlease();
		return $this->mysqli->real_escape_string((string)$value);
	}

	/**
	* Handle connection errors @todo
	* @throws	MagratheaDbException
	*/
	private function ConnectionErrorHandle($msg="", $data=null): MagratheaDBException { 
		return new MagratheaDBException($msg);
	}
	/**
	* Builds the exception for a query error
	* @param 	string 		$error 		error message
	* @param 	string 		$sql 		query that failed
	* @param 	array 		$values 	query values
	* @param 	int 		$errno 		MySQL error number, used as the exception's code
	* @param 	\Exception 	$previous 	original exception, if mysqli threw one
	* @return 	MagratheaDBException
	*/
	private function ErrorHandle($error, $sql, $values=null, $errno=0, ?\Exception $previous=null): MagratheaDBException{
		// the exception adds itself to the Debugger
		$ex = new MagratheaDBException($error, $sql, (int)$errno, $previous);
		$ex->SetQueryData($sql, $values);
		return $ex;
	}
	/**
	* Wraps mysqli's own exception (PHP 8.1+ default reporting) in a MagratheaDBException:
	* the code is MySQL's errno, the original is its previous exception.
	* @param 	\mysqli_sql_exception 	$ex 		mysqli's exception
	* @param 	string 		$sql 		query that failed
	* @param 	array 		$values 	query values
	* @return 	MagratheaDBException
	* @throws	MagratheaDBConnectionLostException 	instead, if the errno means the connection is gone
	*/
	private function FromMysqliException(\mysqli_sql_exception $ex, $sql, $values=null): MagratheaDBException {
		$this->CheckConnectionLost($ex->getCode(), $ex->getMessage(), $sql, $values, $ex);
		return $this->ErrorHandle($ex->getMessage(), $sql, $values, $ex->getCode(), $ex);
	}
	/**
	* Runs a mysqli call that isn't a query (begin_transaction(), commit()...), wrapping what it throws
	* @param 	string 		$sql 		statement it stands for, for the error data
	* @param 	callable 	$call 		the mysqli call
	* @return 	mixed 		what the call returns
	* @throws	MagratheaDBException
	*/
	private function MysqliCall(string $sql, callable $call) {
		try {
			return $call();
		} catch(\mysqli_sql_exception $ex) {
			throw $this->FromMysqliException($ex, $sql);
		}
	}
	/**
	* If $errno means the connection is gone (2006, 2013, 4031), drops the dead connection and throws.
	* Never retries: the next call on this instance opens a new connection.
	* @param 	int 		$errno 		MySQL error number
	* @param 	string 		$error 		error message
	* @param 	string 		$sql 		query that failed
	* @param 	array 		$values 	query values
	* @param 	\Exception 	$previous 	original exception, if mysqli threw one
	* @throws	MagratheaDBConnectionLostException
	*/
	private function CheckConnectionLost($errno, $error, $sql, $values=null, ?\Exception $previous=null): void {
		if(!in_array((int)$errno, self::CONNECTION_LOST_ERRORS)) return;
		$this->Disconnect();
		$ex = new MagratheaDBConnectionLostException($error, $sql, (int)$errno, $previous);
		$ex->SetQueryData($sql, $values);
		throw $ex;
	}

	/**
	* Control Log
	* @param 	string 		      $sql 		Query to be logged
	* @param 	object|array    $values 	Values to be logged
	*/
	private function LogControl($sql, $values=null){
		Debugger::Instance()->AddQuery($sql, $values);
	}


	/**
	 * Gets a mysqli result and returns an array with the rows, according to the selected fetch mode
	 * @param object  $result        result to be fetched
	 * @param boolean $firstLineOnly should we fetch all the result or do we need only the first line?
	 */
	private function FetchResult($result, $firstLineOnly=false){
		$arrResult = array();
		$isArrayResponse = false;
		switch($this->fetchmode){
			case self::FETCH_OBJECT:
				$fetch_fn = "fetch_object";
			break;
			case self::FETCH_NUM:
				$fetch_fn = "fetch_num";
				$isArrayResponse = true;
			break;
			case self::FETCH_ARRAY:
				$fetch_fn = "fetch_array";
				$isArrayResponse = true;
			break;
			case self::FETCH_ASSOC:
			default:
				$fetch_fn = "fetch_assoc";
				$isArrayResponse = true;
			break;
		}
		if($firstLineOnly){
			$arrResult = $result->$fetch_fn();
			if($isArrayResponse)
				$arrResult = array_change_key_case($arrResult, CASE_LOWER);
		}
		else 
			while( $obj = $result->$fetch_fn() ){
				if($isArrayResponse)
					$obj = array_change_key_case($obj, CASE_LOWER);
				array_push($arrResult, $obj);
			}
		return $arrResult;
	}


	// QUERY FUNCTIONS

	/**
	* Runs a query on this instance's connection
	* @param 	string 		$sql 		Query to be executed
	* @return 	\mysqli_result|bool 		mysqli's query result (false on error, with mysqli reporting off)
	* @throws	MagratheaDBException 	if mysqli throws (PHP 8.1+ default reporting)
	* @throws	MagratheaDBConnectionLostException
	*/
	private function RunQuery(string $sql) {
		try {
			$result = $this->mysqli->query($sql);
		} catch(\mysqli_sql_exception $ex) {
			throw $this->FromMysqliException($ex, $sql);
		}
		if($result === false) {
			$this->CheckConnectionLost($this->mysqli->errno, $this->mysqli->error, $sql);
		}
		$this->affectedRows = (int)$this->mysqli->affected_rows;
		return $result;
	}

	/**
	 * Splits a SQL string into individual statements, respecting quoted strings
	 * so that semicolons inside 'values' or "values" are not treated as separators.
	 * @param  string   $sql  Raw SQL string, possibly containing multiple statements
	 * @return string[]       Array of individual SQL statements (trimmed, non-empty)
	 */
	private function SplitQueries(string $sql): array {
		$queries = [];
		$current = '';
		$len = strlen($sql);
		$inString = false;
		$stringChar = '';

		for ($i = 0; $i < $len; $i++) {
			$char = $sql[$i];
			if ($inString) {
				$current .= $char;
				if ($char === '\\') {
					if ($i + 1 < $len) $current .= $sql[++$i];
				} elseif ($char === $stringChar) {
					$inString = false;
				}
			} else {
				if ($char === "'" || $char === '"') {
					$inString = true;
					$stringChar = $char;
					$current .= $char;
				} elseif ($char === ';') {
					$trimmed = trim($current);
					if ($trimmed !== '') $queries[] = $trimmed;
					$current = '';
				} else {
					$current .= $char;
				}
			}
		}

		$trimmed = trim($current);
		if ($trimmed !== '') $queries[] = $trimmed;

		return $queries;
	}

	/**
	* executes the query and returns the full data
	* @param 	string 		$sql 		Query to be executed
	* @return 	object 		$result 	Result of the query
	*/
	public function Query(string $sql){
		$queries = $this->SplitQueries($sql);
		if (count($queries) > 1) {
			return $this->QueryMulti($queries);
		}
		$this->LogControl($sql);
		$this->OpenConnectionPlease();
		$result = $this->RunQuery($sql);
		if(is_object($result))
			$this->count = $result->num_rows;
		return $result;
	}
	
	/**
	* executes the query and returns the full data in an array
	* @param 	string 		$sql 		Query to be executed
	* @return 	array 		$result 	Result of the query (one row for line result)
	*/
	public function QueryAll(string $sql){
		$arrRetorno = array();
		$this->LogControl($sql);
		$this->OpenConnectionPlease();
		$result = $this->RunQuery($sql);
		if(!$result){
			throw $this->ErrorHandle($this->mysqli->error, $sql, null, $this->mysqli->errno);
		}
		if(is_object($result) ){
			$this->count = $result->num_rows;
			$arrRetorno = $this->FetchResult($result);
			$result->close();
		}
		return $arrRetorno;
	}
	
	/**
	* executes the query and returns only the first row of the result
	* @param 	  array|object|string 		$sql 		Query to be executed
	* @return 	array 		      				$result 	First line of the query
	*/
	public function QueryRow(string $sql) : array | object {
		$arrRetorno = array();
		$this->LogControl($sql);
		$this->OpenConnectionPlease();
		$result = $this->RunQuery($sql);
		if(!$result){
			throw $this->ErrorHandle($this->mysqli->error, $sql, null, $this->mysqli->errno);
		}
		if(is_object($result) ){
			$this->count = $result->num_rows;
			if($this->count > 0)
				$arrRetorno = $this->FetchResult($result, true);
			$result->close();
		}
		return $arrRetorno;
	}
	
	/**
	* executes the query and returns only the first value of the first row of the result
	* @param 	string 		$sql 		Query to be executed
	* @return 	object 		$result 	First value of the first line
	*/
	public function QueryOne($sql){
		$this->LogControl($sql);
		$this->OpenConnectionPlease();
		$result = $this->RunQuery($sql);
		if(!$result){
			throw $this->ErrorHandle($this->mysqli->error, $sql, null, $this->mysqli->errno);
		}
		if(is_object($result) ){
			$this->count = $result->num_rows;
			$rs = $result->fetch_row();
			$result->close();
		}
		return $rs[0] ?? null;
	}

	/**
	* receives an array of queries and executes them all in one transaction.
	* Stops at the first failing query and rolls everything back.
	* @param 	array 		$query_array  	Array of queries to be executed
	* @return 	bool 		true on success; false if a query (or the commit) failed and mysqli returned the error (mysqli reporting off)
	* @throws 	MagratheaDBException 	if mysqli threw the error (PHP 8.1+ default reporting), after the rollback
	* @throws 	MagratheaDBConnectionLostException 	if the connection is lost (MySQL discards the transaction with it)
	*/
	public function QueryTransaction($query_array){
		if ($this->transactionDepth > 0) {
			throw new MagratheaDBException("QueryTransaction() can't run inside Transaction(): run its queries directly in the Transaction() callback");
		}
		$this->OpenConnectionPlease();

		try {
			$this->MysqliCall("SET autocommit = 0", fn() => $this->mysqli->autocommit(false));
			$this->MysqliCall("START TRANSACTION", fn() => $this->mysqli->begin_transaction());
			foreach( $query_array as $query ){
				$this->LogControl($query);
				if ($this->RunQuery($query) === false) {
					$this->ErrorHandle($this->mysqli->error, $query, null, $this->mysqli->errno);
					$this->RollbackTransaction();
					return false;
				}
			}
			if (!$this->MysqliCall("COMMIT", fn() => $this->mysqli->commit())) {
				$this->CheckConnectionLost($this->mysqli->errno, $this->mysqli->error, "COMMIT");
				$this->ErrorHandle($this->mysqli->error, "COMMIT", null, $this->mysqli->errno);
				$this->RollbackTransaction();
				return false;
			}
		} catch(MagratheaDBConnectionLostException $ex) {
			throw $ex;
		} catch(\Exception $ex) {
			$this->RollbackTransaction();
			throw $ex;
		}
		$this->RestoreAutocommit();
		return true;
	}

	/**
	* Runs $fn inside a transaction and returns what $fn returns.
	* Everything $fn runs on this instance's connection (queries, model saves, GetWhere...) is committed
	* together when it returns, or rolled back if it throws; the exception is then rethrown unchanged.
	* Nested calls run inline: only the outermost call begins, commits or rolls back,
	* and if an inner call threw, the outermost one rolls back even if the exception was caught.
	* @param 	callable 	$fn 	function to run in the transaction
	* @return 	mixed 		what $fn returns
	* @throws 	MagratheaDBException 	if the transaction can't begin or commit, or an inner call failed
	*/
	public function Transaction(callable $fn): mixed {
		if ($this->transactionDepth > 0) {
			$this->transactionDepth++;
			try {
				return $fn();
			} catch(\Throwable $ex) {
				$this->transactionFailed = true;
				throw $ex;
			} finally {
				$this->transactionDepth--;
			}
		}

		$this->OpenConnectionPlease();
		$this->TransactionStep("START TRANSACTION", fn() => $this->mysqli->begin_transaction());
		$this->transactionDepth = 1;
		$this->transactionFailed = false;
		try {
			$result = $fn();
			if ($this->transactionFailed) {
				throw new MagratheaDBException("transaction marked as failed by an inner call (its exception was caught): rolled back");
			}
			$this->TransactionStep("COMMIT", fn() => $this->mysqli->commit());
			return $result;
		} catch(\Throwable $ex) {
			$this->RollbackTransaction();
			throw $ex;
		} finally {
			$this->transactionDepth = 0;
			$this->transactionFailed = false;
		}
	}
	/**
	* Runs begin_transaction() or commit(), throwing on failure
	* @param 	string 		$sql 		statement it stands for, for the error data
	* @param 	callable 	$step 		the mysqli call
	* @throws 	MagratheaDBException
	*/
	private function TransactionStep(string $sql, callable $step): void {
		if (!$this->MysqliCall($sql, $step)) {
			$this->CheckConnectionLost($this->mysqli->errno, $this->mysqli->error, $sql);
			throw $this->ErrorHandle($this->mysqli->error, $sql, null, $this->mysqli->errno);
		}
	}

	/**
	* Rolls back the open transaction and turns autocommit back on.
	* Any error doing it is ignored, and the connection is dropped instead:
	* its state is unknown, and closing it makes MySQL discard the transaction.
	*/
	private function RollbackTransaction(): void {
		if (!$this->mysqli) return;
		try {
			if (!$this->mysqli->rollback()) {
				$this->Disconnect();
				return;
			}
		} catch(\Throwable $ex) {
			$this->Disconnect();
			return;
		}
		$this->RestoreAutocommit();
	}
	/**
	* Turns autocommit back on; if that fails, the connection is dropped,
	* so the next query never runs inside a leftover transaction.
	*/
	private function RestoreAutocommit(): void {
		if (!$this->mysqli) return;
		try {
			if (!$this->mysqli->autocommit(true)) $this->Disconnect();
		} catch(\Throwable $ex) {
			$this->Disconnect();
		}
	}


	/**
	* receives a string with multiple queries and executes them all
	*	@todo confirms if this is working properly
	* @param 	array|string 		$multipleQueries  	String of queries to be executed
	* @param 	bool						$killable						Should all queries be killed in case of error?
	* @throws 	MagratheaDBException 	with $killable, on the first failing statement
	* @throws 	MagratheaDBConnectionLostException 	always, even without $killable: the next statements can't run
	*/
	public function QueryMulti($queries, $killable=true) {
		if (is_string($queries)) {
			$queries = explode(";\n", $queries);
		}
		$this->OpenConnectionPlease();
		$results = array();
		$q = 0;
		foreach ($queries as $query) {
			$query = trim($query);
			if(!$query) continue;
			try {
				$result = $this->RunQuery($query);
			} catch(MagratheaDBConnectionLostException $ex) {
				throw $ex;
			} catch(MagratheaDBException $ex) {
				// PHP 8.1+ default reporting: mysqli throws instead of returning false
				if ($killable) throw $ex;
				$results[$q++] = [
					"query" => $query,
					"success" => false,
					"result" => $ex->getMessage(),
				];
				continue;
			}
			if ($result) {
				$rows = array();
				if (substr(trim($query), 0, 6) == "CREATE") {
					$rows = array("success" => true);
				}
				// query() already returns the stored result; store_result() is only for multi_query()/real_query()
				if (is_object($result)) {
					while ($row = $result->fetch_assoc()) {
						$rows[] = $row;
					}
					$result->free();
				}
				$results[$q] = [
					"query" => $query,
					"success" => true,
					"result" => $rows,
				];
			} else {
				if ($killable) {
					throw $this->ErrorHandle($this->mysqli->error, $query, null, $this->mysqli->errno);
				}
				$results[$q] = [
					"query" => $query,
					"success" => false,
					"result" => $this->mysqli->error,
				];
			}
			$q++;
		}
		return $results;
	}

	/**
	 * imports a .sql file to the database
	 * @param		string 	$file_path		Path of the file to import
	 * @param 	bool		$killable			Should all queries be killed in case of error?
	 * @throws 	MagratheaDBException
	 */
	public function ImportFile($file_path, $killable=true) {
		if(!$file_path) {
			throw new MagratheaDBException("SQL file path is empty: [".$file_path."]");
		}
		$queries = file_get_contents($file_path);
		$rs = $this->QueryMulti($queries, $killable);
		return $rs;
	}
	
	/**
	* Prepares and execute a query and returns the inserted id (if any)
	* 	@todo validates types and avoids injection. Does it?
	* @param 	string 		$query 		Query to be executed
	* @param 	array 		$arrTypes 	Array of types from the values to be inserted
	* @param 	array 		$arrValues 	Array of values to be inserted
	* @return 	int|bool 	inserted id, or true if there's none
	* @throws 	MagratheaDBException 	on any error: code = MySQL errno, query and values attached (`query`, `values`)
	*/
	public function PrepareAndExecute($query, $arrTypes, $arrValues){

		$this->LogControl($query, $arrValues);
		$this->OpenConnectionPlease();

		try {
			$stm = $this->mysqli->prepare($query);
		} catch(\mysqli_sql_exception $ex) {
			throw $this->FromMysqliException($ex, $query, $arrValues);
		}
		if(!$stm || $this->mysqli->error ){
			$this->CheckConnectionLost($this->mysqli->errno, $this->mysqli->error, $query, $arrValues);
			throw $this->ErrorHandle($this->mysqli->error, $query, $arrValues, $this->mysqli->errno);
		}
		$params = "";
		if($arrTypes){
			foreach ($arrTypes as $type) {
				switch ($type) {
					case "int":
					case "integer":
					case "boolean":
						$params .= "i";
						break;
					case "float":
						$params .= "d";
						break;
					case "datetime":
					case "date":
					case "text":
					case "string":
					default:
						$params .= "s";
						break;
				}
			}
		}

		$args = $arrValues;
		try{
			$valArgs = $this->makeValuesReferenced($args);
			if (strnatcmp(phpversion(),'8') >= 0) {
				$stm->bind_param($params, ...array_values($arrValues));
			} else {
				call_user_func_array(array($stm, "bind_param"), $valArgs);
			}
			$stm->execute();
			if($stm->error) {
				$this->CheckConnectionLost($stm->errno, $stm->error, $query, $args);
				throw $this->ErrorHandle($stm->error, $query, $args, $stm->errno);
			}
			$lastId = $stm->insert_id;
			$this->affectedRows = (int)$stm->affected_rows;
			$stm->close();
		} catch(MagratheaDBConnectionLostException $ex) {
			throw $ex;
		} catch(MagratheaDBException $ex) {
			throw $ex->SetQueryData($query, $args);
		} catch(\mysqli_sql_exception $err){
			throw $this->FromMysqliException($err, $query, $args);
		}
		if($lastId)
			return $lastId;
		else
			return true;
	}

	/**
	 * since PHP 5.3, it's necessary to pass values by reference in mysqli function to send them as args.
	 * Details can be found on @link http://php.net/manual/en/mysqli-stmt.bind-param.php
	 * @param  array 	$arr 	array to be "converted"
	 * @return array 	array as reference, ready to be used!
	 */
	private function makeValuesReferenced($arr){
		//Reference is required for PHP 5.3+
		if (strnatcmp(phpversion(),'5.3') >= 0) {
			$refs = array(); 
			foreach($arr as $key => $value) {
				if(is_object($value)) {
					$ex = new MagratheaDBException("MySQL operation does not work with given object - query-key=".$key.", value=[".$value."]", 5022);
					$ex->SetQueryData(null, $arr);
					throw $ex;
				}
				$refs[$key] = &$arr[$key];
			}
			return $refs; 
		}
		return $arr;
	}

  public function __toString() {
    $rs = "";
    $rs .= "MAGRATHEA DATABASE \n";
    $rs .= "Connection details: \n";
    if (!$this->connDetails || count($this->connDetails) == 0) {
      $rs .= "No Connections Details set";
      return $rs;
    }
    $rs .= "[db_host]=".$this->connDetails["hostspec"].(empty($this->connDetails["port"]) ? "" : ":".$this->connDetails["port"])."\n";
    $rs .= "[db_name]=".$this->connDetails["database"]."\n";
    $rs .= "[db_user]=".$this->connDetails["username"]."\n";
    $rs .= "[db_pass]=".$this->connDetails["password"]."\n";

    return $rs;
  }
}
?>