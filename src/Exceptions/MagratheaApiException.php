<?php

namespace Magrathea2\Exceptions;
use Magrathea2\Exceptions\MagratheaException;

/**
* Class for Magrathea Api Errors
*/
class MagratheaApiException extends MagratheaException {
	public $status;
	public $code = 0;
	public function __construct($message = "Magrathea Api Error", $kill=true, $code=0, $data=null, \Exception $previous = null) {
		$this->code = $code;
		if($kill) {
			$this->killerError = true;
			$this->status = 500;
		} else {
			$this->killerError = false;
			$this->status = 200;
		}
		parent::__construct($message, $code, $previous);
	}
	/**
	 * Set status
	 * @param 	int		$status 	status (default: 500 for killer error; 200 for fail)
	 * @return MagratheaApiException
	 */
	public function SetStatus($st): MagratheaApiException {
		$this->status = $st;
		return $this;
	}

	public function __toString(): string {
		$rs = "[MAGRATHEA API EXCEPTION]";
		$rs .= "\nMessage: ".$this->message;
		return $rs;
	}
}
