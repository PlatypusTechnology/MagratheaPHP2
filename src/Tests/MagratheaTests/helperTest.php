<?php

use Magrathea2\MagratheaHelper;
use Magrathea2\Tests\TestsHelper;

class MagratheaHelperTest extends \PHPUnit\Framework\TestCase {

	public function testRandomString() {
		TestsHelper::Print("Testing RandomString generation");
		$length = 12;
		$random = MagratheaHelper::RandomString($length);
		$this->assertIsString($random);
		$this->assertEquals($length, strlen($random));
		$random2 = MagratheaHelper::RandomString($length);
		$this->assertNotEquals($random, $random2);
	}

	public function testHexToRgb() {
		TestsHelper::Print("Testing HexToRgb conversion");
		$helper = new MagratheaHelper();
		
		$hexWithHash = "#CB8008";
		$expected = ['r' => 203, 'g' => 128, 'b' => 8];
		$this->assertEquals($expected, $helper->HexToRgb($hexWithHash));

		$hexWithoutHash = "CB8008";
		$this->assertEquals($expected, $helper->HexToRgb($hexWithoutHash));

		$shortHex = "#C80";
		$expectedShort = ['r' => 204, 'g' => 136, 'b' => 0];
		$this->assertEquals($expectedShort, $helper->HexToRgb($shortHex));
	}

	public function testEnsureTrailingSlash() {
		TestsHelper::Print("Testing EnsureTrailingSlash");
		$pathWithSlash = "/my/path/";
		$this->assertEquals($pathWithSlash, MagratheaHelper::EnsureTrailingSlash($pathWithSlash));

		$pathWithoutSlash = "/my/path";
		$this->assertEquals($pathWithSlash, MagratheaHelper::EnsureTrailingSlash($pathWithoutSlash));

		$emptyPath = "";
		$this->assertEquals($emptyPath, MagratheaHelper::EnsureTrailingSlash($emptyPath));

		$nullPath = null;
		$this->assertNull(MagratheaHelper::EnsureTrailingSlash($nullPath));
	}

	public function testFormatSize() {
		TestsHelper::Print("Testing FormatSize");
		$this->assertEquals("0 bytes", MagratheaHelper::FormatSize(0));
		$this->assertEquals("100.00 bytes", MagratheaHelper::FormatSize(100));
		$this->assertEquals("1.00 KB", MagratheaHelper::FormatSize(1024));
		$this->assertEquals("1.50 KB", MagratheaHelper::FormatSize(1536));
		$this->assertEquals("1.00 MB", MagratheaHelper::FormatSize(1024 * 1024));
		$this->assertEquals("1.25 GB", MagratheaHelper::FormatSize(1.25 * 1024 * 1024 * 1024));
		$this->assertEquals("1.00 TB", MagratheaHelper::FormatSize(pow(1024, 4)));

		// Test decimals
		$this->assertEquals("1.46 GB", MagratheaHelper::FormatSize(1572864 * 1024, 2));
		$this->assertEquals("1.5 GB", MagratheaHelper::FormatSize(1572864 * 1024, 1));
		$this->assertEquals("2 GB", MagratheaHelper::FormatSize(1572864 * 1024, 0));
	}
}
