<?php
use Magrathea2\DB\Query;
use Magrathea2\DB\QueryHelper;
use Magrathea2\DB\QueryType;


class queryTest extends \PHPUnit\Framework\TestCase {

	function testQueryTypes() {
		$selQuery = Query::Select();
		$this->assertEquals(QueryType::Select, $selQuery->GetType());
		$upQuery = Query::Update();
		$this->assertEquals(QueryType::Update, $upQuery->GetType());
		$inQuery = Query::Insert();
		$this->assertEquals(QueryType::Insert, $inQuery->GetType());
		$delQuery = Query::Delete();
		$this->assertEquals(QueryType::Delete, $delQuery->GetType());
	}

	function testGuessQueryType() {
		$type = QueryHelper::GetQueryType("SELECT * FROM _magrathea_logs LIMIT 50");
		$this->assertEquals(QueryType::Select, $type);
		$type = QueryHelper::GetQueryType("UPDATE users SET name = 'Golias' LIMIT 50");
		$this->assertEquals(QueryType::Update, $type);
	}

}

