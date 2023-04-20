<?php

use Magrathea2\Admin\ObjectManager;

class objectManagerTest extends \PHPUnit\Framework\TestCase {

	protected function setUp(): void {
		parent::setUp();
		$this->setTestObjectFile();
	}
	protected function tearDown(): void {
		parent::tearDown();
	}

	private function setTestObjectFile() {
		ObjectManager::Instance()
			->SetObjectFilePath(__DIR__."/test-dump")
			->fileName = "magrathea-object-test.conf";
	}

	function testGetCorrectPath() {
		Magrathea2\MagratheaPHP::Instance()->AppPath(__DIR__."/test-dump");
		$objManager = ObjectManager::Instance();
		$objManager->SetObjectFilePath(null);
		$path = $objManager->GetObjectFilePath();
		$this->assertEquals(__DIR__."/configs", $path);
	}

	function testGetObjects() {
		$objects = ObjectManager::Instance()->GetObjectList();
		$this->assertEquals(["Actor", "Movie", "Studio"], $objects);
	}

	function testGetObjectData() {
		$object = ObjectManager::Instance()->GetObjectData("Movie");
		$this->assertEquals("movies", $object["table_name"]);
		$this->assertEquals("id", $object["db_pk"]);
		$this->assertEquals("string", $object["name_type"]);
	}

	function testGetObjectDetails() {
		$object = ObjectManager::Instance()->GetObjectDetails("Movie");
		$this->assertEquals("Movie", $object["name"]);
		$this->assertEquals("movies", $object["table"]);
		$public = $object["public_properties"];
		$this->assertEquals("PK", $public['id']['description']);
		$this->assertEquals("string", $public['name']['type']);
		$this->assertEquals("(alias: title)", $public['name']['description']);
		$this->assertEquals("year", $public['year']['name']);
		$this->assertEquals("int", $public['year']['type']);
		$this->assertEquals("timestamp", $public['updated_at']['type']);
		$methods = $object["public_methods"];
		$this->assertEquals("Save()", $methods["save"]["name"]);
		$this->assertEquals("Creates new Movie", $methods["insert"]["description"]);
	}

	function testAddAndGetRelations() {
		// insert
		$addStatus = ObjectManager::Instance()->AddRelation("has_many", "Studio", "Movie", "studio_id");
		$this->assertTrue($addStatus["success"]);
		$this->assertCount(2, $addStatus["names"]);

		// should update relation
		$movieRelName = $addStatus["names"][1];
		$updated = ObjectManager::Instance()->UpdateRelation($movieRelName, [ "rel_method" => "ProducedBy" ]);
		$this->assertTrue($updated);

		// confirm getbyname works
		$movieRel = ObjectManager::Instance()->GetRelationByName($movieRelName);
		$this->assertEquals("ProducedBy", $movieRel["rel_method"]);
		$this->assertEquals("Movie", $movieRel["rel_obj_base"]);
		$this->assertEquals("Studio", $movieRel["rel_object"]);

		$rels = ObjectManager::Instance()->GetAllRelations();
		$this->assertArrayHasKey("Movie", $rels);
		$moviesRels = ObjectManager::Instance()->GetRelationsByObject("Studio");
		$moviesRel = $moviesRels[0];
		$this->assertEquals("Studio", $moviesRel["rel_obj_base"]);
		$this->assertEquals("Movie", $moviesRel["rel_object"]);
		$this->assertEquals("studio_id", $moviesRel["rel_field"]);
		$this->assertEquals("GetMovies", $moviesRel["rel_method"]);

		// should not add relation that already exists
		$addStatus2 = ObjectManager::Instance()->AddRelation("has_many", "Studio", "Movie", "studio_id");
		$this->assertFalse($addStatus2["success"]);
		$this->assertStringEndsWith("already exists", $addStatus2["error"]);

		// should be able to delete relation
		$deleted = ObjectManager::Instance()->DeleteRelation($addStatus["names"][1]);
		$this->assertTrue($deleted);
		$mirrorRel = ObjectManager::Instance()->GetRelationByName($addStatus["names"][0]);
		$this->assertCount(0, $mirrorRel);
	}
}

