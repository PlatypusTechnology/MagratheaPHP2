<?php

use Magrathea2\Bootstrap\Start;
use Magrathea2\Config;
use Magrathea2\Tests\TestsHelper;

class bootstrapTest extends \PHPUnit\Framework\TestCase {

	protected function tearDown(): void {
		Config::Instance()->SetConfig(null)->SetEnvironment(null);
		parent::tearDown();
	}

	private function MockConfig(array $dev, array $production = [], array $general = []) {
		Config::Instance()->SetConfig([
			"general" => array_merge(["use_environment" => "dev"], $general),
			"dev" => $dev,
			"production" => $production,
		])->SetEnvironment(null);
	}

	function testLockedWithoutKey() {
		TestsHelper::Print("testing bootstrap locked without key...");
		$this->MockConfig(["db_host" => "localhost"]);
		$this->assertFalse(Start::Instance()->IsUnlocked());
	}

	function testLockedWithFalsyValues() {
		TestsHelper::Print("testing bootstrap locked with falsy values...");
		// parse_ini_file turns a bare `false` into ""; quoted strings stay as written
		foreach(["", "0", "false", "off", "no", false, null] as $value) {
			$this->MockConfig(["bootstrap_unlocked" => $value]);
			$this->assertFalse(Start::Instance()->IsUnlocked(), "unlocked with ".var_export($value, true));
		}
	}

	function testLockedWithKeyInAnotherEnvironment() {
		TestsHelper::Print("testing bootstrap locked with key in another environment...");
		$this->MockConfig([], ["bootstrap_unlocked" => "1"]);
		$this->assertFalse(Start::Instance()->IsUnlocked());
	}

	function testLockedWithKeyInGeneral() {
		TestsHelper::Print("testing bootstrap locked with key in [general]...");
		$this->MockConfig([], [], ["bootstrap_unlocked" => "1"]);
		$this->assertFalse(Start::Instance()->IsUnlocked());
	}

	function testLockedWithMissingEnvironment() {
		TestsHelper::Print("testing bootstrap locked when the environment can't be read...");
		$this->MockConfig(["bootstrap_unlocked" => "1"]);
		Config::Instance()->SetEnvironment("staging");
		$this->assertFalse(Start::Instance()->IsUnlocked());
	}

	function testUnlockedInActiveEnvironment() {
		TestsHelper::Print("testing bootstrap unlocked in active environment...");
		// parse_ini_file turns a bare `true` into "1"
		foreach(["1", "true", true] as $value) {
			$this->MockConfig(["bootstrap_unlocked" => $value]);
			$this->assertTrue(Start::Instance()->IsUnlocked(), "locked with ".var_export($value, true));
		}
	}

	function testSampleConfigUnlocksDevOnly() {
		TestsHelper::Print("testing sample config unlocks dev only...");
		$config = Config::Instance();
		$previousPath = $config->GetPath();
		$config->SetConfig(null)->SetEnvironment(null)
			->SetPath(__DIR__."/../../Bootstrap/docs")->SetConfigFile("magrathea.conf.sample");
		try {
			$this->assertSame("dev", $config->GetEnvironment());
			$this->assertTrue(Start::Instance()->IsUnlocked());
			$config->SetEnvironment("production");
			$this->assertFalse(Start::Instance()->IsUnlocked());
		} finally {
			$config->SetPath($previousPath)->SetConfigFile("magrathea.conf");
		}
	}

	function testLoadShowsLockedPage() {
		TestsHelper::Print("testing bootstrap Load() shows the locked page...");
		$this->MockConfig(["db_host" => "localhost"]);
		ob_start();
		Start::Instance()->Load();
		$html = ob_get_clean();
		$this->assertStringContainsString("Bootstrap is locked", $html);
		$this->assertStringContainsString("<b>dev</b>", $html);
		$this->assertStringNotContainsString("Next</button>", $html);
	}

	function testUnknownActionsAreRejected() {
		TestsHelper::Print("testing bootstrap rejects unknown actions...");
		$start = Start::Instance();
		$invalid = [
			"generate-code",
			"../../Start",
			"../../../../../../etc/passwd",
			"actions/run-query",
			"run-query.php",
			"RUN-QUERY",
			"",
			null,
			["run-query"],
		];
		foreach($invalid as $action) {
			$this->assertNull($start->GetActionFile($action), "accepted ".var_export($action, true));
		}
	}

	function testActionsWhitelistMatchesFolder() {
		TestsHelper::Print("testing bootstrap actions whitelist matches views/actions...");
		$files = glob(__DIR__."/../../Bootstrap/views/actions/*.php");
		$actions = array_map(fn($f) => basename($f, ".php"), $files);
		sort($actions);
		$whitelist = Start::ACTIONS;
		sort($whitelist);
		$this->assertSame($actions, $whitelist);
		foreach(Start::ACTIONS as $action) {
			$this->assertFileExists(Start::Instance()->GetActionFile($action));
		}
	}

}
