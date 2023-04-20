<?php

use function Magrathea2\p_r;
use Magrathea2\Tests\TestsHelper;
use Magrathea2\Config;

	class configTest extends \PHPUnit\Framework\TestCase {

		public function MockConfigContent() {
			try {
				$configClass = Config::Instance();
				$configClass->SetConfigFile("magrathea-test.conf");
				$configClass->SetPath(__DIR__."/test-dump");
			} catch(Exception $ex) {
				echo $ex;
			}
		}

		protected function setUp(): void {
			parent::setUp();
			$this->MockConfigContent();
		}
		protected function tearDown(): void {
			parent::tearDown();
		}

		// load a section in Static Config
		// I check if the section that it returns is an array:
		function testLoadSectionStaticConfig(){
			TestsHelper::Print("testing magratheaConfig loading static config...");
			$thisSection = Config::Instance()->GetConfigSection("general");
			$this->assertIsArray($thisSection);
		}

		// config file must have a default environment option
		function testConfigShouldHaveADefaultEnvironment(){
			TestsHelper::Print("testing magratheaConfig confirming we have a default...");
			$env = Config::Instance()->GetEnvironment();
			$this->assertNotNull($env);
		}

		// required fields
		function testGettingEnvFields(){
			TestsHelper::Print("testing magratheaConfig checking required fields...");
			$env = Config::Instance()->GetConfig("general/use_environment");
			$compress_js = Config::Instance()->GetConfig($env."/db_host");
			$compress_css = Config::Instance()->GetConfig($env."/time_zone");
			$this->assertNotNull($compress_js);
			$this->assertNotNull($compress_css);
		}

	}
