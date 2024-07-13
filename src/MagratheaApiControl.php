<?php

namespace Magrathea2;
use Magrathea2\Exceptions\MagratheaApiException;
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

#######################################################################################
####
####    MAGRATHEA API CONTROL PHP2
####    v. 2.0
####    Magrathea by Paulo Henrique Martins
####    Platypus technology
####    Magrathea2 created: 2023-04 by Paulo Martins
####
#######################################################################################

/**
* 
* Control for Create, Read, List, Update, Delete
**/
class MagratheaApiControl {

	protected $model = null;
	protected $service = null;
	public $userInfo = null;
	public $jwtEncodeType = "HS256";

	public function GetAllHeaders() {
		$headers = [];
		foreach ($_SERVER as $name => $value){
			if (substr($name, 0, 5) == 'HTTP_') {
				$headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
			}
		}
		return $headers;
	}

	public function GetAuthorizationToken() {
		$token = $this->GetAllHeaders()["Authorization"];
		$gotToken = false;
		if (substr($token, 0, 6) == 'Basic ') {
			$token = trim(substr($token, 6));
			$gotToken = true;
		}
		if (substr($token, 0, 7) == 'Bearer ') {
			$token = trim(substr($token, 7));
			$gotToken = true;
		}
		if(!$gotToken) {
			$ex = new MagratheaApiException("Invalid Token: [".$token."]", 400, [ "token" => $token ], true);
			$ex->SetStatus(401);
			throw $ex;
		}
		return $token;
	}

	/**
	 * Get token data
	 * @param 	string 		$token
	 * @return 	any				token data
	 */
	public function GetTokenInfo($token=false) {
		if(!$token) {
			$token = $this->getTokenByType("Bearer");
		}
		if(!$token) {
			$token = $this->getTokenByType("Basic");
		}
		if(!$token) return false;
		$this->userInfo = $this->jwtDecode($token);
		return $this->userInfo;
	}

	public function GetSecret(): string {
		return Config::Instance()->Get("jwt_key");
	}

	public function jwtDecode($token) {
		return JWT::decode($token, new Key(strtr($this->GetSecret(), '-_', '+/'), $this->jwtEncodeType));
	}
	public function jwtEncode($payload) {
		return JWT::encode($payload, strtr($this->GetSecret(), '-_', '+/'), $this->jwtEncodeType);
	}

	/**
	* get access token from header
	*	@param string $type			can be 'Bearer' (for Berarer token) or 'Basic' (for Basic token)
	*	@return string|null			token
	* */
	public function getTokenByType($type): string|null {
		$headers = $this->getAuthorizationHeader();
		// HEADER: Get the access token from the header
		if (!empty($headers)) {
			if (preg_match('/'.$type.'\s(\S+)/', $headers, $matches)) {
				return $matches[1];
			}
		}
		return null;
	}

	/**
	* Get header Authorization
	* */
	public function getAuthorizationHeader(){
		$headers = null;
		if (isset($_SERVER['Authorization'])) {
			$headers = trim($_SERVER["Authorization"]);
		} else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx or fast CGI
			$headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
		} elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) { // htaccess rules
			$headers = trim($_SERVER["REDIRECT_HTTP_AUTHORIZATION"]);
		} elseif (function_exists('apache_request_headers')) {
			$requestHeaders = apache_request_headers();
			// Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
			$requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
			//print_r($requestHeaders);
			if (isset($requestHeaders['Authorization'])) {
				$headers = trim($requestHeaders['Authorization']);
			}
		}
		return $headers;
	}





	public function GetPhpInput() {
		$json = file_get_contents('php://input');
		$jsonData = json_decode($json);
		$data = [];
		if(!$jsonData) return;
		foreach ($jsonData as $key => $value) {
			$data[str_replace('amp;', '', $key)] = $value;
		}
		return $data;
	}

	public function GetPut() {
		if(@$_PUT) return $_PUT;
		if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
			return $this->GetPhpInput();
		}
		return null;
	}
	public function GetPost() {
		if(@$_POST) return $_POST;
		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			return $this->GetPhpInput();
		}
		return null;
	}

	public function List() {
		try {
			return $this->service->GetAll();
		} catch(\Exception $ex) {
			throw $ex;
		}
	}
	public function Read($params=false) {
		try {
			if(!$params) return $this->List();
			$id = $params["id"];
			return new $this->model($id);
		} catch(\Exception $ex) {
			throw $ex;
		}
	}

	public function Create($data=false) {
		if(!$data) $data = $this->GetPost();
		$m = new $this->model();
		if(@$data["id"]) unset($data["id"]);
		foreach ($data as $key => $value) {
			if(property_exists($m, $key)) {
				$m->$key = $value;
			}
		}
		try {
			if($m->Insert()) {
				return $m;
			}
		} catch(\Exception $ex) {
			throw $ex;
		}
	}

	public function Update($data=false) {
		if(!$data) $data = $this->GetPut();
		$id = @$data["id"];
		$m = new $this->model($id);

		if(!$data) throw new \Exception("Empty Data Sent", 500);
		foreach ($data as $key => $value) {
			if(property_exists($m, $key)) {
				$m->$key = $value;
			}
		}
		try {
			if($m->Update()) return $m;
		} catch(\Exception $ex) {
			throw $ex;
		}
	}

	public function Delete($params=false) {
		if(!$params) throw new MagratheaApiException("Empty Data Sent", 500);
		$id = $params["id"];
		$m = new $this->model($id);
		try {
			return $m->Delete();
		} catch(\Exception $ex) {
			throw $ex;
		}
	}

	public function Cache($name, $data=null) {
		MagratheaCache::Instance()
			->Type("json")
			->Cache($name, $data);
	}

}
