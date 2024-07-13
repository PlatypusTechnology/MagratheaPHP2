<?php

namespace Magrathea2;
use Magrathea2\Singleton;

use Magrathea2\Admin\Features\User\AdminUser;
use Magrathea2\Admin\Features\User\AdminUserControl;
use Magrathea2\Exceptions\MagratheaApiException;

class MagratheaApiAuth extends MagratheaApiControl {

	public $tokenExpire = "7 days";

	public function GetHeaders() {
		return $this->getAuthorizationHeader();
	}

	public function ResponseLogin(AdminUser $user): array {
		$expire = date('Y-m-d h:i:s', strtotime(\Magrathea2\now().' + '.$this->tokenExpire));
		$payload = [
			"id" => intval(@$user->id),
			"email" => @$user->email,
			"role" => intval($user->role_id),
			"roleName" => $user ? $user->GetRoleName() : "-",
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
			"expires" => $expire,
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
