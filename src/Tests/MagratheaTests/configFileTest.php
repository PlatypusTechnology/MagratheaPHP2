<?php

use Magrathea2\Tests\TestsHelper;
use Magrathea2\ConfigFile;

class configFileTest extends \PHPUnit\Framework\TestCase {

	private $magConfig;
	private $configPath;
	private $fileName = "test_configFileTest.conf";

	protected function setUp(): void {
		parent::setUp();
		$dumpFolder = __DIR__."/test-dump";
		if (!file_exists($dumpFolder)) {
			mkdir($dumpFolder, 0777, true);
		}

		$this->configPath = realpath($dumpFolder);

		if( file_exists($this->configPath."/".$this->fileName))
			unlink($this->configPath."/".$this->fileName);
		$this->magConfig = new ConfigFile();
		$this->magConfig->setPath($this->configPath);
		$this->magConfig->setFile($this->fileName);
	}
	protected function tearDown(): void {
		if( file_exists($this->configPath."/".$this->fileName))
			unlink($this->configPath."/".$this->fileName);
		parent::tearDown();
	}		

	// Create a new Config
	function testCreateConfigFile(){
		TestsHelper::Print("testing magratheaConfig checking if we can create a config file...");
		$this->magConfig->Save();
		$this->assertTrue(file_exists($this->configPath."/".$this->fileName));
	}

	// Test Save a new Config with something
	function testSaveConfigFile(){
		TestsHelper::Print("testing magratheaConfig saving a config file...");
		$confs = array("config_test" => "ok", 
			"config_test2" => "another_ok" );
		$this->magConfig->setConfig($confs);
		$this->magConfig->Save(false);
		$this->assertTrue(file_exists($this->configPath."/".$this->fileName));
	}

	// if you save configs with sections and without sections in the same file, it shoulf be an error
	function testErrorWhenSavingAMixedArrayOfConfig(){
		TestsHelper::Print("testing magratheaConfig confirming an error when config file is invalid...");
		$confs = array(
			'this_section' => array(
				'this_var' => 'ok', 
				'this_other_var' => 'ok' ), 
			'simple_item' => 'ok'
		 );
		$this->magConfig->setConfig($confs);
//			$this->expectException(new PatternExpectation("/section/i"));
		$this->expectException(\Magrathea2\Exceptions\MagratheaConfigException::class);
		$this->magConfig->Save(true);
	}

	// save a single item with section
	function testSaveASingleItemWithSections(){
		TestsHelper::Print("testing magratheaConfig saving a config file with sections...");
		$confs = array(
			'this_section' => array(
				'this_var' => 'ok' )
		 );
		$this->magConfig->setConfig($confs);
		$this->magConfig->Save(true);
		$this->assertTrue(file_exists($this->configPath."/".$this->fileName));
	}

	// Load a var from a config file
	function testLoadVarFromConfigFile(){
		TestsHelper::Print("testing magratheaConfig loading a var from a previously saved config file...");
		$confs = array('config_test' => "ok" );
		$this->magConfig->setConfig($confs);
		$this->magConfig->Save(false);

		$newConf = new ConfigFile();
		$newConf->setPath($this->configPath);
		$newConf->setFile($this->fileName);
		$var = $newConf->GetConfig("config_test");

		$this->assertEquals($var, "ok");
	}

	// Load a var from a section from a config file
	function testLoadVarFromSection(){
		TestsHelper::Print("testing magratheaConfig loading a var from a section...");
		$confs = array(
			'this_section' => array(
				'this_var' => 'ok', 
				'this_other_var' => 'ok2' ), 
			'this_other_section' => array(
				'this_var' => 'ok3', 
				'this_other_var' => 'ok4' ), 
			'this_last_section' => array() 
		 );
		$this->magConfig->setConfig($confs);
		$this->magConfig->Save(true);

		$newConf = new ConfigFile();
		$newConf->setPath($this->configPath);
		$newConf->setFile($this->fileName);
		$section = (array)$newConf->GetConfig();
		$this->assertCount(3, $section);

		$section = $newConf->GetConfig("this_section");
		$this->assertEquals($section["this_var"], "ok");

		$var = $newConf->getConfig("this_other_section/this_var");


		$this->assertEquals($var, "ok3");
	}

}



