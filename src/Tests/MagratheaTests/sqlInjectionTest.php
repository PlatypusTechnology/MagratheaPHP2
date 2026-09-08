<?php

use Magrathea2\DB\Database;
use Magrathea2\DB\Query;
use Magrathea2\Debugger;
use Magrathea2\Exceptions\MagratheaModelException;
use Magrathea2\MagratheaModel;
use Magrathea2\Tests\TestsHelper;

class SqlInjectionTestModel extends MagratheaModel {
	protected $dbTable = "sql_injection_test";
	protected $dbPk = "reference";
	protected $dbValues = [
		"reference" => "uuid",
		"name" => "string",
	];
	protected $autoload = null;
	public $reference, $name, $created_at, $updated_at;
}

/**
 * Regression tests for the SQL injection fix in Query::BuildWhere(), QueryUpdate::SQL(),
 * QueryInsert::SQL() and MagratheaModel::GetById() (non-int PK branch).
 * All of them used to interpolate raw values straight into the SQL string.
 */
class sqlInjectionTest extends \PHPUnit\Framework\TestCase {

	protected function setUp(): void {
		parent::setUp();
		// Database::Escape() needs a connection; the mock provides a connection-free equivalent.
		// Mock() replaces the singleton instance itself, so only call it once - a second call
		// would land on the DatabaseSimulate instance, which has no Mock() method of its own.
		if (!(Database::Instance() instanceof \Magrathea2\DB\DatabaseSimulate)) {
			Database::Instance()->Mock();
		}
	}

	function testWhereArrayEscapesSingleQuotes() {
		TestsHelper::Print("testing Query::Where(array) escapes a single quote instead of letting it break out of the SQL string");
		$malicious = "x' OR '1'='1";
		$sql = Query::Select()->Table("users")->Where(["name" => $malicious])->SQL();
		$this->assertStringContainsString("`name` = 'x\\' OR \\'1\\'=\\'1'", $sql);
		$this->assertStringNotContainsString("`name` = 'x' OR '1'='1'", $sql);
	}

	function testWhereArrayEscapesInjectedStatements() {
		TestsHelper::Print("testing a stacked-query payload is escaped, not left able to close the string early");
		$malicious = "x'; DROP TABLE users; -- ";
		$sql = Query::Select()->Table("users")->Where(["name" => $malicious])->SQL();
		$this->assertStringContainsString("\\'; DROP TABLE users", $sql);
		$this->assertStringNotContainsString("`name` = 'x'; DROP TABLE users", $sql);
	}

	function testWhereArrayIsNoopForBenignValues() {
		TestsHelper::Print("testing escaping is a no-op for values with no special characters (behavior-preserving)");
		$sql = Query::Select()->Table("users")->Where(["name" => "regular value"])->SQL();
		$this->assertStringContainsString("`name` = 'regular value'", $sql);
	}

	function testUpdateSetEscapesSingleQuotes() {
		TestsHelper::Print("testing QueryUpdate::Set() escapes a single quote in the value being SET");
		$malicious = "x', admin = 1 -- ";
		$sql = Query::Update()->Table("users")->Set("name", $malicious)->SQL();
		$this->assertStringContainsString("name = 'x\\', admin = 1", $sql);
		$this->assertStringNotContainsString("name = 'x', admin = 1", $sql);
	}

	function testInsertValuesEscapesSingleQuotes() {
		TestsHelper::Print("testing QueryInsert::Values() escapes a single quote in an inserted value");
		$malicious = "x', (SELECT password FROM users LIMIT 1))-- ";
		$sql = Query::Insert()->Table("users")->Values(["name" => $malicious])->SQL();
		$this->assertStringContainsString("'x\\', (SELECT password FROM users LIMIT 1))-- '", $sql);
	}

	function testGetByIdEscapesNonIntPrimaryKey() {
		TestsHelper::Print("testing MagratheaModel::GetById() escapes a non-int PK value before it reaches SQL");
		Debugger::Instance()->SetDebug()->LogQueries(true);

		$model = new SqlInjectionTestModel();
		$malicious = "x' OR '1'='1";
		try {
			$model->GetById($malicious);
			$this->fail("Expected MagratheaModelException (the mocked DB always returns no rows)");
		} catch (MagratheaModelException $ex) {
			// expected: DatabaseSimulate::QueryRow() always returns an empty result
		}

		$debugItems = new \ReflectionProperty(Debugger::class, "debugItems");
		$debugItems->setAccessible(true);
		$queryLog = array_values(array_filter($debugItems->getValue(Debugger::Instance()), fn($item) => is_string($item) && str_contains($item, "SELECT")));
		$this->assertNotEmpty($queryLog, "expected the SELECT built by GetById() to have been logged");
		$loggedQuery = end($queryLog);

		$this->assertStringContainsString("x\\' OR \\'1\\'=\\'1", $loggedQuery);
		$this->assertStringNotContainsString("= 'x' OR '1'='1'", $loggedQuery);

		Debugger::Instance()->SetType(Debugger::NONE);
	}
}
