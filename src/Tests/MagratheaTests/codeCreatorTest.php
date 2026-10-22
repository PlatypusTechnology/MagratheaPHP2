<?php

use Magrathea2\Admin\CodeCreator;
use Magrathea2\Admin\CodeManager;
use Magrathea2\Admin\ObjectManager;
use Magrathea2\ConfigFile;
use Magrathea2\Exceptions\MagratheaException;

class codeCreatorTest extends \PHPUnit\Framework\TestCase {

	private $strictLine = "\tprotected \$strictTypes = true;\n";
	private $roundTripFile = "test_codeCreatorTest.conf";

	protected function setUp(): void {
		parent::setUp();
		ObjectManager::Instance()
			->SetObjectFilePath(__DIR__."/test-dump")
			->fileName = "magrathea-object-test.conf";
		// Load() reads these from the admin's app config (database); set them directly
		$creator = CodeCreator::Instance();
		$creator->namespace = "App";
		$creator->structure = "mvc";
	}
	protected function tearDown(): void {
		$file = __DIR__."/test-dump/".$this->roundTripFile;
		if(file_exists($file)) unlink($file);
		parent::tearDown();
	}

	private function baseCode($object, $data = null): string {
		if($data === null) $data = ObjectManager::Instance()->GetObjectData($object);
		return CodeCreator::Instance()->GenerateBaseCodeForObject(ucfirst($object), $data);
	}

	function testStrictObjectGetsStrictTypes() {
		$code = $this->baseCode("review");
		$this->assertStringContainsString("\tprotected \$autoload = null;\n".$this->strictLine, $code);
	}

	function testLegacyObjectHasNoStrictTypes() {
		$data = ObjectManager::Instance()->GetObjectData("movie");
		$this->assertArrayNotHasKey("strict_types", $data);
		$code = $this->baseCode("movie", $data);
		$this->assertStringNotContainsString("strictTypes", $code);
	}

	function testStrictTypesFalseEmitsNothing() {
		$data = ObjectManager::Instance()->GetObjectData("review");
		$data["strict_types"] = "";
		$code = $this->baseCode("review", $data);
		$this->assertStringNotContainsString("strictTypes", $code);
	}

	// the admin posts "true"/"false"; the generator reads them back through parse_ini_file
	function testSelectValuesRoundTripThroughConfigFile() {
		$conf = new ConfigFile();
		$conf->SetPath(__DIR__."/test-dump")->SetFile($this->roundTripFile);
		$conf->SetConfig([
			"strict" => [ "table_name" => "reviews", "db_pk" => "id", "strict_types" => "true" ],
			"legacy" => [ "table_name" => "reviews", "db_pk" => "id", "strict_types" => "false" ],
		])->Save();

		$reread = new ConfigFile();
		$reread->SetPath(__DIR__."/test-dump")->SetFile($this->roundTripFile);
		$data = $reread->GetConfig();
		$this->assertSame("1", $data["strict"]["strict_types"]);
		$this->assertSame("", $data["legacy"]["strict_types"]);
		$this->assertStringContainsString($this->strictLine, $this->baseCode("review", $data["strict"]));
		$this->assertStringNotContainsString("strictTypes", $this->baseCode("review", $data["legacy"]));
	}

	// mvc: models and controls live in different namespaces
	function testMvcBaseGetControlUsesControlsNamespace() {
		$this->assertStringContainsString("return new \\App\\Controls\\Base\\ReviewControlBase();", $this->baseCode("review"));
	}
	function testFeatureBaseGetControlUsesObjectNamespace() {
		CodeCreator::Instance()->structure = "feature";
		$this->assertStringContainsString("return new \\App\\Review\\Base\\ReviewControlBase();", $this->baseCode("review"));
	}

	function testMvcAdminAndApiUseFullNames() {
		$creator = CodeCreator::Instance();
		$admin = $creator->GenerateCodeForAdmin("Review");
		$this->assertStringContainsString("namespace App\\Controls;", $admin);
		$this->assertStringContainsString("\$this->SetObject(new \\App\\Models\\Review(), new \\App\\Controls\\ReviewControl());", $admin);
		$api = $creator->GenerateCodeForApi("Review");
		$this->assertStringContainsString("get_class(new \\App\\Models\\Review())", $api);
		$this->assertStringContainsString("\$this->service = new \\App\\Controls\\ReviewControl();", $api);
	}
	function testFeatureAdminAndApiUseFullNames() {
		$creator = CodeCreator::Instance();
		$creator->structure = "feature";
		$admin = $creator->GenerateCodeForAdmin("Review");
		$this->assertStringContainsString("namespace App\\Review;", $admin);
		$this->assertStringContainsString("\$this->SetObject(new \\App\\Review\\Review(), new \\App\\Review\\ReviewControl());", $admin);
		$api = $creator->GenerateCodeForApi("Review");
		$this->assertStringContainsString("get_class(new \\App\\Review\\Review())", $api);
		$this->assertStringContainsString("\$this->service = new \\App\\Review\\ReviewControl();", $api);
	}

	private function fileDestination($structure, $type): string {
		$manager = CodeManager::Instance();
		$fileData = $manager->GetFileList("Review")[$type];
		$method = new \ReflectionMethod($manager, "GetFileDestination");
		$method->setAccessible(true);
		return $method->invoke($manager, $structure, "/app", $fileData);
	}
	function testMvcAdminAndApiGoToControls() {
		$this->assertSame("/app/Controls/ReviewAdmin.php", $this->fileDestination("mvc", "admin"));
		$this->assertSame("/app/Controls/ReviewApi.php", $this->fileDestination("mvc", "api"));
	}
	function testFeatureAdminAndApiGoToObjectFolder() {
		$this->assertSame("/app/ReviewAdmin.php", $this->fileDestination("feature", "admin"));
		$this->assertSame("/app/ReviewApi.php", $this->fileDestination("feature", "api"));
	}
	function testUnknownStructureThrows() {
		$this->expectException(MagratheaException::class);
		$this->fileDestination("hexagonal", "model");
	}
}
