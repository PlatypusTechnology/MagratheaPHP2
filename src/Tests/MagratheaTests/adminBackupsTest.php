<?php

use Magrathea2\Admin\AdminDatabase;
use Magrathea2\Admin\Api\DatabaseApi;
use Magrathea2\Config;
use Magrathea2\Exceptions\MagratheaException;
use Magrathea2\Tests\TestsHelper;

// AdminDatabase with a recorded RunProcess(): no mysqldump needed
class FakeBackupAdminDatabase extends AdminDatabase {
	public $commands = [];
	public $defaultsSeen = [];
	public $exitCode = 0;
	public $writeResult = true;
	protected function RunProcess(array $command): array {
		$this->commands[] = $command;
		$defaults = substr($command[1], strlen("--defaults-extra-file="));
		$this->defaultsSeen[] = [
			"file" => $defaults,
			"content" => @file_get_contents($defaults),
			"perms" => @fileperms($defaults) & 0777,
		];
		$result = substr($command[3], strlen("--result-file="));
		if($this->writeResult) file_put_contents($result, "-- dump\n");
		return ["code" => $this->exitCode, "stdout" => "", "stderr" => $this->exitCode ? "mysqldump: Got error: 1045" : ""];
	}
	// the real one, for the no-shell test
	public function RealRunProcess(array $command): array {
		return parent::RunProcess($command);
	}
}

class adminBackupsTest extends \PHPUnit\Framework\TestCase {

	private $folder;
	private $previous;
	private $db = [
		"db_host" => "localhost",
		"db_name" => "magrathea",
		"db_user" => "root",
		"db_pass" => "s3cr3t-pass",
		"db_port" => "3306",
	];

	protected function setUp(): void {
		parent::setUp();
		$this->folder = sys_get_temp_dir()."/magrathea-backups-test-".getmypid();
		system('rm -rf -- '.escapeshellarg($this->folder));
		mkdir($this->folder);
		$this->SetDbConfig($this->db);
		$this->previous = AdminDatabase::Instance();
	}
	protected function tearDown(): void {
		AdminDatabase::MockClass($this->previous);
		system('rm -rf -- '.escapeshellarg($this->folder));
		Config::Instance()->SetConfig(null)->SetEnvironment(null);
		parent::tearDown();
	}

	private function SetDbConfig(array $db) {
		Config::Instance()->SetConfig([
			"general" => ["use_environment" => "test"],
			"test" => $db + ["backups_path" => $this->folder],
		])->SetEnvironment(null);
	}
	private function Fake(): FakeBackupAdminDatabase {
		$fake = FakeBackupAdminDatabase::Instance()->LoadBackupFolder();
		$fake->commands = [];
		$fake->defaultsSeen = [];
		$fake->exitCode = 0;
		$fake->writeResult = true;
		return $fake;
	}

	function testInvalidNamesAreRejected() {
		TestsHelper::Print("testing backup file names that could leave the folder or reach a shell are rejected...");
		$adminDb = $this->Fake();
		foreach(["../x", "a/b.sql", "x;rm -rf /", "`id`", ".sql", "", "a\\b.sql", "a..b.sql", "-opt.sql", "x.sql\0"] as $name) {
			try {
				$adminDb->ValidateBackupFileName($name);
				$this->fail("accepted: ".$name);
			} catch(MagratheaException $ex) {
				$this->assertSame("invalid file name", $ex->getMessage(), $name);
			}
		}
		$this->expectException(MagratheaException::class);
		$adminDb->ValidateBackupFileName(["x.sql"]);
	}

	function testValidNames() {
		TestsHelper::Print("testing backup file names get .sql appended...");
		$adminDb = $this->Fake();
		$this->assertSame("backup_20261023.sql", $adminDb->ValidateBackupFileName("backup_20261023"));
		$this->assertSame("backup_20261023.sql", $adminDb->ValidateBackupFileName("backup_20261023.sql"));
		$this->assertSame("my-db.2026.sql", $adminDb->ValidateBackupFileName("my-db.2026"));
	}

	function testBackupRunsWithoutShellOrPassword() {
		TestsHelper::Print("testing DoBackup() runs mysqldump with an argument array and the password in a 0600 defaults file...");
		$adminDb = $this->Fake();
		$rs = $adminDb->DoBackup("backup_20261023");
		$this->assertSame(["file" => "backup_20261023.sql", "size" => 8], $rs);
		$this->assertCount(1, $adminDb->commands);
		$command = $adminDb->commands[0];
		$this->assertSame("mysqldump", $command[0]);
		$this->assertStringStartsWith("--defaults-extra-file=", $command[1]);
		$this->assertSame(
			["--opt", "--result-file=".$this->folder."/backup_20261023.sql", "magrathea"],
			array_slice($command, 2)
		);
		foreach($command as $arg) {
			$this->assertStringNotContainsString("s3cr3t-pass", $arg);
		}
		$seen = $adminDb->defaultsSeen[0];
		$this->assertSame(0600, $seen["perms"]);
		$this->assertSame(
			"[client]\nuser=\"root\"\npassword=\"s3cr3t-pass\"\nhost=\"localhost\"\nport=\"3306\"\n",
			$seen["content"]
		);
		$this->assertFileDoesNotExist($seen["file"], "defaults file deleted after success");
		$this->assertFileExists($this->folder."/backup_20261023.sql");
	}

	function testExistingFileIsRefused() {
		TestsHelper::Print("testing DoBackup() refuses to overwrite a backup...");
		$adminDb = $this->Fake();
		file_put_contents($this->folder."/old.sql", "keep me");
		try {
			$adminDb->DoBackup("old");
			$this->fail("overwrote old.sql");
		} catch(MagratheaException $ex) {
			$this->assertSame("file already exists", $ex->getMessage());
		}
		$this->assertSame([], $adminDb->commands);
		$this->assertSame("keep me", file_get_contents($this->folder."/old.sql"));
	}

	function testFailedDumpRemovesPartialFile() {
		TestsHelper::Print("testing a failed mysqldump removes the partial file and the defaults file, and throws...");
		$adminDb = $this->Fake();
		$adminDb->exitCode = 2;
		try {
			$adminDb->DoBackup("broken");
			$this->fail("no exception");
		} catch(MagratheaException $ex) {
			$this->assertSame("mysqldump failed (exit code 2): mysqldump: Got error: 1045", $ex->getMessage());
		}
		$this->assertFileDoesNotExist($this->folder."/broken.sql");
		$this->assertFileDoesNotExist($adminDb->defaultsSeen[0]["file"], "defaults file deleted after failure");
	}

	function testDefaultsFileQuoting() {
		TestsHelper::Print("testing the defaults file quotes values and doubles backslashes...");
		$this->SetDbConfig(["db_pass" => 'a"b\\c', "db_user" => "o'neil", "db_port" => null] + $this->db);
		$adminDb = $this->Fake();
		$adminDb->DoBackup("quotes");
		$this->assertSame(
			"[client]\nuser=\"o'neil\"\npassword='a\"b\\\\c'\nhost=\"localhost\"\n",
			$adminDb->defaultsSeen[0]["content"]
		);
	}

	function testDefaultsFileRejectsUnquotableValues() {
		TestsHelper::Print("testing a password with both quote characters or a line break throws, without running mysqldump...");
		foreach(['a"b\'c', "a\nb"] as $pass) {
			$this->SetDbConfig(["db_pass" => $pass] + $this->db);
			$adminDb = $this->Fake();
			try {
				$adminDb->DoBackup("nope");
				$this->fail("accepted the password");
			} catch(MagratheaException $ex) {
				$this->assertStringContainsString("password", $ex->getMessage());
			}
			$this->assertSame([], $adminDb->commands);
			$this->assertFileDoesNotExist($this->folder."/nope.sql");
		}
	}

	function testRunProcessUsesNoShell() {
		TestsHelper::Print("testing RunProcess() passes arguments as they are, without a shell...");
		$adminDb = $this->Fake();
		$arg = "a; echo injected `id` \$(id) 'q' \"d\"";
		$rs = $adminDb->RealRunProcess([PHP_BINARY, "-r", 'fwrite(STDERR, "err"); echo $argv[1]; exit(3);', $arg]);
		$this->assertSame(["code" => 3, "stdout" => $arg, "stderr" => "err"], $rs);
	}

	function testCreateBackupFileResponse() {
		TestsHelper::Print("testing the backup API answers the file and size, never the command...");
		AdminDatabase::MockClass($this->Fake());
		$rs = (new DatabaseApi())->CreateBackupFile(["filename" => "api_backup"]);
		$this->assertSame(["success" => true, "file" => "api_backup.sql", "size" => 8], $rs);
		$this->assertArrayNotHasKey("command", $rs);
		$this->assertFalse(method_exists(AdminDatabase::class, "GetCommand"));
	}

}
