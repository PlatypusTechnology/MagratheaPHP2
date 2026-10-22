<?php

namespace Magrathea2\Exceptions;

use Exception;
use Magrathea2\Exceptions\MagratheaException;

/**
* Class for Magrathea Api Errors
*/
class MagratheaApiException extends MagratheaException {
	/** @var int|null HTTP status set with `SetStatus()`; null: `MagratheaApi` derives it from the code */
	public $status = null;
	public $code = 0;
	public function __construct($message = "Magrathea Api Error", $code=0, $data=null, $kill=true, ?\Exception $previous = null) {
		$this->code = $code;
		$this->killerError = (bool) $kill;
		if($data) {
			$this->_data = $data;
		}
		parent::__construct($message, $code, $previous);
	}
	/**
	 * Sets the HTTP status of the error response, instead of the one derived from the code.
	 * Only 400-599 is used; anything else falls back to the code.
	 * @param 	int		$status 	HTTP status
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
