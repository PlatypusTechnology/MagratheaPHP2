<?php

use Magrathea2\DB\Database;
use Magrathea2\MagratheaModel;
use Magrathea2\Tests\TestsHelper;

// Database that records PrepareAndExecute() instead of running it
class TimestampsRecordingDatabase extends Database {
	public $executed = [];
	public function PrepareAndExecute($query, $arrTypes, $arrValues) {
		$this->executed[] = ["query" => $query, "types" => $arrTypes, "values" => $arrValues];
		return 7;
	}
}

class TimestampsTestModel extends MagratheaModel {
	protected $dbTable = "timestamps_test";
	protected $dbPk = "id";
	protected $dbValues = [
		"id" => "int",
		"name" => "string",
		"created_at" => "datetime",
		"updated_at" => "datetime",
	];
	protected $autoload = null;
	public $id, $name, $created_at, $updated_at;
}

/**
 * Pins how Insert()/Update() handle created_at / updated_at (2.4.0 item 15, kept as is):
 * Insert() leaves unset timestamps to the database (DEFAULT CURRENT_TIMESTAMP or a trigger),
 * Update() always writes updated_at with PHP's now().
 */
class modelTimestampsTest extends \PHPUnit\Framework\TestCase {

	private $previousDb;
	private TimestampsRecordingDatabase $db;

	protected function setUp(): void {
		parent::setUp();
		$this->previousDb = Database::Instance();
		$this->db = TimestampsRecordingDatabase::Instance();
		$this->db->executed = [];
		Database::MockClass($this->db);
	}
	protected function tearDown(): void {
		Database::MockClass($this->previousDb);
		parent::tearDown();
	}

	private function LastExecuted(): array {
		return end($this->db->executed);
	}

	function testInsertLeavesUnsetTimestampsToTheDatabase() {
		TestsHelper::Print("testing Insert() doesn't send created_at/updated_at when they're unset...");
		$model = new TimestampsTestModel();
		$model->name = "new";
		$this->assertSame(7, $model->Insert());
		$run = $this->LastExecuted();
		$this->assertSame("INSERT INTO timestamps_test (`name`) VALUES (?) ", $run["query"]);
		$this->assertSame(["name" => "new"], $run["values"]);
	}

	function testInsertSetsPhpTimestampsOnTheObject() {
		TestsHelper::Print("testing Insert() fills created_at/updated_at on the object with now()...");
		$model = new TimestampsTestModel();
		$model->name = "new";
		$model->Insert();
		$this->assertMatchesRegularExpression("/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/", $model->created_at);
		$this->assertMatchesRegularExpression("/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/", $model->updated_at);
	}

	function testInsertReplacesAssignedTimestampsWithNow() {
		TestsHelper::Print("testing Insert() sends now() for timestamps assigned on the object, not the assigned value...");
		$model = new TimestampsTestModel();
		$model->name = "imported";
		$model->created_at = "2001-01-01 00:00:00";
		$model->Insert();
		$run = $this->LastExecuted();
		$this->assertSame("INSERT INTO timestamps_test (`name`,`created_at`) VALUES (?, ?) ", $run["query"]);
		$this->assertNotSame("2001-01-01 00:00:00", $run["values"]["created_at"]);
		$this->assertMatchesRegularExpression("/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/", $run["values"]["created_at"]);
	}

	function testSetRefusesTimestamps() {
		TestsHelper::Print("testing Set() ignores created_at/updated_at...");
		$model = new TimestampsTestModel();
		$model->Set("created_at", "2001-01-01 00:00:00");
		$this->assertNull($model->created_at);
	}

	function testUpdateWritesUpdatedAtNeverCreatedAt() {
		TestsHelper::Print("testing Update() writes updated_at with now() and never created_at...");
		$model = new TimestampsTestModel();
		$model->id = 3;
		$model->name = "changed";
		$model->created_at = "2001-01-01 00:00:00";
		$model->updated_at = "2001-01-01 00:00:00";
		$model->Update();
		$run = $this->LastExecuted();
		$this->assertStringNotContainsString("created_at", $run["query"]);
		$this->assertStringContainsString("`updated_at`= ?", $run["query"]);
		$this->assertNotSame("2001-01-01 00:00:00", $run["values"]["updated_at"]);
	}

}
