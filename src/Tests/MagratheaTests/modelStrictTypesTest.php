<?php

use Magrathea2\MagratheaModel;
use Magrathea2\Exceptions\MagratheaModelException;
use Magrathea2\Tests\TestsHelper;

class StrictTypesTestModel extends MagratheaModel {
	protected $dbTable = "strict_types_test";
	protected $dbPk = "id";
	protected $dbValues = [
		"id" => "int",
		"name" => "string",
		"bio" => "text",
		"price" => "float",
		"active" => "boolean",
		"born_on" => "date",
		"reference" => "uuid",
	];
	public $id, $name, $bio, $price, $active, $born_on, $reference, $created_at, $updated_at;
	public $extra_joined_column;
	public $strictTypes;
	public function __construct(bool $strictTypes = false) {
		$this->strictTypes = $strictTypes;
	}
	public function Insert() {}
	public function Update() {}
}

class modelStrictTypesTest extends \PHPUnit\Framework\TestCase {

	protected function setUp(): void {
		parent::setUp();
		// avoids the Debugger trying to log/display the exceptions this suite intentionally throws
		TestsHelper::Debug();
	}

	private function row(array $overrides = []): array {
		return array_merge([
			"id" => "42",
			"name" => "Widget",
			"bio" => "A widget.",
			"price" => "9.99",
			"active" => "1",
			"born_on" => "2020-01-01",
			"reference" => "0190f1a0-0000-7000-8000-000000000000",
			"created_at" => "2020-01-01 00:00:00",
		], $overrides);
	}

	function testLooseModeKeepsRawStrings() {
		TestsHelper::Print("testing strictTypes=false leaves values as the driver returned them");
		$model = new StrictTypesTestModel(false);
		$model->LoadObjectFromTableRow($this->row());
		$this->assertSame("42", $model->id);
		$this->assertSame("9.99", $model->price);
		$this->assertSame("1", $model->active);
	}

	function testStrictModeCastsDeclaredTypes() {
		TestsHelper::Print("testing strictTypes=true casts int/float/boolean to native PHP types");
		$model = new StrictTypesTestModel(true);
		$model->LoadObjectFromTableRow($this->row());
		$this->assertSame(42, $model->id);
		$this->assertIsInt($model->id);
		$this->assertSame(9.99, $model->price);
		$this->assertIsFloat($model->price);
		$this->assertTrue($model->active);
		$this->assertIsBool($model->active);
	}

	function testStrictModeLeavesTextDateUuidUntouched() {
		TestsHelper::Print("testing strictTypes=true leaves string/text/date/uuid fields as strings");
		$model = new StrictTypesTestModel(true);
		$model->LoadObjectFromTableRow($this->row());
		$this->assertSame("Widget", $model->name);
		$this->assertSame("A widget.", $model->bio);
		$this->assertSame("2020-01-01", $model->born_on);
		$this->assertSame("0190f1a0-0000-7000-8000-000000000000", $model->reference);
		$this->assertSame("2020-01-01 00:00:00", $model->created_at);
	}

	function testStrictModeKeepsNullAsNull() {
		TestsHelper::Print("testing strictTypes=true never casts null, even for int/float/boolean fields");
		$model = new StrictTypesTestModel(true);
		$model->LoadObjectFromTableRow($this->row(["id" => "1", "price" => null, "active" => null]));
		$this->assertNull($model->price);
		$this->assertNull($model->active);
	}

	function testStrictModeLeavesUnmappedColumnsUntouched() {
		TestsHelper::Print("testing strictTypes=true leaves columns with no \$dbValues entry (e.g. join aliases) as-is");
		$model = new StrictTypesTestModel(true);
		$model->LoadObjectFromTableRow($this->row(["extra_joined_column" => "raw"]));
		$this->assertSame("raw", $model->extra_joined_column);
	}

	function testStrictModeThrowsOnBadIntCast() {
		TestsHelper::Print("testing strictTypes=true throws MagratheaModelException on schema drift (non-numeric string in an int column)");
		$this->expectException(MagratheaModelException::class);
		$model = new StrictTypesTestModel(true);
		$model->LoadObjectFromTableRow($this->row(["id" => "not-a-number"]));
	}

	function testStrictModeThrowsOnBadFloatCast() {
		TestsHelper::Print("testing strictTypes=true throws MagratheaModelException on a non-numeric float column");
		$this->expectException(MagratheaModelException::class);
		$model = new StrictTypesTestModel(true);
		$model->LoadObjectFromTableRow($this->row(["price" => "free"]));
	}
}
