<?php

namespace Magrathea;

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
            $this->msg = $message->GetMessage();
        } else {
            $this->msg = $message ? $message : "Magrathea has failed... =(";
        }
        $this->message = $message;
        MagratheaDebugger::Instance()->Add($this);
        parent::__construct($message, $code, $previous);
    }

    public $killerError = true;
    public $msg = "";
    
    public function stackTrace() {
        return get_class($this).": {".$this->message."}\n@ ".$this->getFile().":".$this->getLine();
    }

    public function getMsg() { return $this->msg; }

    public function display(){
        echo "MAGRATHEA ERROR! <br/>";
        echo $this->message;
    }
}

class MagratheaApiException extends MagratheaException {
    protected $_data;
    public function __construct($message = "Magrathea Admin Error", $code = 0, \Exception $previous = null) {
        parent::__construct($message, $code, $previous);
    }


    public function SetData($data) {
        $this->_data = $data;
    }
    public function GetData() {
        return $this->_data;
    }

}

class MagratheaAdminException extends MagratheaException {
    public function __construct($message = "Magrathea Admin Error", $code = 0, \Exception $previous = null) {
        parent::__construct($message, $code, $previous);
    }    
}

class MagratheaConfigException extends MagratheaException {
    public function __construct($message = "Magrathea Config has failed... =(", $code = 0, \Exception $previous = null) {
        parent::__construct($message, $code, $previous);
    }    
}

class MagratheaDBException extends MagratheaException {
    public $query = "no_query_logged";
    public function __construct($message = "Magrathea Database has failed... =(", $code = 0, \Exception $previous = null) {
        parent::__construct($message, $code, $previous);
    }    
}

class MagratheaModelException extends MagratheaException {
    public function __construct($message = "Error in Magrathea Model", $code = 0, \Exception $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}

class MagratheaViewException extends MagratheaException {
    public function __construct($message = "Error in Magrathea Model", $code = 0, \Exception $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}


class MagratheaControllerException extends MagratheaException {
    public function __construct($message = "Error in Magrathea Control", $code = 0, \Exception $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}
