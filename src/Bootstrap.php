<?php

namespace Magrathea2\Bootstrap;

#######################################################################################
####
####    MAGRATHEA PHP2
####    v. 2.0
####    Magrathea by Paulo Henrique Martins
####    Platypus technology
####    Magrathea2 created: 2022-11 by Paulo Martins
####
#######################################################################################

/**
 * Class for handling and loading Magrathea's Admin system
 * More information on @link http://magrathea.platypusweb.com.br/admin_php.php
 */
class Bootstrap { 

	/**
	 * The title for your adming goes here
	 * @var string
	 */
	public $title = "Magrathea PHP 2";
	/**
	 * If you want to send any var inside the admin system, you can use an array of args through here!
	 * @var array
	 */
	public $args = array();

    public function Load() {
        echo "Loading Bootstrap";
    }

}

?>