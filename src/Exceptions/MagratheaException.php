<?php

namespace Magrathea2\Exceptions;
use Magrathea2\Debugger;

#######################################################################################
####
####    MAGRATHEA PHP2
####    v. 2.0
####    Magrathea by Paulo Henrique Martins
####    Platypus technology
####
#######################################################################################
####
####    Error Class
####    Error handling 
####    Magrathea1 created: 2012-12 by Paulo Martins
####    Magrathea2 created: 2022-11 by Paulo Martins
####
#######################################################################################

class MagratheaException extends \Exception {
	public function __construct($message, $code = 0, \Exception $previous = null) {
		if(is_a($message, "MagratheaException")) {
			$this->msg = ((object)$message)->GetMessage();
		} else {
			$this->msg = $message ? $message : "Magrathea has failed... =(";
		}
		$this->message = $message;
		Debugger::Instance()->Add($this);
		parent::__construct($message, $code, $previous);
	}

	public $killerError = false;
	public $msg = "";
	public $_data = null;

		/**
	 * Set data
	 * @param 	array|object		$data 	data
	 * @return MagratheaApiException
	 */
	public function SetData($data) {
		$this->_data = $data;
		return $this;
	}
	public function GetData() {
		return $this->_data;
	}

	
	public function stackTrace() {
		return get_class($this).": {".$this->message."}\n@ ".$this->getFile().":".$this->getLine();
	}

	public function getMsg() { return $this->getMessage(); }

	public function display(){
		echo "MAGRATHEA ERROR! <br/>";
		echo $this->message;
	}

	public function __toString(): string {
		$rs = "[MAGRATHEA API EXCEPTION]";
		$rs .= "\nMessage: ".$this->message;
		if($this->_data) {
			ob_start();
			var_dump($this->_data);
			$rs .= ob_get_clean();
		}
		return $rs;
	}
}
