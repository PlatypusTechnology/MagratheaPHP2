<?php

namespace Magrathea2\DB;

#######################################################################################
####
####	MAGRATHEA Query Insert
####	v. 2.0
####
####	Magrathea by Paulo Henrique Martins
####	Platypus technology
####
####	updated: 2023-02 by Paulo Martins
####
#######################################################################################

/**
 * Extension of Magrathea Query for creating Insert queries
 */
class QueryInsert extends Query {

	private $fieldNames;
	private $arrValues;

	public function __construct(){
		parent::__construct();
		$this->type = QueryType::Insert;
		$this->obj_array = array();
		$this->fieldNames = array();
		$this->arrValues = array();
	}

	/**
	 * Array with values
	 * 	send an array which each key represents a field and the value
	 * 		is the correspondent and we're gonna
	 * 		build it nicely to you! =)
	 * @param 	array 	$vals 		array with values
	 * @return  QueryInsert
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
		$this->sql = "INSERT INTO `".$this->tables."`";
		$this->sql .= " (`".implode('`, `', $this->fieldNames)."`) ";
		$this->sql .= " VALUES ";
		$this->sql .= " ('".implode('\', \'', $this->arrValues)."') ";
		return $this->sql;
	}

}
