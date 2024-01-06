<?php

namespace Magrathea2\DB;

use Exception;
use Magrathea2\Debugger;
use Magrathea2\Exceptions\MagratheaDBException;
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
* 
*/
class Database extends Singleton {

	const FETCH_ASSOC = 1;
	const FETCH_OBJECT = 2;
	const FETCH_NUM = 3;
	const FETCH_ARRAY = 4;

	private $mysqli;
	private $connDetails;
	private $fetchmode;

	private $count = 0;

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
	* @return  	Database
	*/	
	public function SetConnection($host, $database, $username, $password, $port=null): Database{
		$this->connDetails = array(
			'hostspec' => $host,
			'database' => $database,
			'username' => $username,
			'password' => $password,
		);
		if(!$port){
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
	* Open connection, please. Please! 0=)
	* @return 	boolean		true or false, if connection succedded
	* @throws	MagratheaDbException
	*/
	public function OpenConnectionPlease() : bool {
		if(!$this->connDetails) {
			throw new MagratheaDBException("DB not initialized");
		}
		try{
			if($this->connDetails["port"])
				$this->mysqli = @new \mysqli(
					$this->connDetails["hostspec"], 
					$this->connDetails["username"], 
					$this->connDetails["password"], 
					$this->connDetails["database"],
					$this->connDetails["port"]
				);
			else 
				$this->mysqli = @new \mysqli(
					$this->connDetails["hostspec"], 
					$this->connDetails["username"], 
					$this->connDetails["password"], 
					$this->connDetails["database"]
				);
			if($this->mysqli->connect_errno){
				throw new MagratheaDBException("Failed to connect to MySQL: (".$this->mysqli->connect_errno.") ".$this->mysqli->connect_error);
			}
			$this->mysqli->set_charset("utf8");
		} catch (\Exception $ex) {
			Debugger::Instance()->AddError($ex);
			throw new MagratheaDBException($ex->getMessage());
		}
		return true;
	}
	/**
	* Already used you.. Bye.
	*/
	public function CloseConnectionThanks(){
		$this->mysqli->close();
	}
	
	/**
	* Handle connection errors @todo
	* @throws	MagratheaDbException
	*/
	private function ConnectionErrorHandle($msg="", $data=null): MagratheaDBException { 
		return new MagratheaDBException($msg);
	}
	/**
	* Handle errors @todo
	* @throws	MagratheaDbException
	*/
	private function ErrorHandle($error, $sql, $values=null): MagratheaDBException{ 
		$ex = new MagratheaDBException($error, $sql);
		$ex->SetData($sql, $values);
		Debugger::Instance()->Add($ex);
		return $ex;
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
	* executes the query and returns the full data
	* @param 	string 		$sql 		Query to be executed
	* @return 	object 		$result 	Result of the query
	*/
	public function Query($sql){
		$this->LogControl($sql);
		$this->OpenConnectionPlease();
		$result = $this->mysqli->query($sql);
		if(is_object($result))
			$this->count = $result->num_rows;
		$this->CloseConnectionThanks();
		return $result;
	}
	
	/**
	* executes the query and returns the full data in an array
	* @param 	string 		$sql 		Query to be executed
	* @return 	array 		$result 	Result of the query (one row for line result)
	*/
	public function QueryAll($sql){
		$arrRetorno = array();
		$this->LogControl($sql);
		$this->OpenConnectionPlease();
		$result = $this->mysqli->query($sql);
		if(!$result){
			$ex = $this->ErrorHandle($this->mysqli->error, $sql);
			throw $ex;
		}
		if(is_object($result) ){
			$this->count = $result->num_rows;
			$arrRetorno = $this->FetchResult($result);
			$result->close();
		}
		$this->CloseConnectionThanks();
		return $arrRetorno;
	}
	
	/**
	* executes the query and returns only the first row of the result
	* @param 	  array|object|string 		$sql 		Query to be executed
	* @return 	array 		      				$result 	First line of the query
	*/
	public function QueryRow($sql) : array | object {
		$arrRetorno = array();
		$this->LogControl($sql);
		$this->OpenConnectionPlease();
		try {
			$result = $this->mysqli->query($sql);
		} catch(Exception $ex) {
			throw $this->ErrorHandle($ex, $sql);
		}
		if(!$result){
			$ex = $this->ErrorHandle($this->mysqli->error, $sql);
			throw $ex;
		}
		if(is_object($result) ){
			$this->count = $result->num_rows;
			if($this->count == 0) return $arrRetorno;
			$arrRetorno = $this->FetchResult($result, true);
			$result->close();
		}
		$this->CloseConnectionThanks();
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
		$result = $this->mysqli->query($sql);
		$this->count = $result->num_rows;
		if(!$result){
			$ex = $this->ErrorHandle($this->mysqli->error, $sql);
			throw $ex;
		}
		if(is_object($result) ){
			$retorno = $result->fetch_row();
			$result->close();
		}
		$this->CloseConnectionThanks();
		return $retorno[0];
	}

	/**
	* receives an array of queries and executes them all
	*	@todo confirms if this is working properly
	* @param 	array 		$query_array  	Array of queries to be executed
	* @throws 	MagratheaDBException
	*/
	public function QueryTransaction($query_array){
		$this->OpenConnectionPlease();

		try {
			$this->mysqli->autocommit(false);
			$this->mysqli->begin_transaction();
			foreach( $query_array as $query ){
				$this->LogControl($query);
				$this->mysqli->query($query);
			}
			if ($this->mysqli->error) {
				$this->ErrorHandle($this->mysqli->error, $query);
				return false;
			}
			$this->mysqli->commit();

		} catch(\Exception $ex) {
			throw $ex;
		}
		$this->mysqli->autocommit(true);
		$this->CloseConnectionThanks();
	}


	/**
	* receives a string with multiple queries and executes them all
	*	@todo confirms if this is working properly
	* @param 	array|string 		$multipleQueries  	String of queries to be executed
	* @param 	bool						$killable						Should all queries be killed in case of error?
	* @throws 	MagratheaDBException
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
				if ($this->mysqli->query($query)) {
					$rows = array();
					if (substr(trim($query), 0, 6) == "CREATE") {
						$rows = array("success" => true);
					}
					$result = $this->mysqli->store_result();
					if ($result) {
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
						throw new MagratheaDBException($this->mysqli->error);
					}
					$results[$q] = [
						"query" => $query,
						"success" => false,
						"result" => $this->mysqli->error,
					];
				}
			} catch(\Exception $ex) {
				throw $ex;
			}
			$q++;
		}
		$this->CloseConnectionThanks();
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
	*/
	public function PrepareAndExecute($query, $arrTypes, $arrValues){

		$this->LogControl($query, $arrValues);
		$this->OpenConnectionPlease();

		$stm = $this->mysqli->prepare($query);
		if(!$stm || $this->mysqli->error ){
			$ex = $this->ErrorHandle($this->mysqli->error, $query, $arrValues);
			throw $ex;
		}
		$params = "";
		if($arrTypes){
			foreach ($arrTypes as $type) {
				switch ($type) {
					case "int":
					case "boolean":
						$params .= "i";
						break;
					case "float":
						$params .= "d";
						break;
					case "datetime":
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
				throw $this->ErrorHandle($stm->error, $query);
			}
			$lastId = $stm->insert_id;
			$stm->close();
		} catch(MagratheaDBException $ex) {
			echo "got error! ";
			$ex->SetData($query, $args);
			return null;
		} catch(Exception $err){
			throw $this->ErrorHandle($stm->error, $query);
		}
		$this->CloseConnectionThanks();
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
					$ex->SetData(null, $arr);
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
    $rs .= "[db_host]=".$this->connDetails["hostspec"].":".$this->connDetails["port"]."\n";
    $rs .= "[db_name]=".$this->connDetails["database"]."\n";
    $rs .= "[db_user]=".$this->connDetails["username"]."\n";
    $rs .= "[db_pass]=".$this->connDetails["password"]."\n";

    return $rs;
  }
}
?>