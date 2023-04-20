<?php

namespace Magrathea2\DB;
use Magrathea2\Singleton;

class QueryHelper {

	/**
	 * Gets a string Query and returns its type
	 * @param string $query   mySQL query
	 * @return QueryType
	 */
	static public function GetQueryType($query): QueryType {
		$query_parts = explode(' ', $query);

		switch (strtolower($query_parts[0])) {
			case 'select':
				return QueryType::Select;
			case 'insert':
				return QueryType::Insert;
			case 'update':
				return QueryType::Update;
			case 'delete':
				return QueryType::Delete;
		}
		return QueryType::Unknown;
	}

	/** 
	 * Gets a QueryType and returns it string description
	 * @param QueryType $type
	 * @return string type description
	 */
	static public function GetTypeString($type): string {
		switch($type) {
			case QueryType::Select: return "SELECT";
			case QueryType::Insert: return "INSERT";
			case QueryType::Update: return "UPDATE";
			case QueryType::Delete: return "DELETE";
		}
		return "";
	}
}

