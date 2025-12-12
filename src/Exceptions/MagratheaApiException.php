<?php

namespace Magrathea2\Exceptions;

use Exception;
use Magrathea2\Exceptions\MagratheaException;

/**
* Class for Magrathea Api Errors
*/
class MagratheaApiException extends MagratheaException {
	public $status;
	public $code = 0;
	public function __construct($message = "Magrathea Api Error", $code=0, $data=null, $kill=true, ?\Exception $previous = null) {
		$this->code = $code;
		if($kill) {
			$this->killerError = true;
			$this->status = 500;
		} else {
			$this->killerError = false;
			$this->status = 200;
		}
		if($data) {
			$this->_data = $data;
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

	public static function FromException(Exception $ex, $code=null, $data=null) {
		return new MagratheaApiException(
			$ex->getMessage(),
			$code ? $code : $ex->getCode(),
			$data ? $data : $ex
		);
	}

}
