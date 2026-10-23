<?php

use Magrathea2\Tests\TestsHelper;

class shortOpenTagTest extends \PHPUnit\Framework\TestCase {

	function testNoShortOpenTags() {
		TestsHelper::Print("testing no framework file opens PHP with a short tag (they print as text with short_open_tag=Off)...");
		$src = realpath(__DIR__."/../../");
		$files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($src, \FilesystemIterator::SKIP_DOTS));
		$checked = 0;
		$found = [];
		foreach($files as $file) {
			if($file->getExtension() !== "php") continue;
			$checked++;
			foreach(file($file->getPathname()) as $n => $line) {
				// a short open tag: not followed by "php", "=" (always on) or "xml"
				if(preg_match('/<\?(?!php\b|=|xml)/', $line)) {
					$found[] = substr($file->getPathname(), strlen($src) + 1).":".($n + 1);
				}
			}
		}
		$this->assertGreaterThan(100, $checked);
		$this->assertSame([], $found);
	}

}
