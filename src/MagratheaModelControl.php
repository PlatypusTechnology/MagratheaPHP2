<?php

namespace Magrathea2;
use Magrathea2\DB\Database;
use Magrathea2\DB\Query;
use Magrathea2\Exceptions\MagratheaModelException;

#######################################################################################
####
####	MAGRATHEA PHP
####	v. 2.0
####
####	Magrathea by Paulo Henrique Martins
####	Platypus technology
####
#######################################################################################
####
####	Model Control Class
####	Class and interface for model control
####	
####	created: 2012-12 by Paulo Martins
####	updated: 2023-02 by Paulo Martins
####
#######################################################################################

abstract class MagratheaModelControl{
	protected static $modelNamespace;
	protected static $modelName;
	protected static $dbTable;

	public static function GetModelName() {
		return static::$modelNamespace."\\".static::$modelName;
	}

	private static function ConvertToObject($item) {
		$modelName = static::GetModelName();
		$splitResult = Query::SplitArrayResult($item);
		$new_object = new $modelName();
		if(count($splitResult) > 0)
			$item = $splitResult[$new_object->GetDbTable()];
		$new_object->LoadObjectFromTableRow($item);
		return $new_object;
	}

	/**
	 * Run a query and return a list of the objects
	 * @param 	string 	$sql 	query string
	 * @return  array<object> 	List of objects
	 */
	public static function RunQuery(string $sql){
		$magdb = Database::Instance();
		$result = $magdb->queryAll($sql);
		$objects = array();
		foreach($result as $item){
			$obj = static::ConvertToObject($item);
			array_push($objects, $obj);
		}
		return $objects;
	}
	/**
	 * Run a query and return the first object available
	 * @param 	string 	$sql 	query string
	 * @return  object 	First object found
	 */
	public static function RunRow(string $sql){
		$magdb = Database::Instance();
		$row = $magdb->QueryRow($sql);
		$obj = null;
		if(!empty($row)){
			$obj = static::ConvertToObject($row);
		}
		return $obj;
	}
	/**
	 * Runs a query and returns the result
	 * @param 	string 	$sql 	query string
	 * @return  array		database result
	 */
	public static function QueryResult(string $sql){
		return Database::Instance()->QueryAll($sql);
	}
	/**
	 * Runs a query and returns the first row of result
	 * @param 	string 	$sql 	query string
	 * @return  array		database result (first line)
	 */
	public static function QueryRow(string $sql){
		return Database::Instance()->QueryRow($sql);
	}
	/**
	 * Runs a query and returns the first result
	 * @param 	string 	$sql 	query string
	 * @return  object		database result (first item)
	 */
	public static function QueryOne(string $sql){
		return Database::Instance()->QueryOne($sql);
	}

	/**
	 * Runs a Magrathea Query and returns a list of objects
	 * (calls Run function)
	 * @param 	Query  	$magQuery  		MagratheaQuery query
	 * @return  array<object> 		List of objects
	 */
	public static function RunMagQuery(Query $magQuery){ 
		return self::Run($magQuery); 
	}
	/**
	 * Runs a Magrathea Query and returns the count of matching rows
	 * @param   Query   $magQuery   MagratheaQuery query
	 * @return  int     Number of matching rows
	 */
	public static function Count(Query $magQuery): int {
		$q = self::QueryOne($magQuery->CountSQL());
		return intval($q);
	}

	/**
	 * Runs query with Pagination. 
	 * 	This way, is not necessary to worry about including pagination on Magrathea Query, this function can deal with it
	 * @param 	Query  	$magQuery 		MagratheaQuery query
	 * @param 	int  				&$total   		total of rows (it will be stored in this variable; it's a pointer!)
	 * @param 	integer 			$page     		page to get (0 = first)
	 * @param 	integer 			$limit    		quantity per page (20 = default)
	 * @return  array<object> 		List of objects
	 */
	public static function RunPagination(Query $magQuery, &$total, $page=0, $limit=20){
		$total = self::QueryOne($magQuery->CountSQL());
		$magQuery->Limit($limit)->Page($page);
		return self::Run($magQuery);
	}
	/**
	 * Runs a Magrathea Query and returns a list of objects
	 * @param 	Query  	$magQuery  		MagratheaQuery query
	 * @param 	boolean 			$onlyFirst 		returns all of it or only first row?
	 * @return  array<object> 		List of objects
	 */
	public static function Run(Query $magQuery, $onlyFirst=false){
		$array_joins = $magQuery->GetJoins();
		$arrayObjs = array();

		if(count($array_joins) > 0){
			$objects = [];
			$result = static::QueryResult($magQuery->SQL());
			foreach ($result as $r) {
				$splitResult = Query::SplitArrayResult($r);
				$modelName = static::GetModelName();
				$new_object = new $modelName();
				if(count($splitResult) > 1)
					$r = $splitResult[$new_object->GetDbTable()];
				$new_object->LoadObjectFromTableRow($r);
				foreach($array_joins as $join){
					$obj = $join["obj"];
					if(empty($obj)) continue;
					$obj->LoadObjectFromTableRow($splitResult[$obj->GetDbTable()]);
					if(empty($obj->GetPK())) continue;
					$objname = $obj->ModelName();
					if($join["type"] == "has_many"){
						$objnameField = $objname."s";
						if( empty($arrayObjs[$new_object->GetID()]) )
							$arrayObjs[$new_object->GetID()] = array();
						if( empty($arrayObjs[$new_object->GetID()][$objnameField]) )
							$arrayObjs[$new_object->GetID()][$objnameField] = array();
						$objIndex = count($arrayObjs[$new_object->GetID()][$objnameField]);
						$arrayObjs[$new_object->GetID()][$objnameField][$objIndex] = clone $obj;
						$new_object->$objnameField = $arrayObjs[$new_object->GetID()][$objnameField];
					} else {
						$new_object->$objname = clone $obj;
					}
					unset($obj);
				}
				$objects[$new_object->GetPK()] = clone $new_object;
			}
			if($onlyFirst){
				if(count($objects) > 0) return $objects[0];
			} else return array_values($objects);
		} else {
			return $onlyFirst ? self::RunRow($magQuery->SQL()) : self::RunQuery($magQuery->SQL());
		}
	}

	/**
	 * Gets all from this object
	 * @return  array<object> 	List of objects
	 */
	public static function GetAll() {
		$sql = "SELECT * FROM ".static::$dbTable." ORDER BY created_at ASC";
		return static::RunQuery($sql);
	}

	/**
	 * Gets all from this object
	 * @return  array<object> 	List of objects
	 */
	public static function GetListPage(int $limit=20, int $page=0) {
		$offset = $page * $limit;
		$sql = "SELECT * FROM ".static::$dbTable." ORDER BY created_at DESC LIMIT ".$offset.",".$limit;
		return static::RunQuery($sql);
	}

	/**
	 * Gets all from this object
	 * @return  array<object> 	List of objects
	 */
	public static function GetSelectArray() {
		$relational = self::GetAll();
		$selects = [];
		foreach($relational as $s) {
			$selects[$s->GetID()] = $s->Ref();
		}
		return $selects;
	}

	/**
	 * Builds query with where clause
	 * @param 	string 					$whereSql 		where clause
	 * @return  array<object> 	List of objects
	 */
	public static function GetSimpleWhere(string $whereSql){
		$sql = "SELECT * FROM ".static::$dbTable." WHERE ".$whereSql;
		return static::RunQuery($sql);
	}

	/**
	 * Builds query with where clause
	 * @param 	string|array 	$arr 		where clause
	 * @param 	string 				$condition 	"AND" or "OR" for multiple clauses
	 * @return  array<object> 		List of objects
	 */
	public static function GetWhere(string|array $arr, $condition = "AND"){
		if(!is_array($arr)){
			return static::GetSimpleWhere($arr);
		}
		$whereSql = Query::BuildWhere($arr, $condition);
		$sql = "SELECT * FROM ".static::$dbTable." WHERE ".$whereSql." ORDER BY created_at DESC";
		return static::RunQuery($sql);
	}

	/**
	 * Builds query with where clause, returning only first row
	 * @param 	string|array 			$arr 		where clause
	 * @param 	string 						$condition 	"AND" or "OR" for multiple clauses
	 * @return  object|array 			First object available
	 */
	public static function GetRowWhere(string|array $arr, $condition = "AND"){
		if(!is_array($arr)){
			return static::GetSimpleWhere((string)$arr);
		}
		$whereSql = Query::BuildWhere($arr, $condition);
		$sql = "SELECT * FROM ".static::$dbTable." WHERE ".$whereSql;
		return static::RunRow($sql);
	}

	/**
	 * This function allows to build a query getting multiple objects at once
	 * @param 	array<object> 	$array_objects Array of objects
	 * @param 	string 					$joinGlue      join string to be used on query
	 * @param 	string 					$where         Where clause
	 * @return  array<object> 	List of objects
	 */
	public static function GetMultipleObjects($array_objects, $joinGlue, $where=""){
		$magQuery = new Query();
		$magQuery->Table(static::$dbTable)->SelectArrObj($array_objects)->Join($joinGlue)->Where($where);

		// db:
		$objects = array();
		$result = Database::Instance()->queryAll($magQuery->SQL());

		foreach($result as $item){
			// we have the result... but we have to separate it in the objects... shit, how can I do that?
			$splitResult = Query::SplitArrayResult($item);
			$itemArr = array();
			foreach ($array_objects as $key => $value) {
				$new_object = new $value();
				$new_object->LoadObjectFromTableRow($splitResult[$new_object->GetDbTable()]);
				$itemArr[$key] = $new_object;
			}
			array_push($objects, $itemArr);
		}
		return $objects;
	}

	/**
	 * Show all elements from an object
	 */
	public static function ShowAll(){
		$baseObj = new static::$modelName();
		$all = static::GetAll();
		$properties = $baseObj->GetProperties();
		echo "<table class='magratheaShowAll'>";
		echo "<tr>";
		foreach ($properties as $key => $value) {
			echo "<th>".$key." - (".$value.")</th>";
		}
		echo "</tr>";
		foreach ($all as $i) {
			echo "<tr>";
			foreach ($properties as $key => $value) {
				echo "<td>".$i->$key."</td>";
			}
			echo "</tr>";
		}
		echo "</tr>";
		echo "</table>";
	}

	public function __toString(): string {
		$rs = "";
		$rs .= "[MAGRATHEA MODEL CLASS == ".get_class($this)."]\n";
		$rs .= "Model: ".static::$modelName." / Table: ".static::$dbTable;
		return $rs;
	}

}
