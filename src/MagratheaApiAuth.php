<?php

namespace Magrathea2;
use Magrathea2\Singleton;

use Magrathea2\Admin\Features\User\AdminUser;
use Magrathea2\Admin\Features\User\AdminUserControl;
use Magrathea2\Exceptions\MagratheaApiException;

/**
 * Class for handling API Authentication using JWT.
 * Extends MagratheaApiControl.
 */
class MagratheaApiAuth extends MagratheaApiControl {

	/**
	 * @var string  $tokenExpire  Default token expiration time.
	 */
	public $tokenExpire = "7 days";

	/**
	 * Gets the authorization header.
	 * @return string|null  Authorization header content.
	 */
	public function GetHeaders() {
		return $this->getAuthorizationHeader();
	}

	/**
	 * Logs in an admin user.
	 * @param string $email     User email.
	 * @param string $password  User password.
	 * @return array            Array with token and user data.
	 * @throws MagratheaApiException  If login fails.
	 * @throws \Exception           For other exceptions.
	 */
	public function AdminUserLogin(string $email, string $password): array {
		$control = new AdminUserControl();
		try {
			$rs = $control->Login($email, $password);
			if(!$rs['success']) {
				throw new MagratheaApiException($rs["message"], 403);
			}
			return $this->ResponseLogin($rs["user"]);
		} catch(\Exception $ex) {
			throw $ex;
		}
	}

	/**
	 * Creates a response payload for a user.
	 * @param array $user   User data to be encoded in the token.
	 * @deprecated
	 * @return array        Array containing refresh token, token, and user data.
	 */
	public function ResponseUserPayload(array $user): array {
		$expire = date('Y-m-d h:i:s', strtotime(\Magrathea2\now().' + '.$this->tokenExpire));
		$jwtRefresh = $this->jwtEncode($user);
		$payload["refresh"] = $jwtRefresh;
		$payload["expire"] = $expire;
		$jwt = $this->jwtEncode($payload);
		return [
			"refresh_token" => $jwtRefresh,
			"token" => $jwt,
			"user" => $user,
		];
	}

	/**
	 * Creates a login response for an AdminUser.
	 * @param AdminUser $user The user object.
	 * @return array          Array with token and user data.
	 */
	public function ResponseLogin(AdminUser $user): array {
		$payload = [
			"id" => intval(@$user->id),
			"email" => @$user->email,
			"role" => intval($user->role_id),
			"roleName" => $user ? $user->GetRoleName() : "-",
		];
		return $this->ResponsePayload($payload);
	}

	/**
	 * Creates a response with a generic payload.
	 * @param mixed $payload  The payload to encode.
	 * @return array          Array containing refresh token, token, expiration, and data.
	 */
	public function ResponsePayload($payload): array {
		$expire = date('Y-m-d h:i:s', strtotime(\Magrathea2\now().' + '.$this->tokenExpire));
		$jwtRefresh = $this->jwtEncode($payload);
		$payload["refresh"] = $jwtRefresh;
		$payload["expire"] = $expire;
		$jwt = $this->jwtEncode($payload);
		return [
			"refresh_token" => $jwtRefresh,
			"token" => $jwt,
			"expires" => $expire,
			"data" => $payload
		];
	}

	/**
	 * Refreshes a token.
	 * @return array  New token information.
	 * @throws MagratheaApiException  If the token is invalid or refresh fails.
	 * @throws \Exception           For other exceptions.
	 */
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
	 * @return bool   true if not expired
	 * @throws MagratheaApiException if token is expired
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
	 * @return bool   is logged?
	 * @throws MagratheaApiException if token is invalid or expired
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
