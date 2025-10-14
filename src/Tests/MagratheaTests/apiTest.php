<?php

use Magrathea2\MagratheaApi;
use Magrathea2\MagratheaApiControl;
use Magrathea2\Tests\TestsHelper;

class MagratheaApiTest extends \PHPUnit\Framework\TestCase {

	private MagratheaApi $api;

	protected function setUp(): void {
		parent::setUp();
		$this->api = new MagratheaApi();
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

}