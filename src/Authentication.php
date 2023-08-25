<?php

namespace Magrathea2;

use \Firebase\JWT\JWT;

#######################################################################################
####
####    MAGRATHEA AUTHENTICATION PHP2
####    v. 2.0
####    Magrathea by Paulo Henrique Martins
####    Platypus technology
####    Magrathea2 created: 2023-08 by Paulo Martins
####
#######################################################################################

/**
 * Authentication class
 */
class Authentication {

	public function GenerateToken($payload) {
		$key = Config::Instance()->Get("jwt_key");
		$token = $jwt = JWT::encode($payload, $key);
		return array('source' => $payload, 'token' => $token);
	}

	public function Token() {
//		$token = $this->GetAuthorizationBearer();
		$token = "";

		$key = Config::Instance()->Get("jwt_key");
		$data = $jwt = JWT::decode($token, $key, array('HS256'));
		return $data;
	}

}
