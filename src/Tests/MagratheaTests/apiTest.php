<?php

use Magrathea2\MagratheaApi;
use Magrathea2\MagratheaApiControl;
use Magrathea2\Tests\TestsHelper;
use Magrathea2\DB\Database;
use Magrathea2\Exceptions\MagratheaApiException;
use Magrathea2\Exceptions\MagratheaDBException;
use Magrathea2\Exceptions\MagratheaDBConnectionLostException;

// the CLI running the suite may not have the mysqli extension
if(!class_exists("mysqli_sql_exception")) {
	class mysqli_sql_exception extends RuntimeException {}
}

class FailingHealthCheckDatabaseMock extends Database {
	public function OpenConnectionPlease(): bool {
		throw new \Exception("db down");
	}
}

class RawModeAuthMock extends \Magrathea2\MagratheaApiAuth {
	public function Deny($params): bool {
		$this->SetAuthenticationErrorData("no token");
		return false;
	}
	public function Allow($params): bool {
		return true;
	}
}

class RawModeProtectedControl extends MagratheaApiControl {
	public int $calls = 0;
	public function Secret($params) {
		$this->calls++;
		return "secret data";
	}
}

class StatusCapturingApi extends MagratheaApi {
	public ?int $status = null;
	public function Json($response, int $code=200) {
		$this->status = $code;
		return $response;
	}
}

class ThrowingControl extends MagratheaApiControl {
	public ?\Throwable $toThrow = null;
	public function Fail($params) {
		throw $this->toThrow;
	}
}

class ThrowingAuth extends \Magrathea2\MagratheaApiAuth {
	public ?\Throwable $toThrow = null;
	public function Fail($params): bool {
		throw $this->toThrow;
	}
}

class MagratheaApiTest extends \PHPUnit\Framework\TestCase {

	private MagratheaApi $api;
	private $debugType;

	protected function setUp(): void {
		parent::setUp();
		$this->api = new MagratheaApi();
		$this->resetDatabaseSingleton();
		// DEV echoes every new exception: output here breaks header-dependent tests that run later
		$this->debugType = \Magrathea2\Debugger::Instance()->GetType();
		\Magrathea2\Debugger::Instance()->SetType(\Magrathea2\Debugger::NONE);
	}

	protected function tearDown(): void {
		\Magrathea2\Debugger::Instance()->SetType($this->debugType);
		$this->resetDatabaseSingleton();
		parent::tearDown();
	}

	private function resetDatabaseSingleton(): void {
		$property = new \ReflectionProperty(\Magrathea2\Singleton::class, "instance");
		$property->setAccessible(true);
		$instances = $property->getValue();
		$instances[Database::class] = null;
		$property->setValue(null, $instances);
	}

	public function testApiCanBeInstantiated() {
		TestsHelper::Print("Testing if MagratheaApi can be instantiated");
		$this->assertInstanceOf(MagratheaApi::class, $this->api);
	}

	public function testSetAndGetAddress() {
		TestsHelper::Print("Testing SetAddress and GetAddress");
		$address = "http://my.api/v1";
		$this->api->SetAddress($address);
		$this->assertEquals($address."/", $this->api->GetAddress());
	}

	public function testAddEndpoint() {
		TestsHelper::Print("Testing adding a custom endpoint");
		$this->api->Add("GET", "test", null, function() { return "ok"; });
		$endpoints = $this->api->GetEndpoints();
		$this->assertArrayHasKey("anonymous", $endpoints);
		$this->assertArrayHasKey("GET", $endpoints["anonymous"]);
		$this->assertArrayHasKey("test", $endpoints["anonymous"]["GET"]);
	}

	public function testAddPatchEndpoint() {
		TestsHelper::Print("Testing adding a PATCH endpoint");
		$this->api->Add("PATCH", "test/:id", null, function() { return "ok"; });
		$endpoints = $this->api->GetEndpoints();
		$this->assertArrayHasKey("PATCH", $endpoints["anonymous"]);
		$this->assertArrayHasKey("test/:id", $endpoints["anonymous"]["PATCH"]);
	}

	public function testCrudEndpoints() {
		TestsHelper::Print("Testing adding CRUD endpoints");
		$control = new MagratheaApiControl();
		$this->api->Crud("user", $control);
		$endpoints = $this->api->GetEndpoints();
		$controlClass = get_class($control);
		$this->assertArrayHasKey($controlClass, $endpoints);
		
		$this->assertArrayHasKey("users", $endpoints[$controlClass]["POST"]);
		$this->assertArrayHasKey("users", $endpoints[$controlClass]["GET"]);
		$this->assertArrayHasKey("user/:id", $endpoints[$controlClass]["GET"]);
		$this->assertArrayHasKey("user/:id", $endpoints[$controlClass]["PUT"]);
		$this->assertArrayHasKey("user/:id", $endpoints[$controlClass]["DELETE"]);
	}

	public function testHealthCheck() {
		TestsHelper::Print("Testing HealthCheck endpoint creation");
		$this->api->HealthCheck();
		$endpoints = $this->api->GetEndpoints();
		$this->assertArrayHasKey("anonymous", $endpoints);
		$this->assertArrayHasKey("GET", $endpoints["anonymous"]);
		$this->assertArrayHasKey("health-check", $endpoints["anonymous"]["GET"]);
	}

	public function testHealthCheckResponseWithoutDatabaseFieldByDefault() {
		$this->api->HealthCheck();
		$endpoint = $this->api->GetEndpoints()["anonymous"]["GET"]["health-check"];
		$response = $endpoint["action"]();

		$this->assertEquals("ok", $response["health"]);
		$this->assertArrayNotHasKey("database", $response);
	}

	public function testHealthCheckResponseIncludesDatabaseFailWhenCheckEnabledAndConnectionFails() {
		Database::MockClass(FailingHealthCheckDatabaseMock::Instance());

		$this->api->HealthCheck(true);
		$endpoint = $this->api->GetEndpoints()["anonymous"]["GET"]["health-check"];
		$response = $endpoint["action"]();

		$this->assertEquals("ok", $response["health"]);
		$this->assertEquals("fail", $response["database"]);
	}

	public function testFailedAuthorizationInRawModeReturns401AndSkipsAction() {
		TestsHelper::Print("Testing failed authorization in raw mode doesn't run the action");
		$control = new RawModeProtectedControl();
		$this->api->SetRaw()
			->BaseAuthorization(new RawModeAuthMock(), "Deny")
			->Add("GET", "secret", $control, "Secret", true);

		$response = $this->api->ExecuteUrl("secret", "GET");

		$this->assertSame(0, $control->calls);
		$this->assertFalse($response["success"]);
		$this->assertEquals(401, $response["data"]["code"]);
		$this->assertEquals("no token", $response["data"]["error"]);
		$this->assertStringContainsString("Authorization Failed! (Deny = false)", $response["data"]["message"]);
	}

	public function testBaseAuthorizationRejectsPlainControlAtTheCall() {
		TestsHelper::Print("Testing BaseAuthorization() only accepts MagratheaApiAuth");
		try {
			$this->api->BaseAuthorization(new RawModeProtectedControl(), "Secret");
			$this->fail("expected a TypeError");
		} catch(\TypeError $err) {
			$this->assertStringContainsString("BaseAuthorization(): Argument #1", $err->getMessage());
			$this->assertStringContainsString("MagratheaApiAuth", $err->getMessage());
		}
		$this->assertNull($this->api->authClass);
	}

	private function returnErrorWithSecret(MagratheaApi $api, string $secret) {
		return $api->ReturnError(500, "something failed");
	}
	private function returnApiExceptionWithSecret(MagratheaApi $api, string $secret) {
		return $api->ReturnApiException(new \Magrathea2\Exceptions\MagratheaApiException("something failed", 500));
	}

	public static function stacktraceDebugTypes(): array {
		return [
			"dev" => [\Magrathea2\Debugger::DEV],
			"analysis" => [\Magrathea2\Debugger::ANALYSIS],
		];
	}

	/**
	 * @dataProvider stacktraceDebugTypes
	 */
	public function testErrorStacktracesDontLeakCallArguments(int $debugType) {
		TestsHelper::Print("Testing error stacktraces don't include call arguments");
		$debugger = \Magrathea2\Debugger::Instance();
		$previousType = $debugger->GetType();
		$debugger->SetType($debugType);
		$secret = "super-secret-password-".uniqid();
		$this->api->SetRaw();
		// outside ExecuteUrl(), DEV prints the exception it creates
		ob_start();
		try {
			$responses = [
				"ReturnError" => $this->returnErrorWithSecret($this->api, $secret),
				"ReturnApiException" => $this->returnApiExceptionWithSecret($this->api, $secret),
			];
		} finally {
			ob_end_clean();
			$debugger->SetType($previousType);
		}

		$callers = [
			"ReturnError" => "returnErrorWithSecret",
			"ReturnApiException" => "returnApiExceptionWithSecret",
		];
		foreach($responses as $method => $response) {
			$trace = $response["data"]["stacktrace"];
			$this->assertNotEmpty($trace, $method);
			foreach($trace as $frame) {
				$this->assertArrayNotHasKey("args", $frame, $method);
			}
			$this->assertEquals($method, $trace[0]["function"]);
			$this->assertArrayHasKey("file", $trace[0], $method);
			$this->assertArrayHasKey("line", $trace[0], $method);
			$this->assertEquals($callers[$method], $trace[1]["function"], $method);
			$this->assertStringNotContainsString($secret, print_r($response, true), $method);
		}
	}

	public function testSuccessfulAuthorizationInRawModeRunsAction() {
		TestsHelper::Print("Testing successful authorization in raw mode runs the action");
		$control = new RawModeProtectedControl();
		$this->api->SetRaw()
			->BaseAuthorization(new RawModeAuthMock(), "Allow")
			->Add("GET", "secret", $control, "Secret", true);

		$response = $this->api->ExecuteUrl("secret", "GET");

		$this->assertSame(1, $control->calls);
		$this->assertTrue($response["success"]);
		$this->assertEquals("secret data", $response["data"]);
	}

	public static function errorCodeStatuses(): array {
		return [
			"3 digits" => [404, 404],
			"4 digits" => [4031, 403],
			"5 digits" => [40312, 403],
			"5xx" => [503, 503],
			"code 0" => [0, 500],
			"mysql 1062" => [1062, 500],
			"maps to 200" => [2001, 500],
			"maps to 700" => [7000, 500],
			"3 digits, not an error" => [302, 500],
			"negative" => [-1, 500],
			"pdo sqlstate" => ["23000", 500],
			"non-numeric" => ["HY000", 500],
		];
	}

	/**
	 * @dataProvider errorCodeStatuses
	 */
	public function testReturnErrorDerivesStatusFromCode($code, int $status) {
		TestsHelper::Print("Testing ReturnError derives the HTTP status from the code");
		$api = new StatusCapturingApi();
		$response = $api->ReturnError($code, "failed");
		$this->assertSame($status, $api->status);
		$this->assertFalse($response["success"]);
		$this->assertSame($code, $response["data"]["code"]);
	}

	public static function exceptionCodeStatuses(): array {
		// exception codes are ints
		return array_filter(self::errorCodeStatuses(), fn($row) => is_int($row[0]));
	}

	/**
	 * @dataProvider exceptionCodeStatuses
	 */
	public function testReturnApiExceptionStatusFromCode(int $code, int $status) {
		TestsHelper::Print("Testing ReturnApiException maps codes to 400-599 statuses only");
		$api = new StatusCapturingApi();
		$api->ReturnApiException(new MagratheaApiException("failed", $code));
		$this->assertSame($status, $api->status);
	}

	public function testReturnErrorKeepsExplicitStatus() {
		TestsHelper::Print("Testing ReturnError keeps an explicit status");
		$api = new StatusCapturingApi();
		$api->ReturnError(4031, "failed", null, 200);
		$this->assertSame(200, $api->status);
		$api->ReturnError(0, "failed", null, 418);
		$this->assertSame(418, $api->status);
	}

	public function testSetStatusIsHonored() {
		TestsHelper::Print("Testing MagratheaApiException::SetStatus() sets the HTTP status");
		$api = new StatusCapturingApi();
		$api->ReturnApiException((new MagratheaApiException("no token", 400))->SetStatus(401));
		$this->assertSame(401, $api->status);
		$api->ReturnFail((new MagratheaApiException("no token", 400, null, false))->SetStatus(401));
		$this->assertSame(401, $api->status);
		// not an error status: the code decides
		$api->ReturnApiException((new MagratheaApiException("failed", 4031))->SetStatus(200));
		$this->assertSame(403, $api->status);
		// killer/non-killer no longer imply 500/200
		$api->ReturnApiException(new MagratheaApiException("forbidden", 4031, null, true));
		$this->assertSame(403, $api->status);
		$api->ReturnFail(new MagratheaApiException("forbidden", 4031, null, false));
		$this->assertSame(403, $api->status);
	}

	public function testReturnFailWithException() {
		TestsHelper::Print("Testing ReturnFail sends the exception message with a real status");
		$api = new StatusCapturingApi();
		$response = $api->ReturnFail(new \Exception("boom"));
		$this->assertSame(500, $api->status);
		$this->assertFalse($response["success"]);
		$this->assertSame(["message" => "boom"], $response["data"]);

		$response = $api->ReturnFail(new \Error("type error", 4221));
		$this->assertSame(422, $api->status);
		$this->assertSame(["message" => "type error", "code" => 4221], $response["data"]);

		$response = $api->ReturnFail(new MagratheaApiException("invalid data", 400, ["field" => "name"], false));
		$this->assertSame(400, $api->status);
		$this->assertSame(["message" => "invalid data", "code" => 400, "data" => ["field" => "name"]], $response["data"]);
	}

	public function testReturnFailWithDataKeeps200() {
		TestsHelper::Print("Testing ReturnFail with non-exception data keeps HTTP 200");
		$api = new StatusCapturingApi();
		$response = $api->ReturnFail(["reason" => "nope"]);
		$this->assertSame(200, $api->status);
		$this->assertSame(["reason" => "nope"], $response["data"]);
	}

	public static function thrownStatuses(): array {
		// closures: providers run before setUp(), and a new exception is echoed in DEV
		return [
			"exception, code 0" => [fn() => new \Exception("boom"), 500, "boom"],
			"exception with code" => [fn() => new \Exception("conflict", 4091), 409, "conflict"],
			"exception, mysql code" => [fn() => new \Exception("duplicate", 1062), 500, "duplicate"],
			"api exception" => [fn() => new MagratheaApiException("forbidden", 4031), 403, "forbidden"],
			// a DB error's code is MySQL's errno: always 500, even when it looks like a 4xx
			"db exception 4031" => [fn() => new MagratheaDBException("disconnected", "SELECT 1", 4031), 500, "disconnected"],
			"db exception 4025" => [fn() => new MagratheaDBException("check failed", "INSERT", 4025), 500, "check failed"],
			"db connection lost 4031" => [fn() => new MagratheaDBConnectionLostException("gone", "SELECT 1", 4031), 500, "gone"],
			"mysqli exception 4031" => [fn() => new \mysqli_sql_exception("disconnected", 4031), 500, "disconnected"],
		];
	}

	/**
	 * @dataProvider thrownStatuses
	 */
	public function testExecuteUrlErrorStatuses(\Closure $exception, int $status, string $message) {
		TestsHelper::Print("Testing ExecuteUrl sends real statuses for thrown errors");
		$control = new ThrowingControl();
		$control->toThrow = $exception();
		$api = new StatusCapturingApi();
		$api->SetRaw()->Add("GET", "fail", $control, "Fail");
		$response = $api->ExecuteUrl("fail", "GET");
		$this->assertSame($status, $api->status);
		$this->assertFalse($response["success"]);
		$this->assertSame($message, $response["data"]["message"]);
	}

	public function testExecuteUrlNotFound() {
		TestsHelper::Print("Testing ExecuteUrl sends 404 for an unknown route");
		$api = new StatusCapturingApi();
		$api->SetRaw()->Add("GET", "known", new RawModeProtectedControl(), "Secret");
		$previousMethod = $_SERVER["REQUEST_METHOD"] ?? null;
		$_SERVER["REQUEST_METHOD"] = "GET";
		try {
			$response = $api->ExecuteUrl("unknown", "GET");
		} finally {
			if($previousMethod === null) unset($_SERVER["REQUEST_METHOD"]);
			else $_SERVER["REQUEST_METHOD"] = $previousMethod;
		}
		$this->assertSame(404, $api->status);
		$this->assertFalse($response["success"]);
		$this->assertSame(404, $response["data"]["code"]);
	}

	public function testFailedAuthorizationStatusIs401() {
		TestsHelper::Print("Testing failed authorization sends 401");
		$api = new StatusCapturingApi();
		$api->SetRaw()
			->BaseAuthorization(new RawModeAuthMock(), "Deny")
			->Add("GET", "secret", new RawModeProtectedControl(), "Secret", true);
		$api->ExecuteUrl("secret", "GET");
		$this->assertSame(401, $api->status);
	}

	public static function thrownErrors(): array {
		return [
			"action" => ["Fail", false],
			"auth method" => ["auth", true],
		];
	}

	private function ErrorApi(bool $inAuth): StatusCapturingApi {
		$error = new \TypeError('Argument #1 ($id) must be of type int, string given');
		$api = new StatusCapturingApi();
		$api->SetRaw();
		if($inAuth) {
			$auth = new ThrowingAuth();
			$auth->toThrow = $error;
			$api->BaseAuthorization($auth, "Fail")
				->Add("GET", "fail", new RawModeProtectedControl(), "Secret", true);
		} else {
			$control = new ThrowingControl();
			$control->toThrow = $error;
			$api->Add("GET", "fail", $control, "Fail");
		}
		return $api;
	}

	/**
	 * Points the Logger to a temp file, outside the "test" environment (where Logger does nothing).
	 * @return string the log file path
	 */
	private function LogToTempFile(): string {
		$dir = __DIR__."/test-dump/api-logs";
		if(!is_dir($dir)) mkdir($dir, 0777, true);
		$file = "api-errors.log";
		@unlink($dir."/".$file);
		\Magrathea2\Config::Instance()->SetEnvironment("test-api-log");
		\Magrathea2\Logger::Instance()->SetLogPath($dir)->SetLogFile($file);
		return $dir."/".$file;
	}
	private function ResetLogger(): void {
		\Magrathea2\Config::Instance()->SetEnvironment(null);
		\Magrathea2\Logger::Instance()->SetLogPath(null)->SetLogFile(null);
		system('rm -rf -- '.escapeshellarg(__DIR__."/test-dump/api-logs"));
	}

	/**
	 * @dataProvider thrownErrors
	 */
	public function testExecuteUrlCatchesError(string $where, bool $inAuth) {
		TestsHelper::Print("Testing ExecuteUrl turns a PHP Error into a JSON 500 (".$where.")");
		$log = $this->LogToTempFile();
		try {
			$api = $this->ErrorApi($inAuth);
			$response = $api->ExecuteUrl("fail", "GET");
		} finally {
			$logged = @file_get_contents($log);
			$this->ResetLogger();
		}
		$this->assertSame(500, $api->status);
		$this->assertFalse($response["success"]);
		$this->assertSame(["message" => "Internal Server Error"], $response["data"]);
		$this->assertStringContainsString("TypeError Catch:", $logged);
		$this->assertStringContainsString("must be of type int, string given", $logged);
		$this->assertStringContainsString("apiTest.php:", $logged);
	}

	public function testExecuteUrlShowsErrorInDev() {
		TestsHelper::Print("Testing ExecuteUrl shows the PHP Error's message in DEV");
		$log = $this->LogToTempFile();
		\Magrathea2\Debugger::Instance()->SetDev();
		try {
			$api = $this->ErrorApi(false);
			$response = $api->ExecuteUrl("fail", "GET");
		} finally {
			$this->ResetLogger();
		}
		$this->assertSame(500, $api->status);
		$this->assertSame('Argument #1 ($id) must be of type int, string given', $response["data"]["message"]);
		$this->assertSame("TypeError", $response["data"]["error"]);
		$this->assertStringContainsString("apiTest.php:", $response["data"]["file"]);
	}

	public function testErrorResponseWhenLoggerFails() {
		TestsHelper::Print("Testing a PHP Error still gets a JSON 500 when the Logger can't write");
		$errorLog = tempnam(sys_get_temp_dir(), "magrathea-error-log");
		$previousErrorLog = ini_set("error_log", $errorLog);
		\Magrathea2\Config::Instance()->SetEnvironment("test-api-log");
		\Magrathea2\Logger::Instance()->SetLogPath("/nonexistent/magrathea-logs")->SetLogFile("x.log");
		try {
			$api = $this->ErrorApi(false);
			$response = $api->ExecuteUrl("fail", "GET");
		} finally {
			ini_set("error_log", $previousErrorLog === false ? "" : $previousErrorLog);
			$this->ResetLogger();
			$logged = file_get_contents($errorLog);
			unlink($errorLog);
		}
		$this->assertSame(500, $api->status);
		$this->assertFalse($response["success"]);
		$this->assertStringContainsString("TypeError: Argument #1", $logged);
	}

}
