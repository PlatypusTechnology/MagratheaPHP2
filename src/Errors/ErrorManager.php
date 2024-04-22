<?php

namespace Magrathea2\Errors;

use Exception;
use Magrathea2\Singleton;

#######################################################################################
####
####    MAGRATHEA PHP2
####    v. 2.0
####    Magrathea by Paulo Henrique Martins
####    Platypus technology
####    ErrorManeger created: 2024-04 by Paulo Martins
####
#######################################################################################

/**
* This class will provide you the quickest access possible to the magrathea.conf config file.
*/
class ErrorManager extends Singleton {

	public function DisplayException(Exception $ex) {
		$errorMessage = $ex->getMessage();
		include(__DIR__."/view-exception.php");
		die;
	}

}
