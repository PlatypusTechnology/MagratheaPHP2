<?php

namespace Magrathea2\Admin;

use Magrathea2\Singleton;

#######################################################################################
####
####    MAGRATHEA PHP2
####    v. 2.0
####    Magrathea by Paulo Henrique Martins
####    Platypus technology
####    Admin created: 2022-12 by Paulo Martins
####
#######################################################################################

/**
 * Class for CSRF token management on Magrathea's Admin
 */
class AdminCsrf extends Singleton {

	private $sessionKey = "magrathea_csrf_token";

	/**
	 * Gets the current session's CSRF token, creating one if it doesn't exist yet
	 * @return 	string 		token
	 */
	public function GetToken(): string {
		if (empty($_SESSION[$this->sessionKey])) {
			$this->Regenerate();
		}
		return $_SESSION[$this->sessionKey];
	}

	/**
	 * Forces a fresh CSRF token, overwriting any existing one
	 * @return 	string 		new token
	 */
	public function Regenerate(): string {
		$_SESSION[$this->sessionKey] = bin2hex(random_bytes(32));
		return $_SESSION[$this->sessionKey];
	}

	/**
	 * Validates a token against the one stored in session
	 * @param 	?string 	$token 		token to validate
	 * @return 	bool
	 */
	public function Validate(?string $token): bool {
		if (empty($token) || empty($_SESSION[$this->sessionKey])) return false;
		return hash_equals($_SESSION[$this->sessionKey], $token);
	}

}

?>