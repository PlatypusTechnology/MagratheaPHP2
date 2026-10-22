<?php

use Magrathea2\Debugger;
use Magrathea2\MagratheaApi;
use Magrathea2\MagratheaApiControl;
use Magrathea2\Exceptions\MagratheaException;
use Magrathea2\Exceptions\MagratheaDBException;
use Magrathea2\Tests\TestsHelper;

class CapturingControl extends MagratheaApiControl {
	public function Caught($params) {
		try {
			throw (new MagratheaException("caught one", 42))->SetData(["id" => 7]);
		} catch(MagratheaException $ex) {}
		return "data";
	}
	public function Thrown($params) {
		throw new MagratheaException("thrown one", 409);
	}
}

class debuggerTest extends \PHPUnit\Framework\TestCase {

	private $debugType;

	protected function setUp(): void {
		parent::setUp();
		$this->debugType = Debugger::Instance()->GetType();
	}

	protected function tearDown(): void {
		while(Debugger::Instance()->IsCapturing()) Debugger::Instance()->StopCapture();
		Debugger::Instance()->SetType($this->debugType);
		$this->SetDebugItems([]);
		parent::tearDown();
	}

	// the singleton keeps what every other test stored
	private function SetDebugItems(array $items) {
		$property = new \ReflectionProperty(Debugger::class, "debugItems");
		$property->setAccessible(true);
		$property->setValue(Debugger::Instance(), $items);
	}
	private function GetDebugItems(): array {
		$property = new \ReflectionProperty(Debugger::class, "debugItems");
		$property->setAccessible(true);
		return $property->getValue(Debugger::Instance());
	}

	function testDevPrintsOutsideApi() {
		TestsHelper::Print("testing DEV prints debug items outside an API call...");
		Debugger::Instance()->SetDev();
		$this->expectOutputString("<pre>hello</pre><pre>INFO: info</pre>");
		Debugger::Instance()->Add("hello");
		Debugger::Instance()->Info("info");
		$this->assertSame([], Debugger::Instance()->GetCaptured());
	}

	function testDevCapturesInsteadOfPrinting() {
		TestsHelper::Print("testing DEV collects debug items while capturing...");
		Debugger::Instance()->SetDev()->StartCapture();
		$this->expectOutputString("");
		$line = __LINE__ + 1;
		$ex = (new MagratheaException("boom", 42))->SetData(["id" => 7]);
		Debugger::Instance()->Add("a string");
		Debugger::Instance()->Add(["an" => "array"]);
		Debugger::Instance()->Info("info");
		$this->assertSame([
			[
				"type" => MagratheaException::class,
				"message" => "boom",
				"code" => 42,
				"file" => __FILE__.":".$line,
				"data" => ["id" => 7],
			],
			"a string",
			["an" => "array"],
			"INFO: info",
		], Debugger::Instance()->GetCaptured());
	}

	function testStopCaptureDropsItemsAndPrintsAgain() {
		TestsHelper::Print("testing StopCapture() drops collected items and DEV prints again...");
		Debugger::Instance()->SetDev()->StartCapture();
		Debugger::Instance()->Add("collected");
		Debugger::Instance()->StopCapture();
		$this->assertFalse(Debugger::Instance()->IsCapturing());
		$this->assertSame([], Debugger::Instance()->GetCaptured());
		$this->expectOutputString("<pre>printed</pre>");
		Debugger::Instance()->Add("printed");
	}

	function testCaptureNests() {
		TestsHelper::Print("testing nested captures keep collecting until the outermost stops...");
		Debugger::Instance()->SetDev()->StartCapture()->StartCapture();
		Debugger::Instance()->Add("inner");
		Debugger::Instance()->StopCapture();
		$this->assertTrue(Debugger::Instance()->IsCapturing());
		Debugger::Instance()->Add("outer");
		$this->assertSame(["inner", "outer"], Debugger::Instance()->GetCaptured());
		Debugger::Instance()->StopCapture();
		$this->assertFalse(Debugger::Instance()->IsCapturing());
		$this->expectOutputString("");
	}

	function testDebugCapturesInsteadOfStoring() {
		TestsHelper::Print("testing DEBUG collects debug items while capturing, instead of storing them for Show()...");
		Debugger::Instance()->SetDebug();
		$this->SetDebugItems([]);
		Debugger::Instance()->Add("before");
		Debugger::Instance()->StartCapture();
		Debugger::Instance()->Add("during");
		new MagratheaException("an exception");
		Debugger::Instance()->Info("info");
		$this->assertSame(["before"], $this->GetDebugItems(), "nothing left for the shutdown Show()");
		$captured = Debugger::Instance()->GetCaptured();
		$this->assertSame("during", $captured[0]);
		$this->assertSame("an exception", $captured[1]["message"]);
		$this->assertSame("INFO: info", $captured[2]);
		$rs = Debugger::Instance()->WithCaptured(["success" => true]);
		$this->assertSame("before", $rs["_debug"][0], "items stored before the call come first");
		$this->assertSame(array_merge(["before"], $captured), $rs["_debug"]);
		$this->expectOutputString("");
	}

	function testShowAtShutdown() {
		TestsHelper::Print("testing ShowAtShutdown() prints in DEBUG, but not after a JSON response...");
		Debugger::Instance()->SetDebug();
		$this->SetDebugItems([]);
		Debugger::Instance()->Add("stored item");
		ob_start();
		Debugger::Instance()->ShowAtShutdown(["X-Powered-By: PHP", "Content-Type: application/json"]);
		$this->assertSame("", ob_get_clean(), "JSON response");
		ob_start();
		Debugger::Instance()->ShowAtShutdown(["content-type: application/json; charset=utf-8"]);
		$this->assertSame("", ob_get_clean(), "JSON response, any case");
		ob_start();
		Debugger::Instance()->ShowAtShutdown(["Content-Type: text/html; charset=UTF-8"]);
		$this->assertStringContainsString("stored item", ob_get_clean(), "HTML response");
		ob_start();
		Debugger::Instance()->ShowAtShutdown([]);
		$this->assertStringContainsString("stored item", ob_get_clean(), "no headers (CLI)");
		Debugger::Instance()->SetDev();
		ob_start();
		Debugger::Instance()->ShowAtShutdown([]);
		$this->assertSame("", ob_get_clean(), "only DEBUG prints at shutdown");
	}

	function testCaptureCollectsNothingInOtherModes() {
		TestsHelper::Print("testing capture collects nothing outside DEV and DEBUG...");
		Debugger::Instance()->StartCapture();
		foreach([Debugger::NONE, Debugger::ANALYSIS] as $type) {
			Debugger::Instance()->SetType($type);
			Debugger::Instance()->Add("ignored");
			new MagratheaException("ignored");
		}
		$this->assertSame([], Debugger::Instance()->GetCaptured());
		$this->assertSame(["success" => true], Debugger::Instance()->WithCaptured(["success" => true]));
	}

	function testSameExceptionCapturedOnce() {
		TestsHelper::Print("testing an exception added twice is collected once...");
		Debugger::Instance()->SetDev()->StartCapture();
		$ex = new MagratheaException("twice"); // adds itself
		Debugger::Instance()->AddError($ex);
		Debugger::Instance()->Add("same string");
		Debugger::Instance()->Add("same string");
		$captured = Debugger::Instance()->GetCaptured();
		$this->assertCount(3, $captured);
		$this->assertSame("twice", $captured[0]["message"]);
	}

	function testDBExceptionEntryHasQuery() {
		TestsHelper::Print("testing a DB exception's entry has its query and values...");
		Debugger::Instance()->SetDev()->StartCapture();
		// as Database::ErrorHandle() builds it
		$ex = new MagratheaDBException("Duplicate entry", "INSERT INTO t VALUES (?)");
		$ex->SetQueryData("INSERT INTO t VALUES (?)", ["x"]);
		$entry = Debugger::Instance()->GetCaptured()[0];
		$this->assertSame(MagratheaDBException::class, $entry["type"]);
		$this->assertSame("INSERT INTO t VALUES (?)", $entry["query"]);
		$this->assertSame(["x"], $entry["values"]);
		$this->assertArrayNotHasKey("data", $entry);
	}

	function testWithCaptured() {
		TestsHelper::Print("testing WithCaptured() adds _debug only to associative arrays...");
		Debugger::Instance()->SetDev()->StartCapture();
		$this->assertSame(["success" => true], Debugger::Instance()->WithCaptured(["success" => true]), "nothing collected");
		Debugger::Instance()->Add("item");
		$this->assertSame(["success" => true, "_debug" => ["item"]], Debugger::Instance()->WithCaptured(["success" => true]));
		$this->assertSame([1, 2], Debugger::Instance()->WithCaptured([1, 2]));
		$this->assertSame([], Debugger::Instance()->WithCaptured([]));
		$this->assertSame("text", Debugger::Instance()->WithCaptured("text"));
		$obj = (object)["a" => 1];
		$this->assertSame($obj, Debugger::Instance()->WithCaptured($obj));
	}

	function testWithCapturedSurvivesUnencodableItems() {
		TestsHelper::Print("testing WithCaptured() keeps the response when _debug can't be encoded...");
		Debugger::Instance()->SetDev()->StartCapture();
		Debugger::Instance()->Add("\xB1\x31");
		$rs = Debugger::Instance()->WithCaptured(["success" => true]);
		$this->assertTrue($rs["success"]);
		$this->assertStringContainsString("_debug could not be JSON-encoded", $rs["_debug"][0]);
		$this->assertNotFalse(json_encode($rs));
	}

	function testExecuteUrlCapturesAndStops() {
		TestsHelper::Print("testing ExecuteUrl() captures debug items in DEV and stops afterwards...");
		Debugger::Instance()->SetDev();
		$api = (new MagratheaApi())->SetRaw()
			->Add("GET", "caught", new CapturingControl(), "Caught")
			->Add("GET", "thrown", new CapturingControl(), "Thrown");
		$this->expectOutputString("");
		$this->assertSame(["success" => true, "data" => "data"], $api->ExecuteUrl("caught", "GET"), "raw mode has no _debug");
		$this->assertFalse(Debugger::Instance()->IsCapturing());
		$this->assertFalse($api->ExecuteUrl("thrown", "GET")["success"]);
		$this->assertFalse(Debugger::Instance()->IsCapturing());
	}

	/**
	 * Json() and the cache save print and die: they run in a separate PHP process.
	 * @return string what the process printed
	 */
	private function RunApiProcess(string $mode, string $cachePath = "", string $debugType = "dev"): string {
		$script = tempnam(sys_get_temp_dir(), "magrathea-debug-");
		$code = <<<'PHP'
<?php
require $argv[1];
use Magrathea2\Config;
use Magrathea2\Debugger;
use Magrathea2\MagratheaApi;
use Magrathea2\MagratheaApiControl;
use Magrathea2\MagratheaCache;
use Magrathea2\Exceptions\MagratheaException;
class ProcessControl extends MagratheaApiControl {
	public function Caught($params) {
		try { throw (new MagratheaException("caught one", 42))->SetData(["id" => 7]); } catch(MagratheaException $ex) {}
		Debugger::Instance()->Add("a string");
		return ["ok" => true];
	}
	public function Thrown($params) {
		throw new \Magrathea2\Exceptions\MagratheaApiException("thrown one", 409);
	}
	public function Cached($params) {
		$this->Cache("debug-cache");
		new MagratheaException("before save");
		return "cached data";
	}
}
if($argv[4] == "debug") Debugger::Instance()->SetDebug();
else Debugger::Instance()->SetDev();
$mode = $argv[2];
if($mode == "cached") {
	Config::Instance()->SetConfig([
		"general" => ["use_environment" => "test"],
		"test" => ["cache_path" => $argv[3]],
	])->SetEnvironment(null);
	MagratheaCache::Instance()->Type("json")->LoadCachePath();
}
$api = (new MagratheaApi())->Add("GET", $mode, new ProcessControl(), ucfirst($mode));
if($mode == "list") {
	Debugger::Instance()->StartCapture();
	Debugger::Instance()->Add("item");
	$api->Json([1, 2]);
}
$api->ExecuteUrl($mode, "GET");
PHP;
		file_put_contents($script, $code);
		$bootstrap = realpath(__DIR__."/../phpUnitBootstrap.php");
		$cmd = escapeshellarg(PHP_BINARY)." ".escapeshellarg($script)." ".escapeshellarg($bootstrap)
			." ".escapeshellarg($mode)." ".escapeshellarg($cachePath)." ".escapeshellarg($debugType)." 2>&1";
		try {
			return (string)shell_exec($cmd);
		} finally {
			unlink($script);
		}
	}

	function testJsonResponseHasDebug() {
		TestsHelper::Print("testing a DEV JSON response gets _debug instead of printed <pre>...");
		$output = $this->RunApiProcess("caught");
		$rs = json_decode($output, true);
		$this->assertIsArray($rs, "not JSON: ".$output);
		$this->assertSame(["ok" => true], $rs["data"]);
		$this->assertSame("caught one", $rs["_debug"][0]["message"]);
		$this->assertSame(42, $rs["_debug"][0]["code"]);
		$this->assertSame(["id" => 7], $rs["_debug"][0]["data"]);
		$this->assertSame("a string", $rs["_debug"][1]);
	}

	function testDebugModeJsonResponseHasDebug() {
		TestsHelper::Print("testing a DEBUG JSON response gets _debug instead of HTML printed after it...");
		$output = $this->RunApiProcess("caught", "", "debug");
		$rs = json_decode($output, true);
		$this->assertIsArray($rs, "not JSON: ".$output);
		$this->assertSame(["ok" => true], $rs["data"]);
		$this->assertSame("caught one", $rs["_debug"][0]["message"]);
		$this->assertSame("a string", $rs["_debug"][1]);
	}

	function testErrorResponseHasDebug() {
		TestsHelper::Print("testing a DEV error response gets _debug...");
		$output = $this->RunApiProcess("thrown");
		$rs = json_decode($output, true);
		$this->assertIsArray($rs, "not JSON: ".$output);
		$this->assertFalse($rs["success"]);
		$this->assertSame("thrown one", $rs["data"]["message"]);
		$this->assertSame("Magrathea2\\Exceptions\\MagratheaApiException", $rs["_debug"][0]["type"]);
	}

	function testJsonListHasNoDebug() {
		TestsHelper::Print("testing a JSON list response is sent unchanged...");
		$this->assertSame("[1,2]", $this->RunApiProcess("list"));
	}

	function testCachedResponseHasDebugButCacheFileDoesnt() {
		TestsHelper::Print("testing _debug goes to the response, not to the cache file...");
		$cachePath = __DIR__."/test-dump/debug-cache";
		system('rm -rf -- '.escapeshellarg($cachePath));
		mkdir($cachePath, 0777, true);
		try {
			$output = $this->RunApiProcess("cached", realpath($cachePath));
			$rs = json_decode($output, true);
			$this->assertIsArray($rs, "not JSON: ".$output);
			$this->assertSame("cached data", $rs["data"]);
			$this->assertSame("before save", $rs["_debug"][0]["message"]);
			$saved = json_decode(file_get_contents($cachePath."/debug-cache.json"), true);
			$this->assertSame("cached data", $saved["data"]);
			$this->assertArrayNotHasKey("_debug", $saved);
		} finally {
			system('rm -rf -- '.escapeshellarg($cachePath));
		}
	}

}
