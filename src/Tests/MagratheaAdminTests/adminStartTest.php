<?php

use Magrathea2\Admin\Start;
use Magrathea2\DB\Database;
use Magrathea2\Exceptions\MagratheaDBException;
use Magrathea2\Tests\TestsHelper;

// Admin Start with a controllable user count (int, or an exception to throw)
class FakeAdminStart extends Start {
	public $users = 0;
	public $installDateChecks = 0;
	protected function Initialize() { return $this; }
	public function HasUsers(): bool {
		if ($this->users instanceof \Throwable) throw $this->users;
		return $this->users > 0;
	}
	protected function EnsureInstallDate(): void {
		$this->installDateChecks++;
	}
}

// Admin Start that keeps the real EnsureInstallDate()
class FakeAdminStartRealInstallDate extends Start {
	protected function Initialize() { return $this; }
	public function HasUsers(): bool { return true; }
}

// Records every call made to the database (a Singleton, so MockClass() accepts it)
class RecordingDatabase extends \Magrathea2\Singleton {
	public $calls = [];
	public $row = [];
	public function Escape($s) { return addslashes($s); }
	public function QueryRow($sql) {
		$this->calls[] = ["QueryRow", $sql];
		return $this->row;
	}
	public function PrepareAndExecute($query, $types, $values) {
		$this->calls[] = ["PrepareAndExecute", $query, $values];
		return 1;
	}
	public function __call($name, $args) {
		$this->calls[] = [$name, $args[0] ?? null];
		return null;
	}
	public static function Fresh(array $row): RecordingDatabase {
		$db = static::Instance();
		$db->calls = [];
		$db->row = $row;
		return $db;
	}
	public function Writes(): array {
		return array_values(array_filter($this->calls, fn($c) => $c[0] === "PrepareAndExecute"));
	}
}

class adminStartTest extends \PHPUnit\Framework\TestCase {

	private $previousDb;

	protected function setUp(): void {
		parent::setUp();
		$this->previousDb = Database::Instance();
	}
	protected function tearDown(): void {
		Database::MockClass($this->previousDb);
		parent::tearDown();
	}

	function testSetupOnlyWithoutUsers() {
		TestsHelper::Print("testing admin setup is shown only with zero users...");
		$start = FakeAdminStart::Instance();
		$start->users = 0;
		$this->assertSame(Start::STATE_SETUP, $start->GetInstallState());
		$this->assertTrue($start->CanCreateFirstUser());
		$start->users = 3;
		$this->assertSame(Start::STATE_READY, $start->GetInstallState());
		$this->assertFalse($start->CanCreateFirstUser());
	}

	function testDatabaseErrorNeverShowsSetup() {
		TestsHelper::Print("testing a DB error never leads to the admin setup...");
		$start = FakeAdminStart::Instance();
		foreach([new MagratheaDBException("server has gone away"), new \RuntimeException("mysqli failure")] as $ex) {
			$start->users = $ex;
			$this->assertSame(Start::STATE_DB_ERROR, $start->GetInstallState());
			$this->assertFalse($start->CanCreateFirstUser());
		}
	}

	function testUsersWithoutInstallDateGoToLogin() {
		TestsHelper::Print("testing users without admin_install_date check the install date and skip setup...");
		$start = FakeAdminStart::Instance();
		$start->users = 1;
		$start->installDateChecks = 0;
		$this->assertSame(Start::STATE_READY, $start->GetInstallState());
		$this->assertSame(1, $start->installDateChecks);
		$start->users = 0;
		$start->GetInstallState();
		$this->assertSame(1, $start->installDateChecks, "install date must not be written while there are no users");
	}

	function testMissingInstallDateIsWritten() {
		TestsHelper::Print("testing a missing admin_install_date is written...");
		$db = RecordingDatabase::Fresh([]);
		Database::MockClass($db);
		$this->assertSame(Start::STATE_READY, FakeAdminStartRealInstallDate::Instance()->GetInstallState());
		$writes = $db->Writes();
		$this->assertNotEmpty($writes);
		$this->assertStringContainsString("_magrathea_config", $writes[0][1]);
		$this->assertContains("admin_install_date", $writes[0][2]);
	}

	function testExistingInstallDateIsNotWritten() {
		TestsHelper::Print("testing an existing admin_install_date is left alone...");
		$db = RecordingDatabase::Fresh(["id" => 1, "name" => "admin_install_date", "value" => "2024-01-01 00:00:00", "is_system" => 1]);
		Database::MockClass($db);
		$this->assertSame(Start::STATE_READY, FakeAdminStartRealInstallDate::Instance()->GetInstallState());
		$this->assertEmpty($db->Writes());
	}

	function testViewFileAcceptsExistingPagesAndActions() {
		TestsHelper::Print("testing admin pages/actions resolve to their own folders...");
		$start = FakeAdminStart::Instance();
		$views = realpath(__DIR__."/../../Admin/views");
		$this->assertSame($views."/pages/tests.php", realpath($start->GetViewFile("pages", "tests")));
		$this->assertSame($views."/actions/test-run.php", realpath($start->GetViewFile("actions", "test-run")));
		foreach(glob($views."/actions/*.php") as $file) {
			$this->assertNotNull($start->GetViewFile("actions", basename($file, ".php")), $file);
		}
		foreach(glob($views."/pages/*.php") as $file) {
			$this->assertNotNull($start->GetViewFile("pages", basename($file, ".php")), $file);
		}
	}

	function testViewFileRejectsEverythingElse() {
		TestsHelper::Print("testing admin pages/actions can't include other files...");
		$start = FakeAdminStart::Instance();
		$rejected = [
			"../index", "../login", "../setup", "../../Start", "../../../Bootstrap/views/actions/save-config",
			"config/config-form", "setup/login-form", "pages/tests", "..\\index",
			"/etc/passwd", "tests\0", "missing", "", ".", "..", null, ["tests"], 1,
		];
		foreach($rejected as $name) {
			$this->assertNull($start->GetViewFile("actions", $name), var_export($name, true));
			$this->assertNull($start->GetViewFile("pages", $name), var_export($name, true));
		}
		// an existing name, but only in its own folder
		$this->assertNull($start->GetViewFile("pages", "test-run"));
		$this->assertNull($start->GetViewFile("actions", "tests"));
		// only the two folders
		$this->assertNull($start->GetViewFile("elements", "alert"));
		$this->assertNull($start->GetViewFile("../views/pages", "tests"));
	}

	function testAdminIndexOnlyIncludesThroughViewFile() {
		TestsHelper::Print("testing the admin index routes pages/actions through GetViewFile()...");
		$index = file_get_contents(__DIR__."/../../Admin/views/index.php");
		// no include built from a string plus a variable (fixed ones, like pages/home.php, are fine)
		$this->assertDoesNotMatchRegularExpression('/include\s*\(\s*"[^"]*"\s*\./', $index);
		$this->assertStringContainsString('GetViewFile("actions", $magrathea_subpage)', $index);
		$this->assertStringContainsString('GetViewFile("pages", $page)', $index);
	}

}
