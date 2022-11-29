<?php

namespace Magrathea2\Database;

#######################################################################################
####
####    MAGRATHEA PHP2
####    v. 2.0
####    Magrathea by Paulo Henrique Martins
####    Platypus technology
####    Magrathea2 created: 2022-12 by Paulo Martins
####
#######################################################################################

/**
 * Creates queries making use of objects and tables
 */
class MagratheaQuery{

	protected $select;
	protected $selectDefaultArr;
	protected $selectArr;
	protected $obj_base;
	protected $obj_array;
	protected $tables;
	protected $join;
	protected $joinArr;
	protected $where;
	protected $whereArr;
	protected $order;
	protected $page;
	protected $limit;
	protected $group;

	protected $sql;

	/**
	 * private function
	 * 	basically, you give me anything and I shall send back to you a correct object of that thing
	 * 	(at least, in theory, I should work like that =P)
	 * @param  		type 				$object 	the object that I need is kind of this
	 * @return  	the correct object
	 * @throws 		MagratheaModelException 		If Model does not exists...
	 */
	private function GiveMeThisObjectCorrect($object){
		if(is_string($object)){
			if(class_exists($object)){
				$object = new $object();
			} else {
				throw new MagratheaModelException("Model does not exists: ".$object);
			}
		}
		return $object;
	}

	/**
	 * constructor
	 */
	public function __construct(){
		$this->obj_array = array();
		$this->select = "SELECT ";
		$this->selectArr = array();
		$this->selectExtra = array();
		$this->selectDefaultArr = array();
		$this->join = "";
		$this->joinArr = array();
		$this->joinType = array();
		$this->where = "";
		$this->whereArr = array();
		$this->order = "";
		$this->page = 0;
		$this->limit = null;
		$this->group = null;
		return $this;
	}

	/**
	 * Cleans a value in order to avoid SQL injection
	 * @param 	string 		$query 		string to be cleared
	 * @return  string 		clean data
	 */
	static public function Clean($query){
		$query = str_replace("'", "\'", $query);
		$query = str_replace('"', '\"', $query);
		return $query;
	}	

	/**
	 * Creates. Just that. Just like God did.
	 */
	static public function Create(){
		return new self();
	}
	/**
	 * Generates a SELECT query
	 * @param 	string 				$sel 	string to be selected
	 *                          			in a query SELECT [blablabla] FROM ...
	 *                          			the [blablabla] should be send to this function. Got it?
	 * @return  MagratheaQuery 		
	 */
	static public function Select($sel=""){
		$new_me = new self();
		$new_me->SelectStr($sel);
		return $new_me;
	}
	/**
	 * Generates a DELETE query
	 * @return 		MagratheaQueryDelete
	 */
	static public function Delete(){
		return new MagratheaQueryDelete();
	}

	/**
	 * Generates a UPDATE query
	 * @return 		MagratheaQueryUpdate
	 */
	static public function Update(){
		return new MagratheaQueryUpdate();
	}

	/**
	 * Generates a INSERT query
	 * @return 		MagratheaQueryInsert
	 */
	static public function Insert(){
		return new MagratheaQueryInsert();
	}

	/**
	 * Set table
	 * @param 	string 		$t 		Table to be selected from
	 * @return  itself
	 */
	public function Table($t){
		$this->tables = $t;
		return $this;
	}

	/**
	 * Alias for Obj
	 * @param 	string or object 	$obj 	Object to query
	 * @return  itself
	 */
	public function Object($obj){
		return $this->Obj($obj);
	}
	/**
	 * Set object for getting information in query
	 * @param 	object or string 	$obj 	object or string to be selected
	 * @return  itself
	 */
	public function Obj($obj){
		$obj = $this->GiveMeThisObjectCorrect($obj);
		$this->obj_base = $obj;
		$this->tables = $obj->GetDbTable();
		$this->SelectObj($obj);
		return $this;
	}

	/**
	 * Fields to be included on the query
	 * @param 	string or array 	$fields 	string or array that will be added to the fields in the *SELECT* built
	 * @return  itself
	 */
	public function Fields($fields){
		if(is_array($fields)){
			$this->selectArr = array_merge($this->selectArr, $fields);
		} else {
			array_push($this->selectArr, $fields);
		}
		return $this;
	}
	/**
	 * String to be selected
	 * @param 	string  	$sel 		string that will be used in the *SELECT* built
	 * @return  itself
	 */
	public function SelectStr($sel) {
		if(!empty($sel)){
			array_push($this->selectArr, $sel);
		}
		return $this;
	}
	/**
	 * Includes a field to select query to be added in the end of the clause
	 * @param 	string  	$sel 		string that will be added in the *SELECT* built
	 * @return  itself
	 */
	public function SelectExtra($sel){
		array_push($this->selectExtra, $sel);
		return $this;
	}
	/**
	 * Selects all the fields for an object
	 * @param 	object 		$obj 		object which its fields are going to be added in the *SELECT* built
	 * @return  itself
	 */
	public function SelectObj($obj){
		$fields = $obj->GetFieldsForSelect();
		array_push($this->selectDefaultArr, $fields);
		return $this;
	}
	/**
	 * Select multiple objects
	 * @param 	array<objects> 		$arrObj 		an array of objects that are going to be selected
	 * @return  itself
	 */
	public function SelectArrObj($arrObj){
		foreach ($arrObj as $key => $value) {
			$sThis = $value->GetFieldsForSelect();
			array_push($this->selectDefaultArr, $sThis);
		}
		return $this;
	}

	/**
	 * A Join to be included in the query
	 * @param 	string 			$joinGlue 			join to be included in the query
	 * @return  itself
	 */
	public function Join($joinGlue){
		array_push($this->joinArr, $joinGlue);
		return $this;
	}
	/**
	 * Gets automatically related object
	 * @param 	object or string 		$object 		object or string that will be returned in the query
	 * @param 	string 					$field  		field that is responsible for the relation (from the main object)
	 * @return  itself
	 */	
	public function HasOne($object, $field){
		try{
			if(!$this->obj_base) throw new MagratheaModelException("Object Base is not an object");
			$object = $this->GiveMeThisObjectCorrect($object);
			$this->SelectObj($object);
			$joinGlue = " INNER JOIN ".$object->GetDbTable()." ON ".$this->obj_base->GetDbTable().".".$field." = ".$object->GetDbTable().".".$object->GetPkName();
		} catch(Exception $ex){
			throw new MagratheaModelException("MagratheaQuery 'HasOne' must be used with MagratheaModels => ".$ex->getMessage());
		}
		array_push($this->joinArr, $joinGlue);
		array_push($this->joinType, "has_one");
		array_push($this->obj_array, $object);
		return $this;
	}
	/**
	 * Gets automatically related object
	 * @param 	object or string 		$object 		object or string that will be returned in the query
	 * @param 	string 					$field  		field that is responsible for the relation (from the main object)
	 * @return  itself
	 */	
	public function HasMany($object, $field){
		try{
			if(!$this->obj_base) throw new MagratheaModelException("Object Base is not an object");
			$object = $this->GiveMeThisObjectCorrect($object);
			$this->SelectObj($object);
			$joinGlue = " INNER JOIN ".$object->GetDbTable()." ON ".$object->GetDbTable().".".$field." = ".$this->obj_base->GetDbTable().".".$this->obj_base->GetPkName();
		} catch(Exception $ex){
			throw new MagratheaModelException("MagratheaQuery 'HasMany' must be used with MagratheaModels => ".$ex->getMessage());
		}
		array_push($this->joinArr, $joinGlue);
		array_push($this->joinType, "has_many");
		array_push($this->obj_array, $object);
		return $this;
	}

	/**
	 * Gets automatically related object
	 * @param 	object or string 		$object 		object or string that will be returned in the query
	 * @param 	string 					$field  		field that is responsible for the relation (from the other object)
	 * @return  itself
	 */	
	public function BelongsTo($object, $field){
		try{
			if(!$this->obj_base) throw new MagratheaModelException("Object Base is not an object");
			$object = $this->GiveMeThisObjectCorrect($object);
			$this->SelectObj($object);
			$joinGlue = " INNER JOIN ".$object->GetDbTable()." ON ".$object->GetDbTable().".".$field." = ".$this->obj_base->GetDbTable().".".$this->obj_base->GetPkName();
		} catch(Exception $ex){
			throw new MagratheaModelException("MagratheaQuery 'BelongsTo' must be used with MagratheaModels => ".$ex->getMessage());
		}
		array_push($this->joinArr, $joinGlue);
		array_push($this->joinType, "belongs_to");
		array_push($this->obj_array, $object);
		return $this;
	}
	/**
	 * Includes inner join
	 * @param 	string 		$table 		Table for inner join
	 * @param 	string 		$clause		Clause for where in the join
	 * @return  itself
	 */	
	public function Inner($table, $clause){
		try{
			$joinGlue = " INNER JOIN ".$table." ON ".$clause;
		} catch(Exception $ex){
			throw new MagratheaModelException("MagratheaQuery Exception => ".$ex->getMessage());
		}
		array_push($this->joinArr, $joinGlue);
		return $this;
	}
	/**
	 * Includes inner join with Object
	 * @param 	MagratheaModel 		$object 	object for inner join
	 * @return  itself
	 */	
	public function InnerObject($object, $clause){
		try{
			$object = $this->GiveMeThisObjectCorrect($object);
			$this->SelectObj($object);
			$joinGlue = " INNER JOIN ".$object->GetDbTable()." ON ".$clause;
		} catch(Exception $ex){
			throw new MagratheaModelException("MagratheaQuery Exception => ".$ex->getMessage());
		}
		array_push($this->joinArr, $joinGlue);
		return $this;
	}

	/**
	 * all the objects that are used in this query
	 * @return 		array 	the objects that we have here...
	 */
	public function GetObjArray(){
		return $this->obj_array;
	}
	/**
	 * all the joins that were built in this query
	 * @return 		array 	the objects that we have here...
	 */
	public function GetJoins(){
		if(count($this->joinArr) == 0) return array();
		$joins = array();
		foreach ($this->joinArr as $key => $join) {
			$j = array( 
				"type" => $this->joinType[$key], 
				"obj" => $this->obj_array[$key],
				"glue" => $this->joinArr[$key]
			);
			array_push($joins, $j);
		}
		return $joins;
	}

	/**
	 * Builds where!
	 * 	Is possible to send a string or an array, where the keys of the array will be the name of the fields which the query will be done
	 * @param 	string or array 	$whereSql  		String or array for building the query with
	 * @param 	string 				$condition 		glue condition ("AND" or "OR")
	 * @return  itself
	 */
	public function Where($whereSql, $condition="AND"){
		if(is_array($whereSql)){
			return $this->WhereArray($whereSql, $condition);
		}
		array_push($this->whereArr, $whereSql);
		return $this;
	}
	/**
	 * Builds where
	 * 	Same as *Where*, but accepting only array
	 * @param 	array 		$arr       	Array for building the query
	 * @param 	string 		$condition 	glue condition ("AND" or "OR")
	 * @return  itself
	 */
	public function WhereArray($arr, $condition = "AND"){
		$newWhere = $this->BuildWhere($arr, $condition);
		array_push($this->whereArr, $newWhere);
		return $this;
	}
	/**
	 * Builds where, receiving the column and the value
	 * @param 	string 		$where     		column
	 * @param 	string 		$field     		value for column sent
	 * @param 	string 		$condition 		glue condition ("AND" or "OR")
	 */
	public function W($where, $field, $condition = "AND"){
		$newWhere = $this->BuildWhere(array($where => $field), $condition);
		array_push($this->whereArr, $newWhere);
		return $this;
	}

	/**
	 * alias for *Order*
	 * @param string $o Order by used in query
	 * @return  itself
	 */
	public function OrderBy($o){ return $this->Order($o); }
	/**
	 * Order by...
	 * @param string $o Order by used in query
	 * @return  itself
	 */
	public function Order($o){
		$this->order = $o;
		return $this;
	}

	/**
	 * Let's put a limit on it to help our database? Yes!
	 * @param 	int 	$l 		limit
	 * @return  itself
	 */
	public function Limit($l){
		$this->limit = $l;
		return $this;
	}
	/**
	 * Which page?
	 * 	working altogether with *Limit*, to bring a specific page, with a specific amount of results
	 * @param int 	$p 		page to be retrieved
	 * @return  itself
	 */
	public function Page($p){ // there is a page zero.
		$this->page = $p;
		return $this;
	}

	/**
	 * Alias for *Group*
	 * @param 	string 		$g 		String to build the group
	 * @return  itself
	 */
	public function GroupBy($g){ return $this->Group($g); }
	/**
	 * Groupping the results...
	 * @param 	string 		$g 		String to build the group
	 * @return  itself
	 */
	public function Group($g){
		$this->group = $g;
		return $this;
	}

	/**
	 * ...and we're gonna build the query for you.
	 * 	After gathering all the information, this function returns to you
	 * 		a wonderful SQL query for be executed
	 * 		or to be hang in the wall of a gallery art exhibition,
	 * 		depending how good you are in building queries
	 * @return  	string 		Query!!!
	 */
	public function SQL(){
		$this->sql = "";
		$sqlSelect = $this->select;
		if(count($this->selectArr) > 0){
			$sqlSelect .= implode(', ', $this->selectArr);
		} else if(count($this->selectDefaultArr) > 0){
			$sqlSelect .= implode(', ', $this->selectDefaultArr);
		} else {
			$sqlSelect .= "*";
		}
		if(count($this->selectExtra) > 0){
			$sqlSelect .= ", ".implode(', ', $this->selectExtra);
		}
		$this->sql = $sqlSelect." FROM ".$this->tables;
		if(count($this->joinArr) > 0){
			$this->sql .= " ".implode(' ', $this->joinArr)." ";
		}
		$sqlWhere = $this->where;
		if(count($this->whereArr) > 0){
			$sqlWhere .= $this->where.implode(" AND ", $this->whereArr);
		}
		if(trim($sqlWhere)!=""){
			$this->sql .= " WHERE ".$sqlWhere;
		}
		if(trim($this->group)!=""){
			$this->sql .= " GROUP BY ".$this->group;
		}
		if(trim($this->order)!=""){
			$this->sql .= " ORDER BY ".$this->order;
		}
		if(trim($this->limit)!=""){
			$this->sql .= " LIMIT ".($this->page*$this->limit).", ".$this->limit;
		}

		return $this->sql;
	}

	/**
	 * How many? Tell me the amount!!!
	 * 		We get all the information that you sent to the function
	 * 			and, instead of returning the results,
	 * 			we count how many rows you will have back
	 * @return  int 	amount of rows
	 */
	public function Count(){
		$sqlCount = "SELECT COUNT(1) AS ok ";
		$sqlCount .= " FROM ".$this->tables;
		if(count($this->joinArr) > 0){
			$sqlCount .= " ".implode(' ', $this->joinArr)." ";
		}
		$sqlWhere = $this->where;
		if(count($this->whereArr) > 0){
			$sqlWhere .= $this->where.implode(" AND ", $this->whereArr);
		}
		if(trim($sqlWhere)!=""){
			$sqlCount .= " WHERE ".$sqlWhere;
		}
		if(trim($this->group)!=""){
			$sqlCount .= " GROUP BY ".$this->group;
		}
		return $sqlCount;
	}

	// STATIC AND HELPERS:
	/**
	 * @access private
	 * *INTERNAL USE*
	 * Build *Where* clause with information sent
	 * @param 	array 	$arr       	Array of conditions
	 * @param 	string 	$condition 	glue of the conditions ("AND" or "OR")
	 */
	public static function BuildWhere($arr, $condition){
		$first = true;
		$whereSql = "";
		foreach($arr as $field => $value){
			if( !$first ){ $whereSql .= " ".$condition; $first = false; }
			if($value === null)
				$whereSql .= " ".$field." is null ";
			else 
				$whereSql .= " ".$field." = '".$value."' ";
			$first = false;
		}
		return $whereSql;
	}
	/**
	 * @access private
	 * *INTERNAL USE*
	 * gets an array with "fields" and returns it with "table.fields"
	 * 	sample:
	 * 		array_walk($joinObjDbValues, 'BuildSelect', $joinObjTable);
	 * @param [type] &$value    [description]
	 * @param [type] $key       [description]
	 * @param [type] $tableName [description]
	 */
	public static function BuildSelect(&$value, $key, $tableName) { 
		$value = $tableName.".".$key." AS '".$tableName."/".$key."'"; 
	}
	/**
	 * @access private
	 * *INTERNAL USE*
	 * Gets the result and splits into its specific array for each object
	 * @param 	array 	$arr 	array to be splitted
	 */
	public static function SplitArrayResult($arr){
		$returnArray = array();
		foreach ($arr as $key => $value) {
			$position = strpos($key, '/');
			if(!$position) continue;
			$returnTable = substr($key, 0, $position);
			$returnField = substr($key, $position+1);

			if( @is_null($returnArray[$returnTable]) ) $returnArray[$returnTable] = array();
			$returnArray[$returnTable][$returnField] = $value;
		}
		return $returnArray;
	}

	/**
	 * toString
	 * @return 		string 		SQL 		(when applies)
	 */
	public function __toString(){
		return $this->SQL();
	}

}

/**
 * Extension of Magrathea Query for creating Insert queries
 */
class MagratheaQueryInsert extends MagratheaQuery {

	private $fieldNames;
	private $arrValues;

	public function __construct(){
		parent::__construct();
		$this->obj_array = array();
		$this->fieldNames = array();
		$this->arrValues = array();
		return $this;
	}

	/**
	 * Array with values
	 * 	send an array which each key represents a field and the value
	 * 		is the correspondent and we're gonna
	 * 		build it nicely to you! =)
	 * @param 	array 	$vals 		array with values
	 * @return  itself
	 */	
	public function Values($vals){
		foreach ($vals as $key => $value) {
			array_push($this->fieldNames, $key);
			array_push($this->arrValues, $value);
		}
		return $this;
	}

	/**
	 * ...and we're gonna build the query for you.
	 * 	After gathering all the information, this function returns to you
	 * 		a wonderful SQL query for be executed
	 * 		or to be hang in the wall of a gallery art exhibition,
	 * 		depending how good you are in building queries
	 * @return  	string 		Query!!!
	 */
	public function SQL(){
		$this->sql = "INSERT INTO ".$this->tables;
		$this->sql .= " (".implode(', ', $this->fieldNames).") ";
		$this->sql .= " VALUES ";
		$this->sql .= " ('".implode('\', \'', $this->arrValues)."') ";
		return $this->sql;
	}

}

/**
 * Extension of Magrathea Query for creating Update queries
 */
class MagratheaQueryUpdate extends MagratheaQuery {

	private $fields;
	private $rawFields;

	public function __construct(){
		parent::__construct();
		$this->obj_array = array();
		$this->fields = array();
		$this->rawFields = array();
		$this->where = "";
		$this->whereArr = array();
		return $this;
	}

	/**
	 * Set field for value
	 * @param 	string 		$field 		field
	 * @param 	string 		$value 		value for field sent
	 */
	public function Set($field, $value){
		$this->fields[$field] = $value;
		return $this;
	}

	/**
	 * Set raw condition for update
	 * @version 1.1 +
	 * @param 	string 		$field 		field
	 * @param 	string 		$value 		value for field sent
	 */
	public function SetRaw($condition){
		array_push($this->rawFields, $condition);
		return $this;
	}

	/**
	 * Set array of fields
	 * @param 	array 		$arr 		array fields
	 * @todo  merge array instead of replacing it
	 */
	public function SetArray($arr){
		$this->field = $arr;
		return $this;
	}

	/**
	 * ...and we're gonna build the query for you.
	 * 	After gathering all the information, this function returns to you
	 * 		a wonderful SQL query for be executed
	 * 		or to be hang in the wall of a gallery art exhibition,
	 * 		depending how good you are in building queries
	 * @return  	string 		Query!!!
	 */
	public function SQL(){
		$this->sql = "UPDATE ".$this->tables." SET ";
		$setsArray = array();
		foreach ($this->rawFields as $field) {
			array_push($setsArray, $field);
		}
		foreach ($this->fields as $field => $value) {
			array_push($setsArray, $field." = '".$value."'");
		}
		$this->sql .= implode(", ", $setsArray);
		$sqlWhere = $this->where;
		if(count($this->whereArr) > 0){
			$sqlWhere .= $this->where.implode(" AND ", $this->whereArr);
		}
		if(trim($sqlWhere)!=""){
			$this->sql .= " WHERE ".$sqlWhere;
		}
		return $this->sql;
	}

}

/**
 * Extension of Magrathea Query for creating Delete queries
 */
class MagratheaQueryDelete extends MagratheaQuery {

	public function __construct(){
		parent::__construct();
		$this->obj_array = array();
		$this->join = "";
		$this->joinArr = array();
		$this->where = "";
		$this->whereArr = array();
		$this->order = "";
		return $this;
	}

	/**
	 * ...and we're gonna build the query for you.
	 * 	After gathering all the information, this function returns to you
	 * 		a wonderful SQL query for be executed
	 * 		or to be hang in the wall of a gallery art exhibition,
	 * 		depending how good you are in building queries
	 * @return  	string 		Query!!!
	 */
	public function SQL(){
		$this->sql = "DELETE FROM ".$this->tables;
		if(count($this->joinArr) > 0){
			$this->sql .= " ".implode(' ', $this->joinArr)." ";
		}
		$sqlWhere = $this->where;
		if(count($this->whereArr) > 0){
			$sqlWhere .= $this->where.implode(" AND ", $this->whereArr);
		}
		if(trim($sqlWhere)!=""){
			$this->sql .= " WHERE ".$sqlWhere;
		}
		return $this->sql;
	}

}

?>