<?php
use Magrathea2\Uuid;

class uuidTest extends \PHPUnit\Framework\TestCase {

	function testFormat() {
		$uuid = Uuid::V7();
		$this->assertMatchesRegularExpression(
			'/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
			$uuid
		);
	}

	function testMonotonicOrdering() {
		$first = Uuid::V7();
		$second = Uuid::V7();
		$this->assertLessThanOrEqual(0, strcmp(substr($first, 0, 13), substr($second, 0, 13)));
	}

	function testUniqueness() {
		$this->assertNotEquals(Uuid::V7(), Uuid::V7());
	}

}
