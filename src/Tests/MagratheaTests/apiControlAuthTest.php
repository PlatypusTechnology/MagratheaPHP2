<?php

namespace Magrathea2 {
	// Intercepts setcookie() calls made from within MagratheaApiControl (which is
	// in this namespace) so SetAuthCookie()/ClearAuthCookie() can be asserted on
	// without a real SAPI response — PHP CLI reports headers_sent() === true
	// immediately, so the real setcookie() is a silent no-op under PHPUnit.
	if (!function_exists(__NAMESPACE__."\\setcookie")) {
		function setcookie($name, $value = "", $options = []) {
			$GLOBALS["__setcookie_calls"][] = ["name" => $name, "value" => $value, "options" => $options];
			return true;
		}
	}
}

namespace {

use Magrathea2\MagratheaApiControl;
use Magrathea2\Tests\TestsHelper;
use Magrathea2\Config;

class CookieEnabledApiControl extends MagratheaApiControl {
	protected ?string $cookieName = "test_session";
}

class MagratheaApiControlAuthTest extends \PHPUnit\Framework\TestCase {

	protected function setUp(): void {
		parent::setUp();
		Config::Instance()->SetConfigFile("magrathea-test.conf");
		Config::Instance()->SetPath(__DIR__."/test-dump");
		$GLOBALS["__setcookie_calls"] = [];
	}

	protected function tearDown(): void {
		unset($_COOKIE["test_session"]);
		unset($_SERVER["HTTP_AUTHORIZATION"]);
		unset($_SERVER["Authorization"]);
		$GLOBALS["__setcookie_calls"] = [];
		parent::tearDown();
	}

	private function makeToken(MagratheaApiControl $control, array $payload = []): string {
		return $control->jwtEncode(array_merge(["id" => 42, "exp" => time() + 3600], $payload));
	}

	public function testCookieFallbackDecodesTokenWhenCookieNameOverridden() {
		TestsHelper::Print("Testing GetTokenInfo() falls back to a cookie when \$cookieName is overridden");
		$control = new CookieEnabledApiControl();
		$token = $this->makeToken($control);
		$_COOKIE["test_session"] = $token;

		$info = $control->GetTokenInfo();

		$this->assertNotFalse($info);
		$this->assertEquals(42, $info->id);
	}

	public function testCookieFallbackIsOptInAndDisabledByDefault() {
		TestsHelper::Print("Testing GetTokenInfo() ignores the cookie when \$cookieName is left at the default (null)");
		$control = new MagratheaApiControl();
		$token = $this->makeToken($control);
		$_COOKIE["test_session"] = $token;

		$info = $control->GetTokenInfo();

		$this->assertFalse($info);
	}

	public function testBearerHeaderTakesPriorityOverCookie() {
		TestsHelper::Print("Testing Bearer header wins over a cookie when both are present");
		$control = new CookieEnabledApiControl();
		$bearerToken = $this->makeToken($control, ["id" => 1]);
		$cookieToken = $this->makeToken($control, ["id" => 2]);
		$_SERVER["HTTP_AUTHORIZATION"] = "Bearer ".$bearerToken;
		$_COOKIE["test_session"] = $cookieToken;

		$info = $control->GetTokenInfo();

		$this->assertEquals(1, $info->id);
	}

	public function testSetAuthCookieEmitsExpectedCookieAttributes() {
		TestsHelper::Print("Testing SetAuthCookie() sets Domain/HttpOnly/SameSite/expiry matching the JWT's exp claim");
		$control = new CookieEnabledApiControl();
		$exp = time() + 7200;
		$token = $this->makeToken($control, ["exp" => $exp]);

		$control->SetAuthCookie($token, ".example.com");

		$this->assertCount(1, $GLOBALS["__setcookie_calls"]);
		$call = $GLOBALS["__setcookie_calls"][0];
		$this->assertEquals("test_session", $call["name"]);
		$this->assertEquals($token, $call["value"]);
		$this->assertEquals(".example.com", $call["options"]["domain"]);
		$this->assertTrue($call["options"]["httponly"]);
		$this->assertEquals("Lax", $call["options"]["samesite"]);
		$this->assertEquals($exp, $call["options"]["expires"]);
		// $forceSecureCookie defaults to true and wasn't overridden, so the cookie should be marked Secure
		$this->assertTrue($call["options"]["secure"]);
	}

	public function testClearAuthCookieExpiresInThePast() {
		TestsHelper::Print("Testing ClearAuthCookie() expires the cookie in the past");
		$control = new CookieEnabledApiControl();

		$control->ClearAuthCookie(".example.com");

		$call = $GLOBALS["__setcookie_calls"][0];
		$this->assertEquals("test_session", $call["name"]);
		$this->assertEquals("", $call["value"]);
		$this->assertLessThan(time(), $call["options"]["expires"]);
	}

}

}
