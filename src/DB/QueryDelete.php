<?php

namespace Magrathea2\DB;

#######################################################################################
####
####	MAGRATHEA Query Delete
####	v. 2.0
####
####	Magrathea by Paulo Henrique Martins
####	Platypus technology
####
####	updated: 2023-02 by Paulo Martins
####
#######################################################################################

/**
 * Extension of Magrathea Query for creating Delete queries
 */
class QueryDelete extends Query {

	public function __construct(){
		parent::__construct();
		$this->type = QueryType::Delete;
		$this->obj_array = array();
		$this->join = "";
		$this->joinArr = array();
		$this->where = "";
		$this->whereArr = array();
		$this->order = "";
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
