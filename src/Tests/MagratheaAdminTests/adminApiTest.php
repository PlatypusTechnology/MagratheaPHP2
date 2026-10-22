<?php

namespace Magrathea2\Admin\Api {
	// AdminApi::Call() only instantiates classes in this namespace
	class AdminTestThrowingApi {
		public function TypeFail($params) {
			return strlen([]);
		}
		public function SoftFail($params) {
			throw new \Magrathea2\Exceptions\MagratheaApiException("invalid data", 400, ["field" => "name"], false);
		}
		public function PlainFail($params) {
			throw new \Exception("boom");
		}
		public function DbFail($params) {
			throw new \Magrathea2\Exceptions\MagratheaDBException("disconnected", "SELECT 1", 4031);
		}
	}
}

namespace {

use Magrathea2\Admin\Api\AdminApi;
use Magrathea2\Config;
use Magrathea2\Debugger;
use Magrathea2\Tests\TestsHelper;

class StatusCapturingAdminApi extends AdminApi {
	public ?int $status = null;
	public function Json($response, int $code=200) {
		$this->status = $code;
		return $response;
	}
}

class adminApiTest extends \PHPUnit\Framework\TestCase {

	private $debugType;

	protected function setUp(): void {
		parent::setUp();
		$this->debugType = Debugger::Instance()->GetType();
		// AdminApi sets the debugger to LOG: new exceptions are logged, and Logger skips "test"
		Config::Instance()->SetEnvironment("test");
	}
	protected function tearDown(): void {
		Debugger::Instance()->SetType($this->debugType);
		Config::Instance()->SetEnvironment(null);
		parent::tearDown();
	}

	private function Call(string $method): array {
		$api = new StatusCapturingAdminApi();
		$response = $api->SetRaw()->Call("AdminTestThrowing", $method);
		return [$api->status, $response];
	}

	public function testErrorIsCaught() {
		TestsHelper::Print("Testing AdminApi turns a PHP Error into a JSON 500");
		[$status, $response] = $this->Call("TypeFail");
		$this->assertSame(500, $status);
		$this->assertFalse($response["success"]);
		// AdminApi runs in LOG mode: no details for the client
		$this->assertSame(["message" => "Internal Server Error"], $response["data"]);
	}

	public function testSoftFailStatus() {
		TestsHelper::Print("Testing AdminApi sends a non-killer exception with its status");
		[$status, $response] = $this->Call("SoftFail");
		$this->assertSame(400, $status);
		$this->assertSame(["message" => "invalid data", "code" => 400, "data" => ["field" => "name"]], $response["data"]);
	}

	public function testExceptionStatus() {
		TestsHelper::Print("Testing AdminApi sends a code-0 exception as 500 with its message");
		[$status, $response] = $this->Call("PlainFail");
		$this->assertSame(500, $status);
		$this->assertSame(["message" => "boom"], $response["data"]);
	}

	public function testDbExceptionStatus() {
		TestsHelper::Print("Testing AdminApi sends a DB error as 500, whatever its errno");
		[$status, $response] = $this->Call("DbFail");
		$this->assertSame(500, $status);
		$this->assertFalse($response["success"]);
	}

	public function testMissingMethod() {
		TestsHelper::Print("Testing AdminApi sends 500 for a missing method");
		[$status, $response] = $this->Call("Nope");
		$this->assertSame(500, $status);
		$this->assertFalse($response["success"]);
	}

}

}
