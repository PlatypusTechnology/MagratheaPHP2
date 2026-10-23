<?php

use Magrathea2\Admin\AdminFeature;
use Magrathea2\Admin\Features\AppConfig\AdminFeatureAppConfig;
use Magrathea2\Admin\Features\CrudObject\AdminCrudObject;
use Magrathea2\MagratheaModel;
use Magrathea2\MagratheaModelControl;
use Magrathea2\Tests\TestsHelper;

class DispatchTestFeature extends AdminFeature {
	public $calls = [];
	public function Report() { $this->calls[] = "Report"; }
	public function Index() { $this->calls[] = "Index"; }
	public function WithArg($x) {}
	public function WithOptional($x = 1) {}
	protected function Hidden() {}
	public static function StaticOne() {}
	public function HasPermission($action = null): bool { return true; }
}

class DispatchGadget extends MagratheaModel {
	public static $writes = [];
	protected $dbTable = "dispatch_gadgets";
	protected $dbPk = "id";
	protected $dbValues = [ "id" => "int", "name" => "string" ];
	public $id, $name, $created_at, $updated_at;
	public function __construct($id = null) { $this->id = $id; }
	public function Save() { static::$writes[] = "Save"; return true; }
	public function Delete() { static::$writes[] = "Delete"; return true; }
}
class DispatchGadgetControl extends MagratheaModelControl {
	protected static $modelName = "DispatchGadget";
	protected static $dbTable = "dispatch_gadgets";
}
class DispatchGadgetAdmin extends AdminCrudObject {
	public function Initialize() {
		$this->SetObject(new DispatchGadget(), new DispatchGadgetControl());
	}
	// ReturnError() dies: throw instead, so the test goes on
	public function ReturnError($err) {
		throw new \RuntimeException($err);
	}
}

class adminFeatureDispatchTest extends \PHPUnit\Framework\TestCase {

	protected function setUp(): void {
		parent::setUp();
		$_GET = [];
		$_POST = [];
		DispatchGadget::$writes = [];
	}
	protected function tearDown(): void {
		$_GET = [];
		$_POST = [];
		unset($_SERVER["REQUEST_METHOD"]);
		http_response_code(200);
		parent::tearDown();
	}

	private function Capture(callable $fn): string {
		ob_start();
		try {
			$fn();
		} finally {
			$output = ob_get_clean();
		}
		return $output;
	}

	function testIsDispatchable() {
		TestsHelper::Print("testing only a feature's own public no-argument methods can be called from the URL...");
		$feature = new DispatchTestFeature();
		foreach(["Report", "report", "Index", "GetPage", "getpage", "WithOptional"] as $method) {
			$this->assertTrue($feature->IsDispatchable($method), $method);
		}
		foreach(["WithArg", "Hidden", "StaticOne", "AddJs", "AddCSS", "SetClassPath", "HasPermission", "Initialize",
			"GetMenuItem", "GetSubpageUrl", "IsFeatureActive", "IsDispatchable", "__construct", "__destruct",
			"Nope", "Report()", "", null, ["Report"]] as $method) {
			$this->assertFalse($feature->IsDispatchable($method), var_export($method, true));
		}
	}

	function testCrudActionsAreDispatchable() {
		TestsHelper::Print("testing the built-in CRUD and App Config actions stay reachable...");
		$crud = new DispatchGadgetAdmin();
		foreach(["List", "Form", "Save", "Delete", "GetPage", "Index"] as $method) {
			$this->assertTrue($crud->IsDispatchable($method), $method);
		}
		foreach(["SetObject", "SetControl", "ReturnError", "ShowObjectNotFound", "Fields", "RefuseIfNotPost"] as $method) {
			$this->assertFalse($crud->IsDispatchable($method), $method);
		}
		$appConfig = new AdminFeatureAppConfig();
		foreach(["View", "List", "Migration", "Import"] as $method) {
			$this->assertTrue($appConfig->IsDispatchable($method), $method);
		}
	}

	function testGetPageDispatch() {
		TestsHelper::Print("testing GetPage() runs actions, refuses plumbing and doesn't call itself forever...");
		$feature = new DispatchTestFeature();
		foreach(["GetPage", "getpage", ""] as $subpage) {
			$_GET = ["magrathea_feature_subpage" => $subpage];
			$feature->GetPage();
		}
		$this->assertSame(["Index", "Index", "Index"], $feature->calls);
		$_GET = ["magrathea_feature_subpage" => "Report"];
		$feature->GetPage();
		$this->assertSame("Report", end($feature->calls));
		$_GET = ["magrathea_feature_subpage" => "SetClassPath"];
		$path = $feature->featureClassPath;
		$output = $this->Capture(fn() => $feature->GetPage());
		$this->assertStringContainsString("Page not found", $output);
		$this->assertSame(404, http_response_code());
		$this->assertSame($path, $feature->featureClassPath);
		$this->assertCount(4, $feature->calls);
	}

	function testCrudWritesNeedPost() {
		TestsHelper::Print("testing CRUD Save and Delete answer 405 to a GET and write nothing...");
		$crud = new DispatchGadgetAdmin();
		$_SERVER["REQUEST_METHOD"] = "GET";
		foreach(["Save", "Delete"] as $action) {
			try {
				$crud->$action();
				$this->fail($action." ran on GET");
			} catch(\RuntimeException $ex) {
				$this->assertSame("this action requires a POST request", $ex->getMessage());
			}
			$this->assertSame(405, http_response_code());
		}
		$this->assertSame([], DispatchGadget::$writes);

		$_SERVER["REQUEST_METHOD"] = "POST";
		$_POST = ["id" => 3, "name" => "x"];
		$this->Capture(fn() => $crud->Save());
		$this->Capture(fn() => $crud->Delete());
		$this->assertSame(["Save", "Delete"], DispatchGadget::$writes);
	}

	function testAppConfigImportNeedsPost() {
		TestsHelper::Print("testing App Config Import answers 405 to a GET...");
		$_SERVER["REQUEST_METHOD"] = "GET";
		$output = $this->Capture(fn() => (new AdminFeatureAppConfig())->Import());
		$this->assertStringContainsString("Importing requires a POST request", $output);
		$this->assertSame(405, http_response_code());
	}

}
