<?php

namespace Magrathea2\Exceptions;
use Magrathea2\Exceptions\MagratheaException;

/**
* Class for Magrathea Config Errors
*/
class MagratheaConfigException extends MagratheaException {
	private $configFile;
	public function __construct($message = "Magrathea Config has failed... =(", $file=null, $code=0, \Exception $previous = null) {
		$this->configFile = $file;
		parent::__construct($message, $code, $previous);
	}

	public function __toString(): string {
		$rs = "";
		$rs = "[MAGRATHEA CONFIG EXCEPTION]";
		$rs .= "\nMessage: ".$this->message;
		$rs .= "\nFile: ".$this->configFile;
		return $rs;
	}
}
