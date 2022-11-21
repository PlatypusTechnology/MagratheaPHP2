<?php

namespace Magrathea;

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
class MagratheaDatabase{

	const FETCH_ASSOC = 1;
	const FETCH_OBJECT = 2;
	const FETCH_NUM = 3;
	const FETCH_ARRAY = 4;

	private $mysqli;
	private $connDetails;
	private $fetchmode;

	private $count = 0;

	protected static $inst = null;

	/**
	* This is a singleton!
	* Constructor is private
	*/
	private function __construct(){
	}

	/**
	* This is a singleton!
	* Instance loader
	* @return 	MagratheaDatabase 	Instance of the object
	*/
	public static function Instance(){
		if (self::$inst === null) {
			self::$inst = new MagratheaDatabase();
		}
		return self::$inst;
	}

	/**
	*	Mocker
	*	For Unit Testing
	*/
	public static function Mock($mocker) {
		self::$inst = $mocker;
		return self::$inst;
	}
	
	/**
	* This is a singleton!
	* Should be called by private method Instance.
	* Don't implement new ones
	*/
	public function MagratheaDatabase(){
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
	* @return  	itself
	*/
	public function SetConnectionArray($dsn_arr){
		$this->connDetails = $dsn_arr;
		return $this;
	}
	/**
	* Setups connection
	* @param 	string 	$host 			host address for connection
	* @param 	string 	$database		database name
	* @param 	string 	$username 		username for connection
	* @param 	string 	$password		password for connection
	* @return  	itself
	*/	
	public function SetConnection($host, $database, $username, $password, $port=null): MagratheaDatabase{
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
	* Sets fetchmode, according with MDB2 values. Default mode: assoc.
	* @param 	string 	$fetch 			fetchmode for SQL returns
	*									options available:
	*									assoc:
	*										array with keys as the column names
	*									object:
	*										object with columns as properties
	*									if anything different from those values is sent, "assoc" is used
	* @return  	itself
	*/	
	public function SetFetchMode($fetch){
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
	public function OpenConnectionPlease(){
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
			MagratheaDebugger::Instance()->AddError($ex);
			throw new MagratheaDBException($ex->getMessage());
		}
		return true;
	}
	/**
	* Already uses you.. Bye.
	*/
	public function CloseConnectionThanks(){
		$this->mysqli->close();
	}
	
	/**
	* Handle connection errors @todo
	* @throws	MagratheaDbException
	*/
	private function ConnectionErrorHandle($msg="", $data=null){ 
		throw new MagratheaDBException($msg);
	}
	/**
	* Handle errors @todo
	* @throws	MagratheaDbException
	*/
	private function ErrorHandle($error, $sql, $values=null){ 

		$debug = "MagratheaDatabase ERROR => \n";
		$debug .= " query: [ ".$sql." ] \n";
		if($values != null)
			$debug .= " values: [ ".implode(',', $values)." ] \n";
		$debug .= " error: [ ".$error." ] \n";
		MagratheaDebugger::Instance()->Add($debug);
	}

	/**
	* Control Log
	* @param 	string 		$sql 		Query to be logged
	* @param 	object 		$values 	Values to be logged
	*/
	private function LogControl($sql, $values=null){
		MagratheaDebugger::Instance()->AddQuery($sql, $values);
	}


	/**
	 * Gets a mysqli result and returns an array with the rows, according to the selected fetch mode
	 * @param result  $result        result to be fetched
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
			$this->ErrorHandle($this->mysqli->error, $sql);
			$ex = new MagratheaDBException($this->mysqli->error);
			$ex->query = $sql;
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
	* @param 	string 		$sql 		Query to be executed
	* @return 	object 		$result 	First line of the query
	*/
	public function QueryRow($sql){
		$arrRetorno = array();
		$this->LogControl($sql);
		$this->OpenConnectionPlease();
		$result = $this->mysqli->query($sql);
		if(!$result){
			$this->ErrorHandle($this->mysqli->error, $sql);
			$ex = new MagratheaDBException($this->mysqli->error);
			$ex->query = $sql;
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
			$this->ErrorHandle($this->mysqli->error, $sql);
			$ex = new MagratheaDBException($this->mysqli->error);
			$ex->query = $sql;
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
		}
		$this->mysqli->autocommit(true);
		$this->CloseConnectionThanks();
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
			$this->errorHandle($this->mysqli->error, $query, $arrValues);
			$ex = new MagratheaDBException($this->mysqli->error);
			$ex->query = $query;
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
//		array_unshift($args, $params);
		try{
			$valArgs = $this->makeValuesReferenced($args);
			if (strnatcmp(phpversion(),'8') >= 0) {
				$stm->bind_param($params, ...array_values($arrValues));
			} else {
				call_user_func_array(array($stm, "bind_param"), $valArgs);
			}
			$stm->execute();
			if($stm->error) $this->ConnectionErrorHandle($stm->error);
			$lastId = $stm->insert_id;
			$stm->close();
		} catch(\Exception $err){
			$this->ConnectionErrorHandle($err, $err);
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
					throw new MagratheaDBException("MySQL operation does not work with given object - query-key=".$key, 5022);
				}
				$refs[$key] = &$arr[$key];
			}
			return $refs; 
		}
		return $arr;
	}	
}
?>