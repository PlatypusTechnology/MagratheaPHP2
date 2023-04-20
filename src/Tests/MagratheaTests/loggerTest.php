<?php

use Magrathea2\Config;
use Magrathea2\DB\Database;
use Magrathea2\Debugger;
use Magrathea2\Exceptions\MagratheaConfigException;
use Magrathea2\Exceptions\MagratheaModelException;
use Magrathea2\Logger;
use Magrathea2\MagratheaPHP;

use function Magrathea2\Debug;
use function Magrathea2\p_r;

class loggerTest extends \PHPUnit\Framework\TestCase {

	private $filePath;
	private $fileName = "log.txt";

	protected function setUp(): void {
		parent::setUp();
		clearstatcache();
		$dumpFolder = __DIR__."/test-dump/logs";
		if (!is_dir($dumpFolder)) {
			mkdir($dumpFolder, 0777, true);
		}
		$this->filePath = realpath($dumpFolder);
	}
	protected function tearDown(): void {
		@unlink($this->GetLogFile());
		rmdir($this->filePath);
		parent::tearDown();
	}

	private function GetLogFile() {
		return $this->filePath."/".$this->fileName;
	}

	function getLastLineOfFile($filePath) {
		$file = new SplFileObject($filePath, 'r');
		$lastLine = "";
		while(!$file->eof()) {
			$line = $file->current();
			$file->next();
			if(!empty($line)) $lastLine = trim($line);
		}
		return $lastLine;
	}
	function countLines($filePath) {
		$file = new SplFileObject($filePath, 'r');
		$file->seek(PHP_INT_MAX);
		return $file->key();
	}

	function testGetExceptionOnLogPath() {
		Config::Instance()->SetConfig([])->SetPath("");
		$this->expectException(MagratheaConfigException::class);
		$logger = Logger::Instance()->LoadLogsPath();
	}

	function testGetDefaulltLogPath() {
		MagratheaPHP::Instance()->AppPath(__DIR__."/test-dump");
		$path = Logger::Instance()->LoadLogsPath()->GetLogPath();
		$this->assertEquals(__DIR__."/logs", $path);
		$defaultFile = "log_".@date("Ym").".txt";
		$this->assertEquals($defaultFile, Logger::Instance()->GetLogFile());
	}

	function testGetLogPath() {
		$configClass = Config::Instance();
		try {
			$configClass->SetConfigFile("magrathea-test.conf");
			$configClass->SetPath(__DIR__."/test-dump");
		} catch(Exception $ex) {
			echo $ex;
		}
		$logger = Logger::Instance()->LoadLogsPath();
		$path = $logger->GetLogPath();
		$this->assertEquals("/var/www/html/logs", $path);
		$path = $logger->SetLogPath($this->filePath);
		$path = $logger->GetLogPath();
		$this->assertEquals($this->filePath, $path);
	}
	
	function createTestLogger() {
		Config::Instance()->SetEnvironment("test-logger");
		return Logger::Instance()
			->SetLogPath($this->filePath)
			->SetLogFile($this->fileName);
	}

	function testSaveLog() {
		try {
			$logger = $this->createTestLogger();
			$logThis = "Log something";
			$logger->Log($logThis);
		} catch(Exception $ex) {
			echo $ex;
		}
		$logFile = $this->GetLogFile();
		$line = $this->getLastLineOfFile($logFile);
		$this->assertStringEndsWith($logThis, $line);
	}

	function testSaveMultipleLogs() {
		$logFile = $this->GetLogFile();
		try {
			$logger = $this->createTestLogger();
			$logger->Log("start of File");
			$qtt1 = $this->countLines($logFile);
			$logger->Log("first log of the sequence");
			$logger->Log("second log of the sequence");
			$qtt2 = $this->countLines($logFile);
		} catch(Exception $ex) {
			echo $ex;
		}
		$this->assertEquals(($qtt1 + 2), $qtt2);
	}

	function testLogException() {
		$exception = new MagratheaModelException("some weird admin exception");
		try {
			$logger = $this->createTestLogger();
			$logger->LogError($exception);
		} catch(Exception $ex) {
			echo $ex;
		}
		$logFile = $this->GetLogFile();
		$line = $this->getLastLineOfFile($logFile);
		$this->assertStringContainsString("ModelException", $line);
		$this->assertStringContainsString("some weird admin exception", $line);
	}

	function testLogQueries() {
		Debugger::Instance()->SetType(Debugger::LOG);
		$logger = $this->createTestLogger();
		$queryFile = "queries.txt";
		$queryFilePath = $this->filePath."/".$queryFile;
		$logger->SetLogFile($queryFile);
		$query = "SELECT 1 as ok";

		Database::Instance()->Mock();

		@unlink($queryFilePath);
		Debugger::Instance()->LogQueries(false);
		Database::Instance()->Query($query);
		$this->assertFalse(file_exists($queryFilePath));

		Debugger::Instance()->LogQueries(true);
		Database::Instance()->Query($query);
		$this->assertTrue(file_exists($queryFilePath));
		@unlink($queryFilePath);
	}

}

