<?php

namespace ShowAllTestModels {
	use Magrathea2\MagratheaModel;
	use Magrathea2\MagratheaModelControl;

	// lives in a namespace, so a bare "ShowAllItem" doesn't resolve to it
	class ShowAllItem extends MagratheaModel {
		protected $dbTable = "show_all_item";
		protected $dbPk = "id";
		protected $dbValues = [
			"id" => "int",
			"name" => "string",
			"created_at" => "datetime",
			"updated_at" => "datetime",
		];
		protected $autoload = null;
		public $id, $name, $created_at, $updated_at;
		public function __construct($id = 0) {
			if(!empty($id)) $this->GetById($id);
		}
	}

	class ShowAllItemControl extends MagratheaModelControl {
		protected static $modelNamespace = "ShowAllTestModels";
		protected static $modelName = "ShowAllItem";
		protected static $dbTable = "show_all_item";
	}
}

namespace {
	use Magrathea2\DB\Database;
	use ShowAllTestModels\ShowAllItemControl;

	// Database that answers QueryAll() with $rows
	class ShowAllRowsDatabase extends Database {
		public $rows = [];
		public function QueryAll(string $sql) {
			return $this->rows;
		}
	}

	/**
	 * 2.4.0 item 25: ShowAll() builds its model through GetModelName(), so it works with $modelNamespace.
	 * Values are escaped and every row is closed once.
	 */
	class modelControlShowAllTest extends \PHPUnit\Framework\TestCase {

		private $previousDb;

		protected function setUp(): void {
			parent::setUp();
			$this->previousDb = Database::Instance();
			$db = ShowAllRowsDatabase::Instance();
			$db->rows = [
				["id" => "1", "name" => "Arthur", "created_at" => "2020-01-01 00:00:00", "updated_at" => "2020-01-01 00:00:00"],
				["id" => "2", "name" => "<b>Ford</b>", "created_at" => "2020-01-02 00:00:00", "updated_at" => "2020-01-02 00:00:00"],
			];
			Database::MockClass($db);
		}
		protected function tearDown(): void {
			Database::MockClass($this->previousDb);
			parent::tearDown();
		}

		function testShowAllWorksWithNamespacedModel() {
			ob_start();
			try {
				ShowAllItemControl::ShowAll();
			} finally {
				$html = ob_get_clean();
			}
			$this->assertStringContainsString("<th>name - (string)</th>", $html);
			$this->assertStringContainsString("<td>Arthur</td>", $html);
		}

		function testShowAllEscapesValuesAndClosesRows() {
			ob_start();
			try {
				ShowAllItemControl::ShowAll();
			} finally {
				$html = ob_get_clean();
			}
			$this->assertStringContainsString("<td>&lt;b&gt;Ford&lt;/b&gt;</td>", $html);
			$this->assertStringNotContainsString("<b>Ford</b>", $html);
			$this->assertSame(substr_count($html, "<tr>"), substr_count($html, "</tr>"));
		}
	}
}
