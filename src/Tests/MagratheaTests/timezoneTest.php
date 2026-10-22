<?php

use Magrathea2\Config;
use Magrathea2\Debugger;
use Magrathea2\MagratheaPHP;
use Magrathea2\TimezoneCheck;
use Magrathea2\DB\Database;
use Magrathea2\DB\DatabaseSimulate;
use Magrathea2\Exceptions\MagratheaDBException;
use Magrathea2\Exceptions\MagratheaDBConnectionLostException;
use Magrathea2\Tests\TestsHelper;

// FakeConnectionDatabase, FakeMysqli
require_once __DIR__."/databaseConnectionTest.php";

class timezoneTest extends \PHPUnit\Framework\TestCase {

	private $previousDb;
	private $previousZone;

	protected function setUp(): void {
		parent::setUp();
		TestsHelper::Debug();
		$this->previousDb = Database::Instance();
		$this->previousZone = date_default_timezone_get();
		date_default_timezone_set("America/Sao_Paulo");
	}
	protected function tearDown(): void {
		Database::DisconnectAll();
		Database::MockClass($this->previousDb);
		Config::Instance()->SetConfig(null)->SetEnvironment(null);
		date_default_timezone_set($this->previousZone);
		TestsHelper::Debug();
		parent::tearDown();
	}

	private function SetEnv(array $env, array $general = []) {
		$db = ["db_host" => "localhost", "db_name" => "test_db", "db_user" => "user", "db_pass" => "pass"];
		Config::Instance()->SetConfig([
			"general" => ["use_environment" => "dev"] + $general,
			"dev" => $db + $env,
		])->SetEnvironment(null);
	}
	// connects the fake database through MagratheaPHP::Connect(), with $env in the config
	private function ConnectWith(array $env): FakeConnectionDatabase {
		$db = FakeConnectionDatabase::Fresh();
		Database::MockClass($db);
		$this->SetEnv($env);
		MagratheaPHP::Instance()->Connect();
		return $db;
	}
	private function SetTimezoneCalls(FakeMysqli $conn): array {
		return array_values(array_map(fn($c) => $c[1], array_filter($conn->Called("query"), fn($c) => str_starts_with($c[1], "SET time_zone"))));
	}
	private function Levels(array $findings): array {
		return array_map(fn($f) => $f["level"], $findings);
	}
	private function FindingsWith(array $findings, string $text): array {
		return array_values(array_filter($findings, fn($f) => str_contains($f["message"], $text)));
	}

	// SYNC STATE

	public static function syncStateCases(): array {
		return [
			"timezone, no flag: default on" => [["timezone" => "America/Sao_Paulo"], TimezoneCheck::SYNC_ON],
			"flag true (ini: \"1\")" => [["timezone" => "America/Sao_Paulo", "db_sync_timezone" => "1"], TimezoneCheck::SYNC_ON],
			"flag false (ini: \"\")" => [["timezone" => "America/Sao_Paulo", "db_sync_timezone" => ""], TimezoneCheck::SYNC_OFF],
			"flag \"0\"" => [["timezone" => "America/Sao_Paulo", "db_sync_timezone" => "0"], TimezoneCheck::SYNC_OFF],
			"flag false (bool)" => [["timezone" => "America/Sao_Paulo", "db_sync_timezone" => false], TimezoneCheck::SYNC_OFF],
			"flag \"false\" (quoted)" => [["timezone" => "America/Sao_Paulo", "db_sync_timezone" => "false"], TimezoneCheck::SYNC_OFF],
			"flag \"FALSE\" (quoted)" => [["timezone" => "America/Sao_Paulo", "db_sync_timezone" => "FALSE"], TimezoneCheck::SYNC_OFF],
			"flag \"off\" (quoted)" => [["timezone" => "America/Sao_Paulo", "db_sync_timezone" => "off"], TimezoneCheck::SYNC_OFF],
			"flag \"no\" (quoted)" => [["timezone" => "America/Sao_Paulo", "db_sync_timezone" => "no"], TimezoneCheck::SYNC_OFF],
			"flag \"true\" (quoted)" => [["timezone" => "America/Sao_Paulo", "db_sync_timezone" => "true"], TimezoneCheck::SYNC_ON],
			"flag not a boolean: default on" => [["timezone" => "America/Sao_Paulo", "db_sync_timezone" => "maybe"], TimezoneCheck::SYNC_ON],
			"no timezone" => [[], TimezoneCheck::SYNC_NO_TIMEZONE],
			"no timezone, flag true" => [["db_sync_timezone" => "1"], TimezoneCheck::SYNC_NO_TIMEZONE],
			"only time_zone" => [["time_zone" => "America/Sao_Paulo"], TimezoneCheck::SYNC_NO_TIMEZONE],
		];
	}

	/**
	 * @dataProvider syncStateCases
	 */
	function testSyncState(array $env, string $expected) {
		TestsHelper::Print("testing db_sync_timezone: on by default with a timezone, off when false or without timezone...");
		$this->SetEnv($env);
		$this->assertSame($expected, TimezoneCheck::SyncState());
	}

	// CONNECTION

	function testSyncOnByDefault() {
		TestsHelper::Print("testing a new connection sets MySQL's time_zone to PHP's, right after set_charset, once...");
		$db = $this->ConnectWith(["timezone" => "America/Sao_Paulo"]);
		$db->QueryAll("SELECT 1");
		$db->QueryAll("SELECT 2");
		$conn = $db->Conn();
		$this->assertSame(["SET time_zone = 'America/Sao_Paulo'"], $this->SetTimezoneCalls($conn));
		$this->assertSame(["set_charset", "query"], [$conn->calls[0][0], $conn->calls[1][0]]);
		$this->assertSame("SET time_zone = 'America/Sao_Paulo'", $conn->calls[1][1]);
		$this->assertSame(["zone" => "America/Sao_Paulo", "fallback" => false], $db->GetSyncedTimezone());

		$db->Disconnect();
		$this->assertNull($db->GetSyncedTimezone());
		$db->QueryAll("SELECT 3");
		$this->assertSame(["SET time_zone = 'America/Sao_Paulo'"], $this->SetTimezoneCalls($db->Conn()), "again on the new connection");
	}

	function testSyncSendsPhpZoneNotConfigValue() {
		TestsHelper::Print("testing the zone sent is date_default_timezone_get(), not the raw config value...");
		date_default_timezone_set("Europe/Lisbon");
		$db = $this->ConnectWith(["timezone" => "Not/AZone'; DROP TABLE x; --"]);
		$db->QueryAll("SELECT 1");
		$this->assertSame(["SET time_zone = 'Europe/Lisbon'"], $this->SetTimezoneCalls($db->Conn()));
	}

	function testSyncOffWithFlag() {
		TestsHelper::Print("testing db_sync_timezone = false: no SET time_zone...");
		$db = $this->ConnectWith(["timezone" => "America/Sao_Paulo", "db_sync_timezone" => ""]);
		$db->QueryAll("SELECT 1");
		$this->assertSame([], $this->SetTimezoneCalls($db->Conn()));
		$this->assertNull($db->GetSyncedTimezone());
	}

	function testSyncOffWithoutTimezone() {
		TestsHelper::Print("testing no timezone in the config: no SET time_zone, even with the flag on...");
		$db = $this->ConnectWith(["time_zone" => "America/Sao_Paulo", "db_sync_timezone" => "1"]);
		$db->QueryAll("SELECT 1");
		$this->assertSame([], $this->SetTimezoneCalls($db->Conn()));
	}

	/**
	 * @dataProvider errorReportingModes
	 */
	function testSyncFallsBackToOffset(string $mode) {
		TestsHelper::Print("testing MySQL without timezone tables (1298): the offset is used, and logged in DEV...");
		// the SET runs as the connection opens, before a test could call FailNext() on it
		$db = FailingTimezoneDatabase::FreshFailing(1298, $mode);
		Database::MockClass($db);
		$this->SetEnv(["timezone" => "America/Sao_Paulo"]);
		MagratheaPHP::Instance()->Connect();
		Debugger::Instance()->SetDev();
		ob_start();
		try {
			$db->QueryAll("SELECT 1");
		} finally {
			$output = ob_get_clean();
			TestsHelper::Debug();
		}
		$this->assertSame(["SET time_zone = 'America/Sao_Paulo'", "SET time_zone = '".date("P")."'"], $this->SetTimezoneCalls($db->Conn()));
		$this->assertSame(["zone" => date("P"), "fallback" => true], $db->GetSyncedTimezone());
		$this->assertStringContainsString("set to the offset [".date("P")."]", $output);
		$this->assertFalse($db->Conn()->closed);
	}

	function testSyncOtherErrorThrowsAndDropsConnection() {
		TestsHelper::Print("testing any other SET time_zone error throws and drops the connection...");
		$db = FailingTimezoneDatabase::FreshFailing(1064, "return");
		Database::MockClass($db);
		$this->SetEnv(["timezone" => "America/Sao_Paulo"]);
		MagratheaPHP::Instance()->Connect();
		try {
			$db->QueryAll("SELECT 1");
			$this->fail("expected MagratheaDBException");
		} catch(MagratheaDBException $ex) {
			$this->assertNotInstanceOf(MagratheaDBConnectionLostException::class, $ex);
			$this->assertSame(1064, $ex->getCode());
			$this->assertSame("SET time_zone = 'America/Sao_Paulo'", $ex->query);
		}
		$conn = $db->Conn();
		$this->assertTrue($conn->closed);
		$this->assertCount(0, array_filter($conn->Called("query"), fn($c) => $c[1] === "SELECT 1"), "the query never ran");
	}

	function testSyncConnectionLost() {
		TestsHelper::Print("testing a lost connection on SET time_zone throws MagratheaDBConnectionLostException...");
		$db = FailingTimezoneDatabase::FreshFailing(2006, "exception");
		Database::MockClass($db);
		$this->SetEnv(["timezone" => "America/Sao_Paulo"]);
		MagratheaPHP::Instance()->Connect();
		try {
			$db->QueryAll("SELECT 1");
			$this->fail("expected MagratheaDBConnectionLostException");
		} catch(MagratheaDBConnectionLostException $ex) {
			$this->assertSame(2006, $ex->getCode());
		}
		$this->assertTrue($db->Conn()->closed);
	}

	function testSimulatedSync() {
		TestsHelper::Print("testing DatabaseSimulate records SET time_zone...");
		$sim = DatabaseSimulate::Instance();
		Database::MockClass($sim);
		Debugger::Instance()->SetDebug()->LogQueries(true);
		try {
			$this->SetEnv(["timezone" => "America/Sao_Paulo"]);
			MagratheaPHP::Instance()->Connect();
			$sim->QueryAll("SELECT 1");
			$this->SetEnv(["timezone" => "America/Sao_Paulo", "db_sync_timezone" => ""]);
			MagratheaPHP::Instance()->Connect();
			$sim->QueryAll("SELECT 2");
			$debugItems = new \ReflectionProperty(Debugger::class, "debugItems");
			$logged = array_values(array_filter(
				array_map(fn($item) => is_string($item) && preg_match('/query run: \[(SET time_zone[^\]]*|SELECT \d)\]/', $item, $m) ? $m[1] : null, $debugItems->getValue(Debugger::Instance())),
			));
			$this->assertSame(["SET time_zone = 'America/Sao_Paulo'", "SELECT 1", "SELECT 2"], array_slice($logged, -3));
		} finally {
			$sim->SetTimezoneSync(false);
			Debugger::Instance()->LogQueries(false);
		}
	}

	public static function errorReportingModes(): array {
		return [
			"reporting on (thrown)" => ["exception"],
			"reporting off (returned)" => ["return"],
		];
	}

	// TROUBLESHOOTER

	private function RunWith(array $env, ?array $mysqlRow, array $general = []): array {
		$db = FakeConnectionDatabase::Fresh();
		Database::MockClass($db);
		$this->SetEnv($env, $general);
		MagratheaPHP::Instance()->Connect();
		if($mysqlRow !== null) {
			$db->Escape("open");
			$db->Conn()->rows = [$mysqlRow];
		}
		return TimezoneCheck::Run();
	}
	private function MysqlRow(?int $offset = null): array {
		return [
			"global_tz" => "SYSTEM",
			"session_tz" => "America/Sao_Paulo",
			"mysql_now" => "2026-10-21 10:00:00",
			"mysql_offset" => (string)($offset ?? (int)date("Z")),
		];
	}

	function testCheckAllGood() {
		TestsHelper::Print("testing the troubleshooter with a valid timezone, synced clocks and the sync on...");
		$findings = $this->RunWith(["timezone" => "America/Sao_Paulo"], $this->MysqlRow());
		$this->assertNotContains(TimezoneCheck::WARNING, $this->Levels($findings));
		$this->assertNotContains(TimezoneCheck::ERROR, $this->Levels($findings));
		$this->assertCount(1, $this->FindingsWith($findings, "in sync"));
		$this->assertCount(1, $this->FindingsWith($findings, "sync: on (America/Sao_Paulo)"));
		$this->assertCount(1, $this->FindingsWith($findings, "@@session.time_zone = America/Sao_Paulo"));
		foreach($findings as $f) {
			$this->assertSame(["level", "message"], array_keys($f));
		}
	}

	function testCheckOldKeyOnly() {
		TestsHelper::Print("testing the troubleshooter warns about time_zone without timezone (env or [general])...");
		foreach([[["time_zone" => "America/Sao_Paulo"], []], [[], ["time_zone" => "America/Sao_Paulo"]]] as [$env, $general]) {
			$findings = $this->RunWith($env, $this->MysqlRow(), $general);
			$old = $this->FindingsWith($findings, "`time_zone`");
			$this->assertCount(1, $old);
			$this->assertSame(TimezoneCheck::WARNING, $old[0]["level"]);
			$this->assertCount(1, $this->FindingsWith($findings, "sync: off (no `timezone`"));
		}
	}

	function testCheckOldKeyIgnoredWithNewOne() {
		TestsHelper::Print("testing the troubleshooter doesn't warn about time_zone when timezone is set...");
		$findings = $this->RunWith(["timezone" => "America/Sao_Paulo", "time_zone" => "America/Sao_Paulo"], $this->MysqlRow());
		$this->assertCount(0, $this->FindingsWith($findings, "`time_zone`"));
	}

	function testCheckNoTimezone() {
		TestsHelper::Print("testing the troubleshooter without any timezone key...");
		$findings = $this->RunWith([], $this->MysqlRow());
		$this->assertSame(TimezoneCheck::INFO, $findings[0]["level"]);
		$this->assertStringContainsString("No `timezone`", $findings[0]["message"]);
	}

	function testCheckInvalidTimezone() {
		TestsHelper::Print("testing the troubleshooter reports an invalid timezone as an error, without throwing...");
		$findings = $this->RunWith(["timezone" => "Mars/Olympus_Mons"], $this->MysqlRow());
		$this->assertSame(TimezoneCheck::ERROR, $findings[0]["level"]);
		$this->assertStringContainsString("Mars/Olympus_Mons", $findings[0]["message"]);
		$this->assertStringContainsString("America/Sao_Paulo", $findings[0]["message"], "names the zone PHP stays on");

		// backward-compatible names are accepted by PHP, so they're valid
		$findings = $this->RunWith(["timezone" => "Brazil/East"], $this->MysqlRow());
		$this->assertNotSame(TimezoneCheck::ERROR, $findings[0]["level"]);
	}

	function testCheckOutOfSync() {
		TestsHelper::Print("testing the troubleshooter reports clocks out of sync...");
		$findings = $this->RunWith(["timezone" => "America/Sao_Paulo", "db_sync_timezone" => ""], $this->MysqlRow(0));
		$out = $this->FindingsWith($findings, "out of sync");
		$this->assertCount(1, $out);
		$this->assertSame(TimezoneCheck::ERROR, $out[0]["level"]);
		$this->assertStringContainsString("PHP is UTC".date("P").", MySQL is UTC+00:00", $out[0]["message"]);
		$this->assertCount(1, $this->FindingsWith($findings, "sync: off (`db_sync_timezone = false`)"));
	}

	function testCheckOffsetsComparedInMinutes() {
		TestsHelper::Print("testing a one-second difference between NOW() and UTC_TIMESTAMP() still counts as in sync...");
		$findings = $this->RunWith(["timezone" => "America/Sao_Paulo"], $this->MysqlRow((int)date("Z") + 1));
		$this->assertCount(1, $this->FindingsWith($findings, "in sync"));
		$this->assertCount(0, $this->FindingsWith($findings, "out of sync"));
	}

	function testCheckFallback() {
		TestsHelper::Print("testing the troubleshooter reports the offset fallback...");
		$db = FailingTimezoneDatabase::FreshFailing(1298, "exception");
		Database::MockClass($db);
		$this->SetEnv(["timezone" => "America/Sao_Paulo"]);
		MagratheaPHP::Instance()->Connect();
		$db->NextRows([$this->MysqlRow()]);
		$findings = TimezoneCheck::Run();
		$fallback = $this->FindingsWith($findings, "instead of America/Sao_Paulo");
		$this->assertCount(1, $fallback);
		$this->assertSame(TimezoneCheck::WARNING, $fallback[0]["level"]);
		$this->assertStringContainsString("mysql_tzinfo_to_sql", $fallback[0]["message"]);
	}

	function testCheckDatabaseUnreachable() {
		TestsHelper::Print("testing the troubleshooter doesn't throw when MySQL can't be reached...");
		$db = FakeConnectionDatabase::Fresh();
		Database::MockClass($db);
		$this->SetEnv(["timezone" => "America/Sao_Paulo"]);
		MagratheaPHP::Instance()->Connect();
		$db->failNextConnection = true;
		$findings = TimezoneCheck::Run();
		$unreachable = $this->FindingsWith($findings, "Could not query MySQL");
		$this->assertCount(1, $unreachable);
		$this->assertSame(TimezoneCheck::WARNING, $unreachable[0]["level"]);
		$this->assertCount(1, $this->FindingsWith($findings, "sync: on."));
	}

	function testCheckWithSimulatedDatabase() {
		TestsHelper::Print("testing the troubleshooter runs against DatabaseSimulate...");
		Database::MockClass(DatabaseSimulate::Instance());
		$this->SetEnv(["timezone" => "America/Sao_Paulo"]);
		$findings = TimezoneCheck::Run();
		$this->assertCount(1, $this->FindingsWith($findings, "MySQL returned no timezone data"));
	}

}

// a fake database whose connections fail their first SET time_zone
class FailingTimezoneDatabase extends FakeConnectionDatabase {
	private $failErrno;
	private $failMode;
	private $nextRows = [];
	public static function FreshFailing(int $errno, string $mode): static {
		$db = static::Fresh();
		$db->failErrno = $errno;
		$db->failMode = $mode;
		$db->nextRows = [];
		return $db;
	}
	public function NextRows(array $rows) {
		$this->nextRows = $rows;
	}
	protected function CreateConnection() {
		$conn = parent::CreateConnection();
		$conn->FailNext("query", $this->failErrno, $this->failMode, "Unknown or incorrect time zone", "SET time_zone");
		$conn->rows = $this->nextRows;
		return $conn;
	}
}
