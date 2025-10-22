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
 * Base Control for API endpoints.
 * Provides basic CRUD functionalities and helper methods for handling API requests,
 * such as authentication, data retrieval from requests, and caching.
 */
class MagratheaApiControl {

	/**
	 * @var string|null 	$model 		The class name of the model associated with this control.
	 */
	protected $model = null;
	/**
	 * @var object|null 	$service 	The service object for handling business logic.
	 */
	protected $service = null;
	/**
	 * @var object|null 	$userInfo 	Holds the decoded JWT payload (user information).
	 */
	public $userInfo = null;
	/**
	 * @var string 			$jwtEncodeType 	The encoding algorithm for JWT.
	 */
	public $jwtEncodeType = "HS256";

	/**
	 * Gets all HTTP headers from the request.
	 * @return array<string, string> An associative array of headers.
	 */
	public function GetAllHeaders() {
		$headers = [];
		foreach ($_SERVER as $name => $value){
			if (substr($name, 0, 5) == 'HTTP_') {
				$headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
			}
		}
		return $headers;
	}

	/**
	 * Gets the authorization token from the 'Authorization' header.
	 * It supports 'Basic' and 'Bearer' token types.
	 * @return string The token string.
	 * @throws MagratheaApiException If the token format is invalid.
	 */
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
	 * @param string|false $token The JWT token. If false, it tries to get it from headers.
	 * @return object|false Decoded token data as an object, or false if token is not found.
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

	/**
	 * Gets the secret key for JWT encoding/decoding from config.
	 * @return string The JWT secret key.
	 */
	public function GetSecret(): string {
		return Config::Instance()->Get("jwt_key");
	}

	/**
	 * Decodes a JWT token.
	 * @param string $token The JWT token to decode.
	 * @return object The decoded payload as an object.
	 */
	public function jwtDecode($token) {
		if(!$this->GetSecret()) throw new MagratheaApiException("JWT key empty", 500);
		return JWT::decode($token, new Key(strtr($this->GetSecret(), '-_', '+/'), $this->jwtEncodeType));
	}
	/**
	 * Encodes a payload into a JWT token.
	 * @param mixed $payload The payload to encode.
	 * @return string The generated JWT token.
	 */
	public function jwtEncode($payload) {
		if(!$this->GetSecret()) throw new MagratheaApiException("JWT key empty", 500);
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
	* Gets the Authorization header from various server sources.
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

	/**
	 * Reads and parses the raw input stream (php://input).
	 * @return mixed Parsed data. JSON is decoded into an associative array. Other content types are parsed as a string.
	 */
	public function GetPhpInput() {
		$putfp = fopen('php://input', 'r');
		$putData = '';
		while($data = fread($putfp, 1024))
			$putData .= $data;
		fclose($putfp);
		$contentType = @$_SERVER["CONTENT_TYPE"];
		$isJson = @substr($contentType, -4) == "json";
		if($isJson) return json_decode($putData, true);
		parse_str($putData, $rs);
		return $rs;
		// $jsonData = json_decode($json);
		// $data = [];
		// if(!$jsonData) return;
		// foreach ($jsonData as $key => $value) {
		// 	$data[str_replace('amp;', '', $key)] = $value;
		// }
		// return $data;
	}

	/**
	 * Gets data from a PUT request.
	 * @return array|null The PUT data as an array, or null if not a PUT request.
	 */
	public function GetPut() {
		if(@$_PUT) return $_PUT;
		if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
			return $this->GetPhpInput();
		}
		return null;
	}
	/**
	 * Gets data from a POST request.
	 * @return array|null The POST data as an array, or null if not a POST request.
	 */
	public function GetPost() {
		if(@$_POST) return $_POST;
		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			return $this->GetPhpInput();
		}
		return null;
	}

	/**
	 * Lists all items using the associated service.
	 * @return array An array of items.
	 * @throws \Exception If an error occurs during data retrieval.
	 */
	public function List() {
		try {
			return $this->service->GetAll();
		} catch(\Exception $ex) {
			throw $ex;
		}
	}
	/**
	 * Reads a single item by its ID, or lists all items if no ID is provided.
	 * @param array|false $params Parameters from the request, expecting an "id" key.
	 * @return object|array A model instance if ID is found, or an array of all items.
	 * @throws \Exception If an error occurs.
	 */
	public function Read($params=false) {
		try {
			if(!$params) return $this->List();
			$id = $params["id"];
			return new $this->model($id);
		} catch(\Exception $ex) {
			throw $ex;
		}
	}

	/**
	 * Creates a new item.
	 * @param array|false $data Data for the new item. If false, it uses POST data.
	 * @return object The created model instance.
	 * @throws \Exception If the creation fails.
	 */
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

	/**
	 * Updates an existing item.
	 * @param array $params Parameters from the request, expecting an "id" and/or "data".
	 * @return object The updated model instance.
	 * @throws \Exception If the ID is invalid or data is empty.
	 */
	public function Update($params) {
		$put = $this->GetPut();
		$data = @$params["data"] ? $params["data"] : $this->GetPut();
		$id = @$params["id"] ? $params["id"] : $data["id"];
		if(!$id) {
			throw new \Exception("Invalid Id for update", 500);
		}
		$m = new $this->model();
		$m->SetPK($id);
		$m->Assign($data);
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

	/**
	 * Deletes an item by its ID.
	 * @param array|false $params Parameters from the request, expecting an "id" key.
	 * @return bool True on successful deletion.
	 * @throws MagratheaApiException If no parameters are sent.
	 */
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

	/**
	 * Caches the current request's response.
	 * @param string $name Cache key/name.
	 * @param string|null $data Specific cache identifier to be appended to the name.
	 */
	public function Cache($name, $data=null) {
		$caller = debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS,2)[1]['function'];
		Logger::Instance()->Log("adding cache for [".$name."/".$data."] from (".@$_REQUEST["REQUEST_URI"]." ->".$caller.")");
		MagratheaCache::Instance()
			->Type("json")
			->Cache($name, $data);
	}
	/**
	 * Clears a specific cache entry.
	 * @param string $name Cache key/name.
	 * @param string|null $data Specific cache identifier.
	 */
	public function CacheClear($name, $data=null) {
		MagratheaCache::Instance()
			->Type("json")
			->Clear($name, $data);
	}

	/**
	 * Clears cache entries matching a pattern.
	 * @param string $pattern The pattern to match against cache keys.
	 */
	public function CacheClearPattern($pattern) {
		MagratheaCache::Instance()->RemovePattern($pattern);
	}

	/**
	 * Outputs raw text content and terminates the script.
	 * @param string $content The content to output.
	 */
	public function Raw($content) {
		header('Content-Type: text/plain; charset=utf-8');
		die($content);
	}

}
