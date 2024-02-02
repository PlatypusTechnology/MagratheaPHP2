<?php

use Magrathea2\Admin\Features\AppConfig\AppConfigControl;
use Magrathea2\Tests\TestsHelper;

class appConfigTest extends \PHPUnit\Framework\TestCase {
	protected function setUp(): void {
		parent::setUp();
	}
	protected function tearDown(): void {
		parent::tearDown();
	}

	function testImportParser() {
		TestsHelper::Print("testing parser for appConfig");
		$configControl = new AppConfigControl();
		$t1 = "==[s]code_structure==|>>feature>>;;";
		$r1 = $configControl->ParseLine($t1);
		$this->assertTrue($r1["system"]);
		$this->assertEquals("code_structure", $r1["key"]);
		$this->assertEquals("feature", $r1["value"]);
		$v2 = "a value w1th <special> characters";
		$t2 = "==[n]some_complicated key==|>>".$v2.">>;;";
		$r2 = $configControl->ParseLine($t2);
		$this->assertTrue($r2["system"]);
		$this->assertEquals("some_complicated key", $r2["key"]);
		$this->assertEquals($v2, $r2["value"]);
	}

	function testImportParserNoSystemIndicator() {
		TestsHelper::Print("testing simplest parser for appConfig");
		$configControl = new AppConfigControl();
		$t1 = "==app_name==|>>magrathea>>;;";
		$r1 = $configControl->ParseLine($t1);
		$this->assertFalse($r1["system"]);
		$this->assertEquals("code_structure", $r1["key"]);
		$this->assertEquals("feature", $r1["value"]);
	}
}