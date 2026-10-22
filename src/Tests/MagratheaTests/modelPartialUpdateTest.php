<?php

use Magrathea2\DB\Database;
use Magrathea2\MagratheaModel;
use Magrathea2\Tests\TestsHelper;

// Database that records PrepareAndExecute() instead of running it, and answers QueryRow() with $row
class PartialUpdateRecordingDatabase extends Database {
	public $executed = [];
	public $row = [];
	public function PrepareAndExecute($query, $arrTypes, $arrValues) {
		$this->executed[] = ["query" => $query, "types" => $arrTypes, "values" => $arrValues];
		return 9;
	}
	public function QueryRow(string $sql): array|object {
		return $this->row;
	}
}

class PartialUpdateTestModel extends MagratheaModel {
	protected $dbTable = "partial_update_test";
	protected $dbPk = "id";
	protected $dbValues = [
		"id" => "int",
		"name" => "string",
		"bio" => "text",
		"stock" => "int",
		"active" => "boolean",
		"parent_id" => "int",
		"created_at" => "datetime",
		"updated_at" => "datetime",
	];
	protected $autoload = null;
	public $id, $name, $bio, $stock, $active, $parent_id, $created_at, $updated_at;
	public function __construct($id = 0, bool $strictTypes = false) {
		$this->strictTypes = $strictTypes;
		if(!empty($id)) $this->GetById($id);
	}
}

/**
 * 2.4.0 item 16: a loaded object's Update() writes the fields changed since load
 * (plain assignments included), plus the ones set through Set(), plus updated_at.
 * Objects that were never loaded keep the 2.3.5 rule.
 * 2.4.0 item 17: int/boolean fields that are null (loaded objects) or "" are written as NULL.
 */
class modelPartialUpdateTest extends \PHPUnit\Framework\TestCase {

	private $previousDb;
	private PartialUpdateRecordingDatabase $db;

	protected function setUp(): void {
		parent::setUp();
		$this->previousDb = Database::Instance();
		$this->db = PartialUpdateRecordingDatabase::Instance();
		$this->db->executed = [];
		$this->db->row = [];
		Database::MockClass($this->db);
	}
	protected function tearDown(): void {
		Database::MockClass($this->previousDb);
		parent::tearDown();
	}

	private function row(array $overrides = []): array {
		return array_merge([
			"id" => "3",
			"name" => "Widget",
			"bio" => "",
			"stock" => "5",
			"active" => "0",
			"created_at" => "2020-01-01 00:00:00",
			"updated_at" => "2020-01-01 00:00:00",
		], $overrides);
	}
	private function Loaded(bool $strictTypes = false, array $overrides = []): PartialUpdateTestModel {
		$model = new PartialUpdateTestModel(0, $strictTypes);
		$model->LoadObjectFromTableRow($this->row($overrides));
		return $model;
	}
	private function UpdatedFields(): array {
		$run = end($this->db->executed);
		preg_match_all("/`(\w+)`= \?/", $run["query"], $matches);
		return array_values(array_diff($matches[1], ["id"]));
	}

	function testSetThenPlainAssignmentWritesBoth() {
		TestsHelper::Print("testing a loaded object writes plain assignments made after a Set()...");
		$model = $this->Loaded();
		$model->Set("name", "Gadget");
		$model->bio = "new bio";
		$model->Update();
		$this->assertSame(["name", "bio", "updated_at"], $this->UpdatedFields());
		$run = end($this->db->executed);
		$this->assertSame("Gadget", $run["values"]["name"]);
		$this->assertSame("new bio", $run["values"]["bio"]);
	}

	function testPlainAssignmentOnlyWritesThatField() {
		TestsHelper::Print("testing a loaded object writes only the assigned field...");
		$model = $this->Loaded();
		$model->stock = 4;
		$model->Update();
		$this->assertSame(["stock", "updated_at"], $this->UpdatedFields());
	}

	function testNothingChangedWritesOnlyUpdatedAt() {
		TestsHelper::Print("testing a loaded object with no changes writes only updated_at...");
		$model = $this->Loaded();
		$model->Update();
		$this->assertSame(["updated_at"], $this->UpdatedFields());
	}

	function testConstructorLoadTakesSnapshot() {
		TestsHelper::Print("testing new Model(\$id) (GetById) marks the object as loaded...");
		$row = [];
		foreach($this->row() as $field => $value) $row["partial_update_test/".$field] = $value;
		$this->db->row = $row;
		$model = new PartialUpdateTestModel(3);
		$model->name = "Gadget";
		$model->Update();
		$this->assertSame(["name", "updated_at"], $this->UpdatedFields());
	}

	function testStrictAndLooseComparisonIgnoresType() {
		TestsHelper::Print("testing 5 vs \"5\" and true vs \"1\" are not changes...");
		$strict = $this->Loaded(true, ["active" => "1"]);
		$this->assertSame(5, $strict->stock);
		$strict->stock = "5";
		$strict->active = "1";
		$strict->Update();
		$this->assertSame(["updated_at"], $this->UpdatedFields());

		$loose = $this->Loaded(false);
		$loose->stock = 5;
		$loose->active = false;
		$loose->Update();
		$this->assertSame(["updated_at"], $this->UpdatedFields());
	}

	function testBooleanChangeIsWritten() {
		TestsHelper::Print("testing a changed boolean is written...");
		$model = $this->Loaded(true);
		$this->assertFalse($model->active);
		$model->active = true;
		$model->Update();
		$this->assertSame(["active", "updated_at"], $this->UpdatedFields());
	}

	function testNullIsDifferentFromEmptyString() {
		TestsHelper::Print("testing null vs \"\" counts as a change (both ways)...");
		$model = $this->Loaded();
		$model->bio = null;
		$model->Update();
		$this->assertSame(["bio", "updated_at"], $this->UpdatedFields());

		$model = $this->Loaded(false, ["bio" => null]);
		$model->bio = "";
		$model->Update();
		$this->assertSame(["bio", "updated_at"], $this->UpdatedFields());
	}

	function testFieldsMissingFromRowAreNotWritten() {
		TestsHelper::Print("testing a partial row doesn't write the columns it didn't have...");
		$model = new PartialUpdateTestModel();
		$model->LoadObjectFromTableRow(["id" => "3", "name" => "Widget"]);
		$model->name = "Gadget";
		$model->Update();
		$this->assertSame(["name", "updated_at"], $this->UpdatedFields());
	}

	function testSnapshotAndDirtyValuesResetAfterUpdate() {
		TestsHelper::Print("testing a second Update() doesn't rewrite what the first one saved...");
		$model = $this->Loaded();
		$model->Set("name", "Gadget");
		$model->bio = "new bio";
		$model->Update();
		$model->Update();
		$this->assertSame(["updated_at"], $this->UpdatedFields());
	}

	function testInsertMarksObjectAsLoaded() {
		TestsHelper::Print("testing Update() after Insert() writes only the changes...");
		$model = new PartialUpdateTestModel();
		$model->Set("name", "Widget");
		$model->bio = "bio";
		$this->assertSame(9, $model->Insert());
		$model->stock = 2;
		$model->Update();
		$this->assertSame(["stock", "updated_at"], $this->UpdatedFields());
	}

	function testInsertWithPkMarksObjectAsLoaded() {
		TestsHelper::Print("testing Update() after InsertWithPk() writes only the changes...");
		$model = new PartialUpdateTestModel();
		$model->id = 3;
		$model->name = "Widget";
		$model->InsertWithPk();
		$model->bio = "bio";
		$model->Update();
		$this->assertSame(["bio", "updated_at"], $this->UpdatedFields());
	}

	function testNeverLoadedWithSetKeeps235Rule() {
		TestsHelper::Print("testing a never-loaded object with Set() writes only the Set() fields...");
		$model = new PartialUpdateTestModel();
		$model->id = 3;
		$model->Set("name", "Gadget");
		$model->bio = "dropped, as in 2.3.5";
		$model->Update();
		$this->assertSame(["name"], $this->UpdatedFields());
	}

	function testNeverLoadedWithoutSetKeeps235Rule() {
		TestsHelper::Print("testing a never-loaded object without Set() writes every field (null ints skipped)...");
		$model = new PartialUpdateTestModel();
		$model->id = 3;
		$model->name = "Gadget";
		$model->Update();
		$this->assertSame(["name", "bio", "updated_at"], $this->UpdatedFields());
	}

	function testInsertWritesRelatedModelId() {
		TestsHelper::Print("testing Insert() writes a related model's id, not the object...");
		$model = new PartialUpdateTestModel();
		$model->name = "child";
		$model->parent_id = $this->Loaded(false, ["id" => "4"]);
		$model->Insert();
		$run = end($this->db->executed);
		$this->assertSame("4", $run["values"]["parent_id"]);
	}

	function testRelatedModelWithoutIdIsInsertedFirst() {
		TestsHelper::Print("testing Update() inserts a related model without id and writes the new id...");
		$parent = new PartialUpdateTestModel();
		$parent->name = "parent";
		$model = $this->Loaded();
		$model->parent_id = $parent;
		$model->Update();
		$this->assertCount(2, $this->db->executed);
		$this->assertStringStartsWith("INSERT INTO partial_update_test", $this->db->executed[0]["query"]);
		$this->assertSame(["parent_id", "updated_at"], $this->UpdatedFields());
		$this->assertSame(9, $this->db->executed[1]["values"]["parent_id"]);

		$model->Update();
		$this->assertSame(["updated_at"], $this->UpdatedFields());
	}

	function testRelatedModelIsComparedById() {
		TestsHelper::Print("testing a related model with the loaded id is not a change...");
		$model = $this->Loaded(false, ["parent_id" => "4"]);
		$model->parent_id = $this->Loaded(false, ["id" => "4"]);
		$model->Update();
		$this->assertSame(["updated_at"], $this->UpdatedFields());

		$model->parent_id = $this->Loaded(false, ["id" => "5"]);
		$model->Update();
		$this->assertSame(["parent_id", "updated_at"], $this->UpdatedFields());
	}

	// item 17: null ints

	private function LastValues(): array {
		return end($this->db->executed)["values"];
	}

	function testLoadedNullIntIsWrittenAsNull() {
		TestsHelper::Print("testing a loaded object writes a nulled int as NULL (not skipped, not 0)...");
		$model = $this->Loaded(false, ["parent_id" => "4"]);
		$model->parent_id = null;
		$model->Update();
		$this->assertSame(["parent_id", "updated_at"], $this->UpdatedFields());
		$this->assertArrayHasKey("parent_id", $this->LastValues());
		$this->assertNull($this->LastValues()["parent_id"]);
	}

	function testLoadedSetNullIntIsWrittenAsNull() {
		TestsHelper::Print("testing Set(int, null) on a loaded object writes NULL...");
		$model = $this->Loaded(true);
		$model->Set("stock", null);
		$model->Update();
		$this->assertSame(["stock", "updated_at"], $this->UpdatedFields());
		$this->assertNull($this->LastValues()["stock"]);
	}

	function testEmptyStringIntIsWrittenAsNull() {
		TestsHelper::Print("testing \"\" in an int field is written as NULL, loaded or not...");
		$model = $this->Loaded(false, ["parent_id" => "4"]);
		$model->Assign(["parent_id" => ""]);
		$model->Update();
		$this->assertNull($this->LastValues()["parent_id"]);

		$model = new PartialUpdateTestModel();
		$model->id = 3;
		$model->Set("parent_id", "");
		$model->Update();
		$this->assertSame(["parent_id"], $this->UpdatedFields());
		$this->assertNull($this->LastValues()["parent_id"]);
	}

	function testNullBooleanIsWrittenAsNullAndFalseAsZero() {
		TestsHelper::Print("testing a loaded nullable boolean: null → NULL, false → 0...");
		$model = $this->Loaded(true, ["active" => "1"]);
		$model->active = null;
		$model->Update();
		$this->assertNull($this->LastValues()["active"]);

		$model->active = false;
		$model->Update();
		$this->assertSame(0, $this->LastValues()["active"]);
	}

	function testIntValuesAreBoundAsInts() {
		TestsHelper::Print("testing int fields are written through intval()...");
		$model = $this->Loaded();
		$model->stock = "7";
		$model->Update();
		$this->assertSame(7, $this->LastValues()["stock"]);
	}

	function testNeverLoadedSetNullIntIsStillSkipped() {
		TestsHelper::Print("testing a never-loaded object still skips null ints (2.3.5 rule)...");
		$model = new PartialUpdateTestModel();
		$model->id = 3;
		$model->Set("name", "Gadget");
		$model->Set("parent_id", null);
		$model->Update();
		$this->assertSame(["name"], $this->UpdatedFields());
	}

}
