<?php

namespace Magrathea2\DB;

#######################################################################################
####
####	MAGRATHEA Query Update
####	v. 2.0
####
####	Magrathea by Paulo Henrique Martins
####	Platypus technology
####
####	updated: 2023-02 by Paulo Martins
####
#######################################################################################

/**
 * Extension of Magrathea Query for creating Update queries
 */
class QueryUpdate extends Query {

	private $fields;
	private $rawFields;

	public function __construct(){
		parent::__construct();
		$this->type = QueryType::Update;
		$this->obj_array = array();
		$this->fields = array();
		$this->rawFields = array();
		$this->where = "";
		$this->whereArr = array();
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
		$this->fields = $arr;
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
