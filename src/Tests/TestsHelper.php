<?php

namespace Magrathea2\Tests;

use Magrathea2\Debugger;

#######################################################################################
####
####    MAGRATHEA PHP2
####    v. 2.0
####    Magrathea by Paulo Henrique Martins
####    Platypus technology
####    Test Helper created: 2023-03 by Paulo Martins
####
#######################################################################################
class TestsHelper {
	public static function Print($msg) {
//		echo "\n".$msg;
	}

	public static function Debug() {
		Debugger::Instance()->SetType(Debugger::NONE);
	}
}
