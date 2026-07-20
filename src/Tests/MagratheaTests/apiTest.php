<?php

use Magrathea2\MagratheaApi;
use Magrathea2\MagratheaApiControl;
use Magrathea2\Tests\TestsHelper;
use Magrathea2\DB\Database;

class FailingHealthCheckDatabaseMock {
	public function OpenConnectionPlease() {
		throw new \Exception("db down");
	}

	public function CloseConnectionThanks() {
		return true;
	}
}

class MagratheaApiTest extends \PHPUnit\Framework\TestCase {

	private MagratheaApi $api;

	protected function setUp(): void {
		parent::setUp();
		$this->api = new MagratheaApi();
		$this->resetDatabaseSingleton();
	}

	protected function tearDown(): void {
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
		Database::MockClass(new FailingHealthCheckDatabaseMock());

		$this->api->HealthCheck(true);
		$endpoint = $this->api->GetEndpoints()["anonymous"]["GET"]["health-check"];
		$response = $endpoint["action"]();

		$this->assertEquals("ok", $response["health"]);
		$this->assertEquals("fail", $response["database"]);
	}

}