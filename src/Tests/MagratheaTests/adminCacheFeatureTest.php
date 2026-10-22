<?php

use Magrathea2\Config;
use Magrathea2\MagratheaCache;
use Magrathea2\DB\Database;
use Magrathea2\DB\DatabaseSimulate;
use Magrathea2\Admin\Features\Cache\AdminFeatureCache;
use Magrathea2\Exceptions\MagratheaException;

class adminCacheFeatureTest extends \PHPUnit\Framework\TestCase {

	private $root;
	private $cachePath;
	private $outside;
	private $previousDb;

	protected function setUp(): void {
		parent::setUp();
		$this->root = __DIR__."/test-dump/admin-cache";
		system('rm -rf -- '.escapeshellarg($this->root));
		// the realpath cache still maps cache/link.json to outside.txt from the previous test
		clearstatcache(true);
		mkdir($this->root."/cache/sub", 0777, true);
		$this->cachePath = realpath($this->root."/cache");
		file_put_contents($this->cachePath."/article-1.json", '{"title":"<img src=x onerror=alert(1)>"}');
		file_put_contents($this->cachePath."/sub/inner.json", "{}");
		$this->outside = $this->root."/outside.txt";
		file_put_contents($this->outside, "original");
		symlink($this->outside, $this->cachePath."/link.json");
		Config::Instance()->SetConfig([
			"general" => ["use_environment" => "test"],
			"test" => ["cache_path" => $this->cachePath],
		])->SetEnvironment(null);
		MagratheaCache::Instance()->LoadCachePath();
		// Remove() logs through AdminManager::Log(), which inserts an admin log row
		$this->previousDb = Database::Instance();
		Database::MockClass(DatabaseSimulate::Instance());
		$_POST = [];
		$_SERVER["REQUEST_METHOD"] = "POST";
	}
	protected function tearDown(): void {
		system('rm -rf -- '.escapeshellarg($this->root));
		Database::MockClass($this->previousDb);
		Config::Instance()->SetConfig(null)->SetEnvironment(null);
		$_POST = [];
		unset($_SERVER["REQUEST_METHOD"]);
		parent::tearDown();
	}

	private function run_action(string $action, array $post): string {
		$_POST = $post;
		ob_start();
		try {
			(new AdminFeatureCache())->$action();
		} finally {
			$output = ob_get_clean();
		}
		return $output;
	}

	public static function invalidFiles(): array {
		return [
			"parent dir" => ["../outside.txt"],
			"absolute" => ["/etc/passwd"],
			"sub folder" => ["sub/inner.json"],
			"backslash" => ["..\\outside.txt"],
			"dot dot" => [".."],
			"missing" => ["nope.json"],
			"symlink out" => ["link.json"],
			"empty" => [""],
		];
	}

	/** @dataProvider invalidFiles */
	function testSaveRejectsFilesOutsideTheCacheFolder(string $file) {
		try {
			$this->run_action("Save", ["file" => $file, "content" => "pwned"]);
			$this->fail("Save accepted [".$file."]");
		} catch(MagratheaException $ex) {
			$this->assertSame("invalid file", $ex->getMessage());
		}
		$this->assertSame("original", file_get_contents($this->outside));
		$this->assertFileDoesNotExist($this->root."/nope.json");
		$this->assertFileDoesNotExist($this->cachePath."/nope.json");
	}

	/** @dataProvider invalidFiles */
	function testRemoveViewEditRejectFilesOutsideTheCacheFolder(string $file) {
		foreach(["Remove", "View", "Edit"] as $action) {
			try {
				$this->run_action($action, ["file" => $file]);
				$this->fail($action." accepted [".$file."]");
			} catch(MagratheaException $ex) {
				$this->assertSame("invalid file", $ex->getMessage());
			}
		}
		$this->assertFileExists($this->outside);
		$this->assertFileExists($this->cachePath."/sub/inner.json");
	}

	function testSaveAndRemoveACacheFile() {
		$this->run_action("Save", ["file" => "article-1.json", "content" => '{"title":"x"}']);
		$this->assertSame('{"title":"x"}', file_get_contents($this->cachePath."/article-1.json"));
		$this->run_action("Remove", ["file" => "article-1.json"]);
		$this->assertFileDoesNotExist($this->cachePath."/article-1.json");
	}

	function testViewEscapesContentAndFileName() {
		rename($this->cachePath."/article-1.json", $this->cachePath."/a'<b>.json");
		$html = $this->run_action("View", ["file" => "a'<b>.json"]);
		$this->assertStringNotContainsString("<img src=x", $html);
		$this->assertStringContainsString("&lt;img src=x", $html);
		// (the buttons are in short-open-tag blocks, which don't run with short_open_tag off, as in this CLI)
		$this->assertStringContainsString("a&#039;&lt;b&gt;.json", $html);
	}

	function testClearCacheRequiresPost() {
		$_SERVER["REQUEST_METHOD"] = "GET";
		$this->run_action("ClearCache", []);
		$this->assertSame(405, http_response_code());
		$this->assertFileExists($this->cachePath."/article-1.json");
		http_response_code(200);

		$_SERVER["REQUEST_METHOD"] = "POST";
		$this->run_action("ClearCache", []);
		$this->assertFileDoesNotExist($this->cachePath."/article-1.json");
	}
}
