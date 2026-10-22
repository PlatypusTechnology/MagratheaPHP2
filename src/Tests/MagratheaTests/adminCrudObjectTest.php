<?php

namespace CrudTest;

use Magrathea2\MagratheaModel;
use Magrathea2\MagratheaModelControl;
use Magrathea2\Admin\Features\CrudObject\AdminCrudObject;
use Magrathea2\Exceptions\MagratheaModelException;

class Gadget extends MagratheaModel {
	protected $dbTable = "gadgets";
	protected $dbPk = "id";
	protected $dbValues = [ "id" => "int", "name" => "string" ];
	public $id, $name, $created_at, $updated_at;
	public function __construct($id = 0) {}
}
class GadgetControl extends MagratheaModelControl {
	protected static $modelNamespace = "CrudTest";
	protected static $modelName = "Gadget";
	protected static $dbTable = "gadgets";
}

class GadgetAdmin extends AdminCrudObject {
	public int $initializeCalls = 0;
	public function Initialize() {
		$this->initializeCalls++;
		$this->SetObject(new Gadget());
	}
}
class NamedGadgetAdmin extends GadgetAdmin {
	public string $featureName = "Gadgets";
}
class EmptyCrudAdmin extends AdminCrudObject {}

// no "<model>Control" next to the model (mvc structure)
class Widget extends Gadget {}
class WidgetAdmin extends AdminCrudObject {
	public function Initialize() {
		$this->SetObject(new Widget(), new GadgetControl());
	}
}
class OrphanWidgetAdmin extends AdminCrudObject {
	public function Initialize() {
		$this->SetObject(new Widget());
	}
}

class adminCrudObjectTest extends \PHPUnit\Framework\TestCase {

	function testInitializeRunsOnce() {
		$admin = new GadgetAdmin();
		$this->assertSame(1, $admin->initializeCalls);
	}

	function testSetObjectDefaultsAndControl() {
		$admin = new GadgetAdmin();
		$this->assertSame("CRUDGadget", $admin->featureId);
		$this->assertSame("Gadget CRUD", $admin->featureName);
		$this->assertInstanceOf(GadgetControl::class, $admin->control);
	}

	function testOwnFeatureNameIsKept() {
		$admin = new NamedGadgetAdmin();
		$this->assertSame("Gadgets", $admin->featureName);
		$this->assertSame("CRUDGadget", $admin->featureId);
	}

	function testFeatureIdFallsBackToClassNameWithoutSetObject() {
		$admin = new EmptyCrudAdmin();
		$this->assertSame("EmptyCrudAdmin", $admin->featureId);
	}

	function testExplicitControlIsUsed() {
		$admin = new WidgetAdmin();
		$this->assertInstanceOf(GadgetControl::class, $admin->control);
		$this->assertSame("CRUDWidget", $admin->featureId);
	}

	function testMissingConventionalControlThrows() {
		$this->expectException(MagratheaModelException::class);
		$this->expectExceptionMessage("CrudTest\\WidgetControl");
		new OrphanWidgetAdmin();
	}
}
