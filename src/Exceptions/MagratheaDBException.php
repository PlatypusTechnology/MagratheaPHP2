<?php

namespace Magrathea2\Exceptions;
use Magrathea2\Exceptions\MagratheaException;

/**
* Class for Magrathea DB Errors
*/
class MagratheaDBException extends MagratheaException {
	public $query = "no_query_logged";
	public $values = null;
	public $fullMessage;
	public function __construct($message = "Magrathea Database has failed... =(", $query=null, $code=0, ?\Exception $previous = null) {
		$this->query = $query ? $query : " - ";
		$this->fullMessage = $message;
		parent::__construct($this->CleanErrorMessage($message), $code, $previous);
	}

	/**
	 * Cleans an error message
	 * @return 	string 		error message without stack trace
	 */
	public function CleanErrorMessage(string $errorMessage): string {
		$duplicateEntryPosition = strpos($errorMessage, ' in /');
		if ($duplicateEntryPosition !== false) {
			$cleanedMessage = substr($errorMessage, 0, $duplicateEntryPosition);
			return $cleanedMessage;
		} else {
			return $errorMessage;
		}
	}

	/**
	 * returns the error message before cleaning stack trace
	 * @return 	string 		full error Message
	 */
	public function getFullMessage(): string {
		return $this->fullMessage;
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
