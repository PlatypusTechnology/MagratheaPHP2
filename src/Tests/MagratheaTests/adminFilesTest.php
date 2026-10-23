<?php

use Magrathea2\Admin\AdminFiles;
use Magrathea2\Logger;
use Magrathea2\Tests\TestsHelper;

class adminFilesTest extends \PHPUnit\Framework\TestCase {

	private $root;
	private $logs;
	private $previousLogPath;

	protected function setUp(): void {
		parent::setUp();
		$this->root = sys_get_temp_dir()."/magrathea-files-test-".getmypid();
		system('rm -rf -- '.escapeshellarg($this->root));
		clearstatcache(true);
		mkdir($this->root."/logs/sub", 0777, true);
		$this->logs = realpath($this->root."/logs");
		file_put_contents($this->logs."/log_202610.log", "line 1\nline 2\n<script>alert(1)</script>\n");
		file_put_contents($this->root."/secret.txt", "secret");
		symlink($this->root."/secret.txt", $this->logs."/link.log");
		// GetLogPath() throws without a config: keep the raw value
		$this->previousLogPath = (fn() => $this->logPath)->call(Logger::Instance());
		Logger::Instance()->SetLogPath($this->logs);
		$_POST = [];
	}
	protected function tearDown(): void {
		Logger::Instance()->SetLogPath($this->previousLogPath);
		system('rm -rf -- '.escapeshellarg($this->root));
		$_POST = [];
		http_response_code(200);
		parent::tearDown();
	}

	private function Render(string $action, array $vars = []): string {
		extract($vars);
		ob_start();
		try {
			include(__DIR__."/../../Admin/views/actions/".$action.".php");
		} finally {
			$output = ob_get_clean();
		}
		return $output;
	}

	function testResolveInFolder() {
		TestsHelper::Print("testing AdminFiles::ResolveInFolder() accepts only plain names of files inside the folder...");
		$this->assertSame($this->logs."/log_202610.log", AdminFiles::ResolveInFolder($this->logs, "log_202610.log"));
		$this->assertSame($this->logs."/log_202610.log", AdminFiles::ResolveInFolder($this->root."/logs/", "log_202610.log"));
		foreach(["../secret.txt", "..", ".", "", "sub", "sub/x", $this->root."/secret.txt", "missing.log",
			"link.log", "log_202610.log\0", "a\\b", null, ["log_202610.log"]] as $name) {
			$this->assertNull(AdminFiles::ResolveInFolder($this->logs, $name), var_export($name, true));
		}
		$this->assertNull(AdminFiles::ResolveInFolder($this->root."/nope", "log_202610.log"));
		$this->assertNull(AdminFiles::ResolveInFolder(null, "log_202610.log"));
	}

	function testFileViewCalledDirectlyIsNotFound() {
		TestsHelper::Print("testing file-view renders nothing without a \$viewFile set by an including view...");
		$_POST = ["file" => $this->root."/secret.txt"];
		$output = $this->Render("file-view");
		$this->assertSame("action not found", $output);
		$this->assertSame(404, http_response_code());
	}

	function testLogViewRejectsPaths() {
		TestsHelper::Print("testing log-view only reads a plain file name inside the logs folder...");
		foreach([$this->root."/secret.txt", "../secret.txt", "link.log", "sub", "missing.log"] as $file) {
			$_POST = ["file" => $file];
			$output = $this->Render("log-view");
			$this->assertStringContainsString("invalid log file", $output, $file);
			$this->assertStringNotContainsString("secret", $output, $file);
		}
	}

	function testLogViewEscapesFullFileAndTail() {
		TestsHelper::Print("testing log-view escapes the file and its tail, and can render twice in one request...");
		$_POST = ["file" => "log_202610.log"];
		$full = $this->Render("log-view");
		$this->assertStringContainsString("line 1", $full);
		$this->assertStringContainsString("&lt;script&gt;alert(1)&lt;/script&gt;", $full);
		$this->assertStringNotContainsString("<script>", $full);
		$this->assertStringEndsWith("</pre>", $full);

		$_POST = ["file" => "log_202610.log", "lines" => "1"];
		$tail = $this->Render("log-view");
		$this->assertStringContainsString("&lt;script&gt;alert(1)&lt;/script&gt;", $tail);
		$this->assertStringNotContainsString("line 1", $tail);
		$this->assertStringNotContainsString("<script>", $tail);
		// a second tail in the same request: getLastLines() used to be a global function ("Cannot redeclare")
		$this->assertSame($tail, $this->Render("log-view"));
	}

	function testLogViewClampsLines() {
		TestsHelper::Print("testing log-view clamps lines to 1-10000...");
		$_POST = ["file" => "log_202610.log", "lines" => "-5"];
		$this->assertStringNotContainsString("line 2", $this->Render("log-view"));
		$_POST = ["file" => "log_202610.log", "lines" => "999999999"];
		$this->assertStringContainsString("line 1", $this->Render("log-view"));
	}

}
