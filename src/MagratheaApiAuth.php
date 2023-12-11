<?php

namespace Magrathea2;
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;
use Magrathea2\Singleton;

use Magrathea2\Admin\Features\User\AdminUser;
use Magrathea2\Admin\Features\User\AdminUserControl;
use Magrathea2\Exceptions\MagratheaApiException;

class MagratheaApiAuth extends MagratheaApiControl {

	public $userInfo = null;
	public $jwtEncodeType = "HS256";
	public $tokenExpire = "7 days";

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
	public function GetHeaders() {
		return $this->getAuthorizationHeader();
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

	public function ResponseLogin(AdminUser $user): array {
		$expire = date('Y-m-d h:i:s', strtotime(\Magrathea2\now().' + '.$this->tokenExpire));
		$payload = [
			"id" => @$user->id,
			"email" => @$user->email,
		];
		$jwtRefresh = $this->jwtEncode($payload);
		$payload["refresh"] = $jwtRefresh;
		$payload["expire"] = $expire;
		$jwt = $this->jwtEncode($payload);
		return [
			"refresh_token" => $jwtRefresh,
			"token" => $jwt,
			"user" => $user
		];
	}

	public function ResponsePayload($payload): array {
		$expire = date('Y-m-d h:i:s', strtotime(\Magrathea2\now().' + '.$this->tokenExpire));
		$jwtRefresh = $this->jwtEncode($payload);
		$payload["refresh"] = $jwtRefresh;
		$payload["expire"] = $expire;
		$jwt = $this->jwtEncode($payload);
		return [
			"refresh_token" => $jwtRefresh,
			"token" => $jwt,
			"data" => $payload
		];
	}

	public function Refresh(): array {
		$refresh_token = $_GET["refresh_token"];
		$info = $this->GetTokenInfo();
		if(empty($info)) throw new MagratheaApiException("invalid token", 4011);
		$saved_refresh = $info->refresh;
		if($refresh_token != $saved_refresh) {
			throw new MagratheaApiException("refresh token invalid", 4015);
		}
		$user = new AdminUser($info->id);
		$control = new AdminUserControl();
		$control->SetLoginAsNow($user);
		try {
			return $this->ResponseLogin($user);
		} catch(MagratheaApiException $ex) {
			throw new MagratheaApiException($ex->getMessage(), 500);
		}
	}

	/**
	 * check if token is expired
	 * @return bool 	is expired?
	 */
	public function CheckExpire(): bool {
		$timeStampExp = strtotime($this->userInfo->expire);
		$timeStampNow = strtotime(now());
		if($timeStampExp < $timeStampNow) {
			$ex = new MagratheaApiException("token expired", 4010);
			$ex->SetData(["expiredAt" => $timeStampExp]);
			throw $ex;
		}
		return true;
	}

	/**
	 * check if user is logged with used token
	 * @return bool		is logged?
	 */
	public function IsLogged(): bool {
		try {
			if($this->GetTokenInfo()) {
				return $this->CheckExpire();
			}
			return false;
		} catch(MagratheaApiException $ex) {
			throw new MagratheaApiException($ex->getMessage(), 401);
		}
	}

}
