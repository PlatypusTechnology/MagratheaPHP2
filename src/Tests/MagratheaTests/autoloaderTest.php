<?php

use Magrathea2\Debugger;
use Magrathea2\MagratheaPHP;
use Magrathea2\Tests\TestsHelper;
use function Magrathea2\fileDeclaresNamespace;

class autoloaderTest extends \PHPUnit\Framework\TestCase {

	private $dumpFolder;
	private $codeFolder;
	private $appNamespace;
	private $debugType;

	protected function setUp(): void {
		parent::setUp();
		$this->dumpFolder = __DIR__."/test-dump/autoload";
		// a run that died skipped tearDown: start empty
		system('rm -rf -- '.escapeshellarg($this->dumpFolder));
		mkdir($this->dumpFolder, 0777, true);
		$this->codeFolder = MagratheaPHP::Instance()->codeFolder;
		$this->appNamespace = MagratheaPHP::Instance()->appNamespace;
		$this->debugType = Debugger::Instance()->GetType();
	}

	protected function tearDown(): void {
		while(Debugger::Instance()->IsCapturing()) Debugger::Instance()->StopCapture();
		Debugger::Instance()->SetType($this->debugType);
		MagratheaPHP::Instance()->codeFolder = $this->codeFolder;
		MagratheaPHP::Instance()->appNamespace = $this->appNamespace;
		system('rm -rf -- '.escapeshellarg($this->dumpFolder));
		parent::tearDown();
	}

	// writes <folder>/<class>.php declaring $namespace\$class, with a Where() that returns $folder
	private function WriteClass(string $folder, ?string $namespace, string $class): string {
		$dir = $this->dumpFolder."/".$folder;
		if(!is_dir($dir)) mkdir($dir, 0777, true);
		$code = "<?php\n";
		if($namespace !== null) $code .= "namespace ".$namespace.";\n\n";
		$code .= "class ".$class." {\n\tpublic static function Where() { return \"".$folder."\"; }\n}\n";
		file_put_contents($dir."/".$class.".php", $code);
		return $dir;
	}

	private function CodeFolders(string ...$folders) {
		MagratheaPHP::Instance()->codeFolder = array_map(fn($f) => $this->dumpFolder."/".$f, $folders);
	}

	function testSingleMatchLoads() {
		TestsHelper::Print("testing autoloader loads the only matching file...");
		$this->WriteClass("one", "AutoloadTest\\Single", "AutoSingle");
		$this->CodeFolders("empty", "one");
		$this->assertTrue(class_exists("AutoloadTest\\Single\\AutoSingle"));
		$this->assertSame("one", \AutoloadTest\Single\AutoSingle::Where());
	}

	function testDuplicateLoadsMatchingNamespace() {
		TestsHelper::Print("testing autoloader picks the file whose namespace matches...");
		$this->WriteClass("models", "AutoloadTest\\Models", "AutoArticle");
		$this->WriteClass("admin", "AutoloadTest\\Admin", "AutoArticle");
		$this->CodeFolders("models", "admin");
		// 2.3.5 loaded models/ (first folder) for both
		$this->assertTrue(class_exists("AutoloadTest\\Admin\\AutoArticle"));
		$this->assertSame("admin", \AutoloadTest\Admin\AutoArticle::Where());
		$this->assertTrue(class_exists("AutoloadTest\\Models\\AutoArticle"));
		$this->assertSame("models", \AutoloadTest\Models\AutoArticle::Where());
	}

	function testDuplicateNamespaceIsCaseInsensitive() {
		TestsHelper::Print("testing autoloader compares namespaces case-insensitively...");
		$this->WriteClass("first", "AutoloadTest\\Other", "AutoCase");
		$this->WriteClass("second", "AutoloadTest\\Cased", "AutoCase");
		$this->CodeFolders("first", "second");
		$this->assertTrue(class_exists("autoloadtest\\CASED\\AutoCase"));
		$this->assertSame("second", \AutoloadTest\Cased\AutoCase::Where());
	}

	function testDuplicateGlobalClass() {
		TestsHelper::Print("testing autoloader matches a global class with a file without namespace...");
		$this->WriteClass("first", "AutoloadTest\\Namespaced", "AutoGlobal");
		$this->WriteClass("second", null, "AutoGlobal");
		$this->CodeFolders("first", "second");
		$this->assertTrue(class_exists("AutoGlobal"));
		$this->assertSame("second", \AutoGlobal::Where());
	}

	function testDuplicateWithoutMatchLoadsFirst() {
		TestsHelper::Print("testing autoloader keeps the first file when no namespace matches...");
		$this->WriteClass("first", "AutoloadTest\\First", "AutoNone");
		$this->WriteClass("second", "AutoloadTest\\Second", "AutoNone");
		$this->CodeFolders("first", "second");
		// the first file declares another namespace: the class isn't defined, as in 2.3.5
		$this->assertFalse(class_exists("AutoloadTest\\Third\\AutoNone"));
		$this->assertTrue(class_exists("AutoloadTest\\First\\AutoNone", false));
		$this->assertFalse(class_exists("AutoloadTest\\Second\\AutoNone", false));
	}

	function testDuplicateWarnsInDev() {
		TestsHelper::Print("testing autoloader adds a debug warning for duplicates in DEV...");
		$this->WriteClass("models", "AutoloadTest\\Warn\\Models", "AutoWarn");
		$admin = $this->WriteClass("admin", "AutoloadTest\\Warn\\Admin", "AutoWarn");
		$this->CodeFolders("models", "admin");
		Debugger::Instance()->SetDev()->StartCapture();
		$this->assertTrue(class_exists("AutoloadTest\\Warn\\Admin\\AutoWarn"));
		$captured = Debugger::Instance()->GetCaptured();
		$this->assertCount(1, $captured);
		$this->assertStringContainsString("[AutoloadTest\\Warn\\Admin\\AutoWarn] has more than one file", $captured[0]);
		$this->assertStringContainsString("loaded [".$admin."/AutoWarn.php]", $captured[0]);
	}

	function testDuplicateIsSilentOutsideDev() {
		TestsHelper::Print("testing autoloader doesn't warn about duplicates outside DEV...");
		$this->WriteClass("models", "AutoloadTest\\Quiet\\Models", "AutoQuiet");
		$this->WriteClass("admin", "AutoloadTest\\Quiet\\Admin", "AutoQuiet");
		$this->CodeFolders("models", "admin");
		Debugger::Instance()->SetDebug()->StartCapture();
		$this->assertTrue(class_exists("AutoloadTest\\Quiet\\Admin\\AutoQuiet"));
		$this->assertSame([], Debugger::Instance()->GetCaptured());
	}

	function testSameFolderTwiceIsNotADuplicate() {
		TestsHelper::Print("testing autoloader doesn't count a folder added twice as a duplicate...");
		$this->WriteClass("feature", "AutoloadTest\\Twice", "AutoTwice");
		// AddFeature("X") + AddRootCodeFolder("features/X"): same folder, different spelling
		$this->CodeFolders("feature", "feature/../feature");
		Debugger::Instance()->SetDev()->StartCapture();
		$this->assertTrue(class_exists("AutoloadTest\\Twice\\AutoTwice"));
		$this->assertSame([], Debugger::Instance()->GetCaptured());
	}

	function testFileDeclaresNamespace() {
		TestsHelper::Print("testing fileDeclaresNamespace()...");
		$file = $this->dumpFolder."/ns.php";
		file_put_contents($file, "<?php\n// namespace Commented;\nnamespace First\\Block {\n}\n\tnamespace Second {\n}\n");
		$this->assertTrue(fileDeclaresNamespace($file, "First\\Block"));
		$this->assertTrue(fileDeclaresNamespace($file, "second"));
		$this->assertFalse(fileDeclaresNamespace($file, "Commented"));
		$this->assertFalse(fileDeclaresNamespace($file, ""));
		file_put_contents($file, "<?php\nclass NoNamespace {}\n");
		$this->assertTrue(fileDeclaresNamespace($file, ""));
		$this->assertFalse(fileDeclaresNamespace($file, "Any"));
		$this->assertFalse(fileDeclaresNamespace($this->dumpFolder."/missing.php", ""));
	}

	function testMissingAppClassReturnsFalse() {
		TestsHelper::Print("testing class_exists() returns false for a missing app class...");
		MagratheaPHP::Instance()->AppNamespace("AutoloadApp");
		$this->CodeFolders("empty");
		// 2.3.5 threw MagratheaException
		$this->assertFalse(class_exists("AutoloadApp\\Missing\\AutoMissing"));
	}

	function testMissingAppClassIsPhpError() {
		TestsHelper::Print("testing new on a missing app class fails with PHP's own error...");
		MagratheaPHP::Instance()->AppNamespace("AutoloadApp");
		$this->CodeFolders("empty");
		$this->expectException(\Error::class);
		$this->expectExceptionMessage('Class "AutoloadApp\\Missing\\AutoNew" not found');
		new \AutoloadApp\Missing\AutoNew();
	}

	function testMissingAppClassRunsNextAutoloader() {
		TestsHelper::Print("testing autoloaders registered after Magrathea's run for a missing app class...");
		MagratheaPHP::Instance()->AppNamespace("AutoloadApp");
		$this->CodeFolders("empty");
		$next = function($class) {
			if($class == "AutoloadApp\\Next\\AutoNext") eval("namespace AutoloadApp\\Next; class AutoNext {}");
		};
		spl_autoload_register($next);
		try {
			$this->assertTrue(class_exists("AutoloadApp\\Next\\AutoNext"));
		} finally {
			spl_autoload_unregister($next);
		}
	}

	function testMissingAppClassHintInDev() {
		TestsHelper::Print("testing a missing app class adds the code folders hint in DEV...");
		MagratheaPHP::Instance()->AppNamespace("AutoloadApp");
		$this->CodeFolders("empty", "other");
		Debugger::Instance()->SetDev()->StartCapture();
		$this->assertFalse(class_exists("AutoloadApp\\Missing\\AutoHint"));
		$this->assertSame([
			"Could not find class [AutoloadApp\\Missing\\AutoHint]. Are the code folders correct? [".$this->dumpFolder."/empty, ".$this->dumpFolder."/other]",
		], Debugger::Instance()->GetCaptured());
	}

	function testMissingAppClassSilentOutsideDev() {
		TestsHelper::Print("testing a missing app class adds no hint outside DEV...");
		MagratheaPHP::Instance()->AppNamespace("AutoloadApp");
		$this->CodeFolders("empty");
		Debugger::Instance()->SetDebug()->StartCapture();
		$this->assertFalse(class_exists("AutoloadApp\\Missing\\AutoDebug"));
		$this->assertSame([], Debugger::Instance()->GetCaptured());
	}

	function testMissingClassOutsideAppNamespaceNoHint() {
		TestsHelper::Print("testing a missing class outside the app namespace adds no hint...");
		MagratheaPHP::Instance()->AppNamespace("AutoloadApp");
		$this->CodeFolders("empty");
		Debugger::Instance()->SetDev()->StartCapture();
		// "AutoloadApplication" only starts with the app namespace's text
		$this->assertFalse(class_exists("AutoloadApplication\\AutoOther"));
		$this->assertFalse(class_exists("Vendor\\AutoOther"));
		$this->assertSame([], Debugger::Instance()->GetCaptured());
	}

	function testLoadVendorFindsAutoload() {
		TestsHelper::Print("testing LoadVendor() finds Composer's autoload.php, standalone or installed...");
		// a separate process: a missing file was a fatal require, which would end the whole suite
		$bootstrap = realpath(__DIR__."/../phpUnitBootstrap.php");
		$code = 'require $argv[1]; Magrathea2\MagratheaPHP::LoadVendor(); Magrathea2\MagratheaPHP::LoadVendor(); echo "ok";';
		$cmd = escapeshellarg(PHP_BINARY)." -r ".escapeshellarg($code)." ".escapeshellarg($bootstrap)." 2>&1";
		$this->assertSame("ok", (string)shell_exec($cmd));
	}

}
