<?php

use Magrathea2\Config;
use Magrathea2\Debugger;
use Magrathea2\Logger;
use Magrathea2\MagratheaApi;
use Magrathea2\MagratheaApiControl;
use Magrathea2\MagratheaCache;
use Magrathea2\Tests\TestsHelper;

class CachedRawModeControl extends MagratheaApiControl {
	public int $calls = 0;
	public function Cached($params) {
		$this->Cache("raw-endpoint");
		$this->calls++;
		return "fresh data";
	}
}

class cacheTest extends \PHPUnit\Framework\TestCase {

	private $cachePath;
	private $cacheName = "atomic-test";

	protected function setUp(): void {
		parent::setUp();
		clearstatcache();
		$dumpFolder = __DIR__."/test-dump/cache";
		// a run that died skipped tearDown: start empty
		system('rm -rf -- '.escapeshellarg($dumpFolder));
		mkdir($dumpFolder, 0777, true);
		$this->cachePath = realpath($dumpFolder);
		Config::Instance()->SetConfig([
			"general" => ["use_environment" => "test"],
			"test" => ["cache_path" => $this->cachePath],
		])->SetEnvironment(null);
		// raw-mode API tests leave the singleton bypassed
		MagratheaCache::Instance()->Bypass(false)->Type("json")->LoadCachePath();
		// no cache file yet: sets the cache handle without serving anything
		MagratheaCache::Instance()->Cache($this->cacheName);
	}
	protected function tearDown(): void {
		@chmod($this->cachePath, 0777);
		system('rm -rf -- '.escapeshellarg($this->cachePath), $retval);
		if ($retval != 0) { // UNIX commands return zero on success
			echo "error on teardown: ".$retval;
		}
		Config::Instance()->SetConfig(null)->SetEnvironment(null);
		parent::tearDown();
	}

	private function GetTmpFiles(): array {
		return glob($this->cachePath."/*.tmp");
	}

	function testSaveFile() {
		TestsHelper::Print("testing cache save file...");
		$cache = MagratheaCache::Instance();
		$data = json_encode(["ok" => true]);
		$this->assertSame(strlen($data), $cache->SaveFile($data));
		$this->assertSame($data, file_get_contents($cache->GetCacheFile()));
		$this->assertSame(0, $cache->SaveFile(""));
		$this->assertSame("", file_get_contents($cache->GetCacheFile()));
	}

	function testSaveFileLeavesNoTmpFiles() {
		TestsHelper::Print("testing cache save file leaves no tmp files...");
		$cache = MagratheaCache::Instance();
		for($i = 0; $i < 20; $i++) {
			$cache->SaveFile("data ".$i);
		}
		$this->assertSame([], $this->GetTmpFiles());
		$this->assertSame("data 19", file_get_contents($cache->GetCacheFile()));
	}

	function testSaveFileOnUnwritableDir() {
		TestsHelper::Print("testing cache save file on unwritable dir...");
		if(function_exists("posix_geteuid") && posix_geteuid() === 0) {
			$this->markTestSkipped("root ignores directory permissions");
		}
		$cache = MagratheaCache::Instance();
		$cache->SaveFile("original");
		chmod($this->cachePath, 0555);
		clearstatcache();
		$this->assertFalse($cache->SaveFile("new data"));
		chmod($this->cachePath, 0777);
		$this->assertSame("original", file_get_contents($cache->GetCacheFile()));
		$this->assertSame([], $this->GetTmpFiles());
	}

	function testRemoveAllCacheRemovesTmpFiles() {
		TestsHelper::Print("testing remove all cache removes orphan tmp files...");
		$cache = MagratheaCache::Instance();
		$cache->SaveFile("data");
		// what a process killed mid-write leaves behind
		$orphan = $cache->GetCacheFile().".12345.abcdef012345.tmp";
		file_put_contents($orphan, "partial");
		$removed = $cache->RemoveAllCache();
		$this->assertContains($orphan, $removed);
		$this->assertFileDoesNotExist($orphan);
		$this->assertFileDoesNotExist($cache->GetCacheFile());
	}

	function testClearKeepsCurrentHandle() {
		TestsHelper::Print("testing cache clear keeps the current handle...");
		$cache = MagratheaCache::Instance();
		$other = $this->cachePath."/other-42.json";
		file_put_contents($other, "{}");
		$this->assertTrue($cache->Clear("other", 42));
		$this->assertFileDoesNotExist($other);
		$this->assertSame($this->cacheName, $cache->cacheName);
		$this->assertSame($this->cachePath."/".$this->cacheName.".json", $cache->GetCacheFile());
		$this->assertTrue($cache->saveCache);
	}

	function testBypass() {
		TestsHelper::Print("testing cache bypass...");
		$cache = MagratheaCache::Instance();
		$cache->Bypass();
		$this->assertFalse($cache->saveCache);
		$this->assertFalse($cache->Cache("bypassed"));
		$this->assertFalse($cache->saveCache);
		$this->assertSame($this->cacheName, $cache->cacheName);
		$cache->Bypass(false)->Cache("bypassed");
		$this->assertTrue($cache->saveCache);
		$this->assertSame("bypassed", $cache->cacheName);
	}

	function testCacheLogsOnlyInDev() {
		TestsHelper::Print("testing api control cache logs only in dev...");
		// Logger skips the "test" environment
		Config::Instance()->SetConfig([
			"general" => ["use_environment" => "test-cache-log"],
			"test-cache-log" => ["cache_path" => $this->cachePath],
		])->SetEnvironment(null);
		$logFile = $this->cachePath."/cache-test.log";
		Logger::Instance()->SetLogPath($this->cachePath)->SetLogFile("cache-test.log");
		$debugType = Debugger::Instance()->GetType();
		$control = new CachedRawModeControl();
		try {
			Debugger::Instance()->SetDebug();
			$control->Cache("quiet");
			$this->assertFileDoesNotExist($logFile);
			Debugger::Instance()->SetDev();
			$control->Cache("loud", 7);
			$this->assertStringContainsString("adding cache for [loud/7]", file_get_contents($logFile));
		} finally {
			Debugger::Instance()->SetType($debugType);
			// back to the lazy, config-based log path
			Logger::Instance()->SetLogPath(null)->SetLogFile(null);
		}
	}

	private function RawApi(CachedRawModeControl $control): MagratheaApi {
		return (new MagratheaApi())
			->SetRaw()
			->Add("GET", "cached", $control, "Cached");
	}

	/**
	 * A cache hit/save in raw mode used to die(), and PHPUnit exits 0 when a test dies:
	 * this turns that into a failed run.
	 */
	private function FailIfProcessDies(): \stdClass {
		$guard = new \stdClass();
		$guard->done = false;
		register_shutdown_function(function() use ($guard) {
			if($guard->done) return;
			fwrite(STDERR, "\ncacheTest: the process died inside a raw-mode API call\n");
			exit(1);
		});
		return $guard;
	}

	function testRawModeIgnoresCacheHit() {
		TestsHelper::Print("testing raw mode ignores a cache hit...");
		$file = $this->cachePath."/raw-endpoint.json";
		$cached = json_encode(["success" => true, "data" => "cached data", "cached" => true]);
		file_put_contents($file, $cached);
		$control = new CachedRawModeControl();
		// on 2.3.5 this printed the cached file and killed the test process
		$guard = $this->FailIfProcessDies();
		$response = $this->RawApi($control)->ExecuteUrl("cached", "GET");
		$guard->done = true;
		$this->assertSame(1, $control->calls);
		$this->assertTrue($response["success"]);
		$this->assertSame("fresh data", $response["data"]);
		$this->assertSame($cached, file_get_contents($file));
	}

	function testRawModeDoesNotSave() {
		TestsHelper::Print("testing raw mode doesn't save the cache...");
		$control = new CachedRawModeControl();
		$guard = $this->FailIfProcessDies();
		$response = $this->RawApi($control)->ExecuteUrl("cached", "GET");
		$guard->done = true;
		$this->assertSame(1, $control->calls);
		$this->assertSame("fresh data", $response["data"]);
		$this->assertFileDoesNotExist($this->cachePath."/raw-endpoint.json");
		$this->assertFalse(MagratheaCache::Instance()->saveCache);
	}

	function testConcurrentReaderNeverSeesPartialFile() {
		TestsHelper::Print("testing cache concurrent reader...");
		if(!function_exists("pcntl_fork") || !function_exists("pcntl_exec")) {
			$this->markTestSkipped("pcntl is not available");
		}
		$cache = MagratheaCache::Instance();
		$target = $cache->GetCacheFile();
		$size = 1024 * 1024;
		$payloads = [str_repeat("a", $size), str_repeat("b", $size)];
		$readyFile = $this->cachePath."/reader.ready";
		$stopFile = $this->cachePath."/reader.stop";
		$cache->SaveFile($payloads[0]);

		$pid = pcntl_fork();
		if($pid === -1) {
			$this->markTestSkipped("could not fork");
		}
		if($pid === 0) {
			// reader: exits through exec, so the parent's shutdown functions (DB, PHPUnit) don't run here
			touch($readyFile);
			$reads = 0;
			$bad = 0;
			while(!file_exists($stopFile)) {
				$content = @file_get_contents($target);
				if($content !== $payloads[0] && $content !== $payloads[1]) $bad++;
				$reads++;
				clearstatcache();
			}
			$code = $bad > 0 ? 1 : ($reads === 0 ? 2 : 0);
			pcntl_exec("/bin/sh", ["-c", "exit ".$code]);
			posix_kill(getmypid(), SIGKILL);
		}

		$waited = 0;
		while(!file_exists($readyFile) && $waited < 5000) {
			usleep(1000);
			$waited++;
			clearstatcache();
		}
		$saved = true;
		for($i = 0; $i < 200; $i++) {
			$saved = $saved && $cache->SaveFile($payloads[$i % 2]) === $size;
		}
		touch($stopFile);
		pcntl_waitpid($pid, $status);

		$this->assertTrue($saved, "a SaveFile() call failed");
		$this->assertTrue(pcntl_wifexited($status), "reader did not exit");
		$this->assertSame(0, pcntl_wexitstatus($status), "reader saw a partial file (1) or never read (2)");
		$this->assertSame([], $this->GetTmpFiles());
	}

}
