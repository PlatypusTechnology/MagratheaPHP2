<?php

namespace Magrathea2\Exceptions;
use Magrathea2\Exceptions\MagratheaException;

/**
* Class for Magrathea DB Errors
*/
class MagratheaDBException extends MagratheaException {
	public $query = "no_query_logged";
	public $values = null;
	public function __construct($message = "Magrathea Database has failed... =(", $query=null, $code=0, \Exception $previous = null) {
		$this->query = $query;
		parent::__construct($message, $code, $previous);
	}

	/**
	 * Adds data for debugging
	 * @param 	string 				$query		SQL query;
	 * @param 	array|string 	$values		SQL values
	 * @return	MagratheaDBException 		itself
	 */
	public function SetQueryData($query, $values): MagratheaDBException {
		$this->query = $query;
		$this->values = $values;
		return $this;
	}

	public function __toString() {
		$debug = "MagratheaDatabase ERROR => \n";
		$debug .= " query: [ ".$this->query." ] \n";
		if($this->values != null)
			$debug .= " values: [ ".implode(',', $this->values)." ] \n";
		$debug .= " error: [ ".$this->getMessage()." ] (code: ".$this->getCode().") \n";
		$debug .= " trace: ".$this->stackTrace();
		return $debug;
	}
}
