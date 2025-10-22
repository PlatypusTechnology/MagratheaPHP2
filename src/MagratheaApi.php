<?php

namespace Magrathea2;

use Magrathea2\Authorization\AuthApi;
use Magrathea2\Exceptions\MagratheaApiException;

#######################################################################################
####
####    MAGRATHEA API PHP2
####    v. 2.0
####    Magrathea by Paulo Henrique Martins
####    Platypus technology
####    Magrathea2 created: 2022-11 by Paulo Martins
####
#######################################################################################

/**
 * Creates and manages a RESTful API server.
 * This class is responsible for routing requests, handling authorization,
 * and returning JSON responses.
 */
class MagratheaApi {

	/** @var string The default control to be called. */
	public $control = "Home";
	/** @var string The default action to be called. */
	public $action = "Index";
	/** @var array<string, mixed> The parameters from the request. */
	public $params = array();
	/** @var bool If true, the API will return the result instead of a JSON response. */
	public $returnRaw = false;
	/** @var string|null The base URL of the API. */
	public $apiAddress = null;

	/** @var MagratheaApi|null Singleton instance. */
	protected static $inst = null;

	/**
	 * @var MagratheaApiControl|null The class responsible for handling authorization logic.
	 */
	public ?MagratheaApiControl $authClass = null;
	/**
	 * @var string|null The name of the default authorization method to be called.
	 */
	public ?string $baseAuth = null;

	/**
	 * @var array<string, array<string, mixed>> Stores the defined API endpoints.
	 */
	private $endpoints = array();
	/**
	 * @var callable|null A fallback function to be executed if no route is matched.
	 */
	private $fallback = null;

	/**
	 * Constructor. Initializes the endpoint arrays for different HTTP methods.
	 */
	public function __construct(){
		$endpoints["GET"] = array();
		$endpoints["POST"] = array();
		$endpoints["PUT"] = array();
		$endpoints["DELETE"] = array();
	}

	/**
	 * Sets the base address for the API.
	 * @param string $addr		api url
	 * @return 	MagratheaApi	itself
	 */
	public function SetAddress($addr): MagratheaApi {
		$this->apiAddress = MagratheaHelper::EnsureTrailingSlash($addr);
		return $this;
	}
	/**
	 * Gets the base address of the API.
	 * @return string|null		api address
	 */
	public function GetAddress(): string|null {
		return $this->apiAddress;
	}

	/**
	 * @deprecated This method is deprecated. Initialization is now handled differently.
	 * Start the server, getting base calls
	 * @return 	MagratheaApi	itself
	 */
	public function Start(): MagratheaApi {
		if(!@empty($_GET["magrathea_control"])) self::$inst->control = $_GET["magrathea_control"];
		if(!@empty($_GET["magrathea_action"])) self::$inst->action = $_GET["magrathea_action"];
		if(!@empty($_GET["magrathea_params"])) self::$inst->params = $_GET["magrathea_params"];
		header('Access-Control-Allow-Headers: Access-Control-Allow-Origin, Content-Type, Authorization');
		header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS'); 
		header('Access-Control-Max-Age: 1000');
		header('Content-Type: application/json, charset=utf-8');
		return $this;
	}

	/**
	 * Includes CORS headers to allow requests from any origin.
	 * @return MagratheaApi itself for method chaining.
	 */
	public function AllowAll(): MagratheaApi {
		header('Access-Control-Allow-Origin: *');
		header('Access-Control-Allow-Credentials: true');
		header('Access-Control-Max-Age: 86400');    // cache for 1 day
		return $this;
	}

	/**
	 * Sets headers to disable browser and proxy caching.
	 * @return MagratheaApi itself for method chaining.
	 */
	public function DisableCache(): MagratheaApi {
		header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");
		return $this;
	}

	/**
	 * Includes CORS headers to allow requests from a specific list of origins.
	 * @param 	array<string> 	$allowedOrigins 	An array of allowed origin URLs.
	 * @return  MagratheaApi itself for method chaining.
	 */
	public function Allow(array $allowedOrigins): MagratheaApi{
		if (in_array(@$_SERVER["HTTP_ORIGIN"], $allowedOrigins)) {
			header('Access-Control-Allow-Origin: '.$_SERVER["HTTP_ORIGIN"]);
		}
		header('Access-Control-Allow-Credentials: true');
		return $this;
	}

	/** @var array<string> An array of headers to be accepted by `Access-Control-Allow-Headers`. */
	private array $acceptControlAllowHeaders = ["Authorization", "Content-Type"];
	/**
	 * Adds a header to the list of accepted headers for CORS.
	 * @param string|array<string> $accept The header(s) to add.
	 */
	public function AddAcceptHeaders($accept) {
		$this->acceptControlAllowHeaders = $accept;
	}
	/**
	 * Sets the `Access-Control-Allow-Headers` CORS header.
	 * @param array<string>|null $headers Additional headers to accept.
	 */
	public function AcceptHeaders(?array $headers = null) {
		if($headers != null) {
			array_push($this->acceptControlAllowHeaders, ...$headers);
		}
		header('Access-Control-Allow-Headers: '.implode(",", $this->acceptControlAllowHeaders));
	}

	/**
	 * Configures the API to return the raw result instead of a JSON-encoded response.
	 * @return MagratheaApi itself for method chaining.
	 */
	public function SetRaw(): MagratheaApi {
		$this->returnRaw = true;
		return $this;
	}

	/**
	 * Defines the base authorization handler.
	 * @param MagratheaApiControl $authClass The class containing authorization methods.
	 * @param string|null         $function  The name of the default authorization method.
	 * @return MagratheaApi itself for method chaining.
	 */
	public function BaseAuthorization(MagratheaApiControl $authClass, ?string $function): MagratheaApi {
		$this->authClass = $authClass;
		$this->baseAuth = $function;
		return $this;
	}

	/**
	 * Determines the authorization function to use for an endpoint.
	 * @param string|bool $auth The auth setting for the endpoint.
	 * @return string|bool|null The name of the auth function or false if public.
	 */
	private function getAuthFunction($auth) {
		if($auth === false) {
			return false;
		} else if($auth === true) {
			return $this->baseAuth;
		} else {
			return $auth;
		}		
	}

	/**
	 * Adds a standard set of CRUD (Create, Read, Update, Delete) endpoints for a model.
	 * @param string|array<string> $url     URL for the resource. Can be a string or an array with [singular, plural].
	 * @param MagratheaApiControl  $control The control class that handles the CRUD operations.
	 * @param string|bool          $auth    The authorization rule for these endpoints. `false` for public.
	 * @return MagratheaApi itself for method chaining.
	 */
	public function Crud(
		string|array $url,
		MagratheaApiControl $control,
		string|bool $auth=false,
	): MagratheaApi {
		if(is_array($url)) {
			$singular = $url[0];
			$plural = $url[1];
		} else {
			$singular = $url;
			$plural = $singular."s";
		}

		$authFunction = $this->getAuthFunction($auth);
		$this->endpoints["POST"][$plural] = [ "control" => $control, "action" => "Create", "auth" => $authFunction ];
		$this->endpoints["GET"][$plural] = [ "control" => $control, "action" => "Read", "auth" => $authFunction ];
		$this->endpoints["GET"][$singular."/:id"] = [ "control" => $control, "action" => "Read", "auth" => $authFunction ];
		$this->endpoints["PUT"][$singular."/:id"] = [ "control" => $control, "action" => "Update", "auth" => $authFunction ];
		$this->endpoints["DELETE"][$singular."/:id"] = [ "control" => $control, "action" => "Delete", "auth" => $authFunction ];
		return $this;
	}

	/**
	 * Adds a custom endpoint to the API.
	 * @param string               $method      HTTP method (GET, POST, PUT, DELETE).
	 * @param string               $url         The URL pattern for the endpoint.
	 * @param MagratheaApiControl|null $control The control class that handles the request.
	 * @param string|callable      $function    The method or function to be called.
	 * @param string|bool          $auth        The authorization rule. `false` for public.
	 * @param string|null          $description A description of the endpoint for documentation.
	 * @return MagratheaApi itself for method chaining.
	 */
	public function Add(
		string $method,
		string $url,
		?MagratheaApiControl $control,
		string|callable $function,
		string|bool $auth=false,
		?string $description=null
	): MagratheaApi {
		$method = strtoupper($method);
		$endpoint = [
			"control" => $control,
			"action" => $function,
			"auth" => $this->getAuthFunction($auth),
			"description" => $description,
		];
		$this->endpoints[$method][$url] = $endpoint;
		return $this;
	}

	/**
	 * Sets a fallback function to be called when no route matches the request.
	 * @param callable $fn The function to execute.
	 * @return MagratheaApi itself for method chaining.
	 */
	public function Fallback($fn): MagratheaApi {
		$this->fallback = $fn;
		return $this;
	}

	/**
	 * Prints a debug view of all registered endpoints.
	 * @return MagratheaApi itself for method chaining.
	 */
	public function Debug(): MagratheaApi {
		$baseUrls = $this->GetEndpoints();
		foreach ($baseUrls as $model => $methods) {
			echo "<h3>".$model.":</h3>";
			foreach ($methods as $method => $api) {
				echo "<h5>(".$method.")</h5>";
				echo "<ul>";
				foreach ($api as $url => $data) {
					$action = $data["action"];
					if($action instanceof \Closure) {
						$action = "[closure function]";
					}
					echo "<li>/".$url." => ".$data["control"]."->".$action.$data["args"]."; –– –– –– ".($data["auth"] ? "Authentication: (".$data["auth"].")" : "PUBLIC")."</li>";
				}
				echo "</ul>";
			}
			echo "<hr/>";
		}
		return $this;
	}

	/**
	 * Gets a structured array of all registered endpoints, grouped by control class.
	 * @return array<string, array<string, array<string, mixed>>> A multi-dimensional array of endpoints.
	 */
	public function GetEndpoints(): array {
		$baseUrls = array();
		foreach ($this->endpoints as $method => $functions) {
			foreach ($functions as $url => $fn) {
				$params = array();
				$urlPieces = explode("/", $url);
				if($fn["control"] == null) {
					$base = "anonymous";
				} else {
					$base = get_class($fn["control"]);
				}
				if(!@$baseUrls[$base]) $baseUrls[$base] = array();
				if(!@$baseUrls[$base][$method]) $baseUrls[$base][$method] = array();
				foreach ($urlPieces as $piece) {
					if($piece[0] == ":") array_push($params, substr($piece, 1));
				}

				$baseUrls[$base][$method][$url] = [
					"control" => $base,
					"action" => $fn["action"],
					"auth" => $fn["auth"],
					"args" => "(".(count($params) > 0 ? "['".implode("','", $params)."']" : "").")"
				];
			}
		}

		ksort($baseUrls);
		return $baseUrls;
	}

	/**
	 * Gets a detailed list of all endpoints, grouped by URL.
	 * @return array<string, array<int, array<string, mixed>>> A detailed array of endpoint configurations.
	 */
	public function GetEndpointsDetail() {
		$endpoints = [];
		foreach ($this->endpoints as $method => $functions) {
			foreach ($functions as $url => $fn) {
				$baseClass = $fn["control"] == null ? null : get_class($fn["control"]);
				if(!@$endpoints[$url]) $endpoints[$url] = array();
				$urlPieces = explode("/", $url);
				$params = [];
				foreach ($urlPieces as $piece) {
					if(empty($piece)) continue;
					if($piece[0] == ":") array_push($params, substr($piece, 1));
				}
				$data = [
					"url" => $url,
					"method" => $method,
					"function" => $baseClass,
					"params" => $params,
					"auth" => $fn["auth"],
					"description" => $fn["description"] ?? "",
				];
				array_push($endpoints[$url], $data);
			}
		}
		ksort($endpoints);
		return $endpoints;
	}

	/**
	 * Compares a registered route pattern with the current request URL.
	 * @param array<string> $route The route pattern segments.
	 * @param array<string> $url   The request URL segments.
	 * @return bool True if the URL matches the route pattern.
	 */
	private function CompareRoute($route, $url) {
		if($route == $url) return true;
		if(count($route) != count($url)) return false;
		if($route[0] != $url[0]) return false;
		for ($i=1; $i < count($route); $i++) {
			if($route[$i][0] == ":") {
				continue;
			} else {
				if($route[$i] != $url[$i]) return false;
			}
		}
		return true;
	}
	/**
	 * Finds a matching route for the given URL from the list of available API URLs.
	 * @param array<string>                $url     The request URL segments.
	 * @param array<string, array<mixed>>|false $apiUrls The available endpoints for the request method.
	 * @return string|false The matched route pattern or false if no match is found.
	 */
	private function FindRoute($url, $apiUrls) {
		if(!$apiUrls) return false;
		foreach ($apiUrls as $apiUrl => $value) {
			$route = explode("/", $apiUrl);
			if($this->CompareRoute($route, $url)) return $apiUrl;
		}
		return false;
	}

	/**
	 * Extracts parameters from a URL based on a route pattern.
	 * @param string        $route The matched route pattern (e.g., "user/:id").
	 * @param array<string> $url   The request URL segments.
	 * @return array<string, string>|false An associative array of parameters, or false if no params.
	 */
	private function GetParamsFromRoute($route, $url) {
		if(strpos($route, ':') == false) return false;
		$params = array();
		$r = explode("/", $route);
		for ($i=1; $i < count($r); $i++) {
			if($r[$i][0] == ":") {
				$paramName = substr($r[$i], 1);
				$params[$paramName] = $url[$i];
			}
		}
		return $params;
	}

	/**
	 * Gets the HTTP request method, handling OPTIONS requests for CORS preflight.
	 * @return string The HTTP method (e.g., "GET", "POST").
	 */
	private function getMethod() {
		$method = $_SERVER['REQUEST_METHOD'];
		if($method == "OPTIONS") {
			$realMethod = $_SERVER["HTTP_ACCESS_CONTROL_REQUEST_METHOD"];
			$this->AcceptHeaders();
			header('Access-Control-Allow-Methods: '.$realMethod);
			header('Access-Control-Max-Age: 1728000');
			header("Content-Length: 0");
			header('Content-Type: application/json');
			exit(0);
		} else { return $method; }
	}

	/**
	 * Starts the API, processes the request, and returns the response.
	 * @param bool $returnRaw If true, returns the raw data instead of a JSON response.
	 * @return mixed The result of the API execution.
	 */
	public function Run($returnRaw = false) {
		$urlCtrl = @$_GET["magrathea_control"];
		$action = @$_GET["magrathea_action"];
		$params = @$_GET["magrathea_params"];
		$method = $this->getMethod();
		$this->returnRaw = $returnRaw;

		$fullUrl = strtolower($urlCtrl."/".$action."/".$params);
		return $this->ExecuteUrl($fullUrl, $method);
	}

	/**
	 * Finds the matching endpoint for a given URL and method, and executes it.
	 * @param string $fullUrl The full request URL path.
	 * @param string $method  The HTTP request method.
	 * @return mixed The result of the endpoint execution.
	 */
	public function ExecuteUrl($fullUrl, $method="GET") {
		$url = explode("/", $fullUrl);
		$url = array_filter($url);

		if(count($url) == 0) {
			if(is_callable($this->fallback)) {
				return call_user_func($this->fallback);
			}
		}
		$endpoints = @$this->endpoints[$method];
		$route = $this->FindRoute($url, $endpoints);

		if(!$route) {
			return $this->Return404();
		}

		$ctrl = $endpoints[$route];

		$control = $ctrl["control"];
		$fn = $ctrl["action"];
		$auth = $ctrl["auth"];
		try {
			if($auth && $this->authClass) {
				if(!$this->authClass->$auth()) {
					$this->ReturnError(401, "Authorization Failed (".$auth." = false)", null, 401);
				}
			}
		} catch(MagratheaApiException $ex) {
			return $this->ReturnApiException($ex);
		} catch (\Exception $ex) {
			print_r($ex);
			return $this->ReturnError($ex->getCode(), $ex->getMessage(), $ex);
		}
		$params = $this->GetParamsFromRoute($route, $url);

		if($control != null && !method_exists($control, $fn)) {
			return $this->ReturnError(500, "Function (".$fn.") does not exists in class ".get_class($control));
		}
		try {
			$data = $this->GetData($control, $fn, $params);
			return $this->ReturnSuccess($data);
		} catch(MagratheaApiException $ex) {
			return $this->ReturnApiException($ex);
		} catch (\Exception $ex) {
			if($ex->getCode() == 0) {
				return $this->ReturnFail($ex);
			} else {
				return $this->ReturnError($ex->getCode(), $ex->getMessage(), $ex);
			}
		}
	}

	/**
	 * Calls the action on the control or the callable function for an endpoint.
	 * @param MagratheaApiControl|null $control The control instance.
	 * @param string|callable          $fn      The function/method name or callable.
	 * @param array<string, mixed>|null $params  Parameters to pass to the function.
	 * @return mixed The data returned by the executed function.
	 */
	private function GetData($control, $fn, $params=null) {
		if($control == null){
			if(!is_callable($fn)) {
				throw new MagratheaApiException("no callable function found for endpoint");
			}
			return call_user_func($fn, $params);
		}
		return $control->$fn($params);
	}

	/**
	 * Outputs a JSON response and terminates the script.
	 * @param array<mixed>|object $response The data to be encoded as JSON.
	 * @param int                 $code     The HTTP status code to send.
	 * @return mixed The raw response if `returnRaw` is true.
	 */
	public function Json($response, int $code=200){
		if($this->returnRaw) return $response;
		header('Content-Type: application/json');
		if($code != 200) {
			$status = array(
				200 => '200 OK',
				400 => '400 Bad Request',
				401 => 'Unauthorized',
				422 => 'Unprocessable Entity',
				500 => '500 Internal Server Error'
			);
			http_response_code($code);
			header('Status: '.$status[$code]);
		}
		echo json_encode($response);
		die;
	}

	/**
	 * Returns a JSON 404 Not Found error.
	 * @return mixed
	 */
	private function Return404() {
		$method = $_SERVER['REQUEST_METHOD'];
		$url = @$_GET["magrathea_control"];
		if(@$_GET["magrathea_action"]) $url.= "/".$_GET["magrathea_action"];
		if(@$_GET["magrathea_params"]) $url.= "/".$_GET["magrathea_params"];
		$message = "(".$method.") > /".$url." is not a valid endpoint";
		return $this->ReturnError(404, $message);
	}

	/**
	 * Formats and returns a MagratheaApiException as a JSON response.
	 * @param MagratheaApiException $exception The exception to handle.
	 * @return mixed
	 */
	public function ReturnApiException($exception) {
		$data = [
			"type" => "exception",
			"error" => $exception->GetData(),
			"code" => $exception->getCode(),
			"message" => $exception->getMessage(),
			"debug_level" => Debugger::Instance()->GetTypeDesc(),
		];
		if(Debugger::Instance()->GetType() == Debugger::DEV) {
			$data["stacktrace"] = debug_backtrace();
		}
		return $this->Json(array(
			"success" => false,
			"data" => $data,
		));
	}

	/**
	 * Returns a generic JSON error response.
	 * @param int          $code    The error code.
	 * @param string       $message The error message.
	 * @param mixed|null   $data    Additional error data.
	 * @param int          $status  The HTTP status code.
	 * @return mixed
	 */
	public function ReturnError($code=500, $message="", $data=null, $status=200) {
		$data = [
			"type" => "unknown error",
			"error" => $data,
			"code" => $code,
			"message" => $message,
			"debug_level" => Debugger::Instance()->GetTypeDesc(),
		];
		if(Debugger::Instance()->GetType() == Debugger::DEV) {
			$data["stacktrace"] = debug_backtrace();
		}
		return $this->Json(array(
			"success" => false,
			"data" => $data,
		), $status);
	}

	/**
	 * Returns a successful JSON response.
	 * @param mixed $data The payload to include in the response.
	 * @return mixed
	 */
	public function ReturnSuccess($data) {
		$rs = array(
			"success" => true,
			"data" => $data
		);
		$this->Cache($rs);
		return $this->Json($rs);
	}

	/**
	 * Returns a failure JSON response.
	 * @param mixed $data The error data or exception.
	 * @return mixed
	 */
	public function ReturnFail($data) {
		if(is_a($data, MagratheaApiException::class)) {
			$rs = [
				"message" => $data->getMessage()
			];
			if($data->getCode() != 0) {
				$rs["code"] = $data->getCode();
			}
			if(!empty($data->GetData())) {
				$rs["data"] = $data->GetData();
			}
		} else {
			$rs = $data;
		}
		return $this->Json(array(
			"success" => false,
			"data" => $rs
		));
	}

	/**
	 * Handles caching for the API response.
	 * @param array<string, mixed> $data The data to be cached.
	 */
	public function Cache($data) {
		MagratheaCache::Instance()->HandleApiCache($data);
	}

	/**
	 * Creates a simple `/health-check` endpoint.
	 */
	public function HealthCheck() {
		$this->Add("GET", "health-check", null, function() {
			return [
				"health" => "ok",
				"time" => now(),
			];
		}, false);
	}

}
