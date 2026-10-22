<?php

use Magrathea2\Config;
use Magrathea2\MagratheaPHP;
use Magrathea2\DB\Database;
use Magrathea2\Debugger;
use Magrathea2\Exceptions\MagratheaDBException;
use Magrathea2\Exceptions\MagratheaDBConnectionLostException;
use Magrathea2\Tests\TestsHelper;

// the CLI running the suite may not have the mysqli extension
if(!class_exists("mysqli_sql_exception")) {
	class mysqli_sql_exception extends RuntimeException {}
}

// Fake mysqli result: only what Database reads
class FakeMysqliResult {
	public $num_rows;
	public $closed = false;
	private $rows;
	public function __construct(array $rows) {
		$this->rows = $rows;
		$this->num_rows = count($rows);
	}
	public function fetch_assoc() { return array_shift($this->rows); }
	public function fetch_row() {
		$row = array_shift($this->rows);
		return $row ? array_values($row) : null;
	}
	public function close() { $this->closed = true; }
	public function free() { $this->closed = true; }
}

// Fake mysqli statement: reports execute() to its connection
class FakeMysqliStmt {
	public $error = "";
	public $errno = 0;
	public $insert_id = 0;
	public $affected_rows = 0;
	private $conn;
	public function __construct(FakeMysqli $conn) { $this->conn = $conn; }
	public function bind_param($types, ...$values) {
		$this->conn->calls[] = ["bind_param", $types];
		return true;
	}
	public function execute() {
		$this->conn->calls[] = ["execute"];
		$fail = $this->conn->TakeFailure("execute");
		if($fail) {
			if($fail["mode"] === "exception") throw new mysqli_sql_exception($fail["error"], $fail["errno"]);
			$this->errno = $fail["errno"];
			$this->error = $fail["error"];
			return false;
		}
		$this->insert_id = $this->conn->insert_id;
		$this->affected_rows = $this->conn->affected_rows;
		return true;
	}
	public function close() { return true; }
}

// Fake mysqli connection: records every call
class FakeMysqli {
	public $calls = [];
	public $errno = 0;
	public $error = "";
	public $affected_rows = 0;
	public $insert_id = 0;
	public $rows = [];
	public $results = [];
	public $closed = false;
	private $failures = [];

	// the next $on call fails; with $match, the next one whose SQL contains $match
	public function FailNext(string $on, int $errno, string $mode="exception", string $error="error", ?string $match=null) {
		$this->failures[$on] = ["errno" => $errno, "mode" => $mode, "error" => $error, "match" => $match];
	}
	public function TakeFailure(string $on, ?string $sql=null) {
		$fail = $this->failures[$on] ?? null;
		if($fail && $fail["match"] !== null && !str_contains((string)$sql, $fail["match"])) return null;
		unset($this->failures[$on]);
		return $fail;
	}
	public function Called(string $fn): array {
		return array_values(array_filter($this->calls, fn($c) => $c[0] === $fn));
	}

	public function set_charset($charset) {
		$this->calls[] = ["set_charset", $charset];
		return true;
	}
	public function query($sql) {
		$this->calls[] = ["query", $sql];
		$this->errno = 0;
		$this->error = "";
		$fail = $this->TakeFailure("query", $sql);
		if($fail) {
			if($fail["mode"] === "exception") throw new mysqli_sql_exception($fail["error"], $fail["errno"]);
			$this->errno = $fail["errno"];
			$this->error = $fail["error"];
			return false;
		}
		if(stripos(trim($sql), "SELECT") !== 0) return true;
		$result = new FakeMysqliResult($this->rows);
		$this->results[] = $result;
		return $result;
	}
	public function prepare($sql) {
		$this->calls[] = ["prepare", $sql];
		$fail = $this->TakeFailure("prepare");
		if($fail) {
			if($fail["mode"] === "exception") throw new mysqli_sql_exception($fail["error"], $fail["errno"]);
			$this->errno = $fail["errno"];
			$this->error = $fail["error"];
			return false;
		}
		return new FakeMysqliStmt($this);
	}
	public function real_escape_string($value) {
		$this->calls[] = ["real_escape_string", $value];
		return addslashes($value);
	}
	// applies a FailNext($on): throws, or sets the error and returns true (the caller then returns false)
	private function Failing(string $on): bool {
		$fail = $this->TakeFailure($on);
		if(!$fail) return false;
		if($fail["mode"] === "exception") throw new mysqli_sql_exception($fail["error"], $fail["errno"]);
		$this->errno = $fail["errno"];
		$this->error = $fail["error"];
		return true;
	}
	public function autocommit($on) {
		$this->calls[] = ["autocommit", $on];
		return !$this->Failing("autocommit:".($on ? "on" : "off"));
	}
	public function begin_transaction() {
		$this->calls[] = ["begin_transaction"];
		return !$this->Failing("begin_transaction");
	}
	public function commit() {
		$this->calls[] = ["commit"];
		return !$this->Failing("commit");
	}
	public function rollback() {
		$this->calls[] = ["rollback"];
		return !$this->Failing("rollback");
	}
	public function close() {
		$this->calls[] = ["close"];
		$this->closed = true;
		return true;
	}
}

// Database with a fake connection, created through CreateConnection()
class FakeConnectionDatabase extends Database {
	public $created = [];
	public $failNextConnection = false;
	protected function CreateConnection() {
		if($this->failNextConnection) {
			$this->failNextConnection = false;
			throw new MagratheaDBException("Failed to connect to MySQL: (2002) refused");
		}
		$conn = new FakeMysqli();
		$this->created[] = $conn;
		return $conn;
	}
	public function Conn(): ?FakeMysqli {
		return end($this->created) ?: null;
	}
	public static function Fresh(): static {
		$db = static::Instance();
		$db->Disconnect();
		$db->created = [];
		$db->failNextConnection = false;
		$db->SetTimezoneSync(false);
		$db->SetConnection("localhost", "test_db", "user", "pass");
		return $db;
	}
}
// a second database, as an app would declare it
class ReportsFakeConnectionDatabase extends FakeConnectionDatabase {}

// a model and control, to prove model writes and control reads join the transaction
class TransactionTestModel extends \Magrathea2\MagratheaModel {
	protected $dbTable = "transaction_test";
	protected $dbPk = "id";
	protected $dbValues = ["id" => "int", "name" => "string"];
	protected $autoload = null;
	public $id, $name, $created_at, $updated_at;
}
class TransactionTestControl extends \Magrathea2\MagratheaModelControl {
	protected static $modelNamespace = "";
	protected static $modelName = "TransactionTestModel";
	protected static $dbTable = "transaction_test";
}

class databaseConnectionTest extends \PHPUnit\Framework\TestCase {

	protected function setUp(): void {
		parent::setUp();
		TestsHelper::Debug();
	}
	protected function tearDown(): void {
		Database::DisconnectAll();
		parent::tearDown();
	}

	function testPortIsStoredOnlyWhenGiven() {
		TestsHelper::Print("testing SetConnection() keeps the port only when one is given...");
		$db = FakeConnectionDatabase::Fresh();
		$db->SetConnection("db.host", "test_db", "user", "pass", 3307);
		$this->assertStringContainsString("[db_host]=db.host:3307\n", (string)$db);
		$db->SetConnection("db.host", "test_db", "user", "pass");
		$this->assertStringContainsString("[db_host]=db.host\n", (string)$db);
		$db->SetConnection("db.host", "test_db", "user", "pass", "");
		$this->assertStringContainsString("[db_host]=db.host\n", (string)$db);
	}

	function testConnectReadsDbPort() {
		TestsHelper::Print("testing MagratheaPHP::Connect() passes db_port...");
		$previousDb = Database::Instance();
		$db = FakeConnectionDatabase::Fresh();
		Database::MockClass($db);
		try {
			$dev = ["db_host" => "db.host", "db_name" => "test_db", "db_user" => "user", "db_pass" => "pass"];
			Config::Instance()->SetConfig(["general" => ["use_environment" => "dev"], "dev" => $dev + ["db_port" => "3307"]])->SetEnvironment(null);
			MagratheaPHP::Instance()->Connect();
			$this->assertStringContainsString("[db_host]=db.host:3307\n", (string)$db);

			Config::Instance()->SetConfig(["general" => ["use_environment" => "dev"], "dev" => $dev])->SetEnvironment(null);
			MagratheaPHP::Instance()->Connect();
			$this->assertStringContainsString("[db_host]=db.host\n", (string)$db);
		} finally {
			Database::MockClass($previousDb);
			Config::Instance()->SetConfig(null)->SetEnvironment(null);
		}
	}

	function testOneConnectionForEveryCall() {
		TestsHelper::Print("testing every query reuses the same connection...");
		$db = FakeConnectionDatabase::Fresh();
		$this->assertCount(0, $db->created, "connection opens lazily");
		$db->Escape("it's");
		$db->Query("UPDATE t SET a = 1");
		$db->QueryAll("SELECT * FROM t");
		$db->QueryRow("SELECT * FROM t");
		$db->QueryOne("SELECT COUNT(*) FROM t");
		$db->PrepareAndExecute("INSERT INTO t (a) VALUES (?)", ["int"], [1]);
		$db->QueryMulti(["UPDATE t SET a = 2", "UPDATE t SET a = 3"]);
		$db->CloseConnectionThanks();
		$db->QueryAll("SELECT * FROM t");

		$this->assertCount(1, $db->created);
		$conn = $db->Conn();
		$this->assertCount(1, $conn->Called("set_charset"));
		$this->assertCount(7, $conn->Called("query"));
		$this->assertCount(1, $conn->Called("prepare"));
		$this->assertCount(1, $conn->Called("real_escape_string"));
		$this->assertFalse($conn->closed, "CloseConnectionThanks() doesn't close anymore");
	}

	function testDisconnect() {
		TestsHelper::Print("testing Disconnect() closes, and the next call opens a new connection...");
		$db = FakeConnectionDatabase::Fresh();
		$db->QueryAll("SELECT * FROM t");
		$first = $db->Conn();
		$db->Disconnect();
		$this->assertTrue($first->closed);
		$db->Disconnect(); // nothing open: no error
		$db->QueryAll("SELECT * FROM t");
		$this->assertCount(2, $db->created);
		$this->assertNotSame($first, $db->Conn());
	}

	function testInstancesNeverShareConnection() {
		TestsHelper::Print("testing two Database instances have their own connections...");
		$main = FakeConnectionDatabase::Fresh();
		$reports = ReportsFakeConnectionDatabase::Fresh();
		$this->assertNotSame($main, $reports);
		$main->QueryAll("SELECT * FROM main_table");
		$reports->QueryAll("SELECT * FROM reports_table");
		$main->QueryAll("SELECT * FROM main_table");

		$this->assertCount(1, $main->created);
		$this->assertCount(1, $reports->created);
		$this->assertNotSame($main->Conn(), $reports->Conn());
		$this->assertSame([["query", "SELECT * FROM main_table"], ["query", "SELECT * FROM main_table"]], $main->Conn()->Called("query"));
		$this->assertSame([["query", "SELECT * FROM reports_table"]], $reports->Conn()->Called("query"));

		Database::DisconnectAll();
		$this->assertTrue($main->Conn()->closed);
		$this->assertTrue($reports->Conn()->closed);
	}

	function testFailedConnectionIsNotKept() {
		TestsHelper::Print("testing a failed connection is retried on the next call...");
		$db = FakeConnectionDatabase::Fresh();
		$db->failNextConnection = true;
		try {
			$db->QueryAll("SELECT * FROM t");
			$this->fail("expected MagratheaDBException");
		} catch(MagratheaDBException $ex) {
			$this->assertStringContainsString("refused", $ex->getMessage());
		}
		$db->QueryAll("SELECT * FROM t");
		$this->assertCount(1, $db->created);
	}

	public static function connectionLostCases(): array {
		return [
			"2006 thrown" => [2006, "exception"],
			"2013 thrown" => [2013, "exception"],
			"2006 returned" => [2006, "return"],
			"2013 returned" => [2013, "return"],
			"4031 thrown" => [4031, "exception"],
			"4031 returned" => [4031, "return"],
		];
	}

	/**
	 * @dataProvider connectionLostCases
	 */
	function testConnectionLostOnQuery(int $errno, string $mode) {
		TestsHelper::Print("testing a lost connection throws, drops the connection and never retries...");
		$db = FakeConnectionDatabase::Fresh();
		$db->QueryAll("SELECT 1");
		$conn = $db->Conn();
		$conn->FailNext("query", $errno, $mode, "MySQL server has gone away");
		try {
			$db->QueryRow("SELECT * FROM t WHERE id = 1");
			$this->fail("expected MagratheaDBConnectionLostException");
		} catch(MagratheaDBConnectionLostException $ex) {
			$this->assertSame($errno, $ex->getCode());
			$this->assertSame("SELECT * FROM t WHERE id = 1", $ex->query);
			if($mode === "exception") {
				$this->assertInstanceOf(mysqli_sql_exception::class, $ex->getPrevious());
			}
		}
		$this->assertCount(1, array_filter($conn->Called("query"), fn($c) => $c[1] === "SELECT * FROM t WHERE id = 1"), "sent exactly once");
		$this->assertTrue($conn->closed, "dead connection is dropped");

		$db->QueryAll("SELECT 1");
		$this->assertCount(2, $db->created, "next call opens a new connection");
	}

	/**
	 * @dataProvider connectionLostCases
	 */
	function testConnectionLostOnPrepareAndExecute(int $errno, string $mode) {
		TestsHelper::Print("testing a lost connection in PrepareAndExecute() throws with the query data...");
		$db = FakeConnectionDatabase::Fresh();
		$db->Escape("open");
		$db->Conn()->FailNext("execute", $errno, $mode, "Lost connection to MySQL server during query");
		try {
			$db->PrepareAndExecute("UPDATE t SET a = ? WHERE id = ?", ["int", "int"], [5, 1]);
			$this->fail("expected MagratheaDBConnectionLostException");
		} catch(MagratheaDBConnectionLostException $ex) {
			$this->assertSame($errno, $ex->getCode());
			$this->assertSame("UPDATE t SET a = ? WHERE id = ?", $ex->query);
			$this->assertSame([5, 1], $ex->values);
		}
		$this->assertCount(1, $db->Conn()->Called("execute"));
		$this->assertTrue($db->Conn()->closed);

		$db->Escape("reopen");
		$db->Conn()->FailNext("prepare", $errno, $mode);
		$this->expectException(MagratheaDBConnectionLostException::class);
		$db->PrepareAndExecute("UPDATE t SET a = ?", ["int"], [5]);
	}

	function testQueryErrorReachesDebuggerOnce() {
		TestsHelper::Print("testing a query error is added to the Debugger once...");
		$db = FakeConnectionDatabase::Fresh();
		$db->Escape("open");
		$db->Conn()->FailNext("query", 1062, "return", "Duplicate entry");
		Debugger::Instance()->SetDev();
		ob_start();
		try {
			$db->QueryAll("SELECT * FROM t");
			$this->fail("expected MagratheaDBException");
		} catch(MagratheaDBException $ex) {
		} finally {
			$output = ob_get_clean();
			TestsHelper::Debug();
		}
		$this->assertSame(1, substr_count($output, "MagratheaDatabase ERROR"), $output);
	}

	function testOtherErrorsKeepConnection() {
		TestsHelper::Print("testing a non-connection error stays a plain error and keeps the connection...");
		$db = FakeConnectionDatabase::Fresh();
		$db->Escape("open");
		$conn = $db->Conn();

		$conn->FailNext("query", 1062, "return", "Duplicate entry");
		try {
			$db->QueryAll("SELECT * FROM t");
			$this->fail("expected MagratheaDBException");
		} catch(MagratheaDBException $ex) {
			$this->assertNotInstanceOf(MagratheaDBConnectionLostException::class, $ex);
		}

		$conn->FailNext("query", 1062, "exception", "Duplicate entry");
		try {
			$db->QueryRow("SELECT * FROM t");
			$this->fail("expected MagratheaDBException");
		} catch(MagratheaDBException $ex) {
			$this->assertNotInstanceOf(MagratheaDBConnectionLostException::class, $ex);
		}

		$this->assertFalse($conn->closed);
		$db->QueryAll("SELECT * FROM t");
		$this->assertCount(1, $db->created);
	}

	public static function queryErrorCases(): array {
		$cases = [];
		foreach(["QueryAll", "QueryRow", "QueryOne"] as $method) {
			$cases[$method." thrown"] = [$method, "exception"];
			$cases[$method." returned"] = [$method, "return"];
		}
		// Query() returns false on a returned error (mysqli reporting off)
		$cases["Query thrown"] = ["Query", "exception"];
		return $cases;
	}

	/**
	 * @dataProvider queryErrorCases
	 */
	function testQueryErrorsAreWrappedWithErrno(string $method, string $mode) {
		TestsHelper::Print("testing query errors throw MagratheaDBException with MySQL's errno as code...");
		$db = FakeConnectionDatabase::Fresh();
		$db->Escape("open");
		$db->Conn()->FailNext("query", 1054, $mode, "Unknown column 'nope' in 'field list'");
		try {
			$db->$method("SELECT nope FROM t");
			$this->fail("expected MagratheaDBException");
		} catch(MagratheaDBException $ex) {
			$this->assertSame(1054, $ex->getCode());
			$this->assertSame("Unknown column 'nope' in 'field list'", $ex->getMessage());
			$this->assertSame("SELECT nope FROM t", $ex->query);
			if($mode === "exception") {
				$this->assertInstanceOf(mysqli_sql_exception::class, $ex->getPrevious());
				$this->assertSame(1054, $ex->getPrevious()->getCode());
			}
		}
		$this->assertFalse($db->Conn()->closed);
	}

	function testLastAffectedRows() {
		TestsHelper::Print("testing LastAffectedRows()...");
		$db = FakeConnectionDatabase::Fresh();
		$db->Escape("open");
		$db->Conn()->affected_rows = 3;
		$db->Query("UPDATE t SET a = 1");
		$this->assertSame(3, $db->LastAffectedRows());

		$db->Conn()->affected_rows = 1;
		$db->Conn()->insert_id = 77;
		$this->assertSame(77, $db->PrepareAndExecute("INSERT INTO t (a) VALUES (?)", ["int"], [1]), "return value unchanged");
		$this->assertSame(1, $db->LastAffectedRows());
	}

	function testPrepareAndExecuteBindTypes() {
		TestsHelper::Print("testing PrepareAndExecute() maps Magrathea types to bind_param() letters...");
		$db = FakeConnectionDatabase::Fresh();
		$types = ["int", "integer", "boolean", "float", "decimal", "string", "text", "date", "datetime", "uuid", "i"];
		$db->PrepareAndExecute("INSERT INTO t VALUES (?,?,?,?,?,?,?,?,?,?,?)", $types, array_fill(0, count($types), 1));
		// "integer" is what GetDataTypeFromField() returns for int/boolean fields (it was bound as "s");
		// "decimal" (float fields) stays "s" so exact values don't go through a PHP float
		$this->assertSame([["bind_param", "iiidsssssss"]], $db->Conn()->Called("bind_param"));
	}

	function testQueryRowWithoutRowsFreesResult() {
		TestsHelper::Print("testing QueryRow() with no rows frees the result...");
		$db = FakeConnectionDatabase::Fresh();
		$this->assertSame([], $db->QueryRow("SELECT * FROM t WHERE 0"));
		$this->assertTrue($db->Conn()->results[0]->closed);
	}

	function testQueryMultiReturnsSelectRows() {
		TestsHelper::Print("testing QueryMulti() (and a multi-statement Query()) returns SELECT rows...");
		$db = FakeConnectionDatabase::Fresh();
		$db->Escape("open");
		$db->Conn()->rows = [["id" => "1"], ["id" => "2"]];
		$rs = $db->Query("UPDATE t SET a = 1; SELECT id FROM t");
		$this->assertSame([], $rs[0]["result"]);
		$this->assertTrue($rs[0]["success"]);
		$this->assertSame([["id" => "1"], ["id" => "2"]], $rs[1]["result"]);
		$this->assertTrue($db->Conn()->results[0]->closed, "result is freed");

		$rs = $db->QueryMulti(["CREATE TABLE x (id int)"]);
		$this->assertSame(["success" => true], $rs[0]["result"]);
	}

	public static function errorReportingModes(): array {
		return [
			"reporting on (thrown)" => ["exception"],
			"reporting off (returned)" => ["return"],
		];
	}

	/**
	 * @dataProvider errorReportingModes
	 */
	function testPrepareAndExecuteErrorThrows(string $mode) {
		TestsHelper::Print("testing a failed prepare/execute throws MagratheaDBException with query and values, and prints nothing...");
		$db = FakeConnectionDatabase::Fresh();
		$db->Escape("open");
		foreach(["execute", "prepare"] as $on) {
			$db->Conn()->FailNext($on, 1062, $mode, "Duplicate entry '5' for key 'PRIMARY'");
			ob_start();
			try {
				$db->PrepareAndExecute("INSERT INTO t (id) VALUES (?)", ["int"], [5]);
				$this->fail("expected MagratheaDBException");
			} catch(MagratheaDBException $ex) {
				$this->assertNotInstanceOf(MagratheaDBConnectionLostException::class, $ex);
				$this->assertSame("Duplicate entry '5' for key 'PRIMARY'", $ex->getMessage(), $on);
				$this->assertSame(1062, $ex->getCode(), $on);
				$this->assertSame("INSERT INTO t (id) VALUES (?)", $ex->query, $on);
				$this->assertSame([5], $ex->values, $on);
				if($mode === "exception") {
					$this->assertInstanceOf(mysqli_sql_exception::class, $ex->getPrevious(), $on);
				}
			} finally {
				$output = ob_get_clean();
			}
			$this->assertSame("", $output, $on);
		}
		$this->assertFalse($db->Conn()->closed, "a plain error keeps the connection");
	}

	/**
	 * @dataProvider errorReportingModes
	 */
	function testFailedWriteInTransactionRollsBack(string $mode) {
		TestsHelper::Print("testing a failed model write inside Transaction() throws and rolls back...");
		$previousDb = Database::Instance();
		$db = $this->OpenTransactionDb();
		Database::MockClass($db);
		$ran = false;
		try {
			$db->Conn()->FailNext("execute", 1062, $mode, "Duplicate entry");
			$db->Transaction(function() use (&$ran) {
				$model = new TransactionTestModel();
				$model->name = "dup";
				$model->Insert();
				$ran = true;
			});
			$this->fail("expected MagratheaDBException");
		} catch(MagratheaDBException $ex) {
		} finally {
			Database::MockClass($previousDb);
		}
		$this->assertFalse($ran, "Insert() threw, the callback stopped there");
		$this->assertCount(0, $db->Conn()->Called("commit"));
		$this->assertCount(1, $db->Conn()->Called("rollback"));
	}

	/**
	 * @dataProvider errorReportingModes
	 */
	function testQueryMultiNotKillableRecordsFailureAndContinues(string $mode) {
		TestsHelper::Print("testing QueryMulti(\$queries, false) records a failing statement and runs the next ones...");
		$db = FakeConnectionDatabase::Fresh();
		$db->Escape("open");
		$queries = ["UPDATE t SET a = 1", "INSERT INTO nope (a) VALUES (1)", "UPDATE t SET a = 3"];
		$db->Conn()->FailNext("query", 1146, $mode, "Table 'nope' doesn't exist", "nope");
		$rs = $db->QueryMulti($queries, false);
		$this->assertCount(3, $rs);
		$this->assertTrue($rs[0]["success"]);
		$this->assertSame(["query" => $queries[1], "success" => false, "result" => "Table 'nope' doesn't exist"], $rs[1]);
		$this->assertTrue($rs[2]["success"]);
		$this->assertCount(3, $db->Conn()->Called("query"));
	}

	/**
	 * @dataProvider errorReportingModes
	 */
	function testQueryMultiKillableStopsAtFailure(string $mode) {
		TestsHelper::Print("testing QueryMulti() (killable) throws at the first failing statement...");
		$db = FakeConnectionDatabase::Fresh();
		$db->Escape("open");
		$db->Conn()->FailNext("query", 1146, $mode, "Table 'nope' doesn't exist", "nope");
		try {
			$db->QueryMulti(["UPDATE t SET a = 1", "INSERT INTO nope (a) VALUES (1)", "UPDATE t SET a = 3"]);
			$this->fail("expected an exception");
		} catch(MagratheaDBException $ex) {
			$this->assertSame("Table 'nope' doesn't exist", $ex->getMessage());
			$this->assertSame(1146, $ex->getCode());
			$this->assertSame("INSERT INTO nope (a) VALUES (1)", $ex->query);
		}
		$this->assertCount(2, $db->Conn()->Called("query"), "the rest isn't sent");
	}

	/**
	 * @dataProvider connectionLostCases
	 */
	function testQueryMultiNotKillableStopsOnConnectionLost(int $errno, string $mode) {
		TestsHelper::Print("testing QueryMulti(\$queries, false) still throws on a lost connection...");
		$db = FakeConnectionDatabase::Fresh();
		$db->Escape("open");
		$conn = $db->Conn();
		$conn->FailNext("query", $errno, $mode, "MySQL server has gone away", "nope");
		try {
			$db->QueryMulti(["UPDATE t SET a = 1", "INSERT INTO nope (a) VALUES (1)", "UPDATE t SET a = 3"], false);
			$this->fail("expected MagratheaDBConnectionLostException");
		} catch(MagratheaDBConnectionLostException $ex) {
			$this->assertSame($errno, $ex->getCode());
		}
		$this->assertCount(2, $conn->Called("query"), "the rest isn't sent");
		$this->assertTrue($conn->closed);
	}

	private function TransactionCalls(FakeMysqli $conn): array {
		$names = ["autocommit", "begin_transaction", "query", "commit", "rollback"];
		return array_values(array_map(
			fn($c) => $c[0] === "autocommit" ? "autocommit:".($c[1] ? "on" : "off") : ($c[0] === "query" ? $c[1] : $c[0]),
			array_filter($conn->calls, fn($c) => in_array($c[0], $names))
		));
	}
	private function OpenTransactionDb(): FakeConnectionDatabase {
		$db = FakeConnectionDatabase::Fresh();
		$db->Escape("open");
		return $db;
	}
	private $transaction = ["INSERT INTO t (a) VALUES (1)", "INSERT INTO nope (a) VALUES (1)", "INSERT INTO t (a) VALUES (3)"];

	function testQueryTransactionCommits() {
		TestsHelper::Print("testing QueryTransaction() commits, restores autocommit and returns true...");
		$db = $this->OpenTransactionDb();
		$this->assertTrue($db->QueryTransaction($this->transaction));
		$this->assertSame(["autocommit:off", "begin_transaction", ...$this->transaction, "commit", "autocommit:on"], $this->TransactionCalls($db->Conn()));
		$this->assertFalse($db->Conn()->closed);
	}

	function testQueryTransactionReturnedErrorRollsBack() {
		TestsHelper::Print("testing a returned error (mysqli reporting off) rolls back and returns false...");
		$db = $this->OpenTransactionDb();
		$db->Conn()->FailNext("query", 1146, "return", "Table doesn't exist", "nope");
		$this->assertFalse($db->QueryTransaction($this->transaction));
		$this->assertSame(["autocommit:off", "begin_transaction", $this->transaction[0], $this->transaction[1], "rollback", "autocommit:on"], $this->TransactionCalls($db->Conn()), "stops at the first failure");
		$this->assertFalse($db->Conn()->closed, "connection is clean, so it's kept");
	}

	function testQueryTransactionThrownErrorRollsBackAndRethrows() {
		TestsHelper::Print("testing a thrown error (PHP 8.1+ default) rolls back and throws it wrapped, with its errno...");
		$db = $this->OpenTransactionDb();
		$db->Conn()->FailNext("query", 1146, "exception", "Table doesn't exist", "nope");
		try {
			$db->QueryTransaction($this->transaction);
			$this->fail("expected MagratheaDBException");
		} catch(MagratheaDBException $ex) {
			$this->assertSame(1146, $ex->getCode());
			$this->assertSame("Table doesn't exist", $ex->getMessage());
			$this->assertSame($this->transaction[1], $ex->query);
			$this->assertInstanceOf(mysqli_sql_exception::class, $ex->getPrevious());
		}
		$this->assertSame(["autocommit:off", "begin_transaction", $this->transaction[0], $this->transaction[1], "rollback", "autocommit:on"], $this->TransactionCalls($db->Conn()));
		$this->assertFalse($db->Conn()->closed);
	}

	function testQueryTransactionFailedCommit() {
		TestsHelper::Print("testing a failed commit rolls back (false when returned, MagratheaDBException when thrown)...");
		$db = $this->OpenTransactionDb();
		$db->Conn()->FailNext("commit", 1180, "return", "Got error during COMMIT");
		$this->assertFalse($db->QueryTransaction($this->transaction));
		$this->assertSame(["commit", "rollback", "autocommit:on"], array_slice($this->TransactionCalls($db->Conn()), -3));

		$db->Conn()->FailNext("commit", 1180, "exception", "Got error during COMMIT");
		try {
			$db->QueryTransaction($this->transaction);
			$this->fail("expected MagratheaDBException");
		} catch(MagratheaDBException $ex) {
			$this->assertSame(1180, $ex->getCode());
			$this->assertSame("COMMIT", $ex->query);
		}
		$this->assertSame(["commit", "rollback", "autocommit:on"], array_slice($this->TransactionCalls($db->Conn()), -3));
	}

	public static function commitConnectionLostModes(): array {
		return [
			"thrown" => ["exception"],
			"returned" => ["return"],
		];
	}

	/**
	 * @dataProvider commitConnectionLostModes
	 */
	function testQueryTransactionConnectionLostOnCommit(string $mode) {
		TestsHelper::Print("testing a lost connection on QueryTransaction()'s COMMIT throws without a rollback attempt...");
		$db = $this->OpenTransactionDb();
		$conn = $db->Conn();
		$conn->FailNext("commit", 2013, $mode, "Lost connection to MySQL server during query");
		try {
			$db->QueryTransaction($this->transaction);
			$this->fail("expected MagratheaDBConnectionLostException");
		} catch(MagratheaDBConnectionLostException $ex) {
			$this->assertSame(2013, $ex->getCode());
			$this->assertSame("COMMIT", $ex->query);
		}
		$this->assertCount(0, $conn->Called("rollback"));
		$this->assertTrue($conn->closed);
	}

	function testQueryTransactionFailedRollbackDropsConnection() {
		TestsHelper::Print("testing a failed rollback never replaces the original error, and drops the connection...");
		$db = $this->OpenTransactionDb();
		$conn = $db->Conn();
		$conn->FailNext("query", 1146, "exception", "Table doesn't exist", "nope");
		$conn->FailNext("rollback", 2014, "exception", "Commands out of sync");
		try {
			$db->QueryTransaction($this->transaction);
			$this->fail("expected MagratheaDBException");
		} catch(MagratheaDBException $ex) {
			$this->assertSame(1146, $ex->getCode(), "the original error, not the rollback's");
		}
		$this->assertTrue($conn->closed);
		$db->QueryAll("SELECT 1");
		$this->assertCount(2, $db->created);
	}

	function testQueryTransactionFailedAutocommitRestoreDropsConnection() {
		TestsHelper::Print("testing a failed autocommit restore drops the connection...");
		$db = $this->OpenTransactionDb();
		$conn = $db->Conn();
		$conn->FailNext("autocommit:on", 2014, "return", "Commands out of sync");
		$this->assertTrue($db->QueryTransaction($this->transaction), "the commit went through");
		$this->assertTrue($conn->closed, "never reused with autocommit off");
	}

	function testQueryTransactionConnectionLost() {
		TestsHelper::Print("testing a lost connection in QueryTransaction() throws without trying to roll back...");
		$db = $this->OpenTransactionDb();
		$conn = $db->Conn();
		$conn->FailNext("query", 2013, "exception", "Lost connection to MySQL server during query", "nope");
		try {
			$db->QueryTransaction($this->transaction);
			$this->fail("expected MagratheaDBConnectionLostException");
		} catch(MagratheaDBConnectionLostException $ex) {
			$this->assertSame(2013, $ex->getCode());
		}
		$this->assertCount(0, $conn->Called("rollback"));
		$this->assertCount(0, $conn->Called("commit"));
		$this->assertTrue($conn->closed);
	}


	// TRANSACTION()

	function testTransactionCommitsAndReturnsResult() {
		TestsHelper::Print("testing Transaction() runs everything on one connection, commits and returns the callback's result...");
		$previousDb = Database::Instance();
		$db = FakeConnectionDatabase::Fresh();
		Database::MockClass($db);
		try {
			$db->Escape("open");
			$conn = $db->Conn();
			$conn->insert_id = 9;
			$result = $db->Transaction(function() {
				$model = new TransactionTestModel();
				$model->name = "it's";
				$model->Insert();
				TransactionTestControl::GetWhere(["name" => "it's"]);
				Database::Instance()->QueryRow("SELECT * FROM transaction_test WHERE id = 9 FOR UPDATE");
				return "done";
			});
		} finally {
			Database::MockClass($previousDb);
		}
		$this->assertSame("done", $result);
		$this->assertCount(1, $db->created, "one connection for everything");
		$calls = array_map(fn($c) => $c[0], array_filter($conn->calls, fn($c) => in_array($c[0], ["begin_transaction", "prepare", "execute", "query", "commit", "rollback"])));
		$this->assertSame(["begin_transaction", "prepare", "execute", "query", "query", "commit"], array_values($calls));
	}

	function testTransactionRollsBackAndRethrows() {
		TestsHelper::Print("testing a throw rolls back and rethrows the original exception unchanged...");
		$db = $this->OpenTransactionDb();
		$original = new \RuntimeException("boom");
		try {
			$db->Transaction(function() use ($db, $original) {
				$db->Query("UPDATE t SET a = 1");
				throw $original;
			});
			$this->fail("expected the original exception");
		} catch(\RuntimeException $ex) {
			$this->assertSame($original, $ex);
		}
		$this->assertSame(["begin_transaction", "UPDATE t SET a = 1", "rollback", "autocommit:on"], $this->TransactionCalls($db->Conn()));
		$this->assertFalse($db->Conn()->closed);
	}

	function testTransactionNestedRunsInline() {
		TestsHelper::Print("testing nested Transaction() calls run inline: one begin, one commit...");
		$db = $this->OpenTransactionDb();
		$result = $db->Transaction(function() use ($db) {
			$db->Query("UPDATE t SET a = 1");
			$inner = $db->Transaction(function() use ($db) {
				$db->Query("UPDATE t SET a = 2");
				return $db->Transaction(fn() => "deep");
			});
			return "outer+".$inner;
		});
		$this->assertSame("outer+deep", $result);
		$this->assertSame(["begin_transaction", "UPDATE t SET a = 1", "UPDATE t SET a = 2", "commit"], $this->TransactionCalls($db->Conn()));
	}

	function testTransactionInnerFailureCaughtStillRollsBack() {
		TestsHelper::Print("testing an inner throw caught by outer code still makes the outermost call roll back and throw...");
		$db = $this->OpenTransactionDb();
		try {
			$db->Transaction(function() use ($db) {
				try {
					$db->Transaction(function() { throw new \RuntimeException("inner"); });
				} catch(\RuntimeException $ex) { /* swallowed */ }
				return "looks fine";
			});
			$this->fail("expected MagratheaDBException");
		} catch(MagratheaDBException $ex) {
			$this->assertStringContainsString("marked as failed by an inner call", $ex->getMessage());
		}
		$this->assertSame(["begin_transaction", "rollback", "autocommit:on"], $this->TransactionCalls($db->Conn()));
	}

	function testTransactionInnerFailureUncaught() {
		TestsHelper::Print("testing an uncaught inner throw reaches the caller unchanged, with one rollback...");
		$db = $this->OpenTransactionDb();
		$original = new \LogicException("inner");
		try {
			$db->Transaction(fn() => $db->Transaction(function() use ($original) { throw $original; }));
			$this->fail("expected the original exception");
		} catch(\LogicException $ex) {
			$this->assertSame($original, $ex);
		}
		$this->assertCount(1, $db->Conn()->Called("rollback"));
	}

	function testTransactionStateResetsAfterFailure() {
		TestsHelper::Print("testing depth and failed flag are reset: the next Transaction() and QueryTransaction() work...");
		$db = $this->OpenTransactionDb();
		try {
			$db->Transaction(function() use ($db) {
				try { $db->Transaction(function() { throw new \RuntimeException("inner"); }); } catch(\RuntimeException $ex) {}
			});
		} catch(MagratheaDBException $ex) {}
		$this->assertSame("ok", $db->Transaction(fn() => "ok"));
		$this->assertTrue($db->QueryTransaction(["UPDATE t SET a = 1"]));
		$this->assertCount(2, $db->Conn()->Called("commit"));
	}

	function testTransactionFailedCommit() {
		TestsHelper::Print("testing a failed commit rolls back and throws MagratheaDBException with the errno (returned or thrown)...");
		$db = $this->OpenTransactionDb();
		foreach(["return", "exception"] as $mode) {
			$db->Conn()->FailNext("commit", 1180, $mode, "Got error during COMMIT");
			try {
				$db->Transaction(fn() => $db->Query("UPDATE t SET a = 1"));
				$this->fail("expected MagratheaDBException");
			} catch(MagratheaDBException $ex) {
				$this->assertSame("Got error during COMMIT", $ex->getMessage(), $mode);
				$this->assertSame(1180, $ex->getCode(), $mode);
				$this->assertSame("COMMIT", $ex->query, $mode);
			}
			$this->assertSame(["commit", "rollback", "autocommit:on"], array_slice($this->TransactionCalls($db->Conn()), -3), $mode);
		}
	}

	function testTransactionConnectionLostOnCommit() {
		TestsHelper::Print("testing a lost connection on COMMIT throws the lost exception without a rollback attempt...");
		$db = $this->OpenTransactionDb();
		$conn = $db->Conn();
		$conn->FailNext("commit", 2013, "exception", "Lost connection to MySQL server during query");
		try {
			$db->Transaction(fn() => $db->Query("UPDATE t SET a = 1"));
			$this->fail("expected MagratheaDBConnectionLostException");
		} catch(MagratheaDBConnectionLostException $ex) {
			$this->assertSame(2013, $ex->getCode());
			$this->assertSame("COMMIT", $ex->query);
		}
		$this->assertCount(0, $conn->Called("rollback"));
		$this->assertTrue($conn->closed);
	}

	function testTransactionConnectionLostOnBegin() {
		TestsHelper::Print("testing a stale connection fails on START TRANSACTION, before the callback runs...");
		$db = $this->OpenTransactionDb();
		$db->Conn()->FailNext("begin_transaction", 2006, "exception", "MySQL server has gone away");
		$called = false;
		try {
			$db->Transaction(function() use (&$called) { $called = true; });
			$this->fail("expected MagratheaDBConnectionLostException");
		} catch(MagratheaDBConnectionLostException $ex) {
			$this->assertSame(2006, $ex->getCode());
			$this->assertSame("START TRANSACTION", $ex->query);
		}
		$this->assertFalse($called);
		$this->assertSame("ok", $db->Transaction(fn() => "ok"), "next call reconnects");
		$this->assertCount(2, $db->created);
	}

	function testTransactionFailedRollbackKeepsOriginalError() {
		TestsHelper::Print("testing a failed rollback never replaces the original exception, and drops the connection...");
		$db = $this->OpenTransactionDb();
		$conn = $db->Conn();
		$conn->FailNext("rollback", 2014, "exception", "Commands out of sync");
		$original = new \RuntimeException("boom");
		try {
			$db->Transaction(function() use ($original) { throw $original; });
			$this->fail("expected the original exception");
		} catch(\RuntimeException $ex) {
			$this->assertSame($original, $ex);
		}
		$this->assertTrue($conn->closed);
	}

	function testQueryTransactionInsideTransactionThrows() {
		TestsHelper::Print("testing QueryTransaction() refuses to run inside Transaction()...");
		$db = $this->OpenTransactionDb();
		try {
			$db->Transaction(fn() => $db->QueryTransaction(["UPDATE t SET a = 1"]));
			$this->fail("expected MagratheaDBException");
		} catch(MagratheaDBException $ex) {
			$this->assertStringContainsString("can't run inside Transaction()", $ex->getMessage());
		}
		$this->assertSame(["begin_transaction", "rollback", "autocommit:on"], $this->TransactionCalls($db->Conn()), "its queries never ran");
	}

	function testSimulatedTransaction() {
		TestsHelper::Print("testing DatabaseSimulate::Transaction() logs begin/commit/rollback with the same rules...");
		$sim = \Magrathea2\DB\DatabaseSimulate::Instance();
		Debugger::Instance()->SetDebug()->LogQueries(true);
		try {
			$this->assertSame("ok", $sim->Transaction(fn() => $sim->Transaction(fn() => "ok")));
			try {
				$sim->Transaction(function() use ($sim) {
					try { $sim->Transaction(function() { throw new \RuntimeException("inner"); }); } catch(\RuntimeException $ex) {}
				});
				$this->fail("expected MagratheaDBException");
			} catch(MagratheaDBException $ex) {
				$this->assertStringContainsString("marked as failed", $ex->getMessage());
			}
			try {
				$sim->Transaction(fn() => $sim->QueryTransaction(["UPDATE t SET a = 1"]));
				$this->fail("expected MagratheaDBException");
			} catch(MagratheaDBException $ex) {
				$this->assertStringContainsString("can't run inside Transaction()", $ex->getMessage());
			}
			$this->assertTrue($sim->QueryTransaction(["UPDATE t SET a = 1"]), "state reset afterwards");

			$debugItems = new \ReflectionProperty(Debugger::class, "debugItems");
			$logged = array_values(array_filter(
				array_map(fn($item) => is_string($item) && preg_match('/query run: \[(START TRANSACTION|COMMIT|ROLLBACK)\]/', $item, $m) ? $m[1] : null, $debugItems->getValue(Debugger::Instance())),
			));
			$this->assertSame(["START TRANSACTION", "COMMIT", "START TRANSACTION", "ROLLBACK", "START TRANSACTION", "ROLLBACK"], array_slice($logged, -6));
		} finally {
			Debugger::Instance()->LogQueries(false);
			TestsHelper::Debug();
		}
	}

}
