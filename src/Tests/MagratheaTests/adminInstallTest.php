<?php

use Magrathea2\Admin\Install;
use Magrathea2\Tests\TestsHelper;

class adminInstallTest extends \PHPUnit\Framework\TestCase {

	private string $root;

	protected function setUp(): void {
		parent::setUp();
		$this->root = sys_get_temp_dir()."/magrathea-admin-install-".bin2hex(random_bytes(4));
		mkdir($this->root."/app", 0777, true);
		mkdir($this->root."/vendor", 0777, true);
		mkdir($this->root."/elsewhere", 0777, true);
		// stands in for Composer's autoloader: proves it was found, then stops before the app starts
		file_put_contents($this->root."/vendor/autoload.php", "<?php echo 'autoload found'; exit;");
	}

	protected function tearDown(): void {
		system('rm -rf -- '.escapeshellarg($this->root));
		parent::tearDown();
	}

	function testAdminCodeFindsAutoloadFromAnyFolder() {
		TestsHelper::Print("testing the generated admin entry file finds vendor/autoload.php whatever the current folder...");
		$entry = $this->root."/app/magrathea.php";
		file_put_contents($entry, (new Install())->GetAdminCode());
		// run from a folder that isn't the entry file's: a CWD-relative require would miss it
		$cmd = "cd ".escapeshellarg($this->root."/elsewhere")." && ".escapeshellarg(PHP_BINARY)." ".escapeshellarg($entry)." 2>&1";
		$this->assertSame("autoload found", (string)shell_exec($cmd));
	}

}
