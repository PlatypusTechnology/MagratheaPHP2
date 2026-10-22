<?php

use Magrathea2\DB\Database;
use Magrathea2\DB\DatabaseSimulate;
use Magrathea2\Debugger;
use Magrathea2\MagratheaModel;
use Magrathea2\Tests\TestsHelper;

class BindTypesIntPkModel extends MagratheaModel {
	protected $dbTable = "bind_types_int";
	protected $dbPk = "id";
	protected $dbValues = [
		"id" => "int",
		"name" => "string",
		"stock" => "int",
		"active" => "boolean",
		"price" => "float",
		"created_at" => "datetime",
		"updated_at" => "datetime",
	];
	protected $autoload = null;
	public $id, $name, $stock, $active, $price, $created_at, $updated_at;
}

class BindTypesUuidPkModel extends MagratheaModel {
	protected $dbTable = "bind_types_uuid";
	protected $dbPk = "uuid";
	protected $dbValues = [
		"uuid" => "uuid",
		"name" => "string",
		"stock" => "int",
	];
	protected $autoload = null;
	public $uuid, $name, $stock, $created_at, $updated_at;
}

class BindTypesDecimalModel extends MagratheaModel {
	protected $dbTable = "bind_types_decimal";
	protected $dbPk = "id";
	protected $dbValues = [
		"id" => "int",
		"amount" => "decimal",
	];
	protected $autoload = null;
	public $id, $amount, $created_at, $updated_at;
}

/**
 * 2.4.0 item 19: model writes bind int/boolean fields as "i" (they were all "s"), float stays "s"
 * (exact values), and Update() binds the WHERE PK by the PK field's type (it was always "s").
 * Read from DatabaseSimulate's query log: the bind types are the first logged value.
 */
class modelBindTypesTest extends \PHPUnit\Framework\TestCase {

	private $previousDb;

	protected function setUp(): void {
		parent::setUp();
		$this->previousDb = Database::Instance();
		Database::MockClass(DatabaseSimulate::Instance());
		Debugger::Instance()->SetDebug()->LogQueries(true);
	}
	protected function tearDown(): void {
		Debugger::Instance()->LogQueries(false);
		TestsHelper::Debug();
		Database::MockClass($this->previousDb);
		parent::tearDown();
	}

	// bind types of the last logged query starting with $start
	private function LastBindTypes(string $start): string {
		$debugItems = new \ReflectionProperty(Debugger::class, "debugItems");
		$logged = array_values(array_filter(
			$debugItems->getValue(Debugger::Instance()),
			fn($item) => is_string($item) && str_starts_with($item, "query run: [".$start)
		));
		$this->assertNotEmpty($logged, "expected a logged ".$start." query");
		$this->assertMatchesRegularExpression('/\[0\] => (\w*)\n/', end($logged));
		preg_match('/\[0\] => (\w*)\n/', end($logged), $m);
		return $m[1];
	}

	private function IntPkModel(): BindTypesIntPkModel {
		$model = new BindTypesIntPkModel();
		$model->name = "Widget";
		$model->stock = "5";
		$model->active = true;
		$model->price = "0.10";
		return $model;
	}

	function testInsertBindsIntsAsIntegers() {
		TestsHelper::Print("testing Insert() binds int/boolean fields as i, float and string as s...");
		$this->IntPkModel()->Insert();
		// name, stock, active, price (id is auto-increment, unset timestamps are skipped)
		$this->assertSame("siis", $this->LastBindTypes("INSERT INTO bind_types_int"));
	}

	function testUpdateBindsIntPkAsInteger() {
		TestsHelper::Print("testing Update() binds an int PK as i...");
		$model = $this->IntPkModel();
		$model->id = 3;
		$model->Update();
		// name, stock, active, price, updated_at, then the WHERE PK
		$this->assertSame("siissi", $this->LastBindTypes("UPDATE bind_types_int"));
	}

	function testUpdateBindsUuidPkAsString() {
		TestsHelper::Print("testing Update() binds a uuid PK as s...");
		$model = new BindTypesUuidPkModel();
		$model->uuid = "0190a5b2-6d1e-7c3a-9f00-123456789abc";
		$model->name = "Widget";
		$model->stock = 2;
		$model->Update();
		$this->assertSame("sis", $this->LastBindTypes("UPDATE bind_types_uuid"));
	}

	function testDeleteBindsPkByType() {
		TestsHelper::Print("testing Delete() binds the PK by its type...");
		$model = new BindTypesIntPkModel();
		$model->id = 3;
		$model->Delete();
		$this->assertSame("i", $this->LastBindTypes("DELETE FROM bind_types_int"));

		$model = new BindTypesUuidPkModel();
		$model->uuid = "0190a5b2-6d1e-7c3a-9f00-123456789abc";
		$model->Delete();
		$this->assertSame("s", $this->LastBindTypes("DELETE FROM bind_types_uuid"));
	}

	function testDecimalBindsAsStringUnchanged() {
		TestsHelper::Print("testing a decimal field binds as s and its exact value is never intval()'d...");
		$model = new BindTypesDecimalModel();
		$model->amount = "12345678901234567.89";
		$model->Insert();
		$this->assertSame("s", $this->LastBindTypes("INSERT INTO bind_types_decimal"));
		$model = new BindTypesDecimalModel();
		$model->id = 3;
		$model->amount = "12345678901234567.89";
		$model->Update();
		// amount, then the WHERE PK
		$this->assertSame("si", $this->LastBindTypes("UPDATE bind_types_decimal"));
		$this->assertSame("12345678901234567.89", $model->amount);
	}

}
