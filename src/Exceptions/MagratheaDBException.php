<?php

namespace Magrathea2\Exceptions;
use Magrathea2\Exceptions\MagratheaException;

/**
* Class for Magrathea DB Errors
*/
class MagratheaDBException extends MagratheaException {
    public $query = "no_query_logged";
    public function __construct($message = "Magrathea Database has failed... =(", $query=null, $code=0, \Exception $previous = null) {
        $this->query = $query;
        parent::__construct($message, $code, $previous);
    }    
}


?>